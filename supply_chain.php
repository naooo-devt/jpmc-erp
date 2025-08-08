<?php
// Hide PHP warnings and errors from being displayed to users
error_reporting(0);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

// Fetch user details from session for display.
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

// Handle form submissions for calendar notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add_note') {
                $note_date = $_POST['note_date'];
                $note_text = $_POST['note_text'];
                
                $stmt = $conn->prepare("INSERT INTO calendar_schedule_note_history (note_date, note_text, user) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $note_date, $note_text, $username);
                $stmt->execute();
                
                // Log the action
                $log_text = "Added note for date: $note_date";
                $conn->query("INSERT INTO calendar_schedule_note_history (note_date, note_text, user) VALUES ('$note_date', '$log_text (Note: $note_text)', 'System')");
                
                $_SESSION['message'] = "Note added successfully!";
            } 
            elseif ($_POST['action'] === 'edit_note') {
                $note_id = $_POST['note_id'];
                $note_date = $_POST['note_date'];
                $note_text = $_POST['note_text'];
                
                $stmt = $conn->prepare("UPDATE calendar_schedule_note_history SET note_date = ?, note_text = ? WHERE id = ?");
                $stmt->bind_param("ssi", $note_date, $note_text, $note_id);
                $stmt->execute();
                
                // Log the action
                $log_text = "Edited note ID: $note_id";
                $conn->query("INSERT INTO calendar_schedule_note_history (note_date, note_text, user) VALUES ('$note_date', '$log_text (New note: $note_text)', 'System')");
                
                $_SESSION['message'] = "Note updated successfully!";
            } 
            elseif ($_POST['action'] === 'delete_note') {
                $note_id = $_POST['note_id'];
                
                // Get note info before deleting for the log
                $note = $conn->query("SELECT * FROM calendar_schedule_note_history WHERE id = $note_id")->fetch_assoc();
                
                $stmt = $conn->prepare("DELETE FROM calendar_schedule_note_history WHERE id = ?");
                $stmt->bind_param("i", $note_id);
                $stmt->execute();
                
                // Log the action
                $log_text = "Deleted note ID: $note_id";
                $conn->query("INSERT INTO calendar_schedule_note_history (note_date, note_text, user) VALUES (NOW(), '$log_text (Original note: {$note['note_text']})', 'System')");
                
                $_SESSION['message'] = "Note deleted successfully!";
            }
            
            // Redirect to prevent form resubmission
            header("Location: supply_chain.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

// Fetch suppliers data
$suppliers_sql = "
    SELECT s.id, s.name, s.contact_person, s.email, s.phone, s.address, s.status, s.rating, s.created_at,
           COUNT(po.id) as total_orders,
           SUM(CASE WHEN po.status = 'Completed' THEN 1 ELSE 0 END) as completed_orders
    FROM suppliers s
    LEFT JOIN purchase_orders po ON s.id = po.supplier_id
    GROUP BY s.id
    ORDER BY s.name";
$suppliers_result = $conn->query($suppliers_sql);

// Fetch purchase orders data
$purchase_orders_sql = "
    SELECT po.id, po.order_number, po.order_date, po.expected_delivery, po.status, po.total_amount,
           s.name as supplier_name, s.contact_person as supplier_contact,
           COUNT(poi.id) as total_items
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
    GROUP BY po.id
    ORDER BY po.order_date DESC
    LIMIT 10";
$purchase_orders_result = $conn->query($purchase_orders_sql);

// Fetch recent deliveries
$deliveries_sql = "
    SELECT d.id, d.delivery_number, d.delivery_date, d.status, d.notes,
           po.order_number as po_number, s.name as supplier_name
    FROM deliveries d
    LEFT JOIN purchase_orders po ON d.purchase_order_id = po.id
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    ORDER BY d.delivery_date DESC
    LIMIT 10";
$deliveries_result = $conn->query($deliveries_sql);

// Dashboard statistics
$total_suppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
$total_spent = $conn->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
$avg_delivery_time = $conn->query("
    SELECT AVG(DATEDIFF(d.delivery_date, po.order_date)) as avg_days 
    FROM deliveries d 
    JOIN purchase_orders po ON d.purchase_order_id = po.id 
    WHERE d.status = 'Delivered'")->fetch_assoc()['avg_days'] ?? 0;

// Fetch calendar notes from the database
$calendar_notes = [];
$notes_result = $conn->query("SELECT id, note_date, note_text, user FROM calendar_schedule_note_history WHERE user != 'System' ORDER BY note_date DESC");
if ($notes_result && $notes_result->num_rows > 0) {
    while ($note = $notes_result->fetch_assoc()) {
        $calendar_notes[] = [
            'id' => $note['id'],
            'date' => $note['note_date'],
            'text' => $note['note_text'],
            'user' => $note['user']
        ];
    }
}

// Fetch audit log entries (system-generated entries)
$audit_logs = [];
$logs_result = $conn->query("SELECT id, note_date, note_text, user FROM calendar_schedule_note_history WHERE user = 'System' ORDER BY created_at DESC LIMIT 10");
if ($logs_result && $logs_result->num_rows > 0) {
    while ($log = $logs_result->fetch_assoc()) {
        $audit_logs[] = [
            'id' => $log['id'],
            'date' => $log['note_date'],
            'text' => $log['note_text'],
            'user' => $log['user']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Supply Chain Management - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="images/logo.png">
    <style>
    :root {
        --primary-blue: #2563eb;
        --primary-red: #dc2626;
        --dark-blue: #1e40af;
        --light-blue: #3b82f6;
        --success: #10b981;
        --warning: #f59e0b;
        --error: #ef4444;
        --info: #3b82f6;
        --white: #ffffff;
        --light-gray: #f8fafc;
        --gray: #64748b;
        --dark-gray: #475569;
        --border-color: #e2e8f0;
        --sidebar-width: 280px;
        --header-height: 70px;
        --content-padding: 30px;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-fast: all 0.15s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        font-size: 16px;
        height: 100%;
    }

    body {
        font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        background-color: var(--light-gray);
        color: var(--dark-gray);
        line-height: 1.6;
        display: flex;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* Sidebar Styles */
    .sidebar {
        width: var(--sidebar-width);
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: var(--white);
        height: 100vh;
        position: fixed;
        transition: var(--transition);
        z-index: 1000;
        box-shadow: var(--shadow-lg);
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        padding: 1.5rem;
        background: rgba(0, 0, 0, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
    }

    .company-logo {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        border-radius: 12px;
        overflow: hidden;
        background: var(--white);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .company-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .company-name {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .company-subtitle {
        font-size: 0.875rem;
        opacity: 0.8;
    }

    .sidebar-menu {
        flex: 1;
        padding: 1.5rem 0;
        overflow-y: auto;
    }

    .menu-section {
        margin-bottom: 2rem;
    }

    .menu-section-title {
        padding: 0 1.5rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.7;
    }

    .menu-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        color: var(--white);
        text-decoration: none;
        transition: var(--transition-fast);
        cursor: pointer;
        position: relative;
    }

    .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .menu-item.active {
        background: rgba(255, 255, 255, 0.15);
        border-right: 3px solid var(--white);
    }

    .menu-item i {
        width: 20px;
        margin-right: 0.75rem;
        font-size: 1rem;
    }

    .menu-item span {
        flex: 1;
        font-weight: 500;
    }

    .menu-item.menu-dropdown {
        justify-content: space-between;
    }

    .dropdown-menu {
        display: none;
        background: rgba(0, 0, 0, 0.1);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .dropdown-menu.active {
        display: block;
    }

    .dropdown-menu .menu-item {
        padding-left: 3rem;
        font-size: 0.9rem;
    }

    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        transition: var(--transition);
    }

    .header {
        background: var(--white);
        padding: 1rem var(--content-padding);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--shadow-sm);
        height: var(--header-height);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .header-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-gray);
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--light-gray);
        border-radius: 8px;
        font-weight: 500;
    }

    .content {
        flex: 1;
        padding: var(--content-padding);
        overflow-y: auto;
        max-width: 100%;
    }

    /* Dashboard Grid */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--white);
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        transition: var(--transition);
        min-height: 160px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .stat-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .stat-subtitle {
        font-size: 0.75rem;
        color: var(--gray);
        margin-top: 0.25rem;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: var(--white);
        flex-shrink: 0;
    }

    .stat-icon.blue { background: var(--primary-blue); }
    .stat-icon.green { background: var(--success); }
    .stat-icon.orange { background: var(--warning); }
    .stat-icon.red { background: var(--error); }

    .stat-value {
        font-size: clamp(1.5rem, 4vw, 2rem);
        font-weight: 700;
        color: var(--dark-gray);
        margin-bottom: 0.5rem;
        line-height: 1.2;
    }

    .stat-change {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .stat-change.positive {
        color: var(--success);
    }

    .stat-change.negative {
        color: var(--error);
    }

    /* Module Content */
    .module-content {
        display: none;
    }

    .module-content.active {
        display: block;
    }

    /* Tabs - Standardized to match finance-tabs */
    .tabs {
        display: flex;
        background: var(--white);
        border-radius: 12px;
        padding: 8px;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
    }

    .tab {
        flex: 1;
        padding: 12px 24px;
        border: none;
        background: transparent;
        color: var(--gray);
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        border-radius: 8px;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .tab:hover {
        background: var(--light-gray);
        color: var(--dark-gray);
    }

    .tab.active {
        background: var(--primary-blue);
        color: var(--white);
    }

    .tab i {
        font-size: 1rem;
    }

    /* Tables */
    .table-section {
        background: var(--white);
        border-radius: 12px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .table-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .table-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--dark-gray);
    }

    .table-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .table-responsive {
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--gray) var(--light-gray);
    }

    .table-responsive::-webkit-scrollbar {
        height: 6px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: var(--light-gray);
        border-radius: 3px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: var(--gray);
        border-radius: 3px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    th {
        background: var(--light-gray);
        font-weight: 600;
        color: var(--dark-gray);
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    td {
        font-size: 0.875rem;
    }

    tr:hover {
        background: var(--light-gray);
    }

    /* Buttons */
    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: var(--transition-fast);
        font-size: 0.875rem;
        white-space: nowrap;
    }

    .btn-primary {
        background: var(--primary-blue);
        color: var(--white);
    }

    .btn-primary:hover {
        background: var(--dark-blue);
    }

    .btn-success {
        background: var(--success);
        color: var,--white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .btn-warning {
        background: var(--warning);
        color: var,--white;
    }

    .btn-warning:hover {
        background: #d97706;
    }

    .btn-danger {
        background: var(--error);
        color: var,--white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .btn-outline {
        background: transparent;
        color: var(--primary-blue);
        border: 1px solid var(--primary-blue);
    }

    .btn-outline:hover {
        background: var(--primary-blue);
        color: var,--white;
    }

    /* Status Badges */
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .status-badge.active { background: #dcfce7; color: #166534; }
    .status-badge.inactive { background: #fef2f2; color: #991b1b; }
    .status-badge.pending { background: #fef3c7; color: #92400e; }
    .status-badge.completed { background: #dbeafe; color: #1e40af; }
    .status-badge.delivered { background: #dcfce7; color: #166534; }
    .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
    
    /* Raw Materials Status Badges */
    .status-badge.normal { 
        background: #10b981 !important; 
        color: white !important; 
        border: 2px solid #059669 !important; 
    }
    
    .status-badge.out-of-stock { 
        background: #6b7280 !important; 
        color: white !important; 
        border: 2px solid #4b5563 !important; 
    }
    
    .status-badge.critical { 
        background: #dc2626 !important; 
        color: white !important; 
        border: 2px solid #b91c1c !important; 
    }
    
    .status-badge.low { 
        background: #d97706 !important; 
        color: white !important; 
        border: 2px solid #b45309 !important; 
    }

    /* Rating Stars */
    .rating {
        display: flex;
        gap: 0.125rem;
    }

    .star {
        color: #fbbf24;
        font-size: 0.875rem;
    }

    .star.empty {
        color: #d1d5db;
    }

    /* Mobile Menu Toggle */
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: var(--dark-gray);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
    }

    /* Responsive Design */
    @media (max-width: 1400px) {
        :root {
            --sidebar-width: 260px;
            --content-padding: 25px;
        }
        
        .dashboard-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
    }

    @media (max-width: 1200px) {
        :root {
            --sidebar-width: 240px;
            --content-padding: 20px;
        }
        
        .dashboard-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1.25rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
        }
    }

    @media (max-width: 1024px) {
        :root {
            --sidebar-width: 220px;
            --content-padding: 15px;
        }
        
        .dashboard-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        
        .table-header {
            padding: 1rem;
        }
        
        th, td {
            padding: 0.75rem;
        }
    }

    @media (max-width: 768px) {
        :root {
            --sidebar-width: 100%;
            --content-padding: 15px;
        }

        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
        }

        .mobile-menu-toggle {
            display: block;
        }

        .header {
            padding: 1rem 15px;
        }

        .header-title {
            font-size: 1.25rem;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-value {
            font-size: 1.5rem;
        }

        .tabs {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tab {
            min-width: 100px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .table-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .table-responsive {
            font-size: 0.75rem;
        }

        th, td {
            padding: 0.5rem;
            font-size: 0.75rem;
        }

        .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        .user-profile {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
    }

    @media (max-width: 480px) {
        :root {
            --content-padding: 10px;
        }

        .header {
            padding: 0.75rem 10px;
        }

        .header-title {
            font-size: 1.125rem;
        }

        .content {
            padding: 10px;
        }

        .dashboard-grid {
            gap: 0.75rem;
        }

        .stat-card {
            padding: 0.75rem;
        }

        .stat-header {
            margin-bottom: 0.75rem;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .stat-value {
            font-size: 1.25rem;
        }

        .tabs {
            padding: 0.375rem;
        }

        .tab {
            min-width: 80px;
            padding: 0.375rem 0.5rem;
            font-size: 0.75rem;
        }

        .table-section {
            margin-bottom: 1rem;
        }

        .table-header {
            padding: 0.75rem;
        }

        .table-title {
            font-size: 1rem;
        }

        .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
        }

        .status-badge {
            padding: 0.125rem 0.5rem;
            font-size: 0.625rem;
        }

        .user-profile {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    }

    @media (max-width: 360px) {
        .sidebar {
            width: 100%;
        }

        .dashboard-grid {
            gap: 0.5rem;
        }

        .stat-card {
            padding: 0.5rem;
        }

        .stat-value {
            font-size: 1.125rem;
        }

        .tabs {
            flex-direction: column;
        }

        .tab {
            min-width: auto;
            width: 100%;
        }

        .table-responsive {
            font-size: 0.625rem;
        }

        th, td {
            padding: 0.375rem;
            font-size: 0.625rem;
        }
    }

    /* High DPI Displays */
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .stat-icon {
            border-radius: 8px;
        }
        
        .btn {
            border-radius: 6px;
        }
        
        .status-badge {
            border-radius: 16px;
        }
    }

    /* Manufacturing Navigation Tabs */
    .manufacturing-nav {
        margin-bottom: 2rem;
    }

    /* Finance Tabs Styles (Standardized) */
    .finance-tabs {
        display: flex;
        background: var(--white);
        border-radius: 12px;
        padding: 8px;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
    }

    .finance-tab {
        flex: 1;
        padding: 12px 24px;
        border: none;
        background: transparent;
        color: var(--gray);
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        border-radius: 8px;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .finance-tab:hover {
        background: var(--light-gray);
        color: var,--dark-gray;
    }

    .finance-tab.active {
        background: var(--primary-blue);
        color: var,--white;
    }

    .finance-tab i {
        font-size: 1rem;
    }

    /* Tab Content */
    .tab-content {
        margin-bottom: 2rem;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    .module-content {
        display: none;
    }

    .module-content.active {
        display: block;
    }

    /* Quality Control Content */
    .quality-control-content {
        background: var(--white);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }

    .quality-control-content h2 {
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-gray);
        margin-bottom: 2rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Tally Sheet */
    .tally-sheet {
        max-width: 1200px;
        margin: 0 auto;
    }

    .sheet-header {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 2rem;
    }

    .logo-placeholder {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--light-gray);
        border: 2px dashed var(--gray);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray);
        font-size: 1.5rem;
    }

    .sheet-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .form-column {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-group label {
        font-weight: 600;
        color: var(--dark-gray);
        font-size: 0.875rem;
    }

    .form-control {
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.875rem;
        transition: var(--transition-fast);
        background: var(--white);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .time-inputs {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .time-inputs span {
        font-weight: 500;
        color: var(--gray);
        font-size: 0.875rem;
    }

    .time-inputs .form-control {
        flex: 1;
    }

    /* Tally Table */
    .tally-table {
        margin-top: 2rem;
    }

    .tally-table .table {
        width: 100%;
        border-collapse: collapse;
        background: var(--white);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .tally-table th {
        background: var(--light-gray);
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--dark-gray);
        border-bottom: 1px solid var(--border-color);
    }

    .tally-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--gray);
    }

    .tally-table tr:last-child td {
        border-bottom: none;
    }

    /* Responsive Design for Tabs */
    @media (max-width: 768px) {
        .nav-tabs {
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-tab {
            min-width: auto;
            width: 100%;
            justify-content: flex-start;
        }

        .sheet-form {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .quality-control-content {
            padding: 1rem;
        }

        .quality-control-content h2 {
            font-size: 1.25rem;
        }
    }

    /* Print Styles */
    @media print {
        .sidebar,
        .header,
        .table-actions,
        .btn,
        .nav-tabs {
            display: none !important;
        }

        .main-content {
            margin-left: 0 !important;
        }

        .content {
            padding: 0 !important;
        }

        .stat-card {
            break-inside: avoid;
            box-shadow: none !important;
            border: 1px solid #ccc !important;
        }

        .table-section {
            break-inside: avoid;
            box-shadow: none !important;
            border: 1px solid #ccc !important;
        }

        .quality-control-content {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
        }
    }

        /* Audit Log Styles */
    .audit-log-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        max-height: 300px;
        background: var(--white);
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        z-index: 1000;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .audit-log-header {
        padding: 12px 16px;
        background: var(--primary-blue);
        color: var(--white);
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .audit-log-toggle {
        background: none;
        border: none;
        color: var(--white);
        cursor: pointer;
        font-size: 1rem;
    }

    .audit-log-content {
        flex: 1;
        overflow-y: auto;
        padding: 0;
        display: none; /* Hidden by default */
    }

    .audit-log-item {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.8rem;
    }

    .audit-log-item:last-child {
        border-bottom: none;
    }

    .audit-log-time {
        color: var(--gray);
        font-size: 0.7rem;
        margin-bottom: 4px;
    }

    .audit-log-text {
        color: var(--dark-gray);
    }

    .audit-log-user {
        color: var(--primary-blue);
        font-weight: 600;
    }

    /* When log is expanded */
    .audit-log-container.expanded .audit-log-content {
        display: block;
    }

    .audit-log-container.expanded .audit-log-toggle i {
        transform: rotate(180deg);
    }

    /* Calendar Note Styles */
    .calendar-note {
        background: var(--white);
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
        box-shadow: var(--shadow-sm);
        border-left: 4px solid var(--primary-blue);
    }

    .calendar-note-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .calendar-note-date {
        font-weight: 600;
        color: var(--dark-blue);
    }

    .calendar-note-user {
        font-size: 0.8rem;
        color: var(--gray);
    }

    .calendar-note-actions {
        display: flex;
        gap: 8px;
    }

    .calendar-note-action {
        background: none;
        border: none;
        color: var(--gray);
        cursor: pointer;
        font-size: 0.8rem;
        transition: var(--transition-fast);
    }

    .calendar-note-action:hover {
        color: var(--primary-blue);
    }

    .calendar-note-text {
        color: var(--dark-gray);
    }

    /* Calendar Container */
    .calendar-container {
        display: flex;
        gap: 20px;
    }

    .calendar-notes-list {
        flex: 1;
        max-width: 400px;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .calendar-container {
            flex-direction: column;
        }
        
        .calendar-notes-list {
            max-width: 100%;
        }
    }
    /* Calendar Container */
    .calendar-wrapper {
        background: var(--white);
        border-radius: 12px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        padding: 20px;
        margin-bottom: 20px;
        width: 100%;
    }

    /* FullCalendar Custom Styling */
    .fc {
        font-family: inherit;
    }

    .fc-header-toolbar {
        margin-bottom: 1em;
    }

    .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dark-gray);
    }

    .fc-button {
        background: var(--white);
        border: 1px solid var(--border-color);
        color: var(--dark-gray);
        font-weight: 500;
        text-transform: capitalize;
        border-radius: 8px !important;
        padding: 6px 12px;
    }

    .fc-button:hover {
        background: var(--light-gray);
    }

    .fc-button-primary:not(:disabled).fc-button-active {
        background: var(--primary-blue);
        color: var(--white);
        border-color: var(--primary-blue);
    }

    .fc-col-header-cell {
        background: var(--light-gray);
        padding: 8px 0;
    }

    .fc-col-header-cell-cushion {
        color: var(--dark-gray);
        font-weight: 600;
        text-decoration: none;
    }

    .fc-daygrid-day {
        padding: 4px;
    }

    .fc-daygrid-day-number {
        color: var(--dark-gray);
        font-weight: 500;
        padding: 4px;
    }

    .fc-day-today {
        background-color: rgba(37, 99, 235, 0.1) !important;
    }

    .fc-day-today .fc-daygrid-day-number {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 1.1em;
    }

    .fc-daygrid-day.fc-day-today {
        background-color: rgba(37, 99, 235, 0.05);
    }

    .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        color: var(--primary-blue);
        font-weight: 700;
    }

    .fc-daygrid-day.fc-day-today {
        position: relative;
    }

    .fc-daygrid-day.fc-day-today:after {
        content: '';
        position: absolute;
        bottom: 2px;
        left: 50%;
        transform: translateX(-50%);
        width: 24px;
        height: 3px;
        background: var(--primary-blue);
        border-radius: 3px;
    }

    .fc-daygrid-event {
        font-size: 0.8em;
        padding: 2px 4px;
        border-radius: 4px;
    }

    .fc-daygrid-event-dot {
        display: none;
    }

    .fc-event-title {
        font-weight: 500;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .fc-toolbar {
            flex-direction: column;
            gap: 10px;
        }
        
        .fc-toolbar-title {
            font-size: 1.1rem;
        }
        
        .fc-button {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
    }

    
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="company-logo">
                <img src="images/logo.png" alt="Company Logo">
            </div>
            <div class="company-name">James Polymer</div>
            <div class="company-subtitle">Manufacturing Corporation</div>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Main Navigation</div>
                <a href="index.php" class="menu-item" data-module="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="finances.php" class="menu-item" data-module="finances">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Finances</span>
                </a>
                <a href="human_resources.php" class="menu-item" data-module="human-resources">
                    <i class="fas fa-users"></i>
                    <span>Human Resources</span>
                </a>
                <div class="menu-item menu-dropdown open" id="supplyChainDropdown">
                    <i class="fas fa-link"></i>
                    <span>Inventory</span>
                    <i class="fas fa-chevron-up"></i>
                </div>
                <div class="dropdown-menu open" id="supplyChainDropdownMenu">
                    <a href="supply_chain.php" class="menu-item active" data-module="manufacturing">
                        <i class="fas fa-industry"></i>
                        <span>Manufacturing</span>
                    </a>
                    <a href="transactions.php" class="menu-item" data-module="transactions">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </div>
                <a href="customer_service.php" class="menu-item" data-module="customer-service">
                    <i class="fas fa-headset"></i>
                    <span>Customer Service</span>
                </a>
                <a href="reports.php" class="menu-item" data-module="reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-section-title">System</div>
                <a href="finished_goods.php" class="menu-item" data-module="system-admin">
                    <i class="fas fa-cog"></i>
                    <span>System Administration</span>
                </a>
                <a href="logout.php" class="menu-item" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="header-title">Inventory Management</h1>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </div>
        <div class="content">
            <!-- Manufacturing Navigation Tabs -->
            <div class="manufacturing-nav">
                <div class="finance-tabs">
                    <button class="finance-tab active" data-tab="schedule">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Calendar</span>
                    </button>
                    <button class="finance-tab" data-tab="raw-materials">
                        <i class="fas fa-cubes"></i>
                        <span>Raw Materials</span>
                    </button>
                    <button class="finance-tab" data-tab="products">
                        <i class="fas fa-box"></i>
                        <span>Product Lists</span>
                    </button>
                    <button class="finance-tab" data-tab="quality-control">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Quality Control</span>
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Schedule Tab -->
                <div class="tab-pane active" id="schedule">
                    <div class="calendar-container">
                        <div style="flex: 2; max-width:900px;">
                            <h2>Production Calendar</h2>
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Literal Calendar -->
                            <div id="calendar" style="background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:20px; margin-bottom:32px;"></div>
                            
                            <button class="btn btn-primary" onclick="openAddNoteModal()">
                                <i class="fas fa-plus"></i> Add New Note
                            </button>
                        </div>
                    </div>

                    <div class="calendar-notes-list">
                            <h2>Recent Notes</h2>
                            <?php if (empty($calendar_notes)): ?>
                                <p>No notes found. Add a note to get started.</p>
                            <?php else: ?>
                                <?php foreach ($calendar_notes as $note): ?>
                                    <div class="calendar-note">
                                        <div class="calendar-note-header">
                                            <div>
                                                <span class="calendar-note-date"><?php echo date('M d, Y', strtotime($note['date'])); ?></span>
                                                <span class="calendar-note-user">by <?php echo $note['user']; ?></span>
                                            </div>
                                            <div class="calendar-note-actions">
                                                <button class="calendar-note-action" onclick="openEditNoteModal(<?php echo $note['id']; ?>, '<?php echo $note['date']; ?>', '<?php echo addslashes($note['text']); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="calendar-note-action" onclick="confirmDeleteNote(<?php echo $note['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="calendar-note-text"><?php echo htmlspecialchars($note['text']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                </div>

                <!-- Raw Materials Tab -->
                <div class="tab-pane" id="raw-materials">
                    <div class="raw-materials-content">
                        <div class="table-section">
                            <div class="table-header">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="rawMaterialSearchInput" class="search-input" placeholder="Search raw materials...">
                                    <div id="recentSearches" class="recent-searches-dropdown"></div>
                                </div>
                                <div class="table-title">Raw Materials Inventory</div>
                                <button class="btn btn-primary" id="openAddMaterialModal">
                                    <i class="fas fa-plus"></i> Add Material
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table id="rawMaterialTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Material Name</th>
                                            <th>Code/Color</th>
                                            <th>Stock</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $raw_materials_sql = "
                                            SELECT rm.id, rm.name, rm.code_color, rm.stock_quantity, l.name as location, rm.status, rm.image1, rm.image2, rm.image3, rm.location_id
                                            FROM raw_materials rm
                                            LEFT JOIN locations l ON rm.location_id = l.id
                                            ORDER BY rm.id";
                                        $raw_materials_result = $conn->query($raw_materials_sql);
                                        $locations_result = $conn->query("SELECT id, name FROM locations ORDER BY name");
                                        
                                        if ($raw_materials_result && $raw_materials_result->num_rows > 0) {
                                            $raw_materials_result->data_seek(0);
                                            $count = 1;
                                            while ($row = $raw_materials_result->fetch_assoc()) {
                                                $status = $row['status'];
                                                $status_class = strtolower(str_replace(' ', '-', $status));
                                                $status_icon = '';
                                                
                                                switch($status) {
                                                    case 'Critical':
                                                        $status_icon = 'fa-exclamation-circle';
                                                        break;
                                                    case 'Low':
                                                        $status_icon = 'fa-exclamation-triangle';
                                                        break;
                                                    case 'Normal':
                                                        $status_icon = 'fa-check-circle';
                                                        break;
                                                    case 'Out of Stock':
                                                        $status_icon = 'fa-times-circle';
                                                        break;
                                                }
                                                
                                                echo "<tr"
                                                    . " data-image1='" . htmlspecialchars($row['image1'] ?? '') . "'"
                                                    . " data-image2='" . htmlspecialchars($row['image2'] ?? '') . "'"
                                                    . " data-image3='" . htmlspecialchars($row['image3'] ?? '') . "'"
                                                    . " data-location-id='" . htmlspecialchars($row['location_id'] ?? '') . "'"
                                                    . ">";
                                                echo "<td data-label='ID'>" . $count++ . "</td>";
                                                echo "<td data-label='Name'>" . htmlspecialchars($row['name']) . "</td>";
                                                echo "<td data-label='Code/Color'>" . htmlspecialchars($row['code_color']) . "</td>";
                                                echo "<td data-label='Stock'>" . htmlspecialchars(number_format($row['stock_quantity'], 2)) . "</td>";
                                                echo "<td data-label='Location'>" . htmlspecialchars($row['location']) . "</td>";
                                                echo "<td data-label='Status'><span class='status-badge " . $status_class . "'><i class='fas " . $status_icon . "'></i> " . htmlspecialchars($status) . "</span></td>";
                                                echo "<td data-label='Actions' class='table-actions'>
                                                        <button class='btn btn-outline view-raw-btn' data-raw-id='" . htmlspecialchars($row['id']) . "'><i class='fas fa-eye'></i> <span class='action-text'>View</span></button>
                                                        <button class='btn btn-primary edit-raw-btn' data-raw-id='" . htmlspecialchars($row['id']) . "'><i class='fas fa-edit'></i> <span class='action-text'>Edit</span></button>
                                                        <button class='btn btn-danger delete-raw-btn' data-raw-id='" . htmlspecialchars($row['id']) . "'><i class='fas fa-trash'></i> <span class='action-text'>Delete</span></button>
                                                      </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                             echo "<tr><td colspan='7' style='text-align:center;'>No raw materials found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Tab -->
                <div class="tab-pane" id="products">
                    <div class="table-section">
                        <div class="table-header">
                            <div class="table-title">Product Lists</div>
                            <div class="table-actions">
                                <button class="btn btn-primary" onclick="openModal('addProductModal')">
                                    <i class="fas fa-plus"></i>
                                    Add Product
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Materials Used</th>
                                        <th>Stock Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $products_sql = "
                                        SELECT pm.product_id, pm.name, pm.stock_quantity, pm.unit_cost, pm.status, 
                                               GROUP_CONCAT(rm.name SEPARATOR ', ') as materials
                                        FROM product_materials pm
                                        LEFT JOIN raw_materials rm ON pm.raw_material_id = rm.id
                                        GROUP BY pm.product_id
                                        ORDER BY pm.product_id";
                                    $products_result = $conn->query($products_sql);

                                    if ($products_result && $products_result->num_rows > 0) {
                                        while ($row = $products_result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['product_id'] ?? 'N/A') . "</td>";
                                            echo "<td>" . htmlspecialchars($row['name'] ?? 'N/A') . "</td>";
                                            echo "<td>" . htmlspecialchars($row['materials'] ?? 'N/A') . "</td>";
                                            echo "<td>" . htmlspecialchars(number_format($row['stock_quantity'] ?? 0, 2)) . "</td>";
                                            echo "<td>₱" . htmlspecialchars(number_format($row['unit_cost'] ?? 0, 2)) . "</td>";
                                            $status_class = ($row['status'] ?? '') === 'Active' ? 'active' : 'inactive';
                                            echo "<td><span class='status-badge $status_class'>" . htmlspecialchars($row['status'] ?? 'N/A') . "</span></td>";
                                            echo "<td>";
                                            echo "<button class='btn btn-outline' onclick='editProduct(" . ($row['product_id'] ?? 0) . ")'><i class='fas fa-edit'></i></button> ";
                                            echo "<button class='btn btn-danger' onclick='deleteProduct(" . ($row['product_id'] ?? 0) . ")'><i class='fas fa-trash'></i></button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' style='text-align:center;'>No products found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quality Control Tab -->
                <div class="tab-pane" id="quality-control">
                    <div class="quality-control-content">
                        <h2>OPERATORS TALLY SHEET</h2>
                        <div class="tally-sheet">
                            <div class="sheet-header">
                                <div class="logo-placeholder"></div>
                            </div>
                            <div class="sheet-form">
                                <div class="form-column">
                                    <div class="form-group">
                                        <label>Machine Capacity Number</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Production Date</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Part Name</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Part Number</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Number of Cavity</label>
                                        <input type="number" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Reference (Cycle time, second)</label>
                                        <input type="number" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Target Quantity per hour</label>
                                        <input type="number" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Target Quantity per day</label>
                                        <input type="number" class="form-control">
                                    </div>
                                </div>
                                <div class="form-column">
                                    <div class="form-group">
                                        <label>Name of Operator</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Name of Q.C</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>In-charge during that shift</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Shifting</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Pre heating time: Start</label>
                                        <div class="time-inputs">
                                            <input type="time" class="form-control">
                                            <span>Stop</span>
                                            <input type="time" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Operation time: In</label>
                                        <div class="time-inputs">
                                            <input type="time" class="form-control">
                                            <span>Out</span>
                                            <input type="time" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Silicon Number</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Number of Plastic bags</label>
                                        <input type="number" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="tally-table">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>HRS</th>
                                            <th>DESCRIPTION</th>
                                            <th>GOOD</th>
                                            <th>REJECT</th>
                                            <th>REMARKS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Audit Log Container -->
    <div class="audit-log-container" id="auditLogContainer">
    <div class="audit-log-header" id="auditLogHeader">
        <span>Audit Log</span>
        <button class="audit-log-toggle" id="auditLogToggle">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>
    <div class="audit-log-content" id="auditLogContent" style="display:none;">
        <?php if (empty($audit_logs)): ?>
            <div class="audit-log-item">No audit log entries found</div>
        <?php else: ?>
            <?php foreach ($audit_logs as $log): ?>
                <div class="audit-log-item">
                    <div class="audit-log-time">
                        <?php echo date('M d, Y H:i', strtotime($log['date'])); ?>
                    </div>
                    <div class="audit-log-text">
                        <?php echo htmlspecialchars($log['text']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


    <!-- Add Raw Material Modal -->
    <div id="addMaterialModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="closeAddMaterialModal">&times;</span>
            <form class="form-section" id="addMaterialForm" method="POST" action="add_material.php" enctype="multipart/form-data">
                <div class="form-header">
                    <h2 class="form-title"><i class="fas fa-plus-circle"></i> Add Raw Material</h2>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-tag"></i> Material Name</label>
                        <input type="text" class="form-input" name="name" placeholder="Enter material name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-barcode"></i> Material Code & Color</label>
                        <input type="text" class="form-input" name="code_color" placeholder="e.g., RM-006 Red" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-boxes"></i> Initial Stock Quantity</label>
                        <input type="number" step="0.01" class="form-input" name="stock_quantity" placeholder="Enter quantity" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <select class="form-input" name="location_id" required>
                            <option value="">Select Location</option>
                            <?php 
                            $locations_result = $conn->query("SELECT id, name FROM locations ORDER BY name");
                            while ($row = $locations_result->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-image"></i> Image 1</label>
                        <input type="file" class="form-input" name="image1" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-image"></i> Image 2</label>
                        <input type="file" class="form-input" name="image2" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-image"></i> Image 3</label>
                        <input type="file" class="form-input" name="image3" accept="image/*">
                    </div>
                </div>
                <div style="margin-top: 25px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button class="btn btn-primary simple-btn" type="submit">
                        <i class="fas fa-save"></i> Save Material
                    </button>
                    <button class="btn btn-outline simple-btn" type="button" id="cancelAddMaterialModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Raw Material Modal -->
    <div id="viewRawMaterialModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="closeViewRawMaterialModal">&times;</span>
            <div class="form-section" style="box-shadow:none; border:none; margin-bottom:0;">
                <div class="form-header">
                    <h2 class="form-title"><i class="fas fa-info-circle"></i> Raw Material Details</h2>
                </div>
                <div class="slider-container">
                    <div class="slider" id="rawMaterialImageSlider"></div>
                    <div class="slider-controls">
                        <button class="slider-btn" id="prevRawImage"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="slider-btn" id="nextRawImage"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-tag"></i> Material Name</label>
                        <p id="viewRawMaterialName" class="form-input" style="border:none; background:none;"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-barcode"></i> Material Code</label>
                        <p id="viewRawMaterialCode" class="form-input" style="border:none; background:none;"></p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-boxes"></i> Stock</label>
                        <p id="viewRawMaterialStock" class="form-input" style="border:none; background:none;"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <p id="viewRawMaterialLocation" class="form-input" style="border:none; background:none;"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-info-circle"></i> Status</label>
                        <p id="viewRawMaterialStatus" class="form-input" style="border:none; background:none;"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Raw Material Modal -->
    <div id="editRawMaterialModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="closeEditRawMaterialModal">&times;</span>
            <form class="form-section" id="editRawMaterialForm" method="POST" action="edit_material.php" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editRawMaterialId">
                <div class="form-header">
                    <h2 class="form-title"><i class="fas fa-edit"></i> Edit Raw Material</h2>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-tag"></i> Material Name</label>
                        <input type="text" class="form-input" name="name" id="editRawMaterialName" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-barcode"></i> Material Code & Color</label>
                        <input type="text" class="form-input" name="code_color" id="editRawMaterialCodeColor" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-boxes"></i> Stock Quantity</label>
                        <input type="number" step="0.01" class="form-input" name="stock_quantity" id="editRawMaterialStock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <select class="form-input" name="location_id" id="editRawMaterialLocation" required>
                            <option value="">Select Location</option>
                            <?php $locs = $conn->query("SELECT id, name FROM locations ORDER BY name"); while ($row = $locs->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-image"></i> Image 1</label>
                        <input type="file" class="form-input" name="image1" id="editImage1" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-image"></i> Image 2</label>
                        <input type="file" class="form-input" name="image2" id="editImage2" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-image"></i> Image 3</label>
                        <input type="file" class="form-input" name="image3" id="editImage3" accept="image/*">
                    </div>
                </div>
                <div style="margin-top: 25px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button class="btn btn-primary simple-btn" type="submit">
                        <i class="fas fa-save"></i> Update Material
                    </button>
                    <button class="btn btn-outline simple-btn" type="button" id="cancelEditRawMaterialModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div id="addNoteModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeAddNoteModal()">&times;</span>
            <form id="addNoteForm" method="POST">
                <input type="hidden" name="action" value="add_note">
                <div class="form-header">
                    <h2 class="form-title"><i class="fas fa-plus-circle"></i> Add Schedule Note</h2>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar-day"></i> Date</label>
                        <input type="date" class="form-input" name="note_date" id="noteDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-sticky-note"></i> Note/Task</label>
                        <textarea class="form-input" name="note_text" id="noteText" rows="3" required placeholder="Enter your note or task details"></textarea>
                    </div>
                </div>
                <div style="margin-top: 25px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button class="btn btn-primary simple-btn" type="submit">
                        <i class="fas fa-save"></i> Save Note
                    </button>
                    <button class="btn btn-outline simple-btn" type="button" onclick="closeAddNoteModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Note Modal -->
    <div id="editNoteModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditNoteModal()">&times;</span>
            <form id="editNoteForm" method="POST">
                <input type="hidden" name="action" value="edit_note">
                <input type="hidden" name="note_id" id="editNoteId">
                <div class="form-header">
                    <h2 class="form-title"><i class="fas fa-edit"></i> Edit Schedule Note</h2>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar-day"></i> Date</label>
                        <input type="date" class="form-input" name="note_date" id="editNoteDate" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-sticky-note"></i> Note/Task</label>
                        <textarea class="form-input" name="note_text" id="editNoteText" rows="3" required></textarea>
                    </div>
                </div>
                <div style="margin-top: 25px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button class="btn btn-primary simple-btn" type="submit">
                        <i class="fas fa-save"></i> Update Note
                    </button>
                    <button class="btn btn-outline simple-btn" type="button" onclick="closeEditNoteModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Note Confirmation Modal -->
    <div id="deleteNoteModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeDeleteNoteModal()">&times;</span>
            <form id="deleteNoteForm" method="POST">
                <input type="hidden" name="action" value="delete_note">
                <input type="hidden" name="note_id" id="deleteNoteId">
                <div class="form-header">
                    <h2 class="form-title"><i class="fas fa-trash-alt"></i> Confirm Deletion</h2>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <p>Are you sure you want to delete this note? This action cannot be undone.</p>
                    </div>
                </div>
                <div style="margin-top: 25px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button class="btn btn-danger simple-btn" type="submit">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="btn btn-outline simple-btn" type="button" onclick="closeDeleteNoteModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add FullCalendar CSS/JS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const modules = document.querySelectorAll('.module-content');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.querySelector('.sidebar');

        // Tab switching - Updated for standardized finance-tab classes
        // Handle manufacturing navigation tabs (first set)
        const manufacturingTabs = document.querySelectorAll('.finance-tab[data-tab="schedule"], .finance-tab[data-tab="raw-materials"], .finance-tab[data-tab="products"], .finance-tab[data-tab="quality-control"]');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        manufacturingTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                
                // Remove active class from manufacturing tabs and tab panes
                manufacturingTabs.forEach(t => t.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding tab pane
                tab.classList.add('active');
                const targetPane = document.getElementById(target);
                if (targetPane) {
                    targetPane.classList.add('active');
                }
                
                console.log('Manufacturing tab clicked:', target);
                
                // Add visual feedback
                tab.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    tab.style.transform = '';
                }, 150);
            });
        });
        
        // Handle secondary tabs (second set)
        const secondaryTabs = document.querySelectorAll('.finance-tab[data-tab="suppliers"], .finance-tab[data-tab="purchase-orders"], .finance-tab[data-tab="deliveries"], .finance-tab[data-tab="analytics"]');
        const moduleContents = document.querySelectorAll('.module-content');
        
        secondaryTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                
                // Remove active class from secondary tabs and module contents
                secondaryTabs.forEach(t => t.classList.remove('active'));
                moduleContents.forEach(m => m.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding module content
                tab.classList.add('active');
                const targetModule = document.getElementById(target);
                if (targetModule) {
                    targetModule.classList.add('active');
                }
                
                console.log('Secondary tab clicked:', target);
                
                // Add visual feedback
                tab.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    tab.style.transform = '';
                }, 150);
            });
        });

        // Mobile menu toggle
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (sidebar && sidebar.classList.contains('active')) {
                if (!sidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Dropdown functionality
        const supplyChainDropdown = document.getElementById('supplyChainDropdown');
        const supplyChainDropdownMenu = document.getElementById('supplyChainDropdownMenu');
        
        if (supplyChainDropdown) {
            supplyChainDropdown.addEventListener('click', function() {
                supplyChainDropdownMenu.classList.toggle('active');
            });
        }

        // Logout functionality
        const logoutBtn = document.getElementById('logoutBtn');
        if(logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.stopPropagation(); 
            });
        }

        // Handle window resize
        function handleResize() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        }

        window.addEventListener('resize', handleResize);

        // Initialize responsive behavior
        handleResize();

        // Literal Calendar using FullCalendar
        if (document.getElementById('calendar')) {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 500,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                dateClick: function(info) {
                    // Set the date in the add note modal when a date is clicked
                    document.getElementById('noteDate').value = info.dateStr;
                    openAddNoteModal();
                }
            });
            calendar.render();
        }
    });

    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function editSupplier(id) {
        console.log('Edit supplier:', id);
    }

    function deleteSupplier(id) {
        if (confirm('Are you sure you want to delete this supplier?')) {
            console.log('Delete supplier:', id);
        }
    }

    function viewOrder(id) {
        console.log('View order:', id);
    }

    function createDelivery(id) {
        console.log('Create delivery for order:', id);
    }

    function viewDelivery(id) {
        console.log('View delivery:', id);
    }

    function receiveDelivery(id) {
        console.log('Receive delivery:', id);
    }

    // Raw Materials Modal Functions
    function openAddMaterialModal() {
        document.getElementById('addMaterialModal').style.display = 'flex';
    }

    function closeAddMaterialModal() {
        document.getElementById('addMaterialModal').style.display = 'none';
    }

    function openViewRawMaterialModal(id) {
        // Fetch material data and populate modal
        console.log('View raw material:', id);
        document.getElementById('viewRawMaterialModal').style.display = 'flex';
    }

    function closeViewRawMaterialModal() {
        document.getElementById('viewRawMaterialModal').style.display = 'none';
    }

    function openEditRawMaterialModal(id) {
        // Fetch material data and populate modal
        console.log('Edit raw material:', id);
        document.getElementById('editRawMaterialModal').style.display = 'flex';
    }

    function closeEditRawMaterialModal() {
        document.getElementById('editRawMaterialModal').style.display = 'none';
    }

    function deleteRawMaterial(id) {
        if (confirm('Are you sure you want to delete this raw material?')) {
            window.location.href = 'delete_material.php?id=' + id;
        }
    }

    // Note Modal Functions
    function openAddNoteModal() {
        // Set today's date as default if not already set
        if (!document.getElementById('noteDate').value) {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('noteDate').value = today;
        }
        document.getElementById('addNoteModal').style.display = 'flex';
    }

    function closeAddNoteModal() {
        document.getElementById('addNoteModal').style.display = 'none';
    }

    function openEditNoteModal(id, date, text) {
        document.getElementById('editNoteId').value = id;
        document.getElementById('editNoteDate').value = date;
        document.getElementById('editNoteText').value = text;
        document.getElementById('editNoteModal').style.display = 'flex';
    }

    function closeEditNoteModal() {
        document.getElementById('editNoteModal').style.display = 'none';
    }

    function confirmDeleteNote(id) {
        document.getElementById('deleteNoteId').value = id;
        document.getElementById('deleteNoteModal').style.display = 'flex';
    }

    function closeDeleteNoteModal() {
        document.getElementById('deleteNoteModal').style.display = 'none';
    }

    // Initialize raw materials functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add Material Modal
        const openAddMaterialBtn = document.getElementById('openAddMaterialModal');
        const closeAddMaterialBtn = document.getElementById('closeAddMaterialModal');
        const cancelAddMaterialBtn = document.getElementById('cancelAddMaterialModal');

        if (openAddMaterialBtn) {
            openAddMaterialBtn.addEventListener('click', openAddMaterialModal);
        }
        if (closeAddMaterialBtn) {
            closeAddMaterialBtn.addEventListener('click', closeAddMaterialModal);
        }
        if (cancelAddMaterialBtn) {
            cancelAddMaterialBtn.addEventListener('click', closeAddMaterialModal);
        }

        // View Raw Material Modal
        const closeViewRawMaterialBtn = document.getElementById('closeViewRawMaterialModal');
        if (closeViewRawMaterialBtn) {
            closeViewRawMaterialBtn.addEventListener('click', closeViewRawMaterialModal);
        }

        // Edit Raw Material Modal
        const closeEditRawMaterialBtn = document.getElementById('closeEditRawMaterialModal');
        if (closeEditRawMaterialBtn) {
            closeEditRawMaterialBtn.addEventListener('click', closeEditRawMaterialModal);
        }

        // Raw material action buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('view-raw-btn')) {
                const id = e.target.getAttribute('data-raw-id');
                openViewRawMaterialModal(id);
            }
            if (e.target.classList.contains('edit-raw-btn')) {
                const id = e.target.getAttribute('data-raw-id');
                openEditRawMaterialModal(id);
            }
            if (e.target.classList.contains('delete-raw-btn')) {
                const id = e.target.getAttribute('data-raw-id');
                deleteRawMaterial(id);
            }
        });

        // Search functionality
        const searchInput = document.getElementById('rawMaterialSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const table = document.getElementById('rawMaterialTable');
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

                for (let row of rows) {
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    for (let cell of cells) {
                        if (cell.textContent.toLowerCase().includes(searchTerm)) {
                            found = true;
                            break;
                        }
                    }
                    row.style.display = found ? '' : 'none';
                }
            });
        }
    });
     // Audit Log Toggle 
document.addEventListener('DOMContentLoaded', function() {
    const auditLogContainer = document.getElementById('auditLogContainer');
    const auditLogHeader = document.getElementById('auditLogHeader');
    const auditLogContent = document.getElementById('auditLogContent');
    const auditLogToggle = document.getElementById('auditLogToggle');

    if (auditLogHeader && auditLogToggle) {
        // Initialize state from localStorage or default to closed
        let auditLogExpanded = localStorage.getItem('auditLogExpanded') === 'true';
        
        // Set initial state
        if (auditLogExpanded) {
            auditLogContainer.classList.add('expanded');
            auditLogContent.style.display = 'block';
        } else {
            auditLogContainer.classList.remove('expanded');
            auditLogContent.style.display = 'none';
        }

        // Toggle function
        function toggleAuditLog() {
            auditLogExpanded = !auditLogExpanded;
            auditLogContainer.classList.toggle('expanded', auditLogExpanded);
            auditLogContent.style.display = auditLogExpanded ? 'block' : 'none';
            localStorage.setItem('auditLogExpanded', auditLogExpanded);
        }

        // Set up event listeners
        auditLogHeader.addEventListener('click', function(e) {
            // Only toggle if the click wasn't on the toggle button itself
            if (!auditLogToggle.contains(e.target)) {
                toggleAuditLog();
            }
        });

        auditLogToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleAuditLog();
        });

        // Close log when clicking outside
        document.addEventListener('click', function(e) {
            if (auditLogExpanded && !auditLogContainer.contains(e.target)) {
                auditLogExpanded = false;
                auditLogContainer.classList.remove('expanded');
                auditLogContent.style.display = 'none';
                localStorage.setItem('auditLogExpanded', false);
            }
        });
    }
});

    </script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

// Fetch user details from session for display.
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $order_number = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
                $supplier_id = $_POST['supplier_id'];
                $order_date = $_POST['order_date'];
                $expected_delivery = $_POST['expected_delivery'];
                $status = $_POST['status'];
                $notes = $_POST['notes'];
                
                $sql = "INSERT INTO purchase_orders (order_number, supplier_id, order_date, expected_delivery, status, notes) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sissss", $order_number, $supplier_id, $order_date, $expected_delivery, $status, $notes);
                
                if ($stmt->execute()) {
                    $order_id = $conn->insert_id;
                    
                    // Handle order items
                    if (isset($_POST['items']) && is_array($_POST['items'])) {
                        foreach ($_POST['items'] as $item) {
                            if (!empty($item['material_id']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                                $material_id = $item['material_id'];
                                $quantity = $item['quantity'];
                                $unit_price = $item['unit_price'];
                                
                                $item_sql = "INSERT INTO purchase_order_items (purchase_order_id, raw_material_id, quantity, unit_price) 
                                            VALUES (?, ?, ?, ?)";
                                $item_stmt = $conn->prepare($item_sql);
                                $item_stmt->bind_param("iidd", $order_id, $material_id, $quantity, $unit_price);
                                $item_stmt->execute();
                            }
                        }
                    }
                    
                    header('Location: purchase_orders.php?success=1');
                    exit;
                } else {
                    $error = "Error adding purchase order: " . $conn->error;
                }
                break;
                
            case 'update_status':
                $order_id = $_POST['order_id'];
                $status = $_POST['status'];
                
                $sql = "UPDATE purchase_orders SET status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $order_id);
                
                if ($stmt->execute()) {
                    header('Location: purchase_orders.php?success=2');
                    exit;
                } else {
                    $error = "Error updating order status: " . $conn->error;
                }
                break;
                
            case 'delete':
                $order_id = $_POST['order_id'];
                
                // Delete order items first
                $conn->query("DELETE FROM purchase_order_items WHERE purchase_order_id = $order_id");
                
                // Delete the order
                $sql = "DELETE FROM purchase_orders WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $order_id);
                
                if ($stmt->execute()) {
                    header('Location: purchase_orders.php?success=3');
                    exit;
                } else {
                    $error = "Error deleting purchase order: " . $conn->error;
                }
                break;
        }
    }
}

// Fetch purchase orders data
$purchase_orders_sql = "
    SELECT po.id, po.order_number, po.order_date, po.expected_delivery, po.status, po.total_amount, po.notes,
           s.name as supplier_name, s.contact_person as supplier_contact, s.phone as supplier_phone,
           COUNT(poi.id) as total_items,
           SUM(poi.quantity * poi.unit_price) as calculated_total
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
    GROUP BY po.id
    ORDER BY po.order_date DESC";
$purchase_orders_result = $conn->query($purchase_orders_sql);

// Fetch suppliers for dropdown
$suppliers_result = $conn->query("SELECT id, name FROM suppliers WHERE status = 'Active' ORDER BY name");

// Fetch raw materials for dropdown
$materials_result = $conn->query("SELECT id, name, code_color FROM raw_materials ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
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

    .menu-dropdown {
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
    }

    .table-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--dark-gray);
    }

    .table-actions {
        display: flex;
        gap: 0.75rem;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    th {
        background: var(--light-gray);
        font-weight: 600;
        color: var(--dark-gray);
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
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
        color: var(--white);
    }

    .btn-success:hover {
        background: #059669;
    }

    .btn-warning {
        background: var(--warning);
        color: var(--white);
    }

    .btn-warning:hover {
        background: #d97706;
    }

    .btn-danger {
        background: var(--error);
        color: var(--white);
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
        color: var(--white);
    }

    /* Status Badges */
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-badge.pending { background: #fef3c7; color: #92400e; }
    .status-badge.approved { background: #dbeafe; color: #1e40af; }
    .status-badge.completed { background: #dcfce7; color: #166534; }
    .status-badge.cancelled { background: #fee2e2; color: #991b1b; }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: var(--white);
        margin: 2% auto;
        padding: 2rem;
        border-radius: 12px;
        width: 95%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-lg);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dark-gray);
    }

    .close {
        color: var(--gray);
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
        background: none;
        border: none;
    }

    .close:hover {
        color: var(--dark-gray);
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--dark-gray);
    }

    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.875rem;
        transition: var(--transition-fast);
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .form-select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.875rem;
        background: var(--white);
        cursor: pointer;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    /* Order Items */
    .order-items {
        margin-top: 1.5rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
    }

    .order-items-header {
        background: var(--light-gray);
        padding: 1rem;
        font-weight: 600;
        border-bottom: 1px solid var(--border-color);
    }

    .order-item {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        align-items: center;
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .remove-item {
        background: var(--error);
        color: var(--white);
        border: none;
        border-radius: 4px;
        padding: 0.25rem 0.5rem;
        cursor: pointer;
        font-size: 0.75rem;
    }

    .add-item-btn {
        background: var(--success);
        color: var(--white);
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        cursor: pointer;
        margin-top: 1rem;
        font-size: 0.875rem;
    }

    /* Alert Messages */
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-weight: 500;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
        }

        .table-responsive {
            font-size: 0.75rem;
        }

        th, td {
            padding: 0.5rem;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .order-item {
            grid-template-columns: 1fr;
            gap: 0.5rem;
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
                <div class="menu-item menu-dropdown" id="supplyChainDropdown">
                    <i class="fas fa-link"></i>
                    <span>Supply Chain</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown-menu" id="supplyChainDropdownMenu">
                    <a href="supply_chain.php" class="menu-item" data-module="manufacturing">
                        <i class="fas fa-industry"></i>
                        <span>Manufacturing</span>
                    </a>
                    <a href="suppliers.php" class="menu-item" data-module="transactions">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </div>
                <a href="transactions.php" class="menu-item" data-module="customer-service">
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
                <h1 class="header-title">Purchase Orders</h1>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </div>
        <div class="content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    switch ($_GET['success']) {
                        case '1': echo 'Purchase order created successfully!'; break;
                        case '2': echo 'Order status updated successfully!'; break;
                        case '3': echo 'Purchase order deleted successfully!'; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="table-section">
                <div class="table-header">
                    <div class="table-title">Purchase Orders Overview</div>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="openModal('addOrderModal')">
                            <i class="fas fa-plus"></i>
                            New Order
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Supplier</th>
                                <th>Order Date</th>
                                <th>Expected Delivery</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($purchase_orders_result && $purchase_orders_result->num_rows > 0) {
                                while ($row = $purchase_orders_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['supplier_name']) . "</td>";
                                    echo "<td>" . date('m/d/Y', strtotime($row['order_date'])) . "</td>";
                                    echo "<td>" . date('m/d/Y', strtotime($row['expected_delivery'])) . "</td>";
                                    echo "<td>" . $row['total_items'] . " items</td>";
                                    echo "<td>₱" . number_format($row['calculated_total'] ?? 0, 2) . "</td>";
                                    $status_class = strtolower($row['status']);
                                    echo "<td><span class='status-badge $status_class'>" . htmlspecialchars($row['status']) . "</span></td>";
                                    echo "<td>";
                                    echo "<button class='btn btn-outline' onclick='viewOrder(" . $row['id'] . ")'><i class='fas fa-eye'></i></button> ";
                                    echo "<button class='btn btn-warning' onclick='updateStatus(" . $row['id'] . ")'><i class='fas fa-edit'></i></button> ";
                                    echo "<button class='btn btn-danger' onclick='deleteOrder(" . $row['id'] . ")'><i class='fas fa-trash'></i></button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align:center;'>No purchase orders found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Purchase Order Modal -->
    <div id="addOrderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Create New Purchase Order</h2>
                <button class="close" onclick="closeModal('addOrderModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            <?php
                            if ($suppliers_result && $suppliers_result->num_rows > 0) {
                                while ($supplier = $suppliers_result->fetch_assoc()) {
                                    echo "<option value='" . $supplier['id'] . "'>" . htmlspecialchars($supplier['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Order Date</label>
                        <input type="date" name="order_date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expected Delivery</label>
                        <input type="date" name="expected_delivery" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="3"></textarea>
                </div>
                
                <div class="order-items">
                    <div class="order-items-header">Order Items</div>
                    <div id="orderItems">
                        <div class="order-item">
                            <select name="items[0][material_id]" class="form-select" required>
                                <option value="">Select Material</option>
                                <?php
                                if ($materials_result && $materials_result->num_rows > 0) {
                                    while ($material = $materials_result->fetch_assoc()) {
                                        echo "<option value='" . $material['id'] . "'>" . htmlspecialchars($material['name'] . ' (' . $material['code_color'] . ')') . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <input type="number" name="items[0][quantity]" placeholder="Quantity" class="form-input" required>
                            <input type="number" name="items[0][unit_price]" placeholder="Unit Price" class="form-input" step="0.01" required>
                            <span class="item-total">₱0.00</span>
                            <button type="button" class="remove-item" onclick="removeItem(this)">×</button>
                        </div>
                    </div>
                    <button type="button" class="add-item-btn" onclick="addItem()">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addOrderModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Update Order Status</h2>
                <button class="close" onclick="closeModal('updateStatusModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="update_order_id">
                <div class="form-group">
                    <label class="form-label">New Status</label>
                    <select name="status" class="form-select" required>
                        <option value="">Select Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('updateStatusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteOrderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Purchase Order</h2>
                <button class="close" onclick="closeModal('deleteOrderModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="order_id" id="delete_order_id">
                <p>Are you sure you want to delete this purchase order? This action cannot be undone.</p>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('deleteOrderModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Order</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/script.js"></script>
    <script>
        let itemCount = 1;

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function updateStatus(orderId) {
            document.getElementById('update_order_id').value = orderId;
            openModal('updateStatusModal');
        }

        function deleteOrder(orderId) {
            document.getElementById('delete_order_id').value = orderId;
            openModal('deleteOrderModal');
        }

        function viewOrder(orderId) {
            // In a real application, you would fetch and display order details
            alert('View order details for ID: ' + orderId);
        }

        function addItem() {
            const container = document.getElementById('orderItems');
            const newItem = document.createElement('div');
            newItem.className = 'order-item';
            newItem.innerHTML = `
                <select name="items[${itemCount}][material_id]" class="form-select" required>
                    <option value="">Select Material</option>
                    <?php
                    if ($materials_result && $materials_result->num_rows > 0) {
                        $materials_result->data_seek(0);
                        while ($material = $materials_result->fetch_assoc()) {
                            echo "<option value='" . $material['id'] . "'>" . htmlspecialchars($material['name'] . ' (' . $material['code_color'] . ')') . "</option>";
                        }
                    }
                    ?>
                </select>
                <input type="number" name="items[${itemCount}][quantity]" placeholder="Quantity" class="form-input" required>
                <input type="number" name="items[${itemCount}][unit_price]" placeholder="Unit Price" class="form-input" step="0.01" required>
                <span class="item-total">₱0.00</span>
                <button type="button" class="remove-item" onclick="removeItem(this)">×</button>
            `;
            container.appendChild(newItem);
            itemCount++;
        }

        function removeItem(button) {
            const item = button.parentElement;
            if (document.querySelectorAll('.order-item').length > 1) {
                item.remove();
            }
        }

        // Calculate item totals
        document.addEventListener('input', function(e) {
            if (e.target.name && e.target.name.includes('quantity') || e.target.name.includes('unit_price')) {
                const item = e.target.closest('.order-item');
                const quantity = item.querySelector('input[name*="quantity"]').value || 0;
                const unitPrice = item.querySelector('input[name*="unit_price"]').value || 0;
                const total = quantity * unitPrice;
                item.querySelector('.item-total').textContent = '₱' + total.toFixed(2);
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const inventoryDropdown = document.getElementById('inventoryDropdown');
            const inventoryDropdownMenu = document.getElementById('inventoryDropdownMenu');
            
            if (inventoryDropdown) {
                inventoryDropdown.addEventListener('click', function() {
                    inventoryDropdownMenu.classList.toggle('active');
                });
            }

            // Logout functionality
            const logoutBtn = document.getElementById('logoutBtn');
            if(logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); 
                });
            }
        });
    </script>
</body>
</html> 
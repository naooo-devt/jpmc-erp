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

$raw_materials_sql = "
    SELECT rm.id, rm.name, rm.code_color, rm.stock_quantity, l.name as location, rm.status, rm.image1, rm.image2, rm.image3, rm.location_id
    FROM raw_materials rm
    LEFT JOIN locations l ON rm.location_id = l.id
    ORDER BY rm.id";
$raw_materials_result = $conn->query($raw_materials_sql);
$locations_result = $conn->query("SELECT id, name FROM locations ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Raw Materials - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="icon" href="images/logo.png">
    <style>
    :root {
        /* Primary Colors */
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
        background: var(--white);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .company-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 0.5rem;
    }

    .company-name {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: var(--white);
    }

    .company-subtitle {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 400;
    }

    .sidebar-menu {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 0;
    }

    .menu-section {
        margin-bottom: 2rem;
    }

    .menu-section-title {
        padding: 0 1.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.6);
        letter-spacing: 0.5px;
    }

    .menu-item {
        padding: 0.75rem 1.25rem;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: var(--transition-fast);
        border-left: 3px solid transparent;
        position: relative;
        margin: 0.125rem 0;
        text-decoration: none;
        color: #fff !important;
    }

    .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        border-left-color: var(--primary-red);
        color: #fff !important;
    }

    .menu-item.active {
        background: rgba(255, 255, 255, 0.15);
        border-left-color: var(--primary-red);
        color: #fff !important;
    }

    .menu-item i {
        margin-right: 0.75rem;
        font-size: 1.1rem;
        width: 1.5rem;
        text-align: center;
    }

    .menu-item span {
        font-weight: 500;
        font-size: 0.95rem;
    }

    .menu-dropdown .fa-chevron-down {
        margin-left: auto;
        transition: transform 0.3s ease;
        font-size: 0.8rem;
    }

    .fa-rotate-180 {
        transform: rotate(180deg);
    }

    .dropdown-menu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(0, 0, 0, 0.1);
    }

    .dropdown-menu.open {
        max-height: 500px;
    }

    .dropdown-menu .menu-item {
        padding-left: 3.25rem !important;
    }

    /* Main Content Styles */
    .main-content {
        flex: 1;
        margin-left: var(--sidebar-width);
        transition: var(--transition);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Header Styles */
    .header {
        height: var(--header-height);
        background: var(--white);
        box-shadow: var(--shadow);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 var(--content-padding);
        position: sticky;
        top: 0;
        z-index: 100;
        border-bottom: 1px solid var(--border-color);
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .header-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-blue);
        margin: 0;
        line-height: 1;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        border-radius: 12px;
        background-color: rgba(37, 99, 235, 0.1);
    }

    .user-profile i {
        font-size: 1.25rem;
        color: var(--primary-blue);
    }

    .user-profile span {
        font-weight: 600;
        color: var(--dark-gray);
        font-size: 0.95rem;
    }

    /* Content Area */
    .content {
        flex: 1;
        padding: var(--content-padding);
        background-color: var(--light-gray);
    }

    .module-content {
        display: none;
        animation: fadeIn 0.3s ease-out;
    }

    .module-content.active {
        display: block;
    }

    /* Table Section */
    .table-section {
        background: var(--white);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        transition: all 0.3s ease;
    }

    .table-section:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .table-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-blue);
        margin: 0;
        letter-spacing: -0.5px;
        line-height: 1;
    }

    /* Search Input */
    .search-container {
        position: relative;
        display: inline-block;
        width: 280px;
    }

    .search-input {
        width: 100%;
        border-radius: 12px;
        padding: 12px 20px 12px 48px;
        font-size: 0.95rem;
        border: 2px solid rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        background-color: #f8fafc;
        color: var(--dark-gray);
    }

    .search-input:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        outline: none;
        background-color: var(--white);
    }

    .search-container .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
        font-size: 16px;
        z-index: 1;
    }

    /* Table Styling */
    #rawMaterialTable {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    #rawMaterialTable th {
        background: transparent;
        border-bottom: 2px solid var(--primary-blue);
        color: var(--dark-blue);
        font-size: 0.85rem;
        padding: 12px 16px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        white-space: nowrap;
        text-align: center;
    }

    #rawMaterialTable td {
        padding: 16px;
        vertical-align: middle;
        border: none;
        background: var(--white);
        font-size: 0.95rem;
        color: var(--dark-gray);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    #rawMaterialTable tr {
        transition: all 0.3s ease;
    }

    #rawMaterialTable tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
    }

    #rawMaterialTable tr td:first-child {
        border-left: 1px solid rgba(0, 0, 0, 0.05);
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }

    #rawMaterialTable tr td:last-child {
        border-right: 1px solid rgba(0, 0, 0, 0.05);
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    /* Status Badges */
    .status-badge {
        padding: 8px 14px;
        border-radius: 24px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 100px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .status-badge.critical {
        background: linear-gradient(135deg, rgba(220, 38, 38, 0.15), rgba(220, 38, 38, 0.25));
        color: #dc2626;
        border: 1px solid rgba(220, 38, 38, 0.3);
    }

    .status-badge.low {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.25));
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .status-badge.normal {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.25)) !important;
        color: #059669 !important;
        border: 1px solid rgba(16, 185, 129, 0.3) !important;
    }

    .status-badge.out-of-stock {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.15), rgba(107, 114, 128, 0.25)) !important;
        color: #6b7280 !important;
        border: 1px solid rgba(107, 114, 128, 0.3) !important;
    }

    /* Force override for status badges */
    table .status-badge.normal {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.25)) !important;
        color: #059669 !important;
        border: 1px solid rgba(16, 185, 129, 0.3) !important;
    }

    table .status-badge.out-of-stock {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.15), rgba(107, 114, 128, 0.25)) !important;
        color: #6b7280 !important;
        border: 1px solid rgba(107, 114, 128, 0.3) !important;
    }

    /* Additional specific overrides */
    .table-responsive .status-badge.normal {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.25)) !important;
        color: #059669 !important;
        border: 1px solid rgba(16, 185, 129, 0.3) !important;
    }

    .table-responsive .status-badge.out-of-stock {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.15), rgba(107, 114, 128, 0.25)) !important;
        color: #6b7280 !important;
        border: 1px solid rgba(107, 114, 128, 0.3) !important;
    }

    /* Debug styles - make them very obvious */
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

    .status-badge i {
        margin-right: 6px;
        font-size: 0.8rem;
    }

    /* Buttons */
    .btn {
        padding: 10px 20px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
        color: var(--white);
        border: 1px solid var(--primary-blue);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    .btn-outline {
        background: transparent;
        border: 2px solid var(--primary-blue);
        color: var(--primary-blue);
        box-shadow: none;
    }

    .btn-outline:hover {
        background: var(--primary-blue);
        color: var(--white);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    .btn-danger {
        background: linear-gradient(135deg, var(--primary-red), #ef4444);
        color: var(--white);
        border: 1px solid var(--primary-red);
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #b91c1c, var(--primary-red));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
    }

    /* Enhanced Modal Styling */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
    }

    .modal-content {
        background: var(--white);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        width: 100%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        padding: 32px;
        border: none;
        animation: modalFadeIn 0.3s ease-out;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, var(--primary-red), var(--primary-blue));
        border-radius: 20px 20px 0 0;
    }

    .close-modal {
        position: absolute;
        top: 24px;
        right: 24px;
        font-size: 1.5rem;
        color: var(--gray);
        cursor: pointer;
        transition: all 0.2s ease;
        background: none;
        border: none;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .close-modal:hover {
        color: var(--primary-red);
        background: rgba(239, 68, 68, 0.1);
        transform: rotate(90deg);
    }

    /* Form Elements */
    .form-section {
        margin-bottom: 0;
    }

    .form-header {
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .form-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-blue);
        letter-spacing: -0.5px;
        margin-bottom: 8px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: var(--dark-gray);
        font-size: 0.95rem;
    }

    .form-input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f8fafc;
        color: var(--dark-gray);
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        background: var(--white);
    }

    /* Image Slider */
    .slider-container {
        position: relative;
        min-height: 240px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 32px;
        margin-top: 16px;
        border-radius: 16px;
        background: #f8fafc;
        overflow: hidden;
    }

    .slider-img {
        width: 100%;
        max-width: 400px;
        height: 240px;
        object-fit: contain;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        margin: auto;
        z-index: 1;
    }

    .slider-controls {
        position: absolute;
        width: 100%;
        top: 50%;
        left: 0;
        display: flex;
        justify-content: space-between;
        transform: translateY(-50%);
        pointer-events: none;
        z-index: 2;
        padding: 0 16px;
    }

    .slider-btn {
        pointer-events: auto;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--white);
        border: 2px solid var(--primary-blue);
        color: var(--primary-blue);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .slider-btn:hover {
        background: var(--primary-blue);
        color: var(--white);
        transform: scale(1.1);
    }

    /* Recent Searches Dropdown */
    .recent-searches-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        background: var(--white);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        z-index: 10;
        max-height: 240px;
        overflow-y: auto;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }

    .search-input:focus + .recent-searches-dropdown,
    .recent-searches-dropdown:hover {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .recent-searches-dropdown div {
        padding: 12px 16px;
        cursor: pointer;
        color: var(--dark-gray);
        font-size: 0.95rem;
        transition: all 0.2s ease;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .recent-searches-dropdown div:last-child {
        border-bottom: none;
    }

    .recent-searches-dropdown div:hover {
        background: rgba(37, 99, 235, 0.05);
        color: var(--primary-blue);
    }

    /* Alert Messages */
    .alert {
        padding: 16px 24px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .alert-success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.2));
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .alert-danger {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.2));
        color: #991b1b;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .alert i {
        font-size: 1.2rem;
    }

    /* Delete Confirmation Modal */
    .delete-confirm-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .delete-confirm-content {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    
    .delete-confirm-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.5rem;
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 992px) {
        .sidebar {
            width: 80px;
            overflow: hidden;
        }
        .sidebar:hover {
            width: var(--sidebar-width);
        }
        .company-name, .company-subtitle, .menu-section-title, .menu-item span, .menu-dropdown .fa-chevron-down {
            display: none;
        }
        .sidebar:hover .company-name, 
        .sidebar:hover .company-subtitle, 
        .sidebar:hover .menu-section-title, 
        .sidebar:hover .menu-item span, 
        .sidebar:hover .menu-dropdown .fa-chevron-down {
            display: block;
        }
        .main-content {
            margin-left: 80px;
        }
        
        /* Improved table actions for medium screens */
        .table-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .table-actions .btn {
            width: 100%;
            padding: 8px;
        }
    }

    @media (max-width: 768px) {
        :root {
            --content-padding: 20px;
            --sidebar-width: 0;
        }
        
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .sidebar.active {
            transform: translateX(0);
            width: var(--sidebar-width);
        }
        
        .main-content {
            margin-left: 0;
        }
        
        .mobile-menu-toggle {
            display: block !important;
        }
        
        .table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        
        .search-container {
            width: 100%;
            order: 2;
        }
        
        .table-title {
            order: 1;
            width: 100%;
        }
        
        #openAddMaterialModal {
            order: 3;
            width: 100%;
        }
        
        #rawMaterialTable th, 
        #rawMaterialTable td {
            padding: 12px 8px;
            font-size: 0.85rem;
        }
        
        /* Stack table cells vertically */
        #rawMaterialTable tr {
            display: block;
            margin-bottom: 16px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            position: relative;
        }
        
        #rawMaterialTable thead {
            display: none;
        }
        
        #rawMaterialTable td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            border-bottom: 1px solid var(--border-color);
            padding: 12px 16px;
        }
        
        #rawMaterialTable td:before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--dark-blue);
            margin-right: 12px;
            flex: 1;
        }
        
        #rawMaterialTable td:last-child {
            border-bottom: none;
        }
        
        #rawMaterialTable tr td:first-child,
        #rawMaterialTable tr td:last-child {
            border-radius: 0;
        }
        
        #rawMaterialTable tr td:first-child {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        
        #rawMaterialTable tr td:last-child {
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            padding: 24px 16px;
            max-width: 95vw;
        }
        
        .slider-img {
            max-width: 90%;
            height: 200px;
        }
        
        .btn {
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            background: var(--primary-blue);
            color: white;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            margin-right: 12px;
            box-shadow: var(--shadow);
        }
        
        .action-text {
            display: none;
        }
    }

    @media (max-width: 576px) {
        :root {
            --content-padding: 16px;
        }
        
        .header {
            flex-direction: row;
            height: var(--header-height);
            padding: 0 16px;
            gap: 0;
        }
        
        .header-title {
            font-size: 1.2rem;
        }
        
        .user-profile span {
            display: none;
        }
        
        .modal-content {
            padding: 20px 12px;
            max-width: 100vw;
            border-radius: 0;
        }
        
        .form-input {
            padding: 12px 14px;
        }
        
        .slider-img {
            height: 180px;
        }
        
        /* Action buttons in a row for small screens */
        .table-actions {
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        .table-actions .btn {
            width: auto;
            padding: 6px 12px;
        }
        
        .action-text {
            display: none;
        }
    }

    /* Visual Icons for Better Understanding */
    .visual-icon {
        display: inline-block;
        width: 24px;
        height: 24px;
        margin-right: 8px;
        vertical-align: middle;
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
    }
    
    /* Simplified UI Elements */
    .simple-btn {
        border-radius: 50px;
        padding: 12px 24px;
        font-size: 1rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    
    .simple-btn i {
        font-size: 1.2rem;
    }
    
    /* High Contrast Mode for Better Visibility */
    @media (prefers-contrast: more) {
        :root {
            --primary-blue: #0038a8;
            --primary-red: #a80000;
            --dark-blue: #002366;
            --white: #ffffff;
            --light-gray: #f0f0f0;
            --gray: #555555;
            --dark-gray: #222222;
        }
        
        .status-badge {
            border: 2px solid currentColor;
        }
        
        .btn {
            border: 2px solid transparent;
        }
        
        .btn-outline {
            border: 2px solid var(--primary-blue);
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
                <a href="raw_materials.php" class="menu-item active" data-module="human-resources">
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
                <i class="fas fa-cubes" style="font-size: 1.5rem; color: var(--dark-blue);"></i>
                <h1 class="header-title">Raw Materials</h1>
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
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($_GET['success'] === 'delete' ? 'Material deleted successfully!' : 'Material added successfully!'); ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($_GET['error'] === 'delete' ? 'Error deleting material. Please try again.' : 'Error adding material. Please try again.'); ?></span>
                </div>
            <?php endif; ?>
            <div class="module-content active" id="raw-materials">
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
                            $locations_result->data_seek(0);
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
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button class="btn btn-outline simple-btn" type="button" id="cancelEditRawMaterialModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="delete-confirm-modal">
        <div class="delete-confirm-content">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--primary-red); margin-bottom: 1rem;"></i>
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this raw material? This action cannot be undone.</p>
            <div class="delete-confirm-buttons">
                <button class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button class="btn btn-outline" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (mobileMenuToggle && sidebar) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Make table responsive on mobile
            function makeTableResponsive() {
                if (window.innerWidth <= 768) {
                    const table = document.getElementById('rawMaterialTable');
                    if (table) {
                        const headers = [];
                        // Get headers
                        table.querySelectorAll('thead th').forEach(th => {
                            headers.push(th.textContent);
                        });
                        
                        // Apply data-labels to cells
                        table.querySelectorAll('tbody tr').forEach(tr => {
                            tr.querySelectorAll('td').forEach((td, i) => {
                                if (headers[i]) {
                                    td.setAttribute('data-label', headers[i]);
                                }
                            });
                        });
                    }
                }
            }
            
            // Run on load and resize
            makeTableResponsive();
            window.addEventListener('resize', makeTableResponsive);
            
            const logoutBtn = document.getElementById('logoutBtn');
            if(logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); 
                });
            }
            
            // Add Material Modal logic
            const openAddMaterialBtn = document.getElementById('openAddMaterialModal');
            const addMaterialModal = document.getElementById('addMaterialModal');
            const closeAddMaterialModal = document.getElementById('closeAddMaterialModal');
            const cancelAddMaterialModal = document.getElementById('cancelAddMaterialModal');
            
            if (openAddMaterialBtn && addMaterialModal) {
                openAddMaterialBtn.addEventListener('click', function() {
                    addMaterialModal.style.display = 'flex';
                });
            }
            
            if (closeAddMaterialModal && addMaterialModal) {
                closeAddMaterialModal.addEventListener('click', function() {
                    addMaterialModal.style.display = 'none';
                });
            }
            
            if (cancelAddMaterialModal && addMaterialModal) {
                cancelAddMaterialModal.addEventListener('click', function() {
                    addMaterialModal.style.display = 'none';
                });
            }
            
            if (addMaterialModal) {
                addMaterialModal.addEventListener('click', function(e) {
                    if (e.target === addMaterialModal) {
                        addMaterialModal.style.display = 'none';
                    }
                });
            }
            
            // AJAX for Add Material
            const addMaterialForm = document.getElementById('addMaterialForm');
            if (addMaterialForm) {
                addMaterialForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(addMaterialForm);
                    fetch('add_material.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.text())
                    .then(response => {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                showAlert('Material added successfully!', 'success');
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                showAlert('Error adding material. Please try again.', 'danger');
                            }
                        } catch {
                            window.location.reload();
                        }
                    })
                    .catch(() => {
                        showAlert('Error adding material. Please try again.', 'danger');
                    });
                });
            }
            
            function showAlert(message, type) {
                let alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-' + type;
                alertDiv.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> <span>${message}</span>`;
                document.querySelector('.table-section').prepend(alertDiv);
                setTimeout(() => alertDiv.remove(), 3000);
            }
            
            // Enhanced search with recent searches and nothing to show
            const searchInput = document.getElementById('rawMaterialSearchInput');
            const table = document.getElementById('rawMaterialTable');
            const recentSearchesDiv = document.getElementById('recentSearches');
            const maxRecent = 5;
            
            function getRecentSearches() {
                return JSON.parse(localStorage.getItem('recentRawMaterialSearches') || '[]');
            }
            
            function setRecentSearches(arr) {
                localStorage.setItem('recentRawMaterialSearches', JSON.stringify(arr.slice(0, maxRecent)));
            }
            
            function showRecentSearches() {
                const recent = getRecentSearches();
                if (recent.length === 0) {
                    recentSearchesDiv.style.display = 'none';
                    return;
                }
                recentSearchesDiv.innerHTML = '';
                recent.forEach(term => {
                    const div = document.createElement('div');
                    div.innerHTML = `<i class="fas fa-history" style="margin-right: 8px;"></i> ${term}`;
                    div.onclick = () => {
                        searchInput.value = term;
                        filterTable(term);
                        recentSearchesDiv.style.display = 'none';
                    };
                    recentSearchesDiv.appendChild(div);
                });
                recentSearchesDiv.style.display = 'block';
            }
            
            function filterTable(searchTerm) {
                const tableRows = table.querySelectorAll('tbody tr');
                let anyVisible = false;
                tableRows.forEach(row => {
                    if (row.classList.contains('nothing-to-show-row')) return;
                    const match = row.textContent.toLowerCase().includes(searchTerm.toLowerCase());
                    row.style.display = match ? '' : 'none';
                    if (match) anyVisible = true;
                });
                
                let nothingRow = table.querySelector('.nothing-to-show-row');
                if (!anyVisible) {
                    if (!nothingRow) {
                        nothingRow = document.createElement('tr');
                        nothingRow.className = 'nothing-to-show-row';
                        nothingRow.innerHTML = `<td colspan="7" style="text-align:center; color:#888; font-weight:600;">No raw materials found matching "${searchTerm}"</td>`;
                        table.querySelector('tbody').appendChild(nothingRow);
                    }
                } else if (nothingRow) {
                    nothingRow.remove();
                }
            }
            
            if (searchInput && table) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value;
                    filterTable(searchTerm);
                    if (searchTerm.trim()) {
                        let recent = getRecentSearches();
                        recent = recent.filter(term => term !== searchTerm);
                        recent.unshift(searchTerm);
                        setRecentSearches(recent);
                    }
                });
                
                searchInput.addEventListener('focus', showRecentSearches);
                searchInput.addEventListener('blur', () => setTimeout(() => recentSearchesDiv.style.display = 'none', 200));
            }
            
            // View Raw Material Modal logic
            const viewRawMaterialModal = document.getElementById('viewRawMaterialModal');
            const closeViewRawMaterialModal = document.getElementById('closeViewRawMaterialModal');
            const rawMaterialImageSlider = document.getElementById('rawMaterialImageSlider');
            const prevRawImage = document.getElementById('prevRawImage');
            const nextRawImage = document.getElementById('nextRawImage');

            function openViewRawMaterialModal(material) {
                document.getElementById('viewRawMaterialName').textContent = material.name;
                document.getElementById('viewRawMaterialCode').textContent = material.code_color;
                document.getElementById('viewRawMaterialStock').textContent = material.stock_quantity;
                document.getElementById('viewRawMaterialLocation').textContent = material.location;
                document.getElementById('viewRawMaterialStatus').innerHTML = material.status;
                
                // Images
                rawMaterialImageSlider.innerHTML = '';
                let currentImage = 0;
                const images = material.images.filter(img => img);
                
                if (images.length > 0) {
                    images.forEach((img, idx) => {
                        const imgTag = document.createElement('img');
                        imgTag.src = img;
                        imgTag.className = 'slider-img';
                        imgTag.style.display = idx === 0 ? 'block' : 'none';
                        imgTag.alt = material.name;
                        rawMaterialImageSlider.appendChild(imgTag);
                    });
                } else {
                    rawMaterialImageSlider.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--gray);"><i class="fas fa-image" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i><p>No images available</p></div>';
                }

                function showImage(idx) {
                    const imgs = rawMaterialImageSlider.querySelectorAll('img');
                    imgs.forEach((img, i) => {
                        img.style.display = i === idx ? 'block' : 'none';
                    });
                }

                prevRawImage.onclick = function() {
                    if (images.length <= 1) return;
                    currentImage = (currentImage - 1 + images.length) % images.length;
                    showImage(currentImage);
                };
                
                nextRawImage.onclick = function() {
                    if (images.length <= 1) return;
                    currentImage = (currentImage + 1) % images.length;
                    showImage(currentImage);
                };
                
                showImage(0);
                viewRawMaterialModal.style.display = 'flex';
            }
            
            if (closeViewRawMaterialModal && viewRawMaterialModal) {
                closeViewRawMaterialModal.addEventListener('click', function() {
                    viewRawMaterialModal.style.display = 'none';
                });
            }
            
            if (viewRawMaterialModal) {
                viewRawMaterialModal.addEventListener('click', function(e) {
                    if (e.target === viewRawMaterialModal) {
                        viewRawMaterialModal.style.display = 'none';
                    }
                });
            }
            
            document.querySelectorAll('.view-raw-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const material = {
                        name: row.cells[1].textContent,
                        code_color: row.cells[2].textContent,
                        stock_quantity: row.cells[3].textContent,
                        location: row.cells[4].textContent,
                        status: row.cells[5].innerHTML,
                        images: []
                    };
                    
                    if (row.dataset.image1) material.images.push('images/' + row.dataset.image1);
                    if (row.dataset.image2) material.images.push('images/' + row.dataset.image2);
                    if (row.dataset.image3) material.images.push('images/' + row.dataset.image3);
                    
                    openViewRawMaterialModal(material);
                });
            });

            // Edit Raw Material Modal logic
            const editRawMaterialModal = document.getElementById('editRawMaterialModal');
            const closeEditRawMaterialModal = document.getElementById('closeEditRawMaterialModal');
            const cancelEditRawMaterialModal = document.getElementById('cancelEditRawMaterialModal');
            
            function openEditRawMaterialModal(material) {
                document.getElementById('editRawMaterialId').value = material.id;
                document.getElementById('editRawMaterialName').value = material.name;
                document.getElementById('editRawMaterialCodeColor').value = material.code_color;
                document.getElementById('editRawMaterialStock').value = material.stock_quantity.replace(/,/g, '');
                document.getElementById('editRawMaterialLocation').value = material.location_id;
                editRawMaterialModal.style.display = 'flex';
            }
            
            if (closeEditRawMaterialModal && editRawMaterialModal) {
                closeEditRawMaterialModal.addEventListener('click', function() {
                    editRawMaterialModal.style.display = 'none';
                });
            }
            
            if (cancelEditRawMaterialModal && editRawMaterialModal) {
                cancelEditRawMaterialModal.addEventListener('click', function() {
                    editRawMaterialModal.style.display = 'none';
                });
            }
            
            if (editRawMaterialModal) {
                editRawMaterialModal.addEventListener('click', function(e) {
                    if (e.target === editRawMaterialModal) {
                        editRawMaterialModal.style.display = 'none';
                    }
                });
            }
            
            document.querySelectorAll('.edit-raw-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const material = {
                        id: this.getAttribute('data-raw-id'),
                        name: row.cells[1].textContent.trim(),
                        code_color: row.cells[2].textContent.trim(),
                        stock_quantity: row.cells[3].textContent.trim(),
                        location: row.cells[4].textContent.trim(),
                        location_id: row.getAttribute('data-location-id'),
                        images: []
                    };
                    openEditRawMaterialModal(material);
                });
            });
            
            // Delete functionality
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            let materialToDelete = null;
            
            // Open delete confirmation
            document.querySelectorAll('.delete-raw-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    materialToDelete = this.getAttribute('data-raw-id');
                    deleteConfirmModal.style.display = 'flex';
                });
            });
            
            // Close delete confirmation
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', function() {
                    deleteConfirmModal.style.display = 'none';
                    materialToDelete = null;
                });
            }
            
            // Confirm delete
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    if (!materialToDelete) {
                        deleteConfirmModal.style.display = 'none';
                        return;
                    }
                    
                    fetch('delete_material.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'id=' + encodeURIComponent(materialToDelete)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'raw_materials.php?success=delete';
                        } else {
                            window.location.href = 'raw_materials.php?error=delete';
                        }
                    })
                    .catch(() => {
                        window.location.href = 'raw_materials.php?error=delete';
                    });
                });
            }
            
            // Close modal when clicking outside
            deleteConfirmModal.addEventListener('click', function(e) {
                if (e.target === deleteConfirmModal) {
                    deleteConfirmModal.style.display = 'none';
                    materialToDelete = null;
                }
            });
            
            // Initialize sidebar dropdowns
            const inventoryDropdown = document.getElementById('inventoryDropdown');
            const inventoryDropdownMenu = document.getElementById('inventoryDropdownMenu');
            const supplyChainDropdown = document.getElementById('supplyChainDropdown');
            const supplyChainDropdownMenu = document.getElementById('supplyChainDropdownMenu');

            if (inventoryDropdown && inventoryDropdownMenu) {
                inventoryDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    inventoryDropdownMenu.classList.toggle('open');
                    inventoryDropdown.classList.toggle('open');
                });
                
                document.addEventListener('click', function(e) {
                    if (!inventoryDropdown.contains(e.target) && !inventoryDropdownMenu.contains(e.target)) {
                        inventoryDropdownMenu.classList.remove('open');
                        inventoryDropdown.classList.remove('open');
                    }
                });
            }

            if (supplyChainDropdown && supplyChainDropdownMenu) {
                supplyChainDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    supplyChainDropdownMenu.classList.toggle('open');
                    supplyChainDropdown.classList.toggle('open');
                });
                
                document.addEventListener('click', function(e) {
                    if (!supplyChainDropdown.contains(e.target) && !supplyChainDropdownMenu.contains(e.target)) {
                        supplyChainDropdownMenu.classList.remove('open');
                        supplyChainDropdown.classList.remove('open');
                    }
                });
            }
        });
    </script>
</body>
</html>
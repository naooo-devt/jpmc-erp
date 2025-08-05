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
                $name = $_POST['name'];
                $contact_person = $_POST['contact_person'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $rating = $_POST['rating'];
                $status = $_POST['status'];
                
                $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address, rating, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssss", $name, $contact_person, $email, $phone, $address, $rating, $status);
                
                if ($stmt->execute()) {
                    header('Location: suppliers.php?success=1');
                    exit;
                } else {
                    $error = "Error adding supplier: " . $conn->error;
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $contact_person = $_POST['contact_person'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $rating = $_POST['rating'];
                $status = $_POST['status'];
                
                $sql = "UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, address=?, rating=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssi", $name, $contact_person, $email, $phone, $address, $rating, $status, $id);
                
                if ($stmt->execute()) {
                    header('Location: suppliers.php?success=2');
                    exit;
                } else {
                    $error = "Error updating supplier: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $sql = "DELETE FROM suppliers WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    header('Location: suppliers.php?success=3');
                    exit;
                } else {
                    $error = "Error deleting supplier: " . $conn->error;
                }
                break;
        }
    }
}

// Fetch suppliers data
$suppliers_sql = "
    SELECT s.id, s.name, s.contact_person, s.email, s.phone, s.address, s.status, s.rating, s.created_at,
           COUNT(po.id) as total_orders,
           SUM(CASE WHEN po.status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
           SUM(po.total_amount) as total_spent
    FROM suppliers s
    LEFT JOIN purchase_orders po ON s.id = po.supplier_id
    GROUP BY s.id
    ORDER BY s.name";
$suppliers_result = $conn->query($suppliers_sql);

// Fetch All Finished Products
$products_sql = "
    SELECT pm.product_id, pm.name, pm.stock_quantity, pm.unit_cost, pm.status, 
           GROUP_CONCAT(rm.name SEPARATOR ', ') as materials
    FROM product_materials pm
    LEFT JOIN raw_materials rm ON pm.raw_material_id = rm.id
    GROUP BY pm.product_id
    ORDER BY pm.product_id";
$products_result = $conn->query($products_sql);

// Fetch all raw materials for the selection dropdown in the modal
$all_raw_materials_sql = "SELECT id, name, code_color FROM raw_materials ORDER BY name";
$all_raw_materials_result = $conn->query($all_raw_materials_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
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

    .menu-item.active {
        background: rgba(255, 255, 255, 0.15);
        border-right: 3px solid var(--white);
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

    .status-badge.active { background: #dcfce7; color: #166534; }
    .status-badge.inactive { background: #fef2f2; color: #991b1b; }

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
        margin: 5% auto;
        padding: 2rem;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
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
    }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
        <!--SideBar MENU -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="header-title">Suppliers Management</h1>
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
                        case '1': echo 'Supplier added successfully!'; break;
                        case '2': echo 'Supplier updated successfully!'; break;
                        case '3': echo 'Supplier deleted successfully!'; break;
                        case '4': echo 'Product updated successfully!'; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Tabs for Suppliers and Products -->
            <div class="finance-tabs">
                <button class="finance-tab active" data-tab="suppliers">
                    <i class="fas fa-users"></i>
                    <span>Suppliers</span>
                </button>
                <button class="finance-tab" data-tab="products">
                    <i class="fas fa-box"></i>
                    <span>Product Lists</span>
                </button>
            </div>

            <!-- Suppliers Tab -->
            <div class="module-content active" id="suppliers">
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-title">Suppliers Overview</div>
                        <div class="table-actions">
                            <button class="btn btn-primary" onclick="openModal('addSupplierModal')">
                                <i class="fas fa-plus"></i>
                                Add Supplier
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Supplier Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Rating</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($suppliers_result && $suppliers_result->num_rows > 0) {
                                    while ($row = $suppliers_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['contact_person']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                        echo "<td>";
                                        echo "<div class='rating'>";
                                        for ($i = 1; $i <= 5; $i++) {
                                            $star_class = $i <= $row['rating'] ? 'star' : 'star empty';
                                            echo "<i class='fas fa-star $star_class'></i>";
                                        }
                                        echo "</div>";
                                        echo "</td>";
                                        echo "<td>" . $row['total_orders'] . " (" . $row['completed_orders'] . " completed)</td>";
                                        echo "<td>₱" . number_format($row['total_spent'] ?? 0, 2) . "</td>";
                                        $status_class = $row['status'] === 'Active' ? 'active' : 'inactive';
                                        echo "<td><span class='status-badge $status_class'>" . htmlspecialchars($row['status']) . "</span></td>";
                                        echo "<td>";
                                        echo "<button class='btn btn-outline' onclick='editSupplier(" . $row['id'] . ")'><i class='fas fa-edit'></i></button> ";
                                        echo "<button class='btn btn-danger' onclick='deleteSupplier(" . $row['id'] . ")'><i class='fas fa-trash'></i></button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9' style='text-align:center;'>No suppliers found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Products Tab -->
            <div class="module-content" id="products">
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
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Product</h2>
                <button class="close" onclick="closeModal('addProductModal')">&times;</button>
            </div>
            <form method="POST" action="add_product.php">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Raw Material</label>
                        <select name="raw_material_id" class="form-input" required>
                            <option value="">Select Raw Material</option>
                            <?php
                            if ($all_raw_materials_result && $all_raw_materials_result->num_rows > 0) {
                                while ($row = $all_raw_materials_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Cost</label>
                        <input type="number" name="unit_cost" class="form-input" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input" required>
                            <option value="">Select Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addProductModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Product</h2>
                <button class="close" onclick="closeModal('editProductModal')">&times;</button>
            </div>
            <form method="POST" action="edit_product.php">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="edit_product_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Raw Material</label>
                        <select name="raw_material_id" id="edit_raw_material_id" class="form-input" required>
                            <option value="">Select Raw Material</option>
                            <?php
                            if ($all_raw_materials_result && $all_raw_materials_result->num_rows > 0) {
                                $all_raw_materials_result->data_seek(0);
                                while ($row = $all_raw_materials_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="edit_stock_quantity" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Cost</label>
                        <input type="number" name="unit_cost" id="edit_unit_cost" class="form-input" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-input" required>
                            <option value="">Select Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editProductModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div id="deleteProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Product</h2>
                <button class="close" onclick="closeModal('deleteProductModal')">&times;</button>
            </div>
            <form method="POST" action="delete_product.php">
                <input type="hidden" name="product_id" id="delete_product_id">
                <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('deleteProductModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div id="addSupplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Supplier</h2>
                <button class="close" onclick="closeModal('addSupplierModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-input" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-input" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="form-select" required>
                            <option value="">Select Rating</option>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addSupplierModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div id="editSupplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Supplier</h2>
                <button class="close" onclick="closeModal('editSupplierModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" name="name" id="edit_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="edit_contact_person" class="form-input" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_address" class="form-input" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="rating" id="edit_rating" class="form-select" required>
                            <option value="">Select Rating</option>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editSupplierModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteSupplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Supplier</h2>
                <button class="close" onclick="closeModal('deleteSupplierModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <p>Are you sure you want to delete this supplier? This action cannot be undone.</p>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('deleteSupplierModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editSupplier(id) {
            // In a real application, you would fetch the supplier data via AJAX
            // For now, we'll just open the modal
            openModal('editSupplierModal');
        }

        function deleteSupplier(id) {
            document.getElementById('delete_id').value = id;
            openModal('deleteSupplierModal');
        }

        function editProduct(id) {
            // In a real application, you would fetch the product data via AJAX
            // For now, we'll just open the modal
            openModal('editProductModal');
        }

        function deleteProduct(id) {
            document.getElementById('delete_product_id').value = id;
            openModal('deleteProductModal');
        }

        // AJAX for deleting product
        document.addEventListener('DOMContentLoaded', function() {
            const deleteProductModal = document.getElementById('deleteProductModal');
            if (deleteProductModal) {
                const form = deleteProductModal.querySelector('form');
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    fetch('delete_product.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            closeModal('deleteProductModal');
                            // Remove the row from the table
                            const id = form.querySelector('[name="product_id"]').value;
                            const row = document.querySelector('tr td button.btn-danger[onclick="deleteProduct(' + id + ')"]');
                            if (row) {
                                // Remove the parent row
                                row.closest('tr').remove();
                            } else {
                                // Fallback: reload the page if row not found
                                location.reload();
                            }
                        } else {
                            alert(data.message || 'Failed to delete product.');
                        }
                    })
                    .catch(() => {
                        alert('Failed to delete product.');
                    });
                });
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.finance-tab');
            const modules = document.querySelectorAll('.module-content');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.getAttribute('data-tab');
                    tabs.forEach(t => t.classList.remove('active'));
                    modules.forEach(m => m.classList.remove('active'));
                    tab.classList.add('active');
                    const targetModule = document.getElementById(target);
                    if (targetModule) {
                        targetModule.classList.add('active');
                    }
                });
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
        });
    </script>
</body>
</html>
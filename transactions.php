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

// --- FIX: Corrected the SQL query to use 'product_materials' table instead of 'products' ---
$all_transactions_sql = "
    SELECT t.transaction_date, rm.name as material_name, p.name as product_name, t.type, t.quantity, l.name as location_name, t.balance
    FROM transactions t
    LEFT JOIN raw_materials rm ON t.raw_material_id = rm.id
    LEFT JOIN product_materials p ON t.product_id = p.product_id
    LEFT JOIN locations l ON t.location_id = l.id
    ORDER BY t.transaction_date DESC";
$all_transactions_result = $conn->query($all_transactions_sql);


// For filters
$raw_materials_for_modals = $conn->query("SELECT id, name, code_color FROM raw_materials ORDER BY name");
if (!$raw_materials_for_modals) {
    echo "<!-- Error: " . $conn->error . " -->";
}

// Handle suppliers form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_supplier':
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
                    header('Location: transactions.php?success=1');
                    exit;
                } else {
                    $error = "Error adding supplier: " . $conn->error;
                }
                break;
                
            case 'edit_supplier':
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
                    header('Location: transactions.php?success=2');
                    exit;
                } else {
                    $error = "Error updating supplier: " . $conn->error;
                }
                break;
                
            case 'delete_supplier':
                $id = $_POST['id'];
                $sql = "DELETE FROM suppliers WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    header('Location: transactions.php?success=3');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="images/logo.png">
    <style>
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
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="company-logo">
                <img src="images/logo.png" alt="Company Logo" style="width: 60px; height: 60px; border-radius: 12px; object-fit: contain; display: block;">
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
                    <span>Supply Chain</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown-menu open" id="supplyChainDropdownMenu">
                    <a href="supply_chain.php" class="menu-item<?php if(basename($_SERVER['PHP_SELF']) == 'supply_chain.php') echo ' active'; ?>" data-module="manufacturing">
                        <i class="fas fa-industry"></i>
                        <span>Manufacturing</span>
                    </a>
                    <a href="transactions.php" class="menu-item<?php if(basename($_SERVER['PHP_SELF']) == 'transactions.php') echo ' active'; ?>" data-module="transactions">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </div>
                <a href="customer_service.php" class="menu-item<?php if(basename($_SERVER['PHP_SELF']) == 'customer_service.php') echo ' active'; ?>" data-module="customer-service">
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
                <h1 class="header-title">Transactions</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"> <?php echo ucfirst($role); ?> </span>
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

                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Tabs for Transactions and Suppliers -->
            <div class="finance-tabs">
                <button class="finance-tab active" data-tab="transactions">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Material Transactions</span>
                </button>
                <button class="finance-tab" data-tab="suppliers">
                    <i class="fas fa-users"></i>
                    <span>Suppliers</span>
                </button>
                <!-- Add these new tabs after Suppliers -->
                <button class="finance-tab" data-tab="purchase-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Purchase Orders</span>
                </button>
                <button class="finance-tab" data-tab="deliveries">
                    <i class="fas fa-truck"></i>
                    <span>Deliveries</span>
                </button>
                <button class="finance-tab" data-tab="analytics">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </button>
            </div>


            <!-- Transactions Tab -->
            <div class="module-content active" id="transactions">
                <div class="section-header">
                    <h2>Material Transactions</h2>
                    <div class="actions">
                        <button class="btn btn-primary" id="addTransactionBtn">
                            <i class="fas fa-plus"></i> New Transaction
                        </button>
                        <button class="btn btn-outline">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                    </div>
                </div>
                <div class="transaction-filters">
                    <div class="filter-group">
                        <label>Transaction Type:</label>
                        <div class="filter-buttons">
                            <button type="button" class="btn btn-outline filter-btn active" data-filter="all" data-type="transType">
                                <i class="fas fa-list"></i> All
                            </button>
                            <button type="button" class="btn btn-outline filter-btn" data-filter="in" data-type="transType">
                                <i class="fas fa-arrow-up"></i> Material In
                            </button>
                            <button type="button" class="btn btn-outline filter-btn" data-filter="out" data-type="transType">
                                <i class="fas fa-arrow-down"></i> Material Out
                            </button>
                        </div>
                        <input type="hidden" id="transTypeFilter" value="all">
                    </div>
                    <div class="filter-group">
                        <label for="transMaterialFilter">Material:</label>
                        <select id="transMaterialFilter">
                            <option value="all">All Materials</option>
                            <?php
                            if ($raw_materials_for_modals && $raw_materials_for_modals->num_rows > 0) {
                                $raw_materials_for_modals->data_seek(0);
                                while($row = $raw_materials_for_modals->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                }
                            } else {
                                echo "<!-- No materials found or query failed -->";
                                // Fallback: Add some test options
                                echo "<option value='1'>Test Material 1</option>";
                                echo "<option value='2'>Test Material 2</option>";
                                echo "<option value='3'>Test Material 3</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button class="btn btn-outline" id="clearTransactionFilters">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
                <div id="filterStatus" class="filter-status" style="display: none; margin-top: 10px; padding: 8px 12px; background-color: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 6px; color: #0369a1; font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i> <span id="filterStatusText"></span>
                </div>
                <div class="transactions-table table-section">
                    <div class="table-responsive">
                        <table id="transactionTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Material</th>
                                    <th>Product Used</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Location</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($all_transactions_result && $all_transactions_result->num_rows > 0) {
                                    $all_transactions_result->data_seek(0);
                                    while ($row = $all_transactions_result->fetch_assoc()) {
                                        $badge_class = strtolower($row['type'] ?? '') === 'out' ? 'out' : 'in';
                                        echo "<tr>";
                                        echo "<td>" . date('m/d/Y', strtotime($row['transaction_date'] ?? '')) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['material_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['product_name'] ?? 'N/A') . "</td>";
                                        echo "<td><span class='badge " . $badge_class . "'>" . htmlspecialchars($row['type'] ?? 'N/A') . "</span></td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['quantity'] ?? 0)) . " Bags</td>";
                                        echo "<td>" . htmlspecialchars($row['location_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['balance'] ?? 0)) . " Bags</td>";
                                        echo "<td>";
                                        echo "<button class='btn btn-outline' onclick='editTransaction(" . ($row['id'] ?? 0) . ")'><i class='fas fa-edit'></i></button> ";
                                        echo "<button class='btn btn-danger' onclick='deleteTransaction(" . ($row['id'] ?? 0) . ")'><i class='fas fa-trash'></i></button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' style='text-align:center;'>No transactions found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pagination">
                    <button class="btn btn-outline" disabled><i class="fas fa-chevron-left"></i> Previous</button>
                    <span>Page 1 of 3</span>
                    <button class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>

            <!-- Suppliers Tab -->
                        
            <div class="module-content" id="suppliers">
                 <!-- Stat Cards (from supply_chain.php Product List tab) -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Suppliers</div>
                            <div class="stat-subtitle">Active vendors</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $total_suppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'];
                        echo $total_suppliers;
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>2 new this month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Pending Orders</div>
                            <div class="stat-subtitle">Awaiting delivery</div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $pending_orders = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
                        echo $pending_orders;
                        ?>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-up"></i>
                        <span>5 overdue</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Spent</div>
                            <div class="stat-subtitle">This year</div>
                        </div>
                        <div class="stat-icon green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        ₱<?php
                        $total_spent = $conn->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
                        echo number_format($total_spent, 2);
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>15.3% vs last year</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Avg Delivery Time</div>
                            <div class="stat-subtitle">Days to deliver</div>
                        </div>
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $avg_delivery_time = $conn->query("
                            SELECT AVG(DATEDIFF(d.delivery_date, po.order_date)) as avg_days 
                            FROM deliveries d 
                            JOIN purchase_orders po ON d.purchase_order_id = po.id 
                            WHERE d.status = 'Delivered'")->fetch_assoc()['avg_days'] ?? 0;
                        echo round($avg_delivery_time, 1); ?> days
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-down"></i>
                        <span>2.1 days faster</span>
                    </div>
                </div>
            </div>
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
                                        echo "<td>" . htmlspecialchars($row['name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['contact_person'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
                                        echo "<td>";
                                        echo "<div class='rating'>";
                                        for ($i = 1; $i <= 5; $i++) {
                                            $star_class = $i <= ($row['rating'] ?? 0) ? 'star' : 'star empty';
                                            echo "<i class='fas fa-star $star_class'></i>";
                                        }
                                        echo "</div>";
                                        echo "</td>";
                                        echo "<td>" . ($row['total_orders'] ?? 0) . " (" . ($row['completed_orders'] ?? 0) . " completed)</td>";
                                        echo "<td>₱" . number_format($row['total_spent'] ?? 0, 2) . "</td>";
                                        $status_class = ($row['status'] ?? '') === 'Active' ? 'active' : 'inactive';
                                        echo "<td><span class='status-badge $status_class'>" . htmlspecialchars($row['status'] ?? 'N/A') . "</span></td>";
                                        echo "<td>";
                                        echo "<button class='btn btn-outline' onclick='editSupplier(" . ($row['id'] ?? 0) . ")'><i class='fas fa-edit'></i></button> ";
                                        echo "<button class='btn btn-danger' onclick='deleteSupplier(" . ($row['id'] ?? 0) . ")'><i class='fas fa-trash'></i></button>";
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

            <!-- Purchase Orders Tab -->
            <div class="module-content" id="purchase-orders">
                 <!-- Stat Cards (from supply_chain.php Product List tab) -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Suppliers</div>
                            <div class="stat-subtitle">Active vendors</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $total_suppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'];
                        echo $total_suppliers;
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>2 new this month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Pending Orders</div>
                            <div class="stat-subtitle">Awaiting delivery</div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $pending_orders = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
                        echo $pending_orders;
                        ?>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-up"></i>
                        <span>5 overdue</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Spent</div>
                            <div class="stat-subtitle">This year</div>
                        </div>
                        <div class="stat-icon green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        ₱<?php
                        $total_spent = $conn->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
                        echo number_format($total_spent, 2);
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>15.3% vs last year</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Avg Delivery Time</div>
                            <div class="stat-subtitle">Days to deliver</div>
                        </div>
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $avg_delivery_time = $conn->query("
                            SELECT AVG(DATEDIFF(d.delivery_date, po.order_date)) as avg_days 
                            FROM deliveries d 
                            JOIN purchase_orders po ON d.purchase_order_id = po.id 
                            WHERE d.status = 'Delivered'")->fetch_assoc()['avg_days'] ?? 0;
                        echo round($avg_delivery_time, 1); ?> days
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-down"></i>
                        <span>2.1 days faster</span>
                    </div>
                </div>
            </div>
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-title">Recent Purchase Orders</div>
                        <div class="table-actions">
                            <button class="btn btn-primary" onclick="openModal('addPurchaseOrderModal')">
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

                                if ($purchase_orders_result && $purchase_orders_result->num_rows > 0) {
                                    while ($row = $purchase_orders_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['supplier_name']) . "</td>";
                                        echo "<td>" . date('m/d/Y', strtotime($row['order_date'])) . "</td>";
                                        echo "<td>" . date('m/d/Y', strtotime($row['expected_delivery'])) . "</td>";
                                        echo "<td>" . $row['total_items'] . " items</td>";
                                        echo "<td>₱" . number_format($row['total_amount'], 2) . "</td>";
                                        $status_class = strtolower($row['status']);
                                        echo "<td><span class='status-badge $status_class'>" . htmlspecialchars($row['status']) . "</span></td>";
                                        echo "<td>";
                                        echo "<button class='btn btn-outline' onclick='viewOrder(" . $row['id'] . ")'><i class='fas fa-eye'></i></button> ";
                                        echo "<button class='btn btn-success' onclick='createDelivery(" . $row['id'] . ")'><i class='fas fa-truck'></i></button>";
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

            <!-- Deliveries Tab -->
            <div class="module-content" id="deliveries">
                 <!-- Stat Cards (from supply_chain.php Product List tab) -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Suppliers</div>
                            <div class="stat-subtitle">Active vendors</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $total_suppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'];
                        echo $total_suppliers;
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>2 new this month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Pending Orders</div>
                            <div class="stat-subtitle">Awaiting delivery</div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $pending_orders = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
                        echo $pending_orders;
                        ?>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-up"></i>
                        <span>5 overdue</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Spent</div>
                            <div class="stat-subtitle">This year</div>
                        </div>
                        <div class="stat-icon green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        ₱<?php
                        $total_spent = $conn->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
                        echo number_format($total_spent, 2);
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>15.3% vs last year</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Avg Delivery Time</div>
                            <div class="stat-subtitle">Days to deliver</div>
                        </div>
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $avg_delivery_time = $conn->query("
                            SELECT AVG(DATEDIFF(d.delivery_date, po.order_date)) as avg_days 
                            FROM deliveries d 
                            JOIN purchase_orders po ON d.purchase_order_id = po.id 
                            WHERE d.status = 'Delivered'")->fetch_assoc()['avg_days'] ?? 0;
                        echo round($avg_delivery_time, 1); ?> days
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-down"></i>
                        <span>2.1 days faster</span>
                    </div>
                </div>
            </div>
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-title">Recent Deliveries</div>
                        <div class="table-actions">
                            <button class="btn btn-primary" onclick="openModal('addDeliveryModal')">
                                <i class="fas fa-plus"></i>
                                New Delivery
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Delivery #</th>
                                    <th>PO Number</th>
                                    <th>Supplier</th>
                                    <th>Delivery Date</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $deliveries_sql = "
                                    SELECT d.id, d.delivery_number, d.delivery_date, d.status, d.notes,
                                           po.order_number as po_number, s.name as supplier_name
                                    FROM deliveries d
                                    LEFT JOIN purchase_orders po ON d.purchase_order_id = po.id
                                    LEFT JOIN suppliers s ON po.supplier_id = s.id
                                    ORDER BY d.delivery_date DESC
                                    LIMIT 10";
                                $deliveries_result = $conn->query($deliveries_sql);

                                if ($deliveries_result && $deliveries_result->num_rows > 0) {
                                    while ($row = $deliveries_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['delivery_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['po_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['supplier_name']) . "</td>";
                                        echo "<td>" . date('m/d/Y', strtotime($row['delivery_date'])) . "</td>";
                                        $status_class = strtolower($row['status']);
                                        echo "<td><span class='status-badge $status_class'>" . htmlspecialchars($row['status']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars($row['notes'] ?? 'N/A') . "</td>";
                                        echo "<td>";
                                        echo "<button class='btn btn-outline' onclick='viewDelivery(" . $row['id'] . ")'><i class='fas fa-eye'></i></button> ";
                                        echo "<button class='btn btn-success' onclick='receiveDelivery(" . $row['id'] . ")'><i class='fas fa-check'></i></button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' style='text-align:center;'>No deliveries found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div class="module-content" id="analytics">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Top Supplier</div>
                                <div class="stat-subtitle">By order volume</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-trophy"></i>
                            </div>
                        </div>
                        <div class="stat-value">ABC Polymers</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>45 orders this year</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">On-Time Delivery</div>
                                <div class="stat-subtitle">This month</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value">87.5%</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+5.2% from last month</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Cost Savings</div>
                                <div class="stat-subtitle">vs last year</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                        </div>
                        <div class="stat-value">₱125,000</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>12.3% reduction</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Quality Score</div>
                                <div class="stat-subtitle">Average rating</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="stat-value">4.2/5.0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+0.3 from last quarter</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- New Transaction Modal (direct child of body) -->
    <div id="addTransactionModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="cancelAddTransactionModal">&times;</span>
            <div class="form-section">
                <div class="form-header">
                    <h2>New Material Transaction</h2>
                </div>
                <form id="addTransactionForm" method="POST" action="add_transaction.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transDate">Date</label>
                            <input type="text" id="transDate" name="transaction_date" class="datepicker form-input" required placeholder="Select date...">
                        </div>
                        <div class="form-group">
                            <label>Transaction Type</label>
                            <div class="transaction-type-toggle">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="transactionTypeToggle">
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label-left">Material Out</span>
                                    <span class="toggle-label-right">Material In</span>
                                </label>
                            </div>
                            <input type="hidden" id="transType" name="type" value="OUT" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transMaterial">Material</label>
                            <select id="transMaterial" name="raw_material_id" class="form-input" required>
                                <option value="">Select Material</option>
                                <?php $materials = $conn->query("SELECT id, name FROM raw_materials ORDER BY name"); while ($row = $materials->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transProduct">Product Used</label>
                            <!-- --- FIX: Query 'product_materials' table and use 'product_id' for the value --- -->
                            <select id="transProduct" name="product_id" class="form-input">
                                <option value="">Select Product (if applicable)</option>
                                <?php $products = $conn->query("SELECT product_id, name FROM product_materials ORDER BY name"); while ($row = $products->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['product_id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transQuantity">Quantity</label>
                            <input type="number" id="transQuantity" name="quantity" class="form-input" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="transLocation">Location</label>
                            <select id="transLocation" name="location_id" class="form-input" required>
                                <option value="">Select Location</option>
                                <?php $locs = $conn->query("SELECT id, name FROM locations ORDER BY name"); while ($row = $locs->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transOperator">Operator</label>
                            <input type="text" id="transOperator" name="operator" class="form-input" required placeholder="Enter operator name...">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transNotes">Notes</label>
                            <textarea id="transNotes" name="notes" class="form-input" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" id="cancelAddTransactionBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div id="addSupplierModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('addSupplierModal')">&times;</span>
            <div class="form-section">
                <div class="form-header">
                    <h2>Add New Supplier</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_supplier">
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
                            <select name="rating" class="form-input" required>
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
                            <select name="status" class="form-input" required>
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
    </div>

    <!-- Edit Supplier Modal -->
    <div id="editSupplierModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editSupplierModal')">&times;</span>
            <div class="form-section">
                <div class="form-header">
                    <h2>Edit Supplier</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_supplier">
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
                            <select name="rating" id="edit_rating" class="form-input" required>
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
                            <select name="status" id="edit_status" class="form-input" required>
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
    </div>

    <!-- Delete Supplier Modal -->
    <div id="deleteSupplierModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deleteSupplierModal')">&times;</span>
            <div class="form-section">
                <div class="form-header">
                    <h2>Delete Supplier</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete this supplier? This action cannot be undone.</p>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('deleteSupplierModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.finance-tab');
            const modules = document.querySelectorAll('.module-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and modules
                    tabs.forEach(t => t.classList.remove('active'));
                    modules.forEach(m => m.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding module
                    tab.classList.add('active');
                    const targetModule = document.getElementById(target);
                    if (targetModule) {
                        targetModule.classList.add('active');
                    }
                });
            });
        });

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
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



        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>

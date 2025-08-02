<?php
// index.php (Dashboard only, with its own sidebar, header, and dashboard modals)

session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

// Fetch user details from session for display.
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

// --- DATA FETCHING FOR THE DASHBOARD ---
$materials_in_query = "SELECT SUM(quantity) as total_in FROM transactions WHERE type = 'IN' AND MONTH(transaction_date) = MONTH(CURRENT_DATE())";
$materials_in_result = $conn->query($materials_in_query);
$materials_in = $materials_in_result->fetch_assoc()['total_in'] ?? 0;

$materials_out_query = "SELECT SUM(quantity) as total_out FROM transactions WHERE type = 'OUT' AND MONTH(transaction_date) = MONTH(CURRENT_DATE())";
$materials_out_result = $conn->query($materials_out_query);
$materials_out = $materials_out_result->fetch_assoc()['total_out'] ?? 0;

$low_stock_query = "SELECT COUNT(*) as low_stock_count FROM raw_materials WHERE status = 'Critical' OR status = 'Low'";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_count = $low_stock_result->fetch_assoc()['low_stock_count'] ?? 0;

// --- Fetch Critical Materials ---
$critical_materials_sql = "
    SELECT rm.name, rm.code_color, rm.stock_quantity, l.name as location_name, rm.status
    FROM raw_materials rm
    LEFT JOIN locations l ON rm.location_id = l.id
    WHERE rm.status = 'Critical'
    ORDER BY rm.stock_quantity ASC";
$critical_materials_result = $conn->query($critical_materials_sql);


$recent_transactions_sql = "
    SELECT t.transaction_date, rm.name as material_name, t.type, t.quantity, l.name as location_name, t.balance
    FROM transactions t
    JOIN raw_materials rm ON t.raw_material_id = rm.id
    JOIN locations l ON t.location_id = l.id
    ORDER BY t.transaction_date DESC
    LIMIT 5";
$transactions_result = $conn->query($recent_transactions_sql);

// Calculate financial data for the dashboard
$total_revenue = 0;
$total_expenses = 0;
$net_profit = 0;

// Get total income
$income_query = "SELECT SUM(amount) as total FROM income";
$income_result = $conn->query($income_query);
if ($income_result && $income_result->num_rows > 0) {
    $total_revenue = $income_result->fetch_assoc()['total'] ?? 0;
}

// Get total expenses
$expenses_query = "SELECT SUM(amount) as total FROM expenses";
$expenses_result = $conn->query($expenses_query);
if ($expenses_result && $expenses_result->num_rows > 0) {
    $total_expenses = $expenses_result->fetch_assoc()['total'] ?? 0;
}

// Calculate net profit
$net_profit = $total_revenue - $total_expenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>James Polymer Manufacturing Corporation - Production & Inventory ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="images/logo.png">
</head>
<body>
    8:57 AM
Moonlight🌕✨
  <!--SideBar MENU -->
    <?php include 'sidebar.php'; ?>
    <!-- Main Content Area (Dashboard only) -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="header-title">Dashboard</h1>
            </div>
           <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </div>
        <div class="content">
            <!-- Dashboard Module -->
            <div class="module-content active" id="dashboard">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Materials In</div>
                                <div class="stat-subtitle">This month</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-arrow-circle-down"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($materials_in); ?> Bags</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>12.7% from last month</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Materials Out</div>
                                <div class="stat-subtitle">This month</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-arrow-circle-up"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($materials_out); ?> Bags</div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-down"></i>
                            <span>3.5% from last month</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Low Stock Items</div>
                                <div class="stat-subtitle">Needs attention</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $low_stock_count; ?></div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-up"></i>
                            <span>3 new alerts today</span>
                        </div>
                    </div>
                </div>
                <div class="charts-section">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">Inventory Movement</div>
                            <div class="chart-controls">
                                <button class="chart-btn active">Weekly</button>
                                <button class="chart-btn">Monthly</button>
                                <button class="chart-btn">Quarterly</button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <canvas id="inventoryMovementChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">Material Stock Levels</div>
                            <div class="chart-controls">
                                <button class="chart-btn active">All</button>
                                <button class="chart-btn">Critical</button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <canvas id="stockLevelsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Critical Materials Table -->
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-title">Critical Materials</div>
                        <div class="table-actions">
                             <a href="raw_materials.php" class="btn btn-primary">Manage Materials</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Material Name</th>
                                    <th>Code / Color</th>
                                    <th>Stock Quantity</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($critical_materials_result && $critical_materials_result->num_rows > 0) {
                                    while ($row = $critical_materials_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['code_color']) . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['stock_quantity'], 2)) . " kg</td>";
                                        echo "<td>" . htmlspecialchars($row['location_name'] ?? 'N/A') . "</td>";
                                        echo "<td><span class='status-badge critical'>" . htmlspecialchars($row['status']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='text-align:center;'>No materials with critical stock level.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Transactions Table -->
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-title">Recent Transactions</div>
                        <div class="table-actions">
                            <a href="transactions.php" class="btn btn-primary">View All</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Material</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Location</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($transactions_result && $transactions_result->num_rows > 0) {
                                    while ($row = $transactions_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . date('m/d/Y', strtotime($row['transaction_date'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['material_name']) . "</td>";
                                        $badge_class = strtolower($row['type']) === 'out' ? 'out' : 'in';
                                        echo "<td><span class='badge " . $badge_class . "'>" . htmlspecialchars($row['type']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['quantity'])) . " Bags</td>";
                                        echo "<td>" . htmlspecialchars($row['location_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['balance'])) . " Bag</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align:center;'>No recent transactions found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard-specific modals (if any) go here -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

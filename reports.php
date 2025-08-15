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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="images/logo.png">
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
                <div class="menu-item menu-dropdown" id="supplyChainDropdown">
                    <i class="fas fa-link"></i>
                    <span>Inventory</span>
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
                <a href="reports.php" class="menu-item active" data-module="reports">
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
                <h1 class="header-title">Reports</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"> <?php echo ucfirst($role); ?> </span>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="module-content active" id="reports">
                 <!-- This section is kept from the original HTML. It can be made dynamic later. -->
                 <div class="section-header">
                    <h2 style="margin-bottom: 0;">Inventory Report</h2>
                </div>
                <div id="inventoryReportContent">
                    <div class="actions" style="margin-top: 1rem;">
                        <button class="btn btn-primary" id="generateReportBtnPDF">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </button>
                        <button class="btn btn-outline" id="generateReportBtnExcel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                    <div class="report-filters">
                        <div class="filter-group">
                            <label for="reportType">Report Type:</label>
                            <select id="reportType">
                                <option value="inventory">Inventory Summary</option>
                                <option value="transactions">Transaction Log</option>
                                <option value="monthly">Monthly Summary</option>
                                <option value="lowstock">Low Stock Report</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="reportPeriod">Period:</label>
                            <select id="reportPeriod">
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month" selected>This Month</option>
                                <option value="quarter">This Quarter</option>
                                <option value="year">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="filter-group custom-range" style="display: none;">
                            <label for="reportDateFrom">From:</label>
                            <input type="date" id="reportDateFrom" class="datepicker">
                        </div>
                        <div class="filter-group custom-range" style="display: none;">
                            <label for="reportDateTo">To:</label>
                            <input type="date" id="reportDateTo" class="datepicker">
                        </div>
                        <button class="btn btn-primary" id="generateReportBtn">
                            <i class="fas fa-filter"></i> Generate Report
                        </button>
                    </div>
                    <div class="report-results" id="reportResults">
                        <!-- Inventory Summary (default) -->
                        <div id="inventorySummarySection">
                            <h3>Monthly Inventory Report - May 2025</h3>
                            <div class="report-summary">
                                <div class="summary-item">
                                    <h4>Total Inventory</h4>
                                    <p>217 Bags</p>
                                </div>
                                <div class="summary-item">
                                    <h4>Materials In</h4>
                                    <p>0 Bags</p>
                                </div>
                                <div class="summary-item">
                                    <h4>Materials Out</h4>
                                    <p>45 Bags</p>
                                </div>
                                <div class="summary-item">
                                    <h4>Low Stock Items</h4>
                                    <p>5 Items</p>
                                </div>
                            </div>
                            <div class="report-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>In</th>
                                            <th>Out</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>GAS KNOB (10 Bags)</td>
                                            <td>0</td>
                                            <td>9 Bags</td>
                                            <td>1 Bag</td>
                                        </tr>
                                        <tr>
                                            <td>PLASTIC CORE (75 Bags)</td>
                                            <td>0</td>
                                            <td>5 Bags</td>
                                            <td>70 Bags</td>
                                        </tr>
                                        <tr>
                                            <td>SWITCH KNOB (7 Bags)</td>
                                            <td>0</td>
                                            <td>6 Bags</td>
                                            <td>1 Bag</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="report-chart">
                                <canvas id="reportChart"></canvas>
                            </div>
                        </div>
                        <!-- Transaction Log Section -->
                        <div id="transactionLogSection" style="display:none;">
                            <h3>Transaction Log</h3>
                            <div class="report-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Transaction ID</th>
                                            <th>Type</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>User</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $transactions = [];
                                        $result_tx = $conn->query("SELECT date, transaction_id, type, product, quantity, user, remarks FROM transactions ORDER BY date DESC LIMIT 50");
                                        if ($result_tx && $result_tx->num_rows > 0) {
                                            while ($row = $result_tx->fetch_assoc()) {
                                                $transactions[] = $row;
                                            }
                                        }
                                        ?>
                                        <?php if (!empty($transactions)): ?>
                                            <?php foreach ($transactions as $tx): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($tx['date']))); ?></td>
                                                <td><?php echo htmlspecialchars($tx['transaction_id']); ?></td>
                                                <td><?php echo htmlspecialchars($tx['type']); ?></td>
                                                <td><?php echo htmlspecialchars($tx['product']); ?></td>
                                                <td><?php echo htmlspecialchars($tx['quantity']); ?></td>
                                                <td><?php echo htmlspecialchars($tx['user']); ?></td>
                                                <td><?php echo htmlspecialchars($tx['remarks']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="7" style="text-align:center;">No transactions found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Monthly Summary Section -->
                        <div id="monthlySummarySection" style="display:none;">
                            <h3>Monthly Summary</h3>
                            <div class="report-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Total In</th>
                                            <th>Total Out</th>
                                            <th>Net Change</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $monthly = [];
                                        $result_month = $conn->query("
                                            SELECT DATE_FORMAT(date, '%Y-%m') as month,
                                                SUM(CASE WHEN type='IN' THEN quantity ELSE 0 END) as total_in,
                                                SUM(CASE WHEN type='OUT' THEN quantity ELSE 0 END) as total_out
                                            FROM transactions
                                            GROUP BY month
                                            ORDER BY month DESC
                                            LIMIT 12
                                        ");
                                        if ($result_month && $result_month->num_rows > 0) {
                                            while ($row = $result_month->fetch_assoc()) {
                                                $row['net'] = $row['total_in'] - $row['total_out'];
                                                $monthly[] = $row;
                                            }
                                        }
                                        ?>
                                        <?php if (!empty($monthly)): ?>
                                            <?php foreach ($monthly as $m): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($m['month']); ?></td>
                                                <td><?php echo htmlspecialchars($m['total_in']); ?></td>
                                                <td><?php echo htmlspecialchars($m['total_out']); ?></td>
                                                <td><?php echo htmlspecialchars($m['net']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" style="text-align:center;">No monthly summary data found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Low Stock Report Section -->
                        <div id="lowStockSection" style="display:none;">
                            <h3>Low Stock Report</h3>
                            <div class="report-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Stock</th>
                                            <th>Minimum Required</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $low_stock = [];
                                        $result_low = $conn->query("SELECT product_name, stock, status FROM finished_goods WHERE stock < 10 ORDER BY stock ASC");
                                        if ($result_low && $result_low->num_rows > 0) {
                                            while ($row = $result_low->fetch_assoc()) {
                                                $low_stock[] = $row;
                                            }
                                        }
                                        ?>
                                        <?php if (!empty($low_stock)): ?>
                                            <?php foreach ($low_stock as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['stock']); ?></td>
                                                <td>10</td>
                                                <td>
                                                    <span style="color:#ef4444;font-weight:600;">Low</span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" style="text-align:center;">No low stock items found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-header" style="margin-top:2rem;">
                    <h2 style="margin-bottom: 0;">History Report</h2>
                </div>
                <div id="historyReportContent">
                    <div class="actions" style="margin-top: 1rem;">
                        <button class="btn btn-primary" id="generateHistoryReportBtnPDF">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </button>
                        <button class="btn btn-outline" id="generateHistoryReportBtnExcel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                    <div class="report-filters">
                        <div class="filter-group">
                            <label for="historyReportType">Report Type:</label>
                            <select id="historyReportType">
                                <option value="finances">Finances</option>
                                <option value="hr">Human Resources</option>
                                <option value="inventory">Inventory</option>
                                <option value="feedback">Customer Feedback</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="historyReportPeriod">Period:</label>
                            <select id="historyReportPeriod">
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month" selected>This Month</option>
                                <option value="quarter">This Quarter</option>
                                <option value="year">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="filter-group custom-range" style="display: none;">
                            <label for="historyReportDateFrom">From:</label>
                            <input type="date" id="historyReportDateFrom" class="datepicker">
                        </div>
                        <div class="filter-group custom-range" style="display: none;">
                            <label for="historyReportDateTo">To:</label>
                            <input type="date" id="historyReportDateTo" class="datepicker">
                        </div>
                        <button class="btn btn-primary" id="generateHistoryReportBtn">
                            <i class="fas fa-filter"></i> Generate Report
                        </button>
                    </div>
                    <div class="report-results" id="historyReportResults">
                        <!-- History report content will be dynamically loaded here -->
                        <div id="historyFinanceSection" style="display:none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin: 0;">Finance Report</h3>
            <div class="account-filters" style="display: flex; gap: 1rem; align-items: center;">
                <div style="display: flex; flex-direction: column; align-items: flex-start;">
                    <label style="color: #374151; font-size: 0.875rem; margin-bottom: 0.25rem; font-weight: 500;">Account Type:</label>
                    <select id="accountTypeFilter" style="background: white; border: 1px solid #d1d5db; border-radius: 6px; padding: 0.5rem; font-size: 0.875rem; color: #374151; min-width: 120px; cursor: pointer;">
                        <option value="all">All Accounts</option>
                        <option value="expenses">Expenses</option>
                        <option value="income">Income</option>
                        <option value="budget">Budget</option>
                    </select>
                </div>
                <button onclick="testFilter()" style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">Test Filter</button>
                <button onclick="resetAllFilters()" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">Reset All</button>
            </div>
        </div>
        
        <?php
        // Collect all finance data from different tables
        $all_finance_data = [];
        
        // Debug: Check if we can connect to database
        if (!$conn) {
            echo "<!-- Database connection failed -->";
        } else {
            echo "<!-- Database connected successfully -->";
        }
        
        // Get expenses data
        $expenses_query = "SELECT date, 'Expense' as transaction_type, amount, category as type, 'Expenses' as account, amount as expenses, 0 as income, 0 as budgeting, description as remarks FROM expenses ORDER BY date DESC LIMIT 20";
        $expenses_result = $conn->query($expenses_query);
        if ($expenses_result && $expenses_result->num_rows > 0) {
            echo "<!-- Found " . $expenses_result->num_rows . " expenses records -->";
            while ($row = $expenses_result->fetch_assoc()) {
                $all_finance_data[] = $row;
            }
        } else {
            echo "<!-- No expenses data found. Error: " . ($conn->error ?: 'No rows') . " -->";
        }
        
        // Get income data
        $income_query = "SELECT date, 'Income' as transaction_type, amount, source as type, 'Income' as account, 0 as expenses, amount as income, 0 as budgeting, description as remarks FROM income ORDER BY date DESC LIMIT 20";
        $income_result = $conn->query($income_query);
        if ($income_result && $income_result->num_rows > 0) {
            echo "<!-- Found " . $income_result->num_rows . " income records -->";
            while ($row = $income_result->fetch_assoc()) {
                $all_finance_data[] = $row;
            }
        } else {
            echo "<!-- No income data found. Error: " . ($conn->error ?: 'No rows') . " -->";
        }
        
        // Get budget data
        $budget_query = "SELECT created_at as date, 'Budget' as transaction_type, allocated as amount, category as type, 'Budget' as account, spent as expenses, allocated as income, allocated as budgeting, CONCAT('Allocated: $', allocated, ' | Spent: $', spent, ' | Remaining: $', remaining) as remarks FROM budget ORDER BY created_at DESC LIMIT 20";
        $budget_result = $conn->query($budget_query);
        if ($budget_result && $budget_result->num_rows > 0) {
            echo "<!-- Found " . $budget_result->num_rows . " budget records -->";
            while ($row = $budget_result->fetch_assoc()) {
                $all_finance_data[] = $row;
            }
        } else {
            echo "<!-- No budget data found. Error: " . ($conn->error ?: 'No rows') . " -->";
        }
        
        echo "<!-- Total finance records collected: " . count($all_finance_data) . " -->";
        
        // Sort all data by date (newest first)
        usort(
            $all_finance_data,
            function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            }
        );
        
        // Limit to 50 total records
        $all_finance_data = array_slice($all_finance_data, 0, 50);
        ?>
        
        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Account</th>
                        <th>Expenses</th>
                        <th>Income</th>
                        <th>Budgeting</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Collect all finance data from different tables
                    $all_finance_data = [];
                    
                    // Debug: Check if we can connect to database
                    if (!$conn) {
                        echo "<!-- Database connection failed -->";
                    } else {
                        echo "<!-- Database connected successfully -->";
                    }
                    
                    // Get expenses data
                    $expenses_query = "SELECT date, 'Expense' as transaction_type, amount, category as type, 'Expenses' as account, amount as expenses, 0 as income, 0 as budgeting, description as remarks FROM expenses ORDER BY date DESC LIMIT 20";
                    $expenses_result = $conn->query($expenses_query);
                    if ($expenses_result && $expenses_result->num_rows > 0) {
                        echo "<!-- Found " . $expenses_result->num_rows . " expenses records -->";
                        while ($row = $expenses_result->fetch_assoc()) {
                            $all_finance_data[] = $row;
                        }
                    } else {
                        echo "<!-- No expenses data found. Error: " . ($conn->error ?: 'No rows') . " -->";
                    }
                    
                    // Get income data
                    $income_query = "SELECT date, 'Income' as transaction_type, amount, source as type, 'Income' as account, 0 as expenses, amount as income, 0 as budgeting, description as remarks FROM income ORDER BY date DESC LIMIT 20";
                    $income_result = $conn->query($income_query);
                    if ($income_result && $income_result->num_rows > 0) {
                        echo "<!-- Found " . $income_result->num_rows . " income records -->";
                        while ($row = $income_result->fetch_assoc()) {
                            $all_finance_data[] = $row;
                        }
                    } else {
                        echo "<!-- No income data found. Error: " . ($conn->error ?: 'No rows') . " -->";
                    }
                    
                    // Get budget data
                    $budget_query = "SELECT created_at as date, 'Budget' as transaction_type, allocated as amount, category as type, 'Budget' as account, spent as expenses, allocated as income, allocated as budgeting, CONCAT('Allocated: $', allocated, ' | Spent: $', spent, ' | Remaining: $', remaining) as remarks FROM budget ORDER BY created_at DESC LIMIT 20";
                    $budget_result = $conn->query($budget_query);
                    if ($budget_result && $budget_result->num_rows > 0) {
                        echo "<!-- Found " . $budget_result->num_rows . " budget records -->";
                        while ($row = $budget_result->fetch_assoc()) {
                            $all_finance_data[] = $row;
                        }
                    } else {
                        echo "<!-- No budget data found. Error: " . ($conn->error ?: 'No rows') . " -->";
                    }
                    
                    echo "<!-- Total finance records collected: " . count($all_finance_data) . " -->";
                    
                    // Sort all data by date (newest first)
                    usort(
                        $all_finance_data,
                        function ($a, $b) {
                            return strtotime($b['date']) - strtotime($a['date']);
                        }
                    );
                    
                    // Limit to 50 total records
                    $all_finance_data = array_slice($all_finance_data, 0, 50);
                    ?>
                    <?php if (!empty($all_finance_data)): ?>
                        <?php foreach ($all_finance_data as $fin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fin['date']); ?></td>
                            <td><?php echo htmlspecialchars($fin['transaction_type']); ?></td>
                            <td><?php echo htmlspecialchars($fin['amount']); ?></td>
                            <td><?php echo htmlspecialchars($fin['type']); ?></td>
                            <td><?php echo htmlspecialchars($fin['account']); ?></td>
                            <td><?php echo htmlspecialchars($fin['expenses']); ?></td>
                            <td><?php echo htmlspecialchars($fin['income']); ?></td>
                            <td><?php echo htmlspecialchars($fin['budgeting']); ?></td>
                            <td><?php echo htmlspecialchars($fin['remarks']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center;">No finance records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
                    <div id="historyHRSection" style="display:none;">
                    <h3>Human Resources Report</h3>
        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Last Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hrs = [];
                    $result_hrs = $conn->query("SELECT name, position, department, status, last_action FROM hr_history ORDER BY last_action DESC LIMIT 50");
                    if ($result_hrs && $result_hrs->num_rows > 0) {
                        while ($row = $result_hrs->fetch_assoc()) {
                            $hrs[] = $row;
                        }
                    }
                    ?>
                    <?php if (!empty($hrs)): ?>
                        <?php foreach ($hrs as $hr): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($hr['name']); ?></td>
                            <td><?php echo htmlspecialchars($hr['position']); ?></td>
                            <td><?php echo htmlspecialchars($hr['department']); ?></td>
                            <td><?php echo htmlspecialchars($hr['last_action']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No Human Resources records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="historyInventorySection" style="display:none;">
        <h3>Inventory Report</h3>
        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Stock</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $inventory = [];
                    $result_inv = $conn->query("SELECT product_name, stock, stock_in, stock_out, status FROM inventory_history ORDER BY product_name ASC LIMIT 50");
                    if ($result_inv && $result_inv->num_rows > 0) {
                        while ($row = $result_inv->fetch_assoc()) {
                            $inventory[] = $row;
                        }
                    }
                    ?>
                    <?php if (!empty($inventory)): ?>
                        <?php foreach ($inventory as $inv): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($inv['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($inv['stock']); ?></td>
                            <td><?php echo htmlspecialchars($inv['stock_in']); ?></td>
                            <td><?php echo htmlspecialchars($inv['stock_out']); ?></td>
                            <td><?php echo htmlspecialchars($inv['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No inventory records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="historyFeedbackSection" style="display:none;">
        <h3>Customer Feedback History</h3>
        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Feedback</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $feedback_history = [];
                    $result_fb_hist = $conn->query("SELECT date, customer_name, email, feedback, rating, status, action FROM customer_feedback_history ORDER BY date DESC LIMIT 50");
                    if ($result_fb_hist && $result_fb_hist->num_rows > 0) {
                        while ($row = $result_fb_hist->fetch_assoc()) {
                            $feedback_history[] = $row;
                        }
                    }
                    ?>
                    <?php if (!empty($feedback_history)): ?>
                        <?php foreach ($feedback_history as $fh): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fh['date']); ?></td>
                            <td><?php echo htmlspecialchars($fh['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($fh['email']); ?></td>
                            <td><?php echo htmlspecialchars($fh['feedback']); ?></td>
                            <td><?php echo htmlspecialchars($fh['rating']); ?></td>
                            <td><?php echo htmlspecialchars($fh['status']); ?></td>
                            <td><?php echo htmlspecialchars($fh['action']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;">No feedback history records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Reports-specific modals go here -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.getElementById('logoutBtn');
            if(logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); 
                });
            }

            // Dropdown functionality
            const supplyChainDropdown = document.getElementById('supplyChainDropdown');
            const supplyChainDropdownMenu = document.getElementById('supplyChainDropdownMenu');
            
            if (supplyChainDropdown) {
                supplyChainDropdown.addEventListener('click', function() {
                    supplyChainDropdownMenu.classList.toggle('active');
                });
            }

            // Show/hide report sections based on report type
            const reportType = document.getElementById('reportType');
            const inventorySection = document.getElementById('inventorySummarySection');
            const transactionSection = document.getElementById('transactionLogSection');
            const monthlySummarySection = document.getElementById('monthlySummarySection');
            const lowStockSection = document.getElementById('lowStockSection');

            function updateReportSection() {
                inventorySection.style.display = 'none';
                transactionSection.style.display = 'none';
                monthlySummarySection.style.display = 'none';
                lowStockSection.style.display = 'none';
                if (reportType.value === 'transactions') {
                    transactionSection.style.display = '';
                } else if (reportType.value === 'monthly') {
                    monthlySummarySection.style.display = '';
                } else if (reportType.value === 'lowstock') {
                    lowStockSection.style.display = '';
                } else {
                    inventorySection.style.display = '';
                }
            }
            if (reportType) {
                reportType.addEventListener('change', updateReportSection);
                updateReportSection();
            }

            // Custom range functionality for inventory reports
            const reportPeriod = document.getElementById('reportPeriod');
            const customRangeGroups = document.querySelectorAll('#inventoryReportContent .custom-range');
            
            // Custom range functionality for history reports
            const historyReportPeriod = document.getElementById('historyReportPeriod');
            const historyCustomRangeGroups = document.querySelectorAll('#historyReportContent .custom-range');
            
            function toggleCustomRange() {
                if (reportPeriod.value === 'custom') {
                    customRangeGroups.forEach(group => group.style.display = 'flex');
                } else {
                    customRangeGroups.forEach(group => group.style.display = 'none');
                }
            }
            
            if (reportPeriod) {
                reportPeriod.addEventListener('change', toggleCustomRange);
                toggleCustomRange();
            }

            // Show/hide history report sections based on dropdown
            const historyType = document.getElementById('historyReportType');
            const historyFinance = document.getElementById('historyFinanceSection');
            const historyHR = document.getElementById('historyHRSection');
            const historyInventory = document.getElementById('historyInventorySection');
            const historyFeedback = document.getElementById('historyFeedbackSection');

            function updateHistorySection() {
                historyFinance.style.display = 'none';
                historyHR.style.display = 'none';
                historyInventory.style.display = 'none';
                historyFeedback.style.display = 'none';
                if (historyType.value === 'finances') {
                    historyFinance.style.display = '';
                } else if (historyType.value === 'hr') {
                    historyHR.style.display = '';
                } else if (historyType.value === 'inventory') {
                    historyInventory.style.display = '';
                } else if (historyType.value === 'feedback') {
                    historyFeedback.style.display = '';
                }
            }
            if (historyType) {
                historyType.addEventListener('change', updateHistorySection);
                updateHistorySection();
            }

            // Function to update filter button appearance
            function updateFilterButtonAppearance(accountType) {
                const filterSelect = document.getElementById('accountTypeFilter');
                if (filterSelect) {
                    if (accountType === 'all') {
                        filterSelect.style.borderColor = '#d1d5db';
                        filterSelect.style.backgroundColor = 'white';
                    } else {
                        filterSelect.style.borderColor = '#2563eb';
                        filterSelect.style.backgroundColor = '#f0f9ff';
                    }
                }
            }

            // Add change event listener to the period dropdown
            if (historyReportPeriod) {
                historyReportPeriod.addEventListener('change', function() {
                    const period = this.value;
                    
                    // If custom range is selected, show the custom date inputs
                    if (period === 'custom') {
                    historyCustomRangeGroups.forEach(group => group.style.display = 'flex');
                        // Don't filter immediately for custom range - wait for date selection
                } else {
                    historyCustomRangeGroups.forEach(group => group.style.display = 'none');
                        // Apply period filter and then account type filter
                        filterFinanceDataByPeriod(period);
                        // Apply account type filter to the period-filtered results
                        const accountType = accountTypeFilter.value;
                        if (accountType !== 'all') {
                            filterFinanceData(accountType);
                        }
                    }
                });
            }

            // Custom date range filtering functionality
            const historyReportDateFrom = document.getElementById('historyReportDateFrom');
            const historyReportDateTo = document.getElementById('historyReportDateTo');

            function filterFinanceDataByCustomDate() {
                const fromDate = historyReportDateFrom.value;
                const toDate = historyReportDateTo.value;
                
                if (!fromDate || !toDate) return;
                
                const tableRows = document.querySelectorAll('#historyFinanceSection tbody tr');
                const startDate = new Date(fromDate);
                const endDate = new Date(toDate);
                
                // Set time to end of day for end date to include the full day
                endDate.setHours(23, 59, 59, 999);
                
                tableRows.forEach(row => {
                    const dateCell = row.cells[0].textContent.trim();
                    const rowDate = new Date(dateCell);
                    
                    if (isNaN(rowDate.getTime())) {
                        row.style.display = 'none';
                        return;
                    }
                    
                    // Show rows within the custom date range
                    const shouldShow = rowDate >= startDate && rowDate <= endDate;
                    row.style.display = shouldShow ? '' : 'none';
                });
                
                // After custom date filtering, apply account type filter if needed
                const accountType = accountTypeFilter.value;
                if (accountType !== 'all') {
                    filterFinanceData(accountType);
                }
            }

            // Add event listeners for custom date inputs
            if (historyReportDateFrom && historyReportDateTo) {
                historyReportDateFrom.addEventListener('change', function() {
                    if (historyReportPeriod.value === 'custom') {
                        filterFinanceDataByCustomDate();
                    }
                });
                
                historyReportDateTo.addEventListener('change', function() {
                    if (historyReportPeriod.value === 'custom') {
                        filterFinanceDataByCustomDate();
                    }
                });
            }

            // Set default dates for custom range (current month)
            if (historyReportDateFrom && historyReportDateTo) {
                const today = new Date();
                const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                
                historyReportDateFrom.value = firstDayOfMonth.toISOString().split('T')[0];
                historyReportDateTo.value = lastDayOfMonth.toISOString().split('T')[0];
            }

            // Account Classification Filter Functionality
            const accountTypeFilter = document.getElementById('accountTypeFilter');
            
            function filterFinanceData(accountType) {
                const tableRows = document.querySelectorAll('#historyFinanceSection tbody tr');
                console.log('Filtering finance data for account type:', accountType);
                console.log('Total rows found:', tableRows.length);
                
                tableRows.forEach((row, index) => {
                    const transactionType = row.cells[1].textContent.trim().toLowerCase();
                    const account = row.cells[4].textContent.trim().toLowerCase();
                    
                    console.log(`Row ${index}: Transaction=${transactionType}, Account=${account}`);
                    
                    let shouldShow = false;
                    
                    if (accountType === 'all') {
                        shouldShow = true;
                    } else if (accountType === 'expenses' && transactionType === 'expense') {
                        shouldShow = true;
                    } else if (accountType === 'income' && transactionType === 'income') {
                        shouldShow = true;
                    } else if (accountType === 'budget' && transactionType === 'budget') {
                        shouldShow = true;
                    } else {
                        shouldShow = false;
                    }
                    
                    row.style.display = shouldShow ? '' : 'none';
                    console.log(`Row ${index}: ${shouldShow ? 'SHOW' : 'HIDE'}`);
                });
            }

            // Period Filter Functionality for Finance Data
            function filterFinanceDataByPeriod(period) {
                const tableRows = document.querySelectorAll('#historyFinanceSection tbody tr');
                const currentDate = new Date();
                
                tableRows.forEach(row => {
                    const dateCell = row.cells[0].textContent.trim();
                    const rowDate = new Date(dateCell);
                    
                    if (isNaN(rowDate.getTime())) {
                        row.style.display = 'none'; // Hide rows with invalid dates
                        return;
                    }
                    
                    let shouldShow = false;
                    
                    switch (period) {
                        case 'today':
                            shouldShow = rowDate.toDateString() === currentDate.toDateString();
                            break;
                        case 'week':
                            const weekAgo = new Date(currentDate.getTime() - 7 * 24 * 60 * 60 * 1000);
                            shouldShow = rowDate >= weekAgo;
                            break;
                        case 'month':
                            const monthAgo = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                            shouldShow = rowDate >= monthAgo;
                            break;
                        case 'quarter':
                            const quarterStart = new Date(currentDate.getFullYear(), Math.floor(currentDate.getMonth() / 3) * 3, 1);
                            shouldShow = rowDate >= quarterStart;
                            break;
                        case 'year':
                            const yearStart = new Date(currentDate.getFullYear(), 0, 1);
                            shouldShow = rowDate >= yearStart;
                            break;
                        case 'custom':
                            // For custom range, show all rows initially
                            // The custom date inputs will handle further filtering
                            shouldShow = true;
                            break;
                        default:
                            shouldShow = true;
                    }
                    
                    row.style.display = shouldShow ? '' : 'none';
                });
            }

            // Add change event listener to the account type filter
            if (accountTypeFilter) {
                accountTypeFilter.addEventListener('change', function() {
                    const accountType = this.value;
                    console.log('Account type filter changed to:', accountType);
                    
                    // Apply account type filter independently
                    filterFinanceData(accountType);
                    
                    // Update filter button appearance to show it's active
                    updateFilterButtonAppearance(accountType);
                });
            }
            
            // Test function to debug filtering
            function testFilter() {
                console.log('=== TESTING FILTER ===');
                
                // Check if finance section is visible
                const financeSection = document.getElementById('historyFinanceSection');
                console.log('Finance section visible:', financeSection.style.display !== 'none');
                
                // Check table rows
                const tableRows = document.querySelectorAll('#historyFinanceSection tbody tr');
                console.log('Total table rows found:', tableRows.length);
                
                // Check first few rows for data
                tableRows.forEach((row, index) => {
                    if (index < 3) { // Only check first 3 rows
                        const cells = row.cells;
                        console.log(`Row ${index}:`, {
                            date: cells[0]?.textContent?.trim(),
                            transaction: cells[1]?.textContent?.trim(),
                            amount: cells[2]?.textContent?.trim(),
                            type: cells[3]?.textContent?.trim(),
                            account: cells[4]?.textContent?.trim()
                        });
                    }
                });
                
                // Test the filter manually
                console.log('Testing filter manually...');
                filterFinanceData('expenses');
            }
            
            // Function to reset all filters and show all rows
            function resetAllFilters() {
                const tableRows = document.querySelectorAll('#historyFinanceSection tbody tr');
                tableRows.forEach(row => {
                    row.style.display = '';
                });
                console.log('All filters reset - showing all rows');
            }
        });
    </script>
</body>
</html>

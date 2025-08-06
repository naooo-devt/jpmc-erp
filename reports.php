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
       <?php include 'sidebar.php'; ?>
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
        <h3>Finance Report</h3>
        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Account</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $finances = [];
                    $result_fin = $conn->query("SELECT date, transaction, amount, type, account, remarks FROM finances ORDER BY date DESC LIMIT 50");
                    if ($result_fin && $result_fin->num_rows > 0) {
                        while ($row = $result_fin->fetch_assoc()) {
                            $finances[] = $row;
                        }
                    }
                    ?>
                    <?php if (!empty($finances)): ?>
                        <?php foreach ($finances as $fin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fin['date']); ?></td>
                            <td><?php echo htmlspecialchars($fin['transaction']); ?></td>
                            <td><?php echo htmlspecialchars($fin['amount']); ?></td>
                            <td><?php echo htmlspecialchars($fin['type']); ?></td>
                            <td><?php echo htmlspecialchars($fin['account']); ?></td>
                            <td><?php echo htmlspecialchars($fin['remarks']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No finance records found.</td></tr>
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
                            <td><?php echo htmlspecialchars($hr['status']); ?></td>
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
            
            function updateHistorySection() {
                historyFinance.style.display = 'none';
                historyHR.style.display = 'none';
                historyInventory.style.display = 'none';
                if (historyType.value === 'finances') {
                    historyFinance.style.display = '';
                } else if (historyType.value === 'hr') {
                    historyHR.style.display = '';
                } else if (historyType.value === 'inventory') {
                    historyInventory.style.display = '';
                }
            }
            if (historyType) {
                historyType.addEventListener('change', updateHistorySection);
                updateHistorySection();
            }

            // Custom range functionality for history reports
            const historyReportPeriod = document.getElementById('historyReportPeriod');
            const historyCustomRangeGroups = document.querySelectorAll('#historyReportContent .custom-range');
            
            function toggleHistoryCustomRange() {
                if (historyReportPeriod.value === 'custom') {
                    historyCustomRangeGroups.forEach(group => group.style.display = 'flex');
                } else {
                    historyCustomRangeGroups.forEach(group => group.style.display = 'none');
                }
            }
            
            if (historyReportPeriod) {
                historyReportPeriod.addEventListener('change', toggleHistoryCustomRange);
                toggleHistoryCustomRange();
            }
        });
    </script>
</body>
</html>
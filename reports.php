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
                    <h2>Inventory Reports</h2>
                    <div class="actions">
                        <button class="btn btn-primary" id="generateReportBtnPDF">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </button>
                        <button class="btn btn-outline" id="generateReportBtnExcel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
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
                <div class="report-results">
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
        });
    </script>
</body>
</html> 
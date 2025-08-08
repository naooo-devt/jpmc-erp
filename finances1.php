<?php
session_start();                            // Loads session data

if(! isset($_SESSION['loggedin'])){         // Only exists for users who have successfully logged in
    header('Location: login.php');          // Redirects user to 'login.php' if not logged in
    exit;                                   // No further code running after redirect
}

require_once 'db_connect.php';              // Establish database connection

function e(string $v): string {             // Defines a small helper for HTML escaping
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$username = e($_SESSION['username'] ?? ''); // Sanitizes session values once
$role     = e($_SESSION['role']     ?? '');

$create_dashboard_table = "CREATE TABLE IF NOT EXISTS dashboard_summary (   /* Creates tables in case they do not exist*/
    id INT AUTO_INCREMENT PRIMARY KEY,
    summary_month DATE NOT NULL, 
    total_income DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_expenses DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    net_profit DECIMAL(12,2) GENERATED ALWAYS AS (total_income - total_expenses) STORED,
    expense_change_percent DECIMAL(5,2) DEFAULT NULL,  
    income_change_percent DECIMAL(5,2) DEFAULT NULL,   
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_summary_month (summary_month)
)";

$create_expenses_table = "CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    budget DECIMAL(10,2) DEFAULT NULL,
    status ENUM('Paid', 'Pending') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$create_income_table = "CREATE TABLE IF NOT EXISTS income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    source VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Received', 'Pending') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$create_invoices_table = "CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    client VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Paid', 'Pending', 'Overdue', 'Draft') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$create_budget_table = "CREATE TABLE IF NOT EXISTS budget (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    allocated DECIMAL(10,2) NOT NULL,
    spent DECIMAL(10,2) DEFAULT 0.00,
    remaining DECIMAL(10,2) GENERATED ALWAYS AS (allocated - spent) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$tables = [                         // Executes table creation
    $create_dashboard_table,
    $create_expenses_table,
    $create_income_table,
    $create_invoices_table,
    $create_budget_table
];

foreach ($tables as $table) {
    if ($conn->query($table) === FALSE) {
        echo "Error creating table: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finances - James Polymers ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/finances.css">
    <link rel="icon" href="images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
    <!-- Header = Module (Left) | User (Right) -->
        <div class="header">
            <div class="header-left"> 
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="header-title">Finances</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </div>  
        <!-- Finances Navigation Bar = Dashboard | Expenses | Income | Invoice | Budgeting -->
        <div class="content">
            <div class="finance-tabs">
                <button class="finance-tab active" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </button>
                <button class="finance-tab" data-tab="expenses">
                    <i class="fas fa-receipt"></i>
                    <span>Expenses</span>
                </button>
                <button class="finance-tab" data-tab="income">
                    <i class="fas fa-arrow-up"></i>
                    <span>Income</span>
                </button>
                <button class="finance-tab" data-tab="invoice">
                    <i class="fas fa-file-invoice"></i>
                    <span>Invoice</span>
                </button>
                <button class="finance-tab" data-tab="budgeting">
                    <i class="fas fa-chart-pie"></i>
                    <span>Budgeting</span>
                </button>
            </div>

            <!-- Finances - Dashboard Tab Contents -->
             


    <?php
    // Fetch summary data from the database
    $result = $conn->query("SELECT summary_month, total_income, total_expenses FROM dashboard_summary ORDER BY summary_month ASC");

    $months = [];
    $income = [];
    $expenses = [];
    $profit = [];

    while ($row = $result->fetch_assoc()) {
        $months[] = date('M', strtotime($row['summary_month']));
        $income[] = (float)$row['total_income'];
        $expenses[] = (float)$row['total_expenses'];
        $profit[] = (float)$row['total_income'] - (float)$row['total_expenses'];
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('financeChart');
    new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
        {
            label: 'Expenses',
            data: <?= json_encode($expenses) ?>,
            backgroundColor: 'rgba(255,99,132,0.5)'
        },
        {
            label: 'Income',
            data: <?= json_encode($income) ?>,
            backgroundColor: 'rgba(54,162,235,0.5)'
        },
        {
            label: 'Profit',
            data: <?= json_encode($profit) ?>,
            backgroundColor: 'rgba(75,192,192,0.5)'
        }
        ]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
    });
    </script>

</body>
</html>


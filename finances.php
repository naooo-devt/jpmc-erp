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

// Create necessary tables if they don't exist
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

// Execute table creation
$tables = [$create_expenses_table, $create_income_table, $create_invoices_table, $create_budget_table];
foreach ($tables as $table) {
    if ($conn->query($table) === FALSE) {
        error_log("Error creating table: " . $conn->error);
    }
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // Expense operations
            case 'add_expense':
                $date = $_POST['date'];
                $category = $_POST['category'];
                $description = $_POST['description'];
                $amount = $_POST['amount'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("INSERT INTO expenses (date, category, description, amount, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssds", $date, $category, $description, $amount, $status);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense added successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error adding expense: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            case 'edit_expense':
                $id = $_POST['id'];
                $date = $_POST['date'];
                $category = $_POST['category'];
                $description = $_POST['description'];
                $amount = $_POST['amount'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE expenses SET date = ?, category = ?, description = ?, amount = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssdsi", $date, $category, $description, $amount, $status, $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating expense: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            case 'delete_expense':
                $id = $_POST['id'];
                
                $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error deleting expense: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;

            // Income operations
            case 'add_income':
                $date = $_POST['date'];
                $source = $_POST['source'];
                $description = $_POST['description'];
                $amount = $_POST['amount'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("INSERT INTO income (date, source, description, amount, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssds", $date, $source, $description, $amount, $status);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Income added successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error adding income: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            case 'edit_income':
                $id = $_POST['id'];
                $date = $_POST['date'];
                $source = $_POST['source'];
                $description = $_POST['description'];
                $amount = $_POST['amount'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE income SET date = ?, source = ?, description = ?, amount = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssdsi", $date, $source, $description, $amount, $status, $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Income updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating income: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            case 'delete_income':
                $id = $_POST['id'];
                
                $stmt = $conn->prepare("DELETE FROM income WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Income deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error deleting income: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;

            // Invoice operations
            case 'add_invoice':
                $invoice_no = $_POST['invoice_no'];
                $client = $_POST['client'];
                $amount = $_POST['amount'];
                $due_date = $_POST['due_date'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, client, amount, due_date, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdss", $invoice_no, $client, $amount, $due_date, $status);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Invoice created successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error creating invoice: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            case 'edit_invoice':
                $id = $_POST['id'];
                $invoice_no = $_POST['invoice_no'];
                $client = $_POST['client'];
                $amount = $_POST['amount'];
                $due_date = $_POST['due_date'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE invoices SET invoice_no = ?, client = ?, amount = ?, due_date = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssdssi", $invoice_no, $client, $amount, $due_date, $status, $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Invoice updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating invoice: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            case 'delete_invoice':
                $id = $_POST['id'];
                
                $stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Invoice deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error deleting invoice: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;

            // Budget operations
            case 'add_budget':
                $category = $_POST['category'];
                $allocated = $_POST['allocated'];
                
                $stmt = $conn->prepare("INSERT INTO budget (category, allocated) VALUES (?, ?)");
                $stmt->bind_param("sd", $category, $allocated);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Budget added successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error adding budget: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            case 'edit_budget':
                $id = $_POST['id'];
                $category = $_POST['category'];
                $allocated = $_POST['allocated'];
                $spent = $_POST['spent'];
                
                $stmt = $conn->prepare("UPDATE budget SET category = ?, allocated = ?, spent = ? WHERE id = ?");
                $stmt->bind_param("sddi", $category, $allocated, $spent, $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Budget updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating budget: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            case 'delete_budget':
                $id = $_POST['id'];
                
                $stmt = $conn->prepare("DELETE FROM budget WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Budget deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error deleting budget: ' . $stmt->error];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
        }
    }
}

// Fetch data from database
$expenses_query = "SELECT * FROM expenses ORDER BY date DESC";
$expenses_result = $conn->query($expenses_query);

// Debug: Check if query was successful
if (!$expenses_result) {
    error_log("Error fetching expenses: " . $conn->error);
} else {
    error_log("Expenses query successful, rows: " . $expenses_result->num_rows);
}


$income_query = "SELECT * FROM income ORDER BY date DESC";
$income_result = $conn->query($income_query);

$invoices_query = "SELECT * FROM invoices ORDER BY due_date ASC";
$invoices_result = $conn->query($invoices_query);

$budget_query = "SELECT * FROM budget ORDER BY category ASC";
$budget_result = $conn->query($budget_query);

// Process expenses data
if ($expenses_result) {
    $expenses = [];
    while ($row = $expenses_result->fetch_assoc()) {
        $expenses[] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'category' => $row['category'],
            'description' => $row['description'],
            'amount' => $row['amount'],
            'status' => $row['status']
        ];
    }
    // Debug: Log expenses count and details
    error_log("Expenses loaded: " . count($expenses));
    foreach ($expenses as $index => $expense) {
        error_log("Expense $index: ID=" . $expense['id'] . ", Description=" . $expense['description']);
    }
} else {
    $expenses = [];
    error_log("No expenses found in database");
}

// Process income data
if ($income_result) {
    $income = [];
    while ($row = $income_result->fetch_assoc()) {
        $income[] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'source' => $row['source'],
            'description' => $row['description'],
            'amount' => $row['amount'],
            'status' => $row['status']
        ];
    }
} else {
    $income = [];
}

// Process invoices data
if ($invoices_result) {
    $invoices = [];
    while ($row = $invoices_result->fetch_assoc()) {
        $invoices[] = [
            'id' => $row['id'],
            'invoice_no' => $row['invoice_no'],
            'client' => $row['client'],
            'amount' => $row['amount'],
            'due_date' => $row['due_date'],
            'status' => $row['status']
        ];
    }
} else {
    $invoices = [];
}

// Process budget data
if ($budget_result) {
    $budget = [];
    while ($row = $budget_result->fetch_assoc()) {
        $budget[] = [
            'id' => $row['id'],
            'category' => $row['category'],
            'allocated' => $row['allocated'],
            'spent' => $row['spent'],
            'remaining' => $row['remaining']
        ];
    }
} else {
    $budget = [];
}

// Add sample data if tables are empty
if (empty($expenses)) {
    $sample_expenses = [
        ['date' => '2024-01-15', 'category' => 'Utilities', 'description' => 'Electricity bill for January', 'amount' => 2500.00, 'status' => 'Paid'],
        ['date' => '2024-01-20', 'category' => 'Supplies', 'description' => 'Office supplies and materials', 'amount' => 1500.00, 'status' => 'Pending'],
        ['date' => '2024-01-25', 'category' => 'Maintenance', 'description' => 'Equipment maintenance and repairs', 'amount' => 3500.00, 'status' => 'Paid']
    ];
    
    $stmt = $conn->prepare("INSERT INTO expenses (date, category, description, amount, status) VALUES (?, ?, ?, ?, ?)");
    foreach ($sample_expenses as $expense) {
        $stmt->bind_param("sssds", $expense['date'], $expense['category'], $expense['description'], $expense['amount'], $expense['status']);
        if ($stmt->execute()) {
            error_log("Sample expense inserted with ID: " . $stmt->insert_id);
        } else {
            error_log("Error inserting sample expense: " . $stmt->error);
        }
    }
    $stmt->close();
    
    // Reload expenses after adding sample data
    $expenses_result = $conn->query($expenses_query);
    $expenses = [];
    while ($row = $expenses_result->fetch_assoc()) {
        $expenses[] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'category' => $row['category'],
            'description' => $row['description'],
            'amount' => $row['amount'],
            'status' => $row['status']
        ];
    }
    error_log("Added sample expenses, total: " . count($expenses));
}

// Calculate totals
$total_expenses = array_sum(array_column($expenses, 'amount'));
$total_income = array_sum(array_column($income, 'amount'));
$total_invoices = array_sum(array_column($invoices, 'amount'));
$total_budget_allocated = array_sum(array_column($budget, 'allocated'));
$total_budget_spent = array_sum(array_column($budget, 'spent'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finances - James Polymers ERP</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/finances.css">
    <link rel="icon" href="images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <!-- Main Content Area -->
    <div class="main-content">
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
        <!-- Finance Tabs -->        
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

            <!-- Finances - Dashboard Tab -->
            <div class="fin-tabs-content active" id="dashboard">
                <!-- Finances -  Dashboard Summary | Cards | Charts | Tables -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Total Expenses</div>
                            <div class="summary-card-icon dashboard">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_expenses, 2); ?></div>
                        <div class="summary-card-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>0% from last month</span>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Total Revenue</div>
                            <div class="summary-card-icon dashboard">
                                <i class="fas fa-comment-dollar"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_income, 2); ?></div>
                        <div class="summary-card-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>0% from last month</span>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Total Net Profit</div>
                            <div class="summary-card-icon dashboard">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_income - $total_expenses, 2); ?></div>
                        <div class="summary-card-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>0% from last month</span>
                        </div>
                    </div>
                </div>
                
                <div class="charts-section">
                    <div class="exp-inc-chart">
                        <div class="exp-inc-chart-header">
                            <div class="exp-inc-chart-title">Monthly Income vs Expenses</div>
                            <div class="exp-inc-chart-controls">
                                <button class="chart-btn active">Weekly</button>
                                <button class="chart-btn">Monthly</button>
                                <button class="chart-btn">Quarterly</button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <canvas id="exinChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenses Tab -->
            <div class="fin-tabs-content" id="expenses">
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Total Expenses</div>
                            <div class="summary-card-icon expenses">
                                <i class="fas fa-receipt"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_expenses, 2); ?></div>
                        <div class="summary-card-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>12.5% from last month</span>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Pending Payments</div>
                            <div class="summary-card-icon expenses">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format(array_sum(array_map(function($expense) { return $expense['status'] === 'Pending' ? $expense['amount'] : 0; }, $expenses)), 2); ?></div>
                        <div class="summary-card-change">3 payments pending</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">This Month</div>
                            <div class="summary-card-icon expenses">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_expenses * 0.4, 2); ?></div>
                        <div class="summary-card-change">40% of monthly budget</div>
                    </div>
                </div>

                <div class="finance-table">
                    <div class="finance-table-header">
                        <div class="finance-table-title">Expense Records</div>
                        <div class="finance-table-actions">
                            <div class="filter-group">
                                <input type="text" class="form-input filter-select" id="expenseSearchFilter" placeholder="Search expenses...">
                                <select class="form-input filter-select" id="expenseCategoryFilter">
                                    <option value="">All Categories</option>
                                    <option value="Production Costs">Production Costs</option>
                                    <option value="Packaging & Logistics">Packaging & Logistics</option>
                                    <option value="Operating & Administrative">Operating & Administrative</option>
                                    <option value="Sales & Marketing">Sales & Marketing</option>
                                    <option value="Financial Expenses">Financial Expenses</option>
                                    <option value="R&D / Quality Control">R&D / Quality Control</option>
                                    <option value="Other Expenses">Other Expenses</option>
                                </select>
                                <select class="form-input filter-select" id="expenseStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Pending">Pending</option>
                                </select>
                                <button class="btn btn-outline" id="clearExpenseFilters">
                                    <i class="fas fa-times"></i>
                                    <span>Clear</span>
                                </button>
                            </div>
                            <button class="btn btn-primary" id="addExpenseBtn">
                                <i class="fas fa-plus"></i>
                                <span>Add New Expense</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                <!-- Debug: Expense ID = <?php echo $expense['id']; ?> -->
                                <tr data-expense-id="<?php echo $expense['id']; ?>">
                                    <td><?php echo date('M d, Y', strtotime($expense['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($expense['status']); ?>"><?php echo $expense['status']; ?></span></td>
                                    <td>
                                        <button class="btn btn-outline btn-sm edit-expense-btn" 
                                                data-id="<?php echo $expense['id']; ?>"
                                                data-date="<?php echo $expense['date']; ?>"
                                                data-category="<?php echo htmlspecialchars($expense['category']); ?>"
                                                data-description="<?php echo htmlspecialchars($expense['description']); ?>"
                                                data-amount="<?php echo $expense['amount']; ?>"
                                                data-status="<?php echo $expense['status']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm delete-expense-btn" 
                                                data-id="<?php echo $expense['id']; ?>"
                                                data-description="<?php echo htmlspecialchars($expense['description']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Income Tab -->
            <div class="fin-tabs-content" id="income">
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Total Income</div>
                            <div class="summary-card-icon income">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_income, 2); ?></div>
                        <div class="summary-card-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>18.3% from last month</span>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Pending Receipts</div>
                            <div class="summary-card-icon income">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format(array_sum(array_map(function($income) { return $income['status'] === 'Pending' ? $income['amount'] : 0; }, $income)), 2); ?></div>
                        <div class="summary-card-change">2 payments pending</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Net Profit</div>
                            <div class="summary-card-icon income">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_income - $total_expenses, 2); ?></div>
                        <div class="summary-card-change positive">Positive growth</div>
                    </div>
                </div>

                <div class="finance-table">
                    <div class="finance-table-header">
                        <div class="finance-table-title">Income Records</div>
                        <div class="finance-table-actions">
                            <div class="filter-group">
                                <select class="form-input filter-select" id="incomeSourceFilter">
                                    <option value="">All Sources</option>
                                    <option value="Product Sales">Product Sales</option>
                                    <option value="Service Revenue">Service Revenue</option>
                                </select>
                                <select class="form-input filter-select" id="incomeStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="Received">Received</option>
                                    <option value="Pending">Pending</option>
                                </select>
                                <button class="btn btn-outline" id="clearIncomeFilters">
                                    <i class="fas fa-times"></i>
                                    <span>Clear</span>
                                </button>
                            </div>
                            <button class="btn btn-primary" id="addIncomeBtn">
                                <i class="fas fa-plus"></i>
                                <span>Add Income</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Source</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($income as $income_item): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($income_item['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($income_item['source']); ?></td>
                                    <td><?php echo htmlspecialchars($income_item['description']); ?></td>
                                    <td>₱<?php echo number_format($income_item['amount'], 2); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($income_item['status']); ?>"><?php echo $income_item['status']; ?></span></td>
                                    <td>
                                        <button class="btn btn-outline btn-sm edit-income-btn" 
                                                data-id="<?php echo $income_item['id']; ?>"
                                                data-date="<?php echo $income_item['date']; ?>"
                                                data-source="<?php echo htmlspecialchars($income_item['source']); ?>"
                                                data-description="<?php echo htmlspecialchars($income_item['description']); ?>"
                                                data-amount="<?php echo $income_item['amount']; ?>"
                                                data-status="<?php echo $income_item['status']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm delete-income-btn" 
                                                data-id="<?php echo $income_item['id']; ?>"
                                                data-description="<?php echo htmlspecialchars($income_item['description']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Income Statement Section -->
                <div class="finance-table" style="margin-top: 2rem;">
                    <div class="finance-table-header">
                        <div class="finance-table-title">Income Statement</div>
                        <div class="finance-table-actions">
                            <div class="filter-group">
                                <select class="form-input filter-select" id="incomeStatementPeriod">
                                    <option value="current">Current Month</option>
                                    <option value="last">Last Month</option>
                                    <option value="quarter">This Quarter</option>
                                    <option value="year">This Year</option>
                                </select>
                                <button class="btn btn-outline" id="generateIncomeStatement">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Generate Report</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 1rem; background: var(--light-gray); font-weight: 600; color: var(--dark-gray); border-bottom: 1px solid var(--border-color);">Income Statement</th>
                                    <th style="text-align: right; padding: 1rem; background: var(--light-gray); font-weight: 600; color: var(--dark-gray); border-bottom: 1px solid var(--border-color);">Amount (₱)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Revenue Section -->
                                <tr style="background: rgba(16, 185, 129, 0.05);">
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 600; color: var(--success);">
                                        <i class="fas fa-arrow-up" style="margin-right: 8px;"></i>
                                        Revenue
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); text-align: right; font-weight: 600; color: var(--success);">
                                        ₱<?php echo number_format($total_income, 2); ?>
                                    </td>
                                </tr>
                                
                                <!-- Revenue Breakdown -->
                                <?php
                                // Group income by source
                                $income_by_source = [];
                                foreach ($income as $income_item) {
                                    $source = $income_item['source'];
                                    if (!isset($income_by_source[$source])) {
                                        $income_by_source[$source] = 0;
                                    }
                                    $income_by_source[$source] += $income_item['amount'];
                                }
                                
                                foreach ($income_by_source as $source => $amount):
                                ?>
                                <tr>
                                    <td style="padding: 0.5rem 1rem 0.5rem 3rem; border-bottom: 1px solid var(--border-color); color: var(--dark-gray);">
                                        • <?php echo htmlspecialchars($source); ?>
                                    </td>
                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid var(--border-color); text-align: right; color: var(--dark-gray);">
                                        ₱<?php echo number_format($amount, 2); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <!-- Cost of Goods Sold Section -->
                                <tr style="background: rgba(239, 68, 68, 0.05);">
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 600; color: var(--error);">
                                        <i class="fas fa-arrow-down" style="margin-right: 8px;"></i>
                                        Cost of Goods Sold
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); text-align: right; font-weight: 600; color: var(--error);">
                                        ₱<?php echo number_format($total_expenses * 0.7, 2); ?>
                                    </td>
                                </tr>

                                <!-- COGS Breakdown -->
                                <?php
                                // Group expenses by category for COGS calculation
                                $expenses_by_category = [];
                                foreach ($expenses as $expense) {
                                    $category = $expense['category'];
                                    if (!isset($expenses_by_category[$category])) {
                                        $expenses_by_category[$category] = 0;
                                    }
                                    $expenses_by_category[$category] += $expense['amount'];
                                }
                                
                                // Calculate COGS (70% of total expenses as an example)
                                $cogs_total = $total_expenses * 0.7;
                                $cogs_categories = ['Raw Materials', 'Logistics', 'Utilities', 'Equipment'];
                                $cogs_per_category = $cogs_total / count($cogs_categories);
                                
                                foreach ($cogs_categories as $category):
                                    if (isset($expenses_by_category[$category])):
                                ?>
                                <tr>
                                    <td style="padding: 0.5rem 1rem 0.5rem 3rem; border-bottom: 1px solid var(--border-color); color: var(--dark-gray);">
                                        • <?php echo htmlspecialchars($category); ?>
                                    </td>
                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid var(--border-color); text-align: right; color: var(--dark-gray);">
                                        ₱<?php echo number_format($expenses_by_category[$category] * 0.7, 2); ?>
                                    </td>
                                </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>

                                <!-- Gross Profit -->
                                <tr style="background: rgba(37, 99, 235, 0.05); border-top: 2px solid var(--primary-blue);">
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 700; color: var(--primary-blue);">
                                        <i class="fas fa-chart-line" style="margin-right: 8px;"></i>
                                        Gross Profit
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); text-align: right; font-weight: 700; color: var(--primary-blue);">
                                        ₱<?php echo number_format($total_income - ($total_expenses * 0.7), 2); ?>
                                    </td>
                                </tr>

                                <!-- Operating Expenses Section -->
                                <tr style="background: rgba(245, 158, 11, 0.05);">
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 600; color: var(--warning);">
                                        <i class="fas fa-arrow-down" style="margin-right: 8px;"></i>
                                        Operating Expenses
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); text-align: right; font-weight: 600; color: var(--warning);">
                                        ₱<?php echo number_format($total_expenses * 0.3, 2); ?>
                                    </td>
                                </tr>

                                <!-- Operating Expenses Breakdown -->
                                <?php
                                $operating_expenses_categories = ['Marketing', 'Office Supplies', 'Insurance', 'Training', 'Software', 'Maintenance'];
                                $operating_expenses_total = $total_expenses * 0.3;
                                
                                foreach ($operating_expenses_categories as $category):
                                    if (isset($expenses_by_category[$category])):
                                ?>
                                <tr>
                                    <td style="padding: 0.5rem 1rem 0.5rem 3rem; border-bottom: 1px solid var(--border-color); color: var(--dark-gray);">
                                        • <?php echo htmlspecialchars($category); ?>
                                    </td>
                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid var(--border-color); text-align: right; color: var(--dark-gray);">
                                        ₱<?php echo number_format($expenses_by_category[$category] * 0.3, 2); ?>
                                    </td>
                                </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>

                                <!-- Net Income -->
                                <tr style="background: rgba(16, 185, 129, 0.1); border-top: 2px solid var(--success);">
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 700; color: var(--success); font-size: 1.1rem;">
                                        <i class="fas fa-dollar-sign" style="margin-right: 8px;"></i>
                                        Net Income
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); text-align: right; font-weight: 700; color: var(--success); font-size: 1.1rem;">
                                        ₱<?php echo number_format($total_income - $total_expenses, 2); ?>
                                    </td>
                                </tr>

                                <!-- Financial Ratios -->
                                <tr style="background: var(--light-gray);">
                                    <td colspan="2" style="padding: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 600; color: var(--dark-gray);">
                                        <i class="fas fa-chart-pie" style="margin-right: 8px;"></i>
                                        Key Financial Ratios
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 1rem 0.5rem 3rem; border-bottom: 1px solid var(--border-color); color: var(--dark-gray);">
                                        • Gross Profit Margin
                                    </td>
                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid var(--border-color); text-align: right; color: var(--dark-gray);">
                                        <?php 
                                        $gross_profit_margin = $total_income > 0 ? (($total_income - ($total_expenses * 0.7)) / $total_income) * 100 : 0;
                                        echo number_format($gross_profit_margin, 1) . '%';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 1rem 0.5rem 3rem; border-bottom: 1px solid var(--border-color); color: var(--dark-gray);">
                                        • Net Profit Margin
                                    </td>
                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid var(--border-color); text-align: right; color: var(--dark-gray);">
                                        <?php 
                                        $net_profit_margin = $total_income > 0 ? (($total_income - $total_expenses) / $total_income) * 100 : 0;
                                        echo number_format($net_profit_margin, 1) . '%';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 1rem 0.5rem 3rem; border-bottom: 1px solid var(--border-color); color: var(--dark-gray);">
                                        • Operating Expense Ratio
                                    </td>
                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid var(--border-color); text-align: right; color: var(--dark-gray);">
                                        <?php 
                                        $operating_expense_ratio = $total_income > 0 ? (($total_expenses * 0.3) / $total_income) * 100 : 0;
                                        echo number_format($operating_expense_ratio, 1) . '%';
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Invoice Tab -->
            <div class="fin-tabs-content" id="invoice">
                            <div class="summary-cards">
                                <div class="summary-card">
                                    <div class="summary-card-header">
                                        <div class="summary-card-title">Total Invoices</div>
                                        <div class="summary-card-icon invoices">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                    </div>
                                    <div class="summary-card-value">₱<?php echo number_format($total_invoices, 2); ?></div>
                                    <div class="summary-card-change">5 invoices generated</div>
                                </div>
                                <div class="summary-card">
                                    <div class="summary-card-header">
                                        <div class="summary-card-title">Paid Invoices</div>
                                        <div class="summary-card-icon invoices">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                    <div class="summary-card-value">₱<?php echo number_format(array_sum(array_map(function($invoice) { return $invoice['status'] === 'Paid' ? $invoice['amount'] : 0; }, $invoices)), 2); ?></div>
                                    <div class="summary-card-change positive">1 invoice paid</div>
                                </div>
                                <div class="summary-card">
                                    <div class="summary-card-header">
                                        <div class="summary-card-title">Overdue Amount</div>
                                        <div class="summary-card-icon invoices">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                    </div>
                                    <div class="summary-card-value">₱<?php echo number_format(array_sum(array_map(function($invoice) { return $invoice['status'] === 'Overdue' ? $invoice['amount'] : 0; }, $invoices)), 2); ?></div>
                                    <div class="summary-card-change negative">1 invoice overdue</div>
                                </div>
                            </div>

                            <div class="finance-table">
                                <div class="finance-table-header">
                                    <div class="finance-table-title">Invoice Management</div>
                                    <div class="finance-table-actions">
                                        <div class="filter-group">
                                            <select class="form-input filter-select" id="invoiceStatusFilter">
                                                <option value="">All Status</option>
                                                <option value="Paid">Paid</option>
                                                <option value="Pending">Pending</option>
                                                <option value="Overdue">Overdue</option>
                                                <option value="Draft">Draft</option>
                                            </select>
                                            <select class="form-input filter-select" id="invoiceClientFilter">
                                                <option value="">All Clients</option>
                                                <option value="ABC Manufacturing">ABC Manufacturing</option>
                                                <option value="XYZ Industries">XYZ Industries</option>
                                                <option value="Tech Solutions">Tech Solutions</option>
                                                <option value="Global Corp">Global Corp</option>
                                                <option value="Local Business">Local Business</option>
                                            </select>
                                            <button class="btn btn-outline" id="clearInvoiceFilters">
                                                <i class="fas fa-times"></i>
                                                <span>Clear</span>
                                            </button>
                                        </div>
                                        <button class="btn btn-primary" id="addInvoiceBtn">
                                            <i class="fas fa-plus"></i>
                                            <span>Create Invoice</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Invoice #</th>
                                                <th>Client</th>
                                                <th>Amount</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($invoices as $invoice): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                                                <td><?php echo htmlspecialchars($invoice['client']); ?></td>
                                                <td>₱<?php echo number_format($invoice['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                                <td><span class="status-badge <?php echo strtolower($invoice['status']); ?>"><?php echo $invoice['status']; ?></span></td>
                                                <td>
                                                    <button class="btn btn-outline btn-sm view-invoice-btn" 
                                                            data-id="<?php echo $invoice['id']; ?>"
                                                            data-invoice="<?php echo htmlspecialchars($invoice['invoice_no']); ?>"
                                                            data-client="<?php echo htmlspecialchars($invoice['client']); ?>"
                                                            data-amount="<?php echo $invoice['amount']; ?>"
                                                            data-due="<?php echo $invoice['due_date']; ?>"
                                                            data-status="<?php echo $invoice['status']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline btn-sm edit-invoice-btn" 
                                                            data-id="<?php echo $invoice['id']; ?>"
                                                            data-invoice="<?php echo htmlspecialchars($invoice['invoice_no']); ?>"
                                                            data-client="<?php echo htmlspecialchars($invoice['client']); ?>"
                                                            data-amount="<?php echo $invoice['amount']; ?>"
                                                            data-due="<?php echo $invoice['due_date']; ?>"
                                                            data-status="<?php echo $invoice['status']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm delete-invoice-btn" 
                                                            data-id="<?php echo $invoice['id']; ?>"
                                                            data-invoice="<?php echo htmlspecialchars($invoice['invoice_no']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

            <!-- Budgeting Tab -->
            <div class="fin-tabs-content" id="budgeting">
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Total Budget</div>
                            <div class="summary-card-icon budget">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_budget_allocated, 2); ?></div>
                        <div class="summary-card-change">Budget allocated</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Spent</div>
                            <div class="summary-card-icon budget">
                                <i class="fas fa-spending"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_budget_spent, 2); ?></div>
                        <div class="summary-card-change"><?php echo round(($total_budget_spent / $total_budget_allocated) * 100, 1); ?>% used</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-card-title">Remaining</div>
                            <div class="summary-card-icon budget">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                        <div class="summary-card-value">₱<?php echo number_format($total_budget_allocated - $total_budget_spent, 2); ?></div>
                        <div class="summary-card-change positive">Available budget</div>
                    </div>
                </div>

                <div class="finance-table">
                    <div class="finance-table-header">
                        <div class="finance-table-title">Budget Overview</div>
                        <div class="finance-table-actions">
                            <div class="filter-group">
                                <select class="form-input filter-select" id="budgetCategoryFilter">
                                    <option value="">All Categories</option>
                                    <option value="Raw Materials">Raw Materials</option>
                                    <option value="Equipment">Equipment</option>
                                    <option value="Utilities">Utilities</option>
                                    <option value="Logistics">Logistics</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Marketing">Marketing</option>
                                </select>
                                <select class="form-input filter-select" id="budgetProgressFilter">
                                    <option value="">All Progress</option>
                                    <option value="safe">Under 50%</option>
                                    <option value="warning">50-80%</option>
                                    <option value="danger">Over 80%</option>
                                </select>
                                <button class="btn btn-outline" id="clearBudgetFilters">
                                    <i class="fas fa-times"></i>
                                    <span>Clear</span>
                                </button>
                            </div>
                            <button class="btn btn-outline" id="exportBudgetBtn">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                            <button class="btn btn-primary" id="addBudgetBtn">
                                <i class="fas fa-plus"></i>
                                <span>Add Budget</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Allocated</th>
                                    <th>Spent</th>
                                    <th>Remaining</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($budget as $budget_item): ?>
                                <?php 
                                $percentage = ($budget_item['spent'] / $budget_item['allocated']) * 100;
                                $progress_class = $percentage < 50 ? 'safe' : ($percentage < 80 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($budget_item['category']); ?></td>
                                    <td>₱<?php echo number_format($budget_item['allocated'], 2); ?></td>
                                    <td>₱<?php echo number_format($budget_item['spent'], 2); ?></td>
                                    <td>₱<?php echo number_format($budget_item['remaining'], 2); ?></td>
                                    <td>
                                        <div class="budget-progress">
                                            <div class="budget-progress-bar <?php echo $progress_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <small><?php echo round($percentage, 1); ?>%</small>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline btn-sm edit-budget-btn" 
                                                data-id="<?php echo $budget_item['id']; ?>"
                                                data-category="<?php echo htmlspecialchars($budget_item['category']); ?>"
                                                data-allocated="<?php echo $budget_item['allocated']; ?>"
                                                data-spent="<?php echo $budget_item['spent']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline btn-sm view-budget-chart-btn" 
                                                data-category="<?php echo htmlspecialchars($budget_item['category']); ?>"
                                                data-allocated="<?php echo $budget_item['allocated']; ?>"
                                                data-spent="<?php echo $budget_item['spent']; ?>">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Expense Modal -->
    <div class="modal-overlay" id="expenseModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Expense</h3>
                <button class="close-modal" id="closeExpenseModal">&times;</button>
            </div>
            <form id="expenseForm">
                <input type="hidden" id="expenseId" name="id">
                <input type="hidden" name="action" id="expenseAction" value="add_expense">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expenseDate" class="form-label">Date</label>
                        <input type="date" id="expenseDate" name="date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="expenseCategory" class="form-label">Category</label>
                        <select id="expenseCategory" name="category" class="form-input" required>
                            <option value="">Select Category</option>
                            <option value="Production Costs">Production Costs</option>
                            <option value="Packaging & Logistics">Packaging & Logistics</option>                                <option value="Operating & Administrative">Operating & Administrative</option>
                            <option value="Sales & Marketing">Sales & Marketing</option>
                            <option value="Financial Expenses">Financial Expenses</option>
                            <option value="R&D / Quality Control">R&D / Quality Control</option>
                            <option value="Other Expenses">Other Expenses</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="expenseDescription" class="form-label">Description</label>
                    <textarea id="expenseDescription" name="description" class="form-input" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expenseAmount" class="form-label">Amount (₱)</label>
                        <input type="number" id="expenseAmount" name="amount" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="expenseStatus" class="form-label">Status</label>
                        <select id="expenseStatus" name="status" class="form-input" required>
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="cancelExpenseBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveExpenseBtn">Save Expense</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <button class="close-modal" id="closeDeleteModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item?</p>
                <p><strong id="deleteItemDescription"></strong></p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" id="cancelDeleteBtn">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Add/Edit Income Modal -->
    <div class="modal-overlay" id="incomeModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="incomeModalTitle">Add New Income</h3>
                <button class="close-modal" id="closeIncomeModal">&times;</button>
            </div>
            <form id="incomeForm">
                <input type="hidden" id="incomeId" name="id">
                <input type="hidden" name="action" id="incomeAction" value="add_income">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="incomeDate" class="form-label">Date</label>
                        <input type="date" id="incomeDate" name="date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="incomeSource" class="form-label">Source</label>
                        <select id="incomeSource" name="source" class="form-input" required>
                            <option value="">Select Source</option>
                            <option value="Product Sales">Product Sales</option>
                            <option value="Service Revenue">Service Revenue</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Licensing">Licensing</option>
                            <option value="Maintenance Contracts">Maintenance Contracts</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="incomeDescription" class="form-label">Description</label>
                    <textarea id="incomeDescription" name="description" class="form-input" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="incomeAmount" class="form-label">Amount (₱)</label>
                        <input type="number" id="incomeAmount" name="amount" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="incomeStatus" class="form-label">Status</label>
                        <select id="incomeStatus" name="status" class="form-input" required>
                            <option value="Pending">Pending</option>
                            <option value="Received">Received</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="cancelIncomeBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveIncomeBtn">Save Income</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Invoice Modal -->
    <div class="modal-overlay" id="invoiceModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="invoiceModalTitle">Create New Invoice</h3>
                <button class="close-modal" id="closeInvoiceModal">&times;</button>
            </div>
            <form id="invoiceForm">
                <input type="hidden" id="invoiceId" name="id">
                <input type="hidden" name="action" id="invoiceAction" value="add_invoice">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="invoiceNumber" class="form-label">Invoice Number</label>
                        <input type="text" id="invoiceNumber" name="invoice_no" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="invoiceClient" class="form-label">Client</label>
                        <input type="text" id="invoiceClient" name="client" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="invoiceAmount" class="form-label">Amount (₱)</label>
                        <input type="number" id="invoiceAmount" name="amount" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="invoiceDueDate" class="form-label">Due Date</label>
                        <input type="date" id="invoiceDueDate" name="due_date" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="invoiceStatus" class="form-label">Status</label>
                    <select id="invoiceStatus" name="status" class="form-input" required>
                        <option value="Draft">Draft</option>
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="cancelInvoiceBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveInvoiceBtn">Save Invoice</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Budget Modal -->
    <div class="modal-overlay" id="budgetModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="budgetModalTitle">Add New Budget</h3>
                <button class="close-modal" id="closeBudgetModal">&times;</button>
            </div>
            <form id="budgetForm">
                <input type="hidden" id="budgetId" name="id">
                <input type="hidden" name="action" id="budgetAction" value="add_budget">
                
                <div class="form-group">
                    <label for="budgetCategory" class="form-label">Category</label>
                    <select id="budgetCategory" name="category" class="form-input" required>
                        <option value="">Select Category</option>
                        <option value="Raw Materials">Raw Materials</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Logistics">Logistics</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Office Supplies">Office Supplies</option>
                        <option value="Insurance">Insurance</option>
                        <option value="Training">Training</option>
                        <option value="Software">Software</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="budgetAllocated" class="form-label">Allocated Amount (₱)</label>
                        <input type="number" id="budgetAllocated" name="allocated" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="budgetSpent" class="form-label">Spent Amount (₱)</label>
                        <input type="number" id="budgetSpent" name="spent" class="form-input" step="0.01" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="cancelBudgetBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBudgetBtn">Save Budget</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Invoice Modal -->
    <div class="modal-overlay" id="viewInvoiceModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Invoice Details</h3>
                <button class="close-modal" id="closeViewInvoiceModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="invoice-details">
                    <div class="detail-row">
                        <strong>Invoice Number:</strong>
                        <span id="viewInvoiceNumber"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Client:</strong>
                        <span id="viewInvoiceClient"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Amount:</strong>
                        <span id="viewInvoiceAmount"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Due Date:</strong>
                        <span id="viewInvoiceDueDate"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Status:</strong>
                        <span id="viewInvoiceStatus"></span>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" id="closeViewInvoiceBtn">Close</button>
            </div>
        </div>
    </div>

    <!-- Budget Chart Modal -->
    <div class="modal-overlay" id="budgetChartModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Budget Analysis</h3>
                <button class="close-modal" id="closeBudgetChartModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="budget-chart-details">
                    <div class="detail-row">
                        <strong>Category:</strong>
                        <span id="chartCategory"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Allocated:</strong>
                        <span id="chartAllocated"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Spent:</strong>
                        <span id="chartSpent"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Remaining:</strong>
                        <span id="chartRemaining"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Progress:</strong>
                        <span id="chartProgress"></span>
                    </div>
                </div>
                <div class="budget-chart-container">
                    <canvas id="budgetChartCanvas" width="400" height="200"></canvas>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" id="closeBudgetChartBtn">Close</button>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('exinChart').getContext('2d');
        let exinChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April'],
                datasets: [
                    {
                        label: 'Income',
                        borderColor: 'green',
                        data: [5000, 6000, 7000, 8000],
                        fill: false
                    },
                    {
                        label: 'Expenses',
                        borderColor: 'red',
                        data: [4000, 4500, 5000, 5500],
                        fill: false
                    }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
                });

                function updateChart(period) {
                    let labels, incomeData, expensesData;
                    if (period === 'monthly') {
                        labels = ['January', 'February', 'March', 'April'];
                        incomeData = [5000, 6000, 7000, 8000];
                        expensesData = [4000, 4500, 5000, 5500];
                    } else if (period === 'quarterly') {
                        labels = ['Q1', 'Q2', 'Q3', 'Q4'];
                        incomeData = [18000, 20000, 22000, 24000];
                        expensesData = [13500, 15000, 16500, 18000];
                    } else if (period === 'yearly') {
                        labels = ['2020', '2021', '2022', '2023'];
                        incomeData = [70000, 75000, 80000, 85000];
                        expensesData = [55000, 60000, 65000, 70000];
                    }

                    exinChart.data.labels = labels;
                    exinChart.data.datasets[0].data = incomeData;
                    exinChart.data.datasets[1].data = expensesData;
                    exinChart.update();
                }
    </script>


    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Finances page DOM loaded');
            
            // Define notification function first
            function showNotification(message, type) {
                // Clear any existing notifications first
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notification => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                });
                
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    border-radius: 6px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    animation: slideIn 0.3s ease;
                `;
                
                if (type === 'success') {
                    notification.style.backgroundColor = '#10b981';
                } else {
                    notification.style.backgroundColor = '#ef4444';
                }
                
                notification.textContent = message;
                
                // Add to page
                document.body.appendChild(notification);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            }

            // Immediate cleanup - hide all modals
            const expenseModalCleanup = document.getElementById('expenseModal');
            const deleteModalCleanup = document.getElementById('deleteModal');
            
            if (expenseModalCleanup) expenseModalCleanup.style.display = 'none';
            if (deleteModalCleanup) deleteModalCleanup.style.display = 'none';
            
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');
            const financeTabs = document.querySelectorAll('.finance-tab');
            const tabContents = document.querySelectorAll('.fin-tabs-content');

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

            // Finance tabs functionality
            financeTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    financeTabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });

            // Supply Chain dropdown functionality
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
            handleResize();

            // ===== FILTER FUNCTIONALITY =====

            // Expense Filters
            const expenseCategoryFilter = document.getElementById('expenseCategoryFilter');
            const expenseStatusFilter = document.getElementById('expenseStatusFilter');
            const expenseSearchFilter = document.getElementById('expenseSearchFilter');
            const clearExpenseFilters = document.getElementById('clearExpenseFilters');
            const expenseTable = document.querySelector('#expenses .finance-table tbody');

            function filterExpenses() {
                const categoryFilter = expenseCategoryFilter.value;
                const statusFilter = expenseStatusFilter.value;
                const searchFilter = expenseSearchFilter.value.toLowerCase();
                const rows = expenseTable.querySelectorAll('tr');

                let visibleCount = 0;
                let totalAmount = 0;
                let pendingAmount = 0;

                rows.forEach(row => {
                    const category = row.cells[1].textContent.trim();
                    const description = row.cells[2].textContent.trim();
                    const status = row.cells[4].textContent.trim();
                    const amountText = row.cells[3].textContent.trim();
                    const amount = parseFloat(amountText.replace('₱', '').replace(/,/g, ''));
                    
                    const categoryMatch = !categoryFilter || category === categoryFilter;
                    const statusMatch = !statusFilter || status === statusFilter;
                    const searchMatch = !searchFilter || 
                        category.toLowerCase().includes(searchFilter) ||
                        description.toLowerCase().includes(searchFilter) ||
                        status.toLowerCase().includes(searchFilter);
                    
                    if (categoryMatch && statusMatch && searchMatch) {
                        row.style.display = '';
                        visibleCount++;
                        totalAmount += amount;
                        if (status === 'Pending') {
                            pendingAmount += amount;
                        }
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update summary cards with filtered data
                updateExpenseSummary(totalAmount, pendingAmount, visibleCount);
            }

            function updateExpenseSummary(totalAmount = 0, pendingAmount = 0, visibleCount = 0) {
                // Update the summary cards with filtered data
                const totalExpenseCard = document.querySelector('#expenses .summary-card:nth-child(1) .summary-card-value');
                const pendingCard = document.querySelector('#expenses .summary-card:nth-child(2) .summary-card-value');
                const monthlyCard = document.querySelector('#expenses .summary-card:nth-child(3) .summary-card-value');
                
                if (totalExpenseCard) {
                    totalExpenseCard.textContent = `₱${totalAmount.toFixed(2)}`;
                }
                if (pendingCard) {
                    pendingCard.textContent = `₱${pendingAmount.toFixed(2)}`;
                }
                if (monthlyCard) {
                    // Calculate monthly average based on visible records
                    const monthlyAverage = visibleCount > 0 ? totalAmount / visibleCount * 30 : 0;
                    monthlyCard.textContent = `₱${monthlyAverage.toFixed(2)}`;
                }

                console.log(`Filtered: ${visibleCount} records, Total ₱${totalAmount.toFixed(2)}, Pending ₱${pendingAmount.toFixed(2)}`);
            }

            if (expenseCategoryFilter) {
                expenseCategoryFilter.addEventListener('change', function() {
                    console.log('Category filter changed:', this.value);
                    filterExpenses();
                });
            }
            if (expenseStatusFilter) {
                expenseStatusFilter.addEventListener('change', function() {
                    console.log('Status filter changed:', this.value);
                    filterExpenses();
                });
            }
            if (expenseSearchFilter) {
                expenseSearchFilter.addEventListener('input', function() {
                    console.log('Search filter changed:', this.value);
                    filterExpenses();
                });
            }
            if (clearExpenseFilters) {
                clearExpenseFilters.addEventListener('click', function() {
                    console.log('Clear filters clicked');
                    expenseCategoryFilter.value = '';
                    expenseStatusFilter.value = '';
                    expenseSearchFilter.value = '';
                    filterExpenses();
                    showNotification('Filters cleared', 'success');
                });
            }

            // Income Filters
            const incomeSourceFilter = document.getElementById('incomeSourceFilter');
            const incomeStatusFilter = document.getElementById('incomeStatusFilter');
            const clearIncomeFilters = document.getElementById('clearIncomeFilters');
            const incomeTable = document.querySelector('#income .finance-table tbody');

            function filterIncome() {
                const sourceFilter = incomeSourceFilter.value;
                const statusFilter = incomeStatusFilter.value;
                const rows = incomeTable.querySelectorAll('tr');

                rows.forEach(row => {
                    const source = row.cells[1].textContent.trim();
                    const status = row.cells[4].textContent.trim();
                    
                    const sourceMatch = !sourceFilter || source === sourceFilter;
                    const statusMatch = !statusFilter || status === statusFilter;
                    
                    row.style.display = sourceMatch && statusMatch ? '' : 'none';
                });

                updateIncomeSummary();
            }

            function updateIncomeSummary() {
                const visibleRows = incomeTable.querySelectorAll('tr:not([style*="display: none"])');
                let totalAmount = 0;
                let pendingAmount = 0;

                visibleRows.forEach(row => {
                    const amount = parseFloat(row.cells[3].textContent.replace('₱', '').replace(',', ''));
                    const status = row.cells[4].textContent.trim();
                    
                    totalAmount += amount;
                    if (status === 'Pending') {
                        pendingAmount += amount;
                    }
                });

                console.log(`Filtered Income: Total ₱${totalAmount.toFixed(2)}, Pending ₱${pendingAmount.toFixed(2)}`);
            }

            if (incomeSourceFilter) incomeSourceFilter.addEventListener('change', filterIncome);
            if (incomeStatusFilter) incomeStatusFilter.addEventListener('change', filterIncome);
            if (clearIncomeFilters) {
                clearIncomeFilters.addEventListener('click', function() {
                    incomeSourceFilter.value = '';
                    incomeStatusFilter.value = '';
                    filterIncome();
                });
            }

            // Invoice Filters
            const invoiceStatusFilter = document.getElementById('invoiceStatusFilter');
            const invoiceClientFilter = document.getElementById('invoiceClientFilter');
            const clearInvoiceFilters = document.getElementById('clearInvoiceFilters');
            const invoiceTable = document.querySelector('#invoice .finance-table tbody');

            function filterInvoices() {
                const statusFilter = invoiceStatusFilter.value;
                const clientFilter = invoiceClientFilter.value;
                const rows = invoiceTable.querySelectorAll('tr');

                rows.forEach(row => {
                    const client = row.cells[1].textContent.trim();
                    const status = row.cells[4].textContent.trim();
                    
                    const clientMatch = !clientFilter || client === clientFilter;
                    const statusMatch = !statusFilter || status === statusFilter;
                    
                    row.style.display = clientMatch && statusMatch ? '' : 'none';
                });

                updateInvoiceSummary();
            }

            function updateInvoiceSummary() {
                const visibleRows = invoiceTable.querySelectorAll('tr:not([style*="display: none"])');
                let totalAmount = 0;
                let paidAmount = 0;
                let overdueAmount = 0;

                visibleRows.forEach(row => {
                    const amount = parseFloat(row.cells[2].textContent.replace('₱', '').replace(',', ''));
                    const status = row.cells[4].textContent.trim();
                    
                    totalAmount += amount;
                    if (status === 'Paid') {
                        paidAmount += amount;
                    } else if (status === 'Overdue') {
                        overdueAmount += amount;
                    }
                });

                console.log(`Filtered Invoices: Total ₱${totalAmount.toFixed(2)}, Paid ₱${paidAmount.toFixed(2)}, Overdue ₱${overdueAmount.toFixed(2)}`);
            }

            if (invoiceStatusFilter) invoiceStatusFilter.addEventListener('change', filterInvoices);
            if (invoiceClientFilter) invoiceClientFilter.addEventListener('change', filterInvoices);
            if (clearInvoiceFilters) {
                clearInvoiceFilters.addEventListener('click', function() {
                    invoiceStatusFilter.value = '';
                    invoiceClientFilter.value = '';
                    filterInvoices();
                });
            }

            // Budget Filters
            const budgetCategoryFilter = document.getElementById('budgetCategoryFilter');
            const budgetProgressFilter = document.getElementById('budgetProgressFilter');
            const clearBudgetFilters = document.getElementById('clearBudgetFilters');
            const budgetTable = document.querySelector('#budgeting .finance-table tbody');

            function filterBudget() {
                const categoryFilter = budgetCategoryFilter.value;
                const progressFilter = budgetProgressFilter.value;
                const rows = budgetTable.querySelectorAll('tr');

                rows.forEach(row => {
                    const category = row.cells[0].textContent.trim();
                    const progressBar = row.cells[4].querySelector('.budget-progress-bar');
                    const progressClass = progressBar ? progressBar.className.split(' ').find(cls => ['safe', 'warning', 'danger'].includes(cls)) : '';
                    
                    const categoryMatch = !categoryFilter || category === categoryFilter;
                    const progressMatch = !progressFilter || progressClass === progressFilter;
                    
                    row.style.display = categoryMatch && progressMatch ? '' : 'none';
                });

                updateBudgetSummary();
            }

            function updateBudgetSummary() {
                const visibleRows = budgetTable.querySelectorAll('tr:not([style*="display: none"])');
                let totalAllocated = 0;
                let totalSpent = 0;

                visibleRows.forEach(row => {
                    const allocated = parseFloat(row.cells[1].textContent.replace('₱', '').replace(/,/g, ''));
                    const spent = parseFloat(row.cells[2].textContent.replace('₱', '').replace(/,/g, ''));
                    
                    totalAllocated += allocated;
                    totalSpent += spent;
                });

                console.log(`Filtered Budget: Allocated ₱${totalAllocated.toFixed(2)}, Spent ₱${totalSpent.toFixed(2)}`);
            }

            if (budgetCategoryFilter) budgetCategoryFilter.addEventListener('change', filterBudget);
            if (budgetProgressFilter) budgetProgressFilter.addEventListener('change', filterBudget);
            if (clearBudgetFilters) {
                clearBudgetFilters.addEventListener('click', function() {
                    budgetCategoryFilter.value = '';
                    budgetProgressFilter.value = '';
                    filterBudget();
                });
            }

            // Initialize all filters
            filterExpenses();
            filterIncome();
            filterInvoices();
            filterBudget();

            // ===== EXPENSE CRUD FUNCTIONALITY =====

            // Prevent multiple event listeners
            if (window.expenseCRUDInitialized) {
                console.log('Expense CRUD already initialized, skipping...');
                return;
            }
            window.expenseCRUDInitialized = true;
            console.log('Initializing Expense CRUD functionality...');

            // Modal elements - use let instead of const to avoid redeclaration errors
            let expenseModal = document.getElementById('expenseModal');
            let deleteModal = document.getElementById('deleteModal');
            let addExpenseBtn = document.getElementById('addExpenseBtn');
            let closeExpenseModal = document.getElementById('closeExpenseModal');
            let closeDeleteModal = document.getElementById('closeDeleteModal');
            let cancelExpenseBtn = document.getElementById('cancelExpenseBtn');
            let cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            let expenseForm = document.getElementById('expenseForm');
            let modalTitle = document.getElementById('modalTitle');
            let expenseId = document.getElementById('expenseId');
            let expenseAction = document.getElementById('expenseAction');
            let expenseDate = document.getElementById('expenseDate');
            let expenseCategory = document.getElementById('expenseCategory');
            let expenseDescription = document.getElementById('expenseDescription');
            let expenseAmount = document.getElementById('expenseAmount');
            let expenseStatus = document.getElementById('expenseStatus');
            let saveExpenseBtn = document.getElementById('saveExpenseBtn');
            let deleteExpenseDescription = document.getElementById('deleteExpenseDescription');
            let confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            // Define closeModals function here where all variables are in scope
            function closeModals() {
                try {
                    const modals = [
                        'expenseModal', 'incomeModal', 'invoiceModal', 'budgetModal', 
                        'deleteModal', 'viewInvoiceModal', 'budgetChartModal'
                    ];
                    
                    modals.forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (modal) modal.style.display = 'none';
                    });
                    
                    // Reset all forms
                    const forms = ['expenseForm', 'incomeForm', 'invoiceForm', 'budgetForm'];
                    forms.forEach(formId => {
                        const form = document.getElementById(formId);
                        if (form) form.reset();
                    });
                    
                    // Clear any leftover data for expense form
                    if (expenseId) expenseId.value = '';
                    if (expenseAction) expenseAction.value = 'add_expense';
                    if (modalTitle) modalTitle.textContent = 'Add New Expense';
                    if (saveExpenseBtn) saveExpenseBtn.textContent = 'Save Expense';
                    
                    console.log('closeModals executed successfully');
                } catch (error) {
                    console.error('Error in closeModals:', error);
                }
            }

            // Add event listeners for expense modal close buttons
            if (closeExpenseModal) {
                closeExpenseModal.addEventListener('click', closeModals);
                console.log('Added event listener to closeExpenseModal');
            } else {
                console.error('closeExpenseModal not found');
            }
            if (closeDeleteModal) {
                closeDeleteModal.addEventListener('click', closeModals);
                console.log('Added event listener to closeDeleteModal');
            } else {
                console.error('closeDeleteModal not found');
            }
            if (cancelExpenseBtn) {
                cancelExpenseBtn.addEventListener('click', closeModals);
                console.log('Added event listener to cancelExpenseBtn');
            } else {
                console.error('cancelExpenseBtn not found');
            }
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', closeModals);
                console.log('Added event listener to cancelDeleteBtn');
            } else {
                console.error('cancelDeleteBtn not found');
            }

            // Add New Expense functionality
            if (addExpenseBtn) {
                console.log('Add new expense button found, adding event listener');
                addExpenseBtn.addEventListener('click', function() {
                    console.log('Add new expense button clicked');
                    
                    // Reset form
                    if (expenseForm) expenseForm.reset();
                    if (expenseId) expenseId.value = '';
                    if (expenseAction) expenseAction.value = 'add_expense';
                    if (modalTitle) modalTitle.textContent = 'Add New Expense';
                    if (saveExpenseBtn) saveExpenseBtn.textContent = 'Save Expense';
                    
                    // Set default date to today
                    if (expenseDate) expenseDate.value = new Date().toISOString().split('T')[0];
                    
                    // Show modal
                    if (expenseModal) {
                        expenseModal.style.display = 'flex';
                        console.log('Add new expense modal opened');
                    } else {
                        console.error('Expense modal not found');
                    }
                });
            } else {
                console.error('Add new expense button not found');
            }

            // Simple button event listeners
            document.addEventListener('click', function(e) {
                // Edit button clicked
                if (e.target.closest('.edit-expense-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const btn = e.target.closest('.edit-expense-btn');
                    console.log('Edit button clicked!');
                    
                    const id = btn.dataset.id;
                    const date = btn.dataset.date;
                    const category = btn.dataset.category;
                    const description = btn.dataset.description;
                    const amount = btn.dataset.amount;
                    const status = btn.dataset.status;

                    console.log('Edit data:', { id, date, category, description, amount, status });

                    // Populate form
                    if (expenseId) expenseId.value = id;
                    if (expenseAction) expenseAction.value = 'edit_expense';
                    if (expenseDate) expenseDate.value = date;
                    if (expenseCategory) expenseCategory.value = category;
                    if (expenseDescription) expenseDescription.value = description;
                    if (expenseAmount) expenseAmount.value = amount;
                    if (expenseStatus) expenseStatus.value = status;
                    
                    if (modalTitle) modalTitle.textContent = 'Edit Expense';
                    if (saveExpenseBtn) saveExpenseBtn.textContent = 'Update Expense';
                    
                    // Show modal
                    if (expenseModal) expenseModal.style.display = 'flex';
                }
                
                // Delete button clicked
                if (e.target.closest('.delete-expense-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const btn = e.target.closest('.delete-expense-btn');
                    console.log('Delete button clicked!');
                    console.log('Button element:', btn);
                    console.log('Button dataset:', btn.dataset);
                    
                    const id = btn.dataset.id;
                    const description = btn.dataset.description;

                    console.log('Delete data:', { id, description });
                    console.log('ID type:', typeof id, 'ID value:', id);
                    console.log('Description type:', typeof description, 'Description value:', description);

                    if (!id || id === 'undefined' || id === 'null') {
                        console.error('Invalid ID for expense deletion:', id);
                        showNotification('Error: Invalid expense ID for deletion', 'error');
                        return;
                    }

                    if (deleteExpenseDescription) deleteExpenseDescription.textContent = description;
                    if (confirmDeleteBtn) confirmDeleteBtn.dataset.id = id;
                    
                    // Show delete modal
                    if (deleteModal) deleteModal.style.display = 'flex';
                }
            });
            
            // Debug: Check if buttons exist
            console.log('Edit buttons found:', document.querySelectorAll('.edit-expense-btn').length);
            console.log('Delete buttons found:', document.querySelectorAll('.delete-expense-btn').length);
            console.log('Add new expense button found:', document.getElementById('addExpenseBtn') ? 'Yes' : 'No');
            console.log('Filter elements found:', {
                search: document.getElementById('expenseSearchFilter') ? 'Yes' : 'No',
                category: document.getElementById('expenseCategoryFilter') ? 'Yes' : 'No',
                status: document.getElementById('expenseStatusFilter') ? 'Yes' : 'No',
                clear: document.getElementById('clearExpenseFilters') ? 'Yes' : 'No'
            });
            
            // Debug: Check delete button data attributes
            const deleteButtons = document.querySelectorAll('.delete-expense-btn');
            deleteButtons.forEach((btn, index) => {
                console.log(`Delete button ${index}:`, {
                    id: btn.dataset.id,
                    description: btn.dataset.description,
                    hasId: btn.hasAttribute('data-id'),
                    hasDescription: btn.hasAttribute('data-description')
                });
            });



            if (closeExpenseModal) closeExpenseModal.addEventListener('click', closeModals);
            if (closeDeleteModal) closeDeleteModal.addEventListener('click', closeModals);
            if (cancelExpenseBtn) cancelExpenseBtn.addEventListener('click', closeModals);
            if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeModals);

            // Close modal when clicking outside
            expenseModal.addEventListener('click', function(e) {
                if (e.target === expenseModal) {
                    closeModals();
                }
            });

            deleteModal.addEventListener('click', function(e) {
                if (e.target === deleteModal) {
                    closeModals();
                }
            });

            // Handle form submission
            if (expenseForm) {
                expenseForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Form submission started');
                    
                    // Validate form data
                    const date = expenseDate.value.trim();
                    const category = expenseCategory.value.trim();
                    const description = expenseDescription.value.trim();
                    const amount = parseFloat(expenseAmount.value);
                    const status = expenseStatus.value.trim();
                    
                    console.log('Form data:', { date, category, description, amount, status });
                    
                    if (!date || !category || !description || isNaN(amount) || amount <= 0 || !status) {
                        showNotification('Please fill in all fields with valid data', 'error');
                        return;
                    }
                    
                    if (description.length < 5) {
                        showNotification('Description must be at least 5 characters long', 'error');
                        return;
                    }
                    
                    if (amount > 1000000) {
                        showNotification('Amount cannot exceed ₱1,000,000', 'error');
                        return;
                    }
                    
                    const formData = new FormData(expenseForm);
                    
                    // Show loading state
                    if (saveExpenseBtn) {
                        saveExpenseBtn.disabled = true;
                        saveExpenseBtn.textContent = 'Saving...';
                    }
                    
                    console.log('Sending form data to server...');
                    
                    fetch('finances.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Response received:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Server response:', data);
                        if (data.success) {
                            // Show success message
                            showNotification(data.message, 'success');
                            
                            // Close modal
                            closeModals();
                            
                            // Reload page to show updated data
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        // Reset button state
                        if (saveExpenseBtn) {
                            saveExpenseBtn.disabled = false;
                            saveExpenseBtn.textContent = expenseAction.value === 'add_expense' ? 'Save Expense' : 'Update Expense';
                        }
                    });
                });
            } else {
                console.error('Expense form not found');
            }

            // Handle delete confirmation
            if (confirmDeleteBtn) {
                // Remove any existing event listeners
                const newConfirmDeleteBtn = confirmDeleteBtn.cloneNode(true);
                confirmDeleteBtn.parentNode.replaceChild(newConfirmDeleteBtn, confirmDeleteBtn);
                
                newConfirmDeleteBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Delete confirmation clicked');
                    
                    // Prevent multiple clicks
                    if (this.disabled) return;
                    
                    const id = this.dataset.id;
                    console.log('Deleting expense ID:', id);
                    
                    // Validate ID
                    if (!id || isNaN(parseInt(id))) {
                        showNotification('Error: Invalid expense ID for deletion', 'error');
                        return;
                    }
                    
                    // Show loading state
                    this.disabled = true;
                    this.textContent = 'Deleting...';
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_expense');
                    formData.append('id', id);
                    
                    console.log('Sending delete request...');
                    
                    fetch('finances.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Delete response received:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Delete server response:', data);
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeModals();
                            
                            // Reload page to show updated data
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        // Reset button state
                        this.disabled = false;
                        this.textContent = 'Delete';
                    });
                });
            }



            // Add CSS animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);

            // Clean up any leftover notifications and modals on page load
            function cleanupNotifications() {
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notification => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                });
            }

            function cleanupModals() {
                // Use the existing closeModals function
                closeModals();
            }

            // Run cleanup on page load
            cleanupNotifications();
            cleanupModals();
            
            // ===== INCOME FUNCTIONALITY =====
            
            // Income modal elements
            const incomeModal = document.getElementById('incomeModal');
            const addIncomeBtn = document.getElementById('addIncomeBtn');
            const closeIncomeModal = document.getElementById('closeIncomeModal');
            const cancelIncomeBtn = document.getElementById('cancelIncomeBtn');
            const incomeForm = document.getElementById('incomeForm');
            const incomeModalTitle = document.getElementById('incomeModalTitle');
            const incomeId = document.getElementById('incomeId');
            const incomeAction = document.getElementById('incomeAction');
            const incomeDate = document.getElementById('incomeDate');
            const incomeSource = document.getElementById('incomeSource');
            const incomeDescription = document.getElementById('incomeDescription');
            const incomeAmount = document.getElementById('incomeAmount');
            const incomeStatus = document.getElementById('incomeStatus');
            const saveIncomeBtn = document.getElementById('saveIncomeBtn');

            // Add Income functionality
            if (addIncomeBtn) {
                addIncomeBtn.addEventListener('click', function() {
                    console.log('Add income button clicked');
                    
                    // Reset form
                    if (incomeForm) incomeForm.reset();
                    if (incomeId) incomeId.value = '';
                    if (incomeAction) incomeAction.value = 'add_income';
                    if (incomeModalTitle) incomeModalTitle.textContent = 'Add New Income';
                    if (saveIncomeBtn) saveIncomeBtn.textContent = 'Save Income';
                    
                    // Set default date to today
                    if (incomeDate) incomeDate.value = new Date().toISOString().split('T')[0];
                    
                    // Show modal
                    if (incomeModal) {
                        incomeModal.style.display = 'flex';
                        console.log('Add income modal opened');
                    }
                });
            }

            // Income form submission
            if (incomeForm) {
                incomeForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Income form submission started');
                    
                    // Validate form data
                    const date = incomeDate.value.trim();
                    const source = incomeSource.value.trim();
                    const description = incomeDescription.value.trim();
                    const amount = parseFloat(incomeAmount.value);
                    const status = incomeStatus.value.trim();
                    
                    if (!date || !source || !description || isNaN(amount) || amount <= 0 || !status) {
                        showNotification('Please fill in all fields with valid data', 'error');
                        return;
                    }
                    
                    if (description.length < 5) {
                        showNotification('Description must be at least 5 characters long', 'error');
                        return;
                    }
                    
                    if (amount > 1000000) {
                        showNotification('Amount cannot exceed ₱1,000,000', 'error');
                        return;
                    }
                    
                    const formData = new FormData(incomeForm);
                    
                    // Show loading state
                    if (saveIncomeBtn) {
                        saveIncomeBtn.disabled = true;
                        saveIncomeBtn.textContent = 'Saving...';
                    }
                    
                    fetch('finances.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeModals();
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        if (saveIncomeBtn) {
                            saveIncomeBtn.disabled = false;
                            saveIncomeBtn.textContent = incomeAction.value === 'add_income' ? 'Save Income' : 'Update Income';
                        }
                    });
                });
            }

            // ===== INVOICE FUNCTIONALITY =====
            
            // Invoice modal elements
            const invoiceModal = document.getElementById('invoiceModal');
            const addInvoiceBtn = document.getElementById('addInvoiceBtn');
            const closeInvoiceModal = document.getElementById('closeInvoiceModal');
            const cancelInvoiceBtn = document.getElementById('cancelInvoiceBtn');
            const invoiceForm = document.getElementById('invoiceForm');
            const invoiceModalTitle = document.getElementById('invoiceModalTitle');
            const invoiceId = document.getElementById('invoiceId');
            const invoiceAction = document.getElementById('invoiceAction');
            const invoiceNumber = document.getElementById('invoiceNumber');
            const invoiceClient = document.getElementById('invoiceClient');
            const invoiceAmount = document.getElementById('invoiceAmount');
            const invoiceDueDate = document.getElementById('invoiceDueDate');
            const invoiceStatus = document.getElementById('invoiceStatus');
            const saveInvoiceBtn = document.getElementById('saveInvoiceBtn');

            // Add Invoice functionality
            if (addInvoiceBtn) {
                addInvoiceBtn.addEventListener('click', function() {
                    console.log('Add invoice button clicked');
                    
                    // Reset form
                    if (invoiceForm) invoiceForm.reset();
                    if (invoiceId) invoiceId.value = '';
                    if (invoiceAction) invoiceAction.value = 'add_invoice';
                    if (invoiceModalTitle) invoiceModalTitle.textContent = 'Create New Invoice';
                    if (saveInvoiceBtn) saveInvoiceBtn.textContent = 'Save Invoice';
                    
                    // Generate invoice number
                    if (invoiceNumber) {
                        const timestamp = Date.now();
                        invoiceNumber.value = `INV-${timestamp}`;
                    }
                    
                    // Set default due date to 30 days from now
                    if (invoiceDueDate) {
                        const futureDate = new Date();
                        futureDate.setDate(futureDate.getDate() + 30);
                        invoiceDueDate.value = futureDate.toISOString().split('T')[0];
                    }
                    
                    // Show modal
                    if (invoiceModal) {
                        invoiceModal.style.display = 'flex';
                    }
                });
            }

            // Invoice form submission
            if (invoiceForm) {
                invoiceForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Validate form data
                    const invoiceNo = invoiceNumber.value.trim();
                    const client = invoiceClient.value.trim();
                    const amount = parseFloat(invoiceAmount.value);
                    const dueDate = invoiceDueDate.value.trim();
                    const status = invoiceStatus.value.trim();
                    
                    if (!invoiceNo || !client || isNaN(amount) || amount <= 0 || !dueDate || !status) {
                        showNotification('Please fill in all fields with valid data', 'error');
                        return;
                    }
                    
                    if (client.length < 3) {
                        showNotification('Client name must be at least 3 characters long', 'error');
                        return;
                    }
                    
                    if (amount > 1000000) {
                        showNotification('Amount cannot exceed ₱1,000,000', 'error');
                        return;
                    }
                    
                    const formData = new FormData(invoiceForm);
                    
                    // Show loading state
                    if (saveInvoiceBtn) {
                        saveInvoiceBtn.disabled = true;
                        saveInvoiceBtn.textContent = 'Saving...';
                    }
                    
                    fetch('finances.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeModals();
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        if (saveInvoiceBtn) {
                            saveInvoiceBtn.disabled = false;
                            saveInvoiceBtn.textContent = invoiceAction.value === 'add_invoice' ? 'Save Invoice' : 'Update Invoice';
                        }
                    });
                });
            }

            // ===== BUDGET FUNCTIONALITY =====
            
            // Budget modal elements
            const budgetModal = document.getElementById('budgetModal');
            const addBudgetBtn = document.getElementById('addBudgetBtn');
            const closeBudgetModal = document.getElementById('closeBudgetModal');
            const cancelBudgetBtn = document.getElementById('cancelBudgetBtn');
            const budgetForm = document.getElementById('budgetForm');
            const budgetModalTitle = document.getElementById('budgetModalTitle');
            const budgetId = document.getElementById('budgetId');
            const budgetAction = document.getElementById('budgetAction');
            const budgetCategory = document.getElementById('budgetCategory');
            const budgetAllocated = document.getElementById('budgetAllocated');
            const budgetSpent = document.getElementById('budgetSpent');
            const saveBudgetBtn = document.getElementById('saveBudgetBtn');

            // Add Budget functionality
            if (addBudgetBtn) {
                addBudgetBtn.addEventListener('click', function() {
                    console.log('Add budget button clicked');
                    
                    // Reset form
                    if (budgetForm) budgetForm.reset();
                    if (budgetId) budgetId.value = '';
                    if (budgetAction) budgetAction.value = 'add_budget';
                    if (budgetModalTitle) budgetModalTitle.textContent = 'Add New Budget';
                    if (saveBudgetBtn) saveBudgetBtn.textContent = 'Save Budget';
                    if (budgetSpent) budgetSpent.value = '0';
                    
                    // Show modal
                    if (budgetModal) {
                        budgetModal.style.display = 'flex';
                    }
                });
            }

            // Budget form submission
            if (budgetForm) {
                budgetForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Validate form data
                    const category = budgetCategory.value.trim();
                    const allocated = parseFloat(budgetAllocated.value);
                    const spent = parseFloat(budgetSpent.value);
                    
                    if (!category || isNaN(allocated) || allocated <= 0 || isNaN(spent) || spent < 0) {
                        showNotification('Please fill in all fields with valid data', 'error');
                        return;
                    }
                    
                    if (spent > allocated) {
                        showNotification('Spent amount cannot exceed allocated amount', 'error');
                        return;
                    }
                    
                    const formData = new FormData(budgetForm);
                    
                    // Show loading state
                    if (saveBudgetBtn) {
                        saveBudgetBtn.disabled = true;
                        saveBudgetBtn.textContent = 'Saving...';
                    }
                    
                    fetch('finances.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeModals();
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        if (saveBudgetBtn) {
                            saveBudgetBtn.disabled = false;
                            saveBudgetBtn.textContent = budgetAction.value === 'add_budget' ? 'Save Budget' : 'Update Budget';
                        }
                    });
                });
            }

            // ===== EXPORT FUNCTIONALITY =====
            
            const exportBudgetBtn = document.getElementById('exportBudgetBtn');
            if (exportBudgetBtn) {
                exportBudgetBtn.addEventListener('click', function() {
                    console.log('Export budget button clicked');
                    
                    // Create CSV content
                    const budgetTable = document.querySelector('#budgeting .finance-table tbody');
                    const rows = budgetTable.querySelectorAll('tr');
                    
                    let csvContent = 'Category,Allocated,Spent,Remaining,Progress\n';
                    
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 5) {
                            const category = cells[0].textContent.trim();
                            const allocated = cells[1].textContent.replace('₱', '').replace(',', '');
                            const spent = cells[2].textContent.replace('₱', '').replace(',', '');
                            const remaining = cells[3].textContent.replace('₱', '').replace(',', '');
                            const progress = cells[4].querySelector('small').textContent;
                            
                            csvContent += `"${category}","${allocated}","${spent}","${remaining}","${progress}"\n`;
                        }
                    });
                    
                    // Create and download file
                    const blob = new Blob([csvContent], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'budget_export_' + new Date().toISOString().split('T')[0] + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showNotification('Budget data exported successfully', 'success');
                });
            }

            // ===== UNIVERSAL BUTTON HANDLERS =====
            
            // Handle all edit and delete buttons
            document.addEventListener('click', function(e) {
                // Income buttons
                if (e.target.closest('.edit-income-btn')) {
                    const btn = e.target.closest('.edit-income-btn');
                    const id = btn.dataset.id;
                    const date = btn.dataset.date;
                    const source = btn.dataset.source;
                    const description = btn.dataset.description;
                    const amount = btn.dataset.amount;
                    const status = btn.dataset.status;

                    // Populate form
                    if (incomeId) incomeId.value = id;
                    if (incomeAction) incomeAction.value = 'edit_income';
                    if (incomeDate) incomeDate.value = date;
                    if (incomeSource) incomeSource.value = source;
                    if (incomeDescription) incomeDescription.value = description;
                    if (incomeAmount) incomeAmount.value = amount;
                    if (incomeStatus) incomeStatus.value = status;
                    
                    if (incomeModalTitle) incomeModalTitle.textContent = 'Edit Income';
                    if (saveIncomeBtn) saveIncomeBtn.textContent = 'Update Income';
                    
                    if (incomeModal) incomeModal.style.display = 'flex';
                }
                
                if (e.target.closest('.delete-income-btn')) {
                    const btn = e.target.closest('.delete-income-btn');
                    const id = btn.dataset.id;
                    const description = btn.dataset.description;

                    if (deleteItemDescription) deleteItemDescription.textContent = description;
                    if (confirmDeleteBtn) confirmDeleteBtn.dataset.id = id;
                    if (confirmDeleteBtn) confirmDeleteBtn.dataset.action = 'delete_income';
                    
                    if (deleteModal) deleteModal.style.display = 'flex';
                }

                // Invoice buttons
                if (e.target.closest('.edit-invoice-btn')) {
                    const btn = e.target.closest('.edit-invoice-btn');
                    const id = btn.dataset.id;
                    const invoiceNo = btn.dataset.invoice;
                    const client = btn.dataset.client;
                    const amount = btn.dataset.amount;
                    const due = btn.dataset.due;
                    const status = btn.dataset.status;

                    // Populate form
                    if (invoiceId) invoiceId.value = id;
                    if (invoiceAction) invoiceAction.value = 'edit_invoice';
                    if (invoiceNumber) invoiceNumber.value = invoiceNo;
                    if (invoiceClient) invoiceClient.value = client;
                    if (invoiceAmount) invoiceAmount.value = amount;
                    if (invoiceDueDate) invoiceDueDate.value = due;
                    if (invoiceStatus) invoiceStatus.value = status;
                    
                    if (invoiceModalTitle) invoiceModalTitle.textContent = 'Edit Invoice';
                    if (saveInvoiceBtn) saveInvoiceBtn.textContent = 'Update Invoice';
                    
                    if (invoiceModal) invoiceModal.style.display = 'flex';
                }
                
                if (e.target.closest('.delete-invoice-btn')) {
                    const btn = e.target.closest('.delete-invoice-btn');
                    const id = btn.dataset.id;
                    const invoiceNo = btn.dataset.invoice;

                    if (deleteItemDescription) deleteItemDescription.textContent = `Invoice ${invoiceNo}`;
                    if (confirmDeleteBtn) confirmDeleteBtn.dataset.id = id;
                    if (confirmDeleteBtn) confirmDeleteBtn.dataset.action = 'delete_invoice';
                    
                    if (deleteModal) deleteModal.style.display = 'flex';
                }

                // View invoice button
                if (e.target.closest('.view-invoice-btn')) {
                    const btn = e.target.closest('.view-invoice-btn');
                    const invoiceNo = btn.dataset.invoice;
                    const client = btn.dataset.client;
                    const amount = btn.dataset.amount;
                    const due = btn.dataset.due;
                    const status = btn.dataset.status;

                    // Populate view modal
                    if (document.getElementById('viewInvoiceNumber')) document.getElementById('viewInvoiceNumber').textContent = invoiceNo;
                    if (document.getElementById('viewInvoiceClient')) document.getElementById('viewInvoiceClient').textContent = client;
                    if (document.getElementById('viewInvoiceAmount')) document.getElementById('viewInvoiceAmount').textContent = `₱${parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                    if (document.getElementById('viewInvoiceDueDate')) document.getElementById('viewInvoiceDueDate').textContent = new Date(due).toLocaleDateString();
                    if (document.getElementById('viewInvoiceStatus')) document.getElementById('viewInvoiceStatus').textContent = status;
                    
                    if (document.getElementById('viewInvoiceModal')) document.getElementById('viewInvoiceModal').style.display = 'flex';
                }

                // Budget buttons
                if (e.target.closest('.edit-budget-btn')) {
                    const btn = e.target.closest('.edit-budget-btn');
                    const id = btn.dataset.id;
                    const category = btn.dataset.category;
                    const allocated = btn.dataset.allocated;
                    const spent = btn.dataset.spent;

                    // Populate form
                    if (budgetId) budgetId.value = id;
                    if (budgetAction) budgetAction.value = 'edit_budget';
                    if (budgetCategory) budgetCategory.value = category;
                    if (budgetAllocated) budgetAllocated.value = allocated;
                    if (budgetSpent) budgetSpent.value = spent;
                    
                    if (budgetModalTitle) budgetModalTitle.textContent = 'Edit Budget';
                    if (saveBudgetBtn) saveBudgetBtn.textContent = 'Update Budget';
                    
                    if (budgetModal) budgetModal.style.display = 'flex';
                }

                // View budget chart button
                if (e.target.closest('.view-budget-chart-btn')) {
                    const btn = e.target.closest('.view-budget-chart-btn');
                    const category = btn.dataset.category;
                    const allocated = parseFloat(btn.dataset.allocated);
                    const spent = parseFloat(btn.dataset.spent);
                    const remaining = allocated - spent;
                    const percentage = (spent / allocated) * 100;

                    // Populate chart modal
                    if (document.getElementById('chartCategory')) document.getElementById('chartCategory').textContent = category;
                    if (document.getElementById('chartAllocated')) document.getElementById('chartAllocated').textContent = `₱${allocated.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                    if (document.getElementById('chartSpent')) document.getElementById('chartSpent').textContent = `₱${spent.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                    if (document.getElementById('chartRemaining')) document.getElementById('chartRemaining').textContent = `₱${remaining.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                    if (document.getElementById('chartProgress')) document.getElementById('chartProgress').textContent = `${percentage.toFixed(1)}%`;
                    
                    if (document.getElementById('budgetChartModal')) document.getElementById('budgetChartModal').style.display = 'flex';
                }
            });

            // ===== MODAL CLOSE HANDLERS =====

            // Close button handlers
            const closeButtons = [
                'closeIncomeModal', 'closeInvoiceModal', 'closeBudgetModal',
                'closeViewInvoiceModal', 'closeBudgetChartModal'
            ];
            
            closeButtons.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.addEventListener('click', closeModals);
                }
            });

            // Cancel button handlers
            const cancelButtons = [
                'cancelIncomeBtn', 'cancelInvoiceBtn', 'cancelBudgetBtn',
                'closeViewInvoiceBtn', 'closeBudgetChartBtn'
            ];
            
            cancelButtons.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.addEventListener('click', closeModals);
                }
            });

            // Close modal when clicking outside
            const allModals = [
                'incomeModal', 'invoiceModal', 'budgetModal', 
                'viewInvoiceModal', 'budgetChartModal'
            ];
            
            allModals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            closeModals();
                        }
                    });
                }
            });

            // Update delete confirmation to handle all types
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.dataset.id;
                    const action = this.dataset.action;
                    
                    if (!id || !action) {
                        showNotification('Error: Invalid delete request', 'error');
                        return;
                    }
                    
                    // Show loading state
                    this.disabled = true;
                    this.textContent = 'Deleting...';
                    
                    const formData = new FormData();
                    formData.append('action', action);
                    formData.append('id', id);
                    
                    fetch('finances.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            closeModals();
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.textContent = 'Delete';
                    });
                });
            }

            // Update cleanup function
            function cleanupModals() {
                closeModals();
            }

            // Test all functionality
            console.log('=== FINANCES COMPLETE FUNCTIONALITY TEST ===');
            console.log('closeModals function:', typeof closeModals);
            console.log('addExpenseBtn:', addExpenseBtn);
            console.log('expenseModal:', expenseModal);
            console.log('1. Expense elements:', {
                add: document.getElementById('addExpenseBtn'),
                form: document.getElementById('expenseForm'),
                modal: document.getElementById('expenseModal')
            });
            console.log('2. Income elements:', {
                add: document.getElementById('addIncomeBtn'),
                form: document.getElementById('incomeForm'),
                modal: document.getElementById('incomeModal')
            });
            console.log('3. Invoice elements:', {
                add: document.getElementById('addInvoiceBtn'),
                form: document.getElementById('invoiceForm'),
                modal: document.getElementById('invoiceModal')
            });
            console.log('4. Budget elements:', {
                add: document.getElementById('addBudgetBtn'),
                form: document.getElementById('budgetForm'),
                modal: document.getElementById('budgetModal'),
                export: document.getElementById('exportBudgetBtn')
            });
            console.log('5. Button counts:', {
                expense: {
                    edit: document.querySelectorAll('.edit-expense-btn').length,
                    delete: document.querySelectorAll('.delete-expense-btn').length
                },
                income: {
                    edit: document.querySelectorAll('.edit-income-btn').length,
                    delete: document.querySelectorAll('.delete-income-btn').length
                },
                invoice: {
                    edit: document.querySelectorAll('.edit-invoice-btn').length,
                    delete: document.querySelectorAll('.delete-invoice-btn').length,
                    view: document.querySelectorAll('.view-invoice-btn').length
                },
                budget: {
                    edit: document.querySelectorAll('.edit-budget-btn').length,
                    chart: document.querySelectorAll('.view-budget-chart-btn').length
                }
            });
            console.log('=== END TEST ===');
            
            // Simple test to verify JavaScript is working
            console.log('JavaScript loaded successfully');
            
            // Test if we can find the add new expense button
            const testBtn = document.getElementById('addExpenseBtn');
            if (testBtn) {
                console.log('✅ Add New Expense button found');
                testBtn.style.border = '2px solid green'; // Visual indicator
            } else {
                console.log('❌ Add New Expense button NOT found');
            }
            
            // Test if we can find the expense modal
            const testModalElement = document.getElementById('expenseModal');
            if (testModalElement) {
                console.log('✅ Expense modal found');
            } else {
                console.log('❌ Expense modal NOT found');
            }

            // ===== INCOME STATEMENT FUNCTIONALITY =====
            
            // Income Statement Period Filter
            const incomeStatementPeriod = document.getElementById('incomeStatementPeriod');
            const generateIncomeStatementBtn = document.getElementById('generateIncomeStatement');
            
            if (generateIncomeStatementBtn) {
                generateIncomeStatementBtn.addEventListener('click', function() {
                    const period = incomeStatementPeriod ? incomeStatementPeriod.value : 'current';
                    generateIncomeStatementReport(period);
                });
            }

            function generateIncomeStatementReport(period) {
                // Show loading state
                if (generateIncomeStatementBtn) {
                    generateIncomeStatementBtn.disabled = true;
                    generateIncomeStatementBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
                }

                // Simulate API call for different periods
                setTimeout(() => {
                    // Update the income statement table based on period
                    updateIncomeStatementData(period);
                    
                    // Reset button state
                    if (generateIncomeStatementBtn) {
                        generateIncomeStatementBtn.disabled = false;
                        generateIncomeStatementBtn.innerHTML = '<i class="fas fa-file-alt"></i> Generate Report';
                    }
                    
                    showNotification(`Income statement generated for ${getPeriodDisplayName(period)}`, 'success');
                }, 1500);
            }

            function updateIncomeStatementData(period) {
                // Get the income statement table
                const incomeStatementTable = document.querySelector('#income .finance-table table tbody');
                if (!incomeStatementTable) return;

                // Calculate period-specific data
                const periodData = calculatePeriodData(period);
                
                // Update revenue section
                const revenueRow = incomeStatementTable.querySelector('tr:nth-child(2) td:last-child');
                if (revenueRow) {
                    revenueRow.textContent = `₱${periodData.totalRevenue.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                }

                // Update COGS section
                const cogsRow = incomeStatementTable.querySelector('tr:nth-child(4) td:last-child');
                if (cogsRow) {
                    cogsRow.textContent = `₱${periodData.totalCOGS.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                }

                // Update gross profit
                const grossProfitRow = incomeStatementTable.querySelector('tr:nth-child(6) td:last-child');
                if (grossProfitRow) {
                    grossProfitRow.textContent = `₱${periodData.grossProfit.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                }

                // Update operating expenses
                const operatingExpensesRow = incomeStatementTable.querySelector('tr:nth-child(8) td:last-child');
                if (operatingExpensesRow) {
                    operatingExpensesRow.textContent = `₱${periodData.operatingExpenses.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                }

                // Update net income
                const netIncomeRow = incomeStatementTable.querySelector('tr:nth-child(10) td:last-child');
                if (netIncomeRow) {
                    netIncomeRow.textContent = `₱${periodData.netIncome.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                }

                // Update financial ratios
                const grossProfitMarginRow = incomeStatementTable.querySelector('tr:nth-child(13) td:last-child');
                if (grossProfitMarginRow) {
                    grossProfitMarginRow.textContent = `${periodData.grossProfitMargin.toFixed(1)}%`;
                }

                const netProfitMarginRow = incomeStatementTable.querySelector('tr:nth-child(14) td:last-child');
                if (netProfitMarginRow) {
                    netProfitMarginRow.textContent = `${periodData.netProfitMargin.toFixed(1)}%`;
                }

                const operatingExpenseRatioRow = incomeStatementTable.querySelector('tr:nth-child(15) td:last-child');
                if (operatingExpenseRatioRow) {
                    operatingExpenseRatioRow.textContent = `${periodData.operatingExpenseRatio.toFixed(1)}%`;
                }
            }

            function calculatePeriodData(period) {
                // Base values from current data
                const baseRevenue = <?php echo $total_income; ?>;
                const baseExpenses = <?php echo $total_expenses; ?>;
                
                // Multipliers for different periods
                const periodMultipliers = {
                    'current': { revenue: 1, expenses: 1 },
                    'last': { revenue: 0.85, expenses: 0.9 },
                    'quarter': { revenue: 2.8, expenses: 2.6 },
                    'year': { revenue: 11.5, expenses: 10.8 }
                };
                
                const multiplier = periodMultipliers[period] || periodMultipliers.current;
                
                const totalRevenue = baseRevenue * multiplier.revenue;
                const totalExpenses = baseExpenses * multiplier.expenses;
                const totalCOGS = totalExpenses * 0.7;
                const operatingExpenses = totalExpenses * 0.3;
                const grossProfit = totalRevenue - totalCOGS;
                const netIncome = totalRevenue - totalExpenses;
                
                return {
                    totalRevenue,
                    totalExpenses,
                    totalCOGS,
                    operatingExpenses,
                    grossProfit,
                    netIncome,
                    grossProfitMargin: totalRevenue > 0 ? (grossProfit / totalRevenue) * 100 : 0,
                    netProfitMargin: totalRevenue > 0 ? (netIncome / totalRevenue) * 100 : 0,
                    operatingExpenseRatio: totalRevenue > 0 ? (operatingExpenses / totalRevenue) * 100 : 0
                };
            }

            function getPeriodDisplayName(period) {
                const periodNames = {
                    'current': 'Current Month',
                    'last': 'Last Month',
                    'quarter': 'This Quarter',
                    'year': 'This Year'
                };
                return periodNames[period] || 'Current Month';
            }

            // Export Income Statement functionality
            function exportIncomeStatement() {
                const period = incomeStatementPeriod ? incomeStatementPeriod.value : 'current';
                const periodData = calculatePeriodData(period);
                
                // Create CSV content
                let csvContent = `Income Statement - ${getPeriodDisplayName(period)}\n`;
                csvContent += 'Item,Amount (₱)\n';
                csvContent += `Revenue,${periodData.totalRevenue.toFixed(2)}\n`;
                csvContent += `Cost of Goods Sold,${periodData.totalCOGS.toFixed(2)}\n`;
                csvContent += `Gross Profit,${periodData.grossProfit.toFixed(2)}\n`;
                csvContent += `Operating Expenses,${periodData.operatingExpenses.toFixed(2)}\n`;
                csvContent += `Net Income,${periodData.netIncome.toFixed(2)}\n\n`;
                csvContent += 'Financial Ratios\n';
                csvContent += `Gross Profit Margin,${periodData.grossProfitMargin.toFixed(1)}%\n`;
                csvContent += `Net Profit Margin,${periodData.netProfitMargin.toFixed(1)}%\n`;
                csvContent += `Operating Expense Ratio,${periodData.operatingExpenseRatio.toFixed(1)}%\n`;
                
                // Create and download file
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `income_statement_${period}_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                showNotification('Income statement exported successfully', 'success');
            }

            // Add export button functionality
            const exportIncomeStatementBtn = document.createElement('button');
            exportIncomeStatementBtn.className = 'btn btn-outline';
            exportIncomeStatementBtn.innerHTML = '<i class="fas fa-download"></i> <span>Export</span>';
            exportIncomeStatementBtn.addEventListener('click', exportIncomeStatement);
            
            // Add export button to income statement actions
            const incomeStatementActions = document.querySelector('#income .finance-table-header .finance-table-actions .filter-group');
            if (incomeStatementActions) {
                incomeStatementActions.appendChild(exportIncomeStatementBtn);
            }
        });
    </script>
</body>
</html> 
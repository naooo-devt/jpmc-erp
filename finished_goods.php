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

// Sample employee data - in a real application, this would come from the database
$employees = [
    [
        'id' => 'JPMC-HRD-025',
        'name' => 'ROBIN NOMBRANDO',
        'position' => 'PROCESS ENGINEER',
        'department' => 'Engineering',
        'date_hired' => '2022-03-01',
        'employment_type' => 'Regular',
        'status' => 'Active',
        'contact' => '09171234567',
        'email' => 'robin.nombrando@company.com'
    ],
    [
        'id' => 'JPMC-HRD-026',
        'name' => 'JONATHAN RAY Y. ANTIONIO',
        'position' => 'QA SUPERVISOR',
        'department' => 'Quality Assurance',
        'date_hired' => '2021-06-15',
        'employment_type' => 'Regular',
        'status' => 'Active',
        'contact' => '09181234567',
        'email' => 'jonathan.antonio@company.com'
    ],
    [
        'id' => 'JPMC-HRD-027',
        'name' => 'ALBERT B. ALACAPA',
        'position' => 'WAREHOUSEMAN',
        'department' => 'Warehouse',
        'date_hired' => '2023-01-10',
        'employment_type' => 'Probationary',
        'status' => 'Active',
        'contact' => '09191234567',
        'email' => 'albert.alacapa@company.com'
    ],
    [
        'id' => 'JPMC-HRD-028',
        'name' => 'MARDY AGUILAR',
        'position' => 'MOLD FABRICATOR',
        'department' => 'Production',
        'date_hired' => '2022-11-05',
        'employment_type' => 'Regular',
        'status' => 'Active',
        'contact' => '09201234567',
        'email' => 'mardy.aguilar@company.com'
    ],
    [
        'id' => 'JPMC-HRD-029',
        'name' => 'JOHN BRYAN FERRER',
        'position' => 'IT SUPERVISOR',
        'department' => 'IT Department',
        'date_hired' => '2020-02-20',
        'employment_type' => 'Regular',
        'status' => 'Active',
        'contact' => '09211234567',
        'email' => 'john.ferrer@company.com'
    ],
    [
        'id' => 'JPMC-HRD-030',
        'name' => 'ANABEL E. PANUNCIAR',
        'position' => 'MACHINE OPERATOR',
        'department' => 'Production',
        'date_hired' => '2023-03-15',
        'employment_type' => 'Probationary',
        'status' => 'Active',
        'contact' => '09221234567',
        'email' => 'anabel.panunciar@company.com'
    ],
    [
        'id' => 'JPMC-HRD-031',
        'name' => 'RICKY V. TONGOL',
        'position' => 'QUALITY CONTROL',
        'department' => 'Quality Assurance',
        'date_hired' => '2021-08-25',
        'employment_type' => 'Regular',
        'status' => 'Active',
        'contact' => '09231234567',
        'email' => 'ricky.tongol@company.com'
    ]
];

// Sample recruitment data
$applicants = [
    [
        'id' => 'APP-001',
        'name' => 'MARIA SANTOS',
        'position_applied' => 'QA SUPERVISOR',
        'date_applied' => '2024-06-01',
        'status' => 'Screening',
        'contact' => '09181234567',
        'email' => 'maria.santos@gmail.com',
        'resume' => '#',
        'assigned_hr' => 'JONATHAN RAY Y. ANTIONIO'
    ],
    [
        'id' => 'APP-002',
        'name' => 'JUAN DELA CRUZ',
        'position_applied' => 'PROCESS ENGINEER',
        'date_applied' => '2024-06-05',
        'status' => 'Interview',
        'contact' => '09191234567',
        'email' => 'juan.delacruz@gmail.com',
        'resume' => '#',
        'assigned_hr' => 'ROBIN NOMBRANDO'
    ]
];

// Sample HR functions data
$hr_functions = [
    [
        'employee_id' => 'JPMC-HRD-025',
        'name' => 'ROBIN NOMBRANDO',
        'leave_type' => 'Sick Leave',
        'date_filed' => '2024-05-20',
        'leave_duration' => '2024-05-21 to 2024-05-23',
        'status' => 'Approved',
        'benefit_type' => 'Health',
        'benefit_start' => '2024-06-01',
        'remarks' => 'Medical certificate provided'
    ],
    [
        'employee_id' => 'JPMC-HRD-026',
        'name' => 'JONATHAN RAY Y. ANTIONIO',
        'leave_type' => 'Vacation Leave',
        'date_filed' => '2024-05-15',
        'leave_duration' => '2024-05-16 to 2024-05-18',
        'status' => 'Pending',
        'benefit_type' => 'Paid Time Off',
        'benefit_start' => '2024-06-10',
        'remarks' => 'N/A'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Administration - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="images/logo.png">
    <style>
        /* System Administration Styles */
        .system-admin-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .system-admin-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--light-gray);
        }

        .system-admin-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-gray);
        }

        .system-admin-actions {
            display: flex;
            gap: 0.75rem;
        }

        .employee-table {
            width: 1800px;
            border-collapse: collapse;
            background: var(--white);
        }

        .employee-table th {
            background: var(--light-gray);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark-gray);
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .employee-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--dark-gray);
            vertical-align: middle;
        }

        .employee-table tr:hover {
            background: var(--light-gray);
        }

        .employee-id {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--primary-blue);
        }

        .employee-name {
            font-weight: 600;
            color: var(--dark-gray);
        }

        .employee-position {
            color: var(--gray);
            font-size: 0.875rem;
        }

        .employee-status {
            padding: 0.25rem 0.75rem;
            border-radius: 16px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .employee-status.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .employee-status.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }

        .employee-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            background: var(--light-gray);
            color: var(--gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: var(--primary-blue);
            color: var(--white);
            transform: scale(1.1);
        }

        .action-btn.view {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-blue);
        }

        .action-btn.edit {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .action-btn.delete {
            background: rgba(239, 68, 68, 0.1);
            color: var,--error);
        }

        .action-btn.view:hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        .action-btn.edit:hover {
            background: var(--warning);
            color: var(--white);
        }

        .action-btn.delete:hover {
            background: var(--error);
            color: var(--white);
        }

        .last-login {
            font-size: 0.75rem;
            color: var(--gray);
        }

        /* Filter and Search Styles */
        .filter-section {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--white);
        }

        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .filter-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.875rem;
            background: var(--white);
            color: var(--dark-gray);
            min-width: 150px;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-box {
            flex: 1;
            min-width: 200px;
        }

        /* Category Buttons */
        .category-buttons {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            background: var(--white);
            border-bottom: 1px solid var(--border-color);
        }

        .category-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: var(--light-gray);
            color: var(--primary-blue);
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 1rem;
            outline: none;
        }

        .category-btn:focus-visible {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
            background: var(--primary-blue);
            color: var(--white);
        }

        .category-btn.active {
            background: var(--primary-blue);
            color: var(--white);
        }

        .category-section {
            display: none;
        }

        .category-section.active {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .system-admin-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .system-admin-actions {
                justify-content: center;
            }

            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                justify-content: space-between;
            }

            .employee-table {
                font-size: 0.75rem;
            }

            .employee-table th,
            .employee-table td {
                padding: 0.5rem;
            }

            .employee-actions {
                flex-direction: column;
                gap: 0.25rem;
            }

            .action-btn {
                width: 28px;
                height: 28px;
            }
        }

        /* Table Scroll */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            overflow-x: unset;
            scrollbar-width: thin;
            scrollbar-color: var(--gray) var(--light-gray);
        }

        .table-scroll-x {
            overflow-x: auto;
            width: 100%;
            display: block;
        }

        .employee-table {
            width: 1800px;
            border-collapse: collapse;
            background: var(--white);
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: var(--gray);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: var(--dark-gray);
        }
    </style>
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
                <i class="fas fa-cog" style="font-size: 1.5rem; color: var(--dark-blue);"></i>
                <h1 class="header-title">System Administration</h1>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="system-admin-container">
                <div class="category-buttons" role="tablist" aria-label="System Administration Sections">
                    <button class="category-btn active" id="btnCompanyProfile" role="tab" aria-selected="true" aria-controls="sectionCompanyProfile" tabindex="0">Company Profile</button>
                    <button class="category-btn" id="btnCurrency" role="tab" aria-selected="false" aria-controls="sectionCurrency" tabindex="0">Currency Settings</button>
                    <button class="category-btn" id="btnChartOfAccounts" role="tab" aria-selected="false" aria-controls="sectionChartOfAccounts" tabindex="0">Chart of Accounts</button>
                    <button class="category-btn" id="btnPaymentTerms" role="tab" aria-selected="false" aria-controls="sectionPaymentTerms" tabindex="0">Payment Terms</button>
                    <button class="category-btn" id="btnAccessLogs" role="tab" aria-selected="false" aria-controls="sectionAccessLogs" tabindex="0">Access Logs</button>
                    <button class="category-btn" id="btnRetention" role="tab" aria-selected="false" aria-controls="sectionRetention" tabindex="0">Data Retention Policies</button>
                    <button class="category-btn" id="btnBackup" role="tab" aria-selected="false" aria-controls="sectionBackup" tabindex="0">Backup & Restore</button>
                    <button class="category-btn" id="btnNotifications" role="tab" aria-selected="false" aria-controls="sectionNotifications" tabindex="0">Notification Settings</button>
                </div>
                <!-- Company Profile Section -->
                <div class="category-section active" id="sectionCompanyProfile">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Company Profile</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-primary" id="editCompanyBtn">
                                <i class="fas fa-edit"></i>
                                <span>Edit Profile</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <tbody>
                                    <?php
                                    // Example: Fetch company profile from DB
                                    $result = $conn->query("SELECT * FROM company_profile LIMIT 1");
                                    $profile = $result ? $result->fetch_assoc() : null;
                                    ?>
                                    <tr><th>Company Name</th><td><?= $profile ? htmlspecialchars($profile['name']) : '' ?></td></tr>
                                    <tr><th>Address</th><td><?= $profile ? htmlspecialchars($profile['address']) : '' ?></td></tr>
                                    <tr><th>Tax ID</th><td><?= $profile ? htmlspecialchars($profile['tax_id']) : '' ?></td></tr>
                                    <tr><th>Contact</th><td><?= $profile ? htmlspecialchars($profile['contact']) : '' ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Currency Settings Section -->
                <div class="category-section" id="sectionCurrency">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Currency Settings</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-primary" id="editCurrencyBtn">
                                <i class="fas fa-edit"></i>
                                <span>Edit Currency</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <tbody>
                                    <?php
                                    // Example: Fetch currency settings from DB
                                    $result = $conn->query("SELECT * FROM currency_settings LIMIT 1");
                                    $currency = $result ? $result->fetch_assoc() : null;
                                    ?>
                                    <tr><th>Default Currency</th><td><?= $currency ? htmlspecialchars($currency['default_currency']) : '' ?></td></tr>
                                    <tr><th>Exchange Rate Source</th><td><?= $currency ? htmlspecialchars($currency['exchange_source']) : '' ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Chart of Accounts Section -->
                <div class="category-section" id="sectionChartOfAccounts">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Chart of Accounts Management</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-primary" id="addAccountBtn">
                                <i class="fas fa-plus"></i>
                                <span>Add Account</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>Account Code</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Example: Fetch chart of accounts from DB
                                    $result = $conn->query("SELECT * FROM chart_of_accounts");
                                    if ($result) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($row['account_code']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['description']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['type']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                                            echo '<td>
                                                <div class="employee-actions">
                                                    <button class="action-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                                                    <button class="action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Payment Terms Section -->
                <div class="category-section" id="sectionPaymentTerms">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Payment Terms</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-primary" id="addPaymentTermBtn">
                                <i class="fas fa-plus"></i>
                                <span>Add Term</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>Term Name</th>
                                        <th>Description</th>
                                        <th>Days</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Example: Fetch payment terms from DB
                                    $result = $conn->query("SELECT * FROM payment_terms");
                                    if ($result) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($row['term_name']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['description']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['days']) . '</td>';
                                            echo '<td>
                                                <div class="employee-actions">
                                                    <button class="action-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                                                    <button class="action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Access Logs Section -->
                <div class="category-section" id="sectionAccessLogs">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Access Logs</div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>IP Address</th>
                                        <th>Action</th>
                                        <th>Date/Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Example: Fetch access logs from DB
                                    $result = $conn->query("SELECT * FROM access_logs ORDER BY datetime DESC LIMIT 100");
                                    if ($result) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['user']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['ip_address']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['action']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['datetime']) . '</td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Data Retention Policies Section -->
                <div class="category-section" id="sectionRetention">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Data Retention Policies</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-primary" id="editRetentionBtn">
                                <i class="fas fa-edit"></i>
                                <span>Edit Policy</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <tbody>
                                    <?php
                                    // Example: Fetch retention policy from DB
                                    $result = $conn->query("SELECT * FROM retention_policy LIMIT 1");
                                    $policy = $result ? $result->fetch_assoc() : null;
                                    ?>
                                    <tr><th>Retention Period</th><td><?= $policy ? htmlspecialchars($policy['retention_period']) : '' ?></td></tr>
                                    <tr><th>Backup Frequency</th><td><?= $policy ? htmlspecialchars($policy['backup_frequency']) : '' ?></td></tr>
                                    <tr><th>Archive Location</th><td><?= $policy ? htmlspecialchars($policy['archive_location']) : '' ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Backup & Restore Section -->
                <div class="category-section" id="sectionBackup">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Backup & Restore</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-primary" id="backupBtn">
                                <i class="fas fa-database"></i>
                                <span>Backup Now</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>Backup Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Example: Fetch backup logs from DB
                                    $result = $conn->query("SELECT * FROM backup_logs ORDER BY backup_date DESC LIMIT 20");
                                    if ($result) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($row['backup_date']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                                            echo '<td>
                                                <div class="employee-actions">
                                                    <button class="action-btn view" title="Download"><i class="fas fa-download"></i></button>
                                                    <button class="action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Notification Settings Section -->
                <div class="category-section" id="sectionNotifications">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Notification Settings</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-primary" id="editNotificationBtn">
                                <i class="fas fa-edit"></i>
                                <span>Edit Notifications</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <tbody>
                                    <?php
                                    // Example: Fetch notification settings from DB
                                    $result = $conn->query("SELECT * FROM notification_settings LIMIT 1");
                                    $notif = $result ? $result->fetch_assoc() : null;
                                    ?>
                                    <tr><th>Email Alerts</th><td><?= $notif ? htmlspecialchars($notif['email_alerts']) : '' ?></td></tr>
                                    <tr><th>SMS Alerts</th><td><?= $notif ? htmlspecialchars($notif['sms_alerts']) : '' ?></td></tr>
                                    <tr><th>Overdue Payment Alerts</th><td><?= $notif ? htmlspecialchars($notif['overdue_alerts']) : '' ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Activity Log Section -->
                <div class="category-section" id="sectionActivityLog" style="display:none;">
                    <div class="system-admin-header">
                        <div class="system-admin-title">System Activity Log</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-outline" id="exportActivityLogBtn">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Date/Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>101</td>
                                        <td>admin</td>
                                        <td>Login</td>
                                        <td>User logged in</td>
                                        <td>192.168.1.10</td>
                                        <td>2024-06-10 09:15:00</td>
                                    </tr>
                                    <!-- ...existing code for dynamic rows... -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Initialize sidebar functionality
        document.addEventListener('DOMContentLoaded', function () {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');
            const supplyChainDropdown = document.getElementById('supplyChainDropdown');
            const supplyChainDropdownMenu = document.getElementById('supplyChainDropdownMenu');

            // Mobile menu toggle
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }

            // Close mobile menu when clicking outside
            document.addEventListener('click', function (event) {
                if (sidebar && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });

            // Supply Chain dropdown functionality
            if (supplyChainDropdown) {
                supplyChainDropdown.addEventListener('click', function () {
                    supplyChainDropdownMenu.classList.toggle('active');
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

            // Employee search functionality
            const employeeSearch = document.getElementById('employeeSearch');
            const positionFilter = document.getElementById('positionFilter');
            const statusFilter = document.getElementById('statusFilter');
            const clearFilters = document.getElementById('clearFilters');
            const employeeRows = document.querySelectorAll('tbody tr');

            function filterEmployees() {
                const searchTerm = employeeSearch.value.toLowerCase();
                const positionValue = positionFilter.value;
                const statusValue = statusFilter.value;

                employeeRows.forEach(row => {
                    const name = row.querySelector('.employee-name').textContent.toLowerCase();
                    const position = row.querySelector('.employee-position').textContent;
                    const status = row.querySelector('.employee-status').textContent;
                    const employeeId = row.querySelector('.employee-id').textContent.toLowerCase();

                    const matchesSearch = name.includes(searchTerm) || employeeId.includes(searchTerm);
                    const matchesPosition = !positionValue || position === positionValue;
                    const matchesStatus = !statusValue || status === statusValue;

                    if (matchesSearch && matchesPosition && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            employeeSearch.addEventListener('input', filterEmployees);
            positionFilter.addEventListener('change', filterEmployees);
            statusFilter.addEventListener('change', filterEmployees);

            clearFilters.addEventListener('click', function () {
                employeeSearch.value = '';
                positionFilter.value = '';
                statusFilter.value = '';
                filterEmployees();
            });

            // Action button handlers
            document.querySelectorAll('.action-btn.view').forEach(btn => {
                btn.addEventListener('click', function () {
                    const employeeId = this.getAttribute('data-id');
                    alert(`View details for employee: ${employeeId}`);
                });
            });

            document.querySelectorAll('.action-btn.edit').forEach(btn => {
                btn.addEventListener('click', function () {
                    const employeeId = this.getAttribute('data-id');
                    alert(`Edit employee: ${employeeId}`);
                });
            });

            document.querySelectorAll('.action-btn.delete').forEach(btn => {
                btn.addEventListener('click', function () {
                    const employeeId = this.getAttribute('data-id');
                    const employeeName = this.getAttribute('data-name');
                    if (confirm(`Are you sure you want to delete employee ${employeeName} (${employeeId})?`)) {
                        alert(`Employee ${employeeName} deleted successfully`);
                    }
                });
            });

            // Add Employee button
            document.getElementById('addEmployeeBtn').addEventListener('click', function () {
                alert('Add new employee functionality will be implemented here');
            });

            // Export button
            document.getElementById('exportBtn').addEventListener('click', function () {
                alert('Export functionality will be implemented here');
            });

            // Recruitment Management search/filter
            const applicantSearch = document.getElementById('applicantSearch');
            const applicantStatusFilter = document.getElementById('applicantStatusFilter');
            const clearApplicantFilters = document.getElementById('clearApplicantFilters');
            const applicantRows = document.querySelectorAll('#sectionRecruitment tbody tr');

            function filterApplicants() {
                const searchTerm = applicantSearch.value.toLowerCase();
                const statusValue = applicantStatusFilter.value;
                applicantRows.forEach(row => {
                    const name = row.children[1].textContent.toLowerCase();
                    const status = row.children[4].textContent;
                    const id = row.children[0].textContent.toLowerCase();
                    const matchesSearch = name.includes(searchTerm) || id.includes(searchTerm);
                    const matchesStatus = !statusValue || status === statusValue;
                    row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
                });
            }
            if (applicantSearch) applicantSearch.addEventListener('input', filterApplicants);
            if (applicantStatusFilter) applicantStatusFilter.addEventListener('change', filterApplicants);
            if (clearApplicantFilters) clearApplicantFilters.addEventListener('click', function () {
                applicantSearch.value = '';
                applicantStatusFilter.value = '';
                filterApplicants();
            });

            // HR Functions search/filter
            const hrSearch = document.getElementById('hrSearch');
            const hrStatusFilter = document.getElementById('hrStatusFilter');
            const clearHRFilters = document.getElementById('clearHRFilters');
            const hrRows = document.querySelectorAll('#sectionHRFunctions tbody tr');

            function filterHRFunctions() {
                const searchTerm = hrSearch.value.toLowerCase();
                const statusValue = hrStatusFilter.value;
                hrRows.forEach(row => {
                    const name = row.children[1].textContent.toLowerCase();
                    const status = row.children[5].textContent;
                    const id = row.children[0].textContent.toLowerCase();
                    const matchesSearch = name.includes(searchTerm) || id.includes(searchTerm);
                    const matchesStatus = !statusValue || status === statusValue;
                    row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
                });
            }
            if (hrSearch) hrSearch.addEventListener('input', filterHRFunctions);
            if (hrStatusFilter) hrStatusFilter.addEventListener('change', filterHRFunctions);
            if (clearHRFilters) clearHRFilters.addEventListener('click', function () {
                hrSearch.value = '';
                hrStatusFilter.value = '';
                filterHRFunctions();
            });

            // Category switching logic
            const btnUserRole = document.getElementById('btnUserRole');
            const btnOrgSettings = document.getElementById('btnOrgSettings');
            const btnAccounting = document.getElementById('btnAccounting');
            const btnMasterData = document.getElementById('btnMasterData');
            const btnAudit = document.getElementById('btnAudit');
            const btnUtilities = document.getElementById('btnUtilities');
            const btnActivityLog = document.getElementById('btnActivityLog');
            const sectionUserRole = document.getElementById('sectionUserRole');
            const sectionOrgSettings = document.getElementById('sectionOrgSettings');
            const sectionAccounting = document.getElementById('sectionAccounting');
            const sectionMasterData = document.getElementById('sectionMasterData');
            const sectionAudit = document.getElementById('sectionAudit');
            const sectionUtilities = document.getElementById('sectionUtilities');
            const sectionActivityLog = document.getElementById('sectionActivityLog');

            function showSection(section) {
                sectionUserRole.classList.remove('active');
                sectionOrgSettings.classList.remove('active');
                sectionAccounting.classList.remove('active');
                sectionMasterData.classList.remove('active');
                sectionAudit.classList.remove('active');
                sectionUtilities.classList.remove('active');
                sectionActivityLog.style.display = 'none';
                btnUserRole.classList.remove('active');
                btnOrgSettings.classList.remove('active');
                btnAccounting.classList.remove('active');
                btnMasterData.classList.remove('active');
                btnAudit.classList.remove('active');
                btnUtilities.classList.remove('active');
                btnActivityLog.classList.remove('active');
                if (section === sectionActivityLog) {
                    sectionActivityLog.style.display = 'block';
                } else {
                    section.classList.add('active');
                }
            }

            btnUserRole.addEventListener('click', function () {
                showSection(sectionUserRole);
                btnUserRole.classList.add('active');
            });
            btnOrgSettings.addEventListener('click', function () {
                showSection(sectionOrgSettings);
                btnOrgSettings.classList.add('active');
            });
            btnAccounting.addEventListener('click', function () {
                showSection(sectionAccounting);
                btnAccounting.classList.add('active');
            });
            btnMasterData.addEventListener('click', function () {
                showSection(sectionMasterData);
                btnMasterData.classList.add('active');
            });
            btnAudit.addEventListener('click', function () {
                showSection(sectionAudit);
                btnAudit.classList.add('active');
            });
            btnUtilities.addEventListener('click', function () {
                showSection(sectionUtilities);
                btnUtilities.classList.add('active');
            });
            btnActivityLog.addEventListener('click', function () {
                showSection(sectionActivityLog);
                btnActivityLog.classList.add('active');
            });

            // Category switching logic for new buttons/sections
            const sections = {
                btnCompanyProfile: 'sectionCompanyProfile',
                btnCurrency: 'sectionCurrency',
                btnChartOfAccounts: 'sectionChartOfAccounts',
                btnPaymentTerms: 'sectionPaymentTerms',
                btnAccessLogs: 'sectionAccessLogs',
                btnRetention: 'sectionRetention',
                btnBackup: 'sectionBackup',
                btnNotifications: 'sectionNotifications'
            };
            Object.keys(sections).forEach(btnId => {
                const btn = document.getElementById(btnId);
                const sectionId = sections[btnId];
                const section = document.getElementById(sectionId);
                if (btn && section) {
                    btn.addEventListener('click', function () {
                        Object.keys(sections).forEach(otherBtnId => {
                            document.getElementById(sections[otherBtnId]).classList.remove('active');
                            const otherBtn = document.getElementById(otherBtnId);
                            otherBtn.classList.remove('active');
                            otherBtn.setAttribute('aria-selected', 'false');
                            otherBtn.setAttribute('tabindex', '-1');
                        });
                        section.classList.add('active');
                        btn.classList.add('active');
                        btn.setAttribute('aria-selected', 'true');
                        btn.setAttribute('tabindex', '0');
                        btn.focus();
                    });
                    btn.addEventListener('keydown', function (e) {
                        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                            e.preventDefault();
                            const btns = Array.from(document.querySelectorAll('.category-btn'));
                            const idx = btns.indexOf(document.activeElement);
                            let nextIdx = e.key === 'ArrowRight' ? idx + 1 : idx - 1;
                            if (nextIdx < 0) nextIdx = btns.length - 1;
                            if (nextIdx >= btns.length) nextIdx = 0;
                            btns[nextIdx].focus();
                        }
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            btn.click();
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>

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

// Remove hardcoded data. Data will be fetched from the database in each section.
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
            color: var(--error);
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
                <a href="reports.php" class="menu-item" data-module="reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-section-title">System</div>
                <a href="finished_goods.php" class="menu-item active" data-module="system-admin">
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
                <div class="category-buttons">
                    <button class="category-btn active" id="btnEmployee">Employee Management</button>
                    <button class="category-btn" id="btnRecruitment">Recruitment Management</button>
                    <button class="category-btn" id="btnHRFunctions">HR Functions</button>
                    <button class="category-btn" id="btnActivityLog">Activity Log</button>
                </div>
                <!-- Employee Management Section -->
                <div class="category-section active" id="sectionEmployee">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Employee Management</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-outline" id="exportBtn">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                            <button class="btn btn-primary" id="addEmployeeBtn">
                                <i class="fas fa-plus"></i>
                                <span>Add Employee</span>
                            </button>
                        </div>
                    </div>

                    <div class="filter-section">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="filter-label">Search:</label>
                                <input type="text" class="filter-input search-box" id="employeeSearch"
                                    placeholder="Search employees...">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Position:</label>
                                <select class="filter-input" id="positionFilter">
                                    <option value="">All Positions</option>
                                    <option value="PROCESS ENGINEER">Process Engineer</option>
                                    <option value="QA SUPERVISOR">QA Supervisor</option>
                                    <option value="WAREHOUSEMAN">Warehouseman</option>
                                    <option value="MOLD FABRICATOR">Mold Fabricator</option>
                                    <option value="IT SUPERVISOR">IT Supervisor</option>
                                    <option value="MACHINE OPERATOR">Machine Operator</option>
                                    <option value="QUALITY CONTROL">Quality Control</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Status:</label>
                                <select class="filter-input" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button class="btn btn-outline" id="clearFilters">
                                    <i class="fas fa-times"></i>
                                    <span>Clear</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Full Name</th>
                                        <th>Position/Job Title</th>
                                        <th>Department</th>
                                        <th>Date Hired</th>
                                        <th>Employment Type</th>
                                        <th>Status</th>
                                        <th>Contact Number</th>
                                        <th>Email Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch employees from the database
                                    $result = $conn->query("SELECT * FROM employees");
                                    if ($result && $result->num_rows > 0):
                                        while ($employee = $result->fetch_assoc()):
                                    ?>
                                        <tr data-employee-id="<?php echo $employee['id']; ?>">
                                            <td><span class="employee-id"><?php echo htmlspecialchars($employee['id']); ?></span></td>
                                            <td><span class="employee-name"><?php echo htmlspecialchars($employee['name']); ?></span></td>
                                            <td><span class="employee-position"><?php echo htmlspecialchars($employee['position']); ?></span></td>
                                            <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['date_hired']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['employment_type']); ?></td>
                                            <td>
                                                <span class="employee-status <?php echo strtolower($employee['status']); ?>">
                                                    <?php echo $employee['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($employee['contact']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                            <td>
                                                <div class="employee-actions">
                                                    <button class="action-btn view" data-id="<?php echo $employee['id']; ?>"
                                                        title="View Profile">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="action-btn edit" data-id="<?php echo $employee['id']; ?>"
                                                        title="Edit Employee">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="action-btn delete" data-id="<?php echo $employee['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                                        title="Delete Employee">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    else:
                                        echo '<tr><td colspan="10" style="text-align:center;">No employees found.</td></tr>';
                                    endif;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Recruitment Management Section -->
                <div class="category-section" id="sectionRecruitment">
                    <div class="system-admin-header">
                        <div class="system-admin-title">Recruitment Management</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-outline" id="exportRecruitmentBtn">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                            <button class="btn btn-primary" id="addApplicantBtn">
                                <i class="fas fa-plus"></i>
                                <span>Add Applicant</span>
                            </button>
                        </div>
                    </div>
                    <div class="filter-section">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="filter-label">Search:</label>
                                <input type="text" class="filter-input search-box" id="applicantSearch" placeholder="Search applicants...">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Status:</label>
                                <select class="filter-input" id="applicantStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="Screening">Screening</option>
                                    <option value="Interview">Interview</option>
                                    <option value="Hired">Hired</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button class="btn btn-outline" id="clearApplicantFilters">
                                    <i class="fas fa-times"></i>
                                    <span>Clear</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>Applicant ID</th>
                                        <th>Full Name</th>
                                        <th>Position Applied</th>
                                        <th>Date Applied</th>
                                        <th>Application Status</th>
                                        <th>Contact Number</th>
                                        <th>Email Address</th>
                                        <th>Resume/CV</th>
                                        <th>Assigned HR</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch applicants from the database
                                    $result = $conn->query("SELECT * FROM applicants");
                                    if ($result && $result->num_rows > 0):
                                        while ($applicant = $result->fetch_assoc()):
                                    ?>
                                        <tr data-applicant-id="<?php echo $applicant['id']; ?>">
                                            <td><?php echo htmlspecialchars($applicant['id']); ?></td>
                                            <td><?php echo htmlspecialchars($applicant['name']); ?></td>
                                            <td><?php echo htmlspecialchars($applicant['position_applied']); ?></td>
                                            <td><?php echo htmlspecialchars($applicant['date_applied']); ?></td>
                                            <td><?php echo htmlspecialchars($applicant['status']); ?></td>
                                            <td><?php echo htmlspecialchars($applicant['contact']); ?></td>
                                            <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                            <td>
                                                <a href="<?php echo $applicant['resume']; ?>" target="_blank" class="action-btn view" title="View Resume">
                                                    <i class="fas fa-file"></i>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($applicant['assigned_hr']); ?></td>
                                            <td>
                                                <div class="employee-actions">
                                                    <button class="action-btn view" data-id="<?php echo $applicant['id']; ?>" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="action-btn edit" data-id="<?php echo $applicant['id']; ?>" title="Edit Applicant">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="action-btn delete" data-id="<?php echo $applicant['id']; ?>" data-name="<?php echo htmlspecialchars($applicant['name']); ?>" title="Delete Applicant">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button class="action-btn edit" data-id="<?php echo $applicant['id']; ?>" title="Update Status">
                                                        <i class="fas fa-sync"></i>
                                                    </button>
                                                    <button class="action-btn edit" data-id="<?php echo $applicant['id']; ?>" title="Schedule Interview">
                                                        <i class="fas fa-calendar"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    else:
                                        echo '<tr><td colspan="10" style="text-align:center;">No applicants found.</td></tr>';
                                    endif;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- HR Functions Section -->
                <div class="category-section" id="sectionHRFunctions">
                    <div class="system-admin-header">
                        <div class="system-admin-title">HR Functions</div>
                        <div class="system-admin-actions">
                            <button class="btn btn-outline" id="exportHRBtn">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                        </div>
                    </div>
                    <div class="filter-section">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="filter-label">Search:</label>
                                <input type="text" class="filter-input search-box" id="hrSearch" placeholder="Search HR functions...">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Status:</label>
                                <select class="filter-input" id="hrStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Denied">Denied</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button class="btn btn-outline" id="clearHRFilters">
                                    <i class="fas fa-times"></i>
                                    <span>Clear</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-container">
                        <div class="table-scroll-x">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Full Name</th>
                                        <th>Leave Type</th>
                                        <th>Date Filed</th>
                                        <th>Leave Duration</th>
                                        <th>Status</th>
                                        <th>Benefit Type</th>
                                        <th>Benefit Start Date</th>
                                        <th>Remarks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch HR functions from the database
                                    $result = $conn->query("SELECT * FROM hr_functions");
                                    if ($result && $result->num_rows > 0):
                                        while ($hr = $result->fetch_assoc()):
                                    ?>
                                        <tr data-employee-id="<?php echo $hr['employee_id']; ?>">
                                            <td><?php echo htmlspecialchars($hr['employee_id']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['name']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['leave_type']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['date_filed']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['leave_duration']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['status']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['benefit_type']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['benefit_start']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['remarks']); ?></td>
                                            <td>
                                                <div class="employee-actions">
                                                    <button class="action-btn edit" data-id="<?php echo $hr['employee_id']; ?>" title="Edit HR Function">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="action-btn delete" data-id="<?php echo $hr['employee_id']; ?>" data-name="<?php echo htmlspecialchars($hr['name']); ?>" title="Delete HR Function">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button class="action-btn edit" data-id="<?php echo $hr['employee_id']; ?>" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="action-btn delete" data-id="<?php echo $hr['employee_id']; ?>" title="Deny">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button class="action-btn view" data-id="<?php echo $hr['employee_id']; ?>" title="View Request">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    else:
                                        echo '<tr><td colspan="10" style="text-align:center;">No HR functions found.</td></tr>';
                                    endif;
                                    ?>
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
                                    <?php
                                    // Fetch activity logs from the database
                                    $result = $conn->query("SELECT * FROM activity_logs ORDER BY datetime DESC LIMIT 100");
                                    if ($result && $result->num_rows > 0):
                                        while ($log = $result->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['id']); ?></td>
                                            <td><?php echo htmlspecialchars($log['user_id']); ?></td>
                                            <td><?php echo htmlspecialchars($log['username']); ?></td>
                                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            <td><?php echo htmlspecialchars($log['datetime']); ?></td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    else:
                                        echo '<tr><td colspan="7" style="text-align:center;">No activity logs found.</td></tr>';
                                    endif;
                                    ?>
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
            const btnEmployee = document.getElementById('btnEmployee');
            const btnRecruitment = document.getElementById('btnRecruitment');
            const btnHRFunctions = document.getElementById('btnHRFunctions');
            const btnActivityLog = document.getElementById('btnActivityLog');
            const sectionEmployee = document.getElementById('sectionEmployee');
            const sectionRecruitment = document.getElementById('sectionRecruitment');
            const sectionHRFunctions = document.getElementById('sectionHRFunctions');
            const sectionActivityLog = document.getElementById('sectionActivityLog');

            function showSection(section) {
                sectionEmployee.classList.remove('active');
                sectionRecruitment.classList.remove('active');
                sectionHRFunctions.classList.remove('active');
                sectionActivityLog.style.display = 'none';
                btnEmployee.classList.remove('active');
                btnRecruitment.classList.remove('active');
                btnHRFunctions.classList.remove('active');
                btnActivityLog.classList.remove('active');
                if (section === sectionActivityLog) {
                    sectionActivityLog.style.display = 'block';
                } else {
                    section.classList.add('active');
                }
            }

            btnEmployee.addEventListener('click', function () {
                showSection(sectionEmployee);
                btnEmployee.classList.add('active');
            });

            btnRecruitment.addEventListener('click', function () {
                showSection(sectionRecruitment);
                btnRecruitment.classList.add('active');
            });

            btnHRFunctions.addEventListener('click', function () {
                showSection(sectionHRFunctions);
                btnHRFunctions.classList.add('active');
            });

            btnActivityLog.addEventListener('click', function () {
                showSection(sectionActivityLog);
                btnActivityLog.classList.add('active');
            });
        });
    </script>
</body>

</html>

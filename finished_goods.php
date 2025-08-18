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

        /* Leave Type Dropdown */
        .leave-type-dropdown {
            width: 100%;
            padding: 0.25rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.875rem;
            background: var(--white);
            color: var(--dark-gray);
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
                                    <?php foreach ($employees as $employee): ?>
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
                                    <?php endforeach; ?>
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
                                    <?php foreach ($applicants as $applicant): ?>
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
                                    <?php endforeach; ?>
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
                                    <?php foreach ($hr_functions as $hr): ?>
                                        <tr data-employee-id="<?php echo $hr['employee_id']; ?>">
                                            <td><?php echo htmlspecialchars($hr['employee_id']); ?></td>
                                            <td><?php echo htmlspecialchars($hr['name']); ?></td>
                                            <td>
                                                <select class="leave-type-dropdown" onchange="updateLeaveType(this, '<?php echo $hr['employee_id']; ?>')">
                                                    <option value="Sick Leave" <?php echo $hr['leave_type'] == 'Sick Leave' ? 'selected' : ''; ?>>Sick Leave</option>
                                                    <option value="Vacation Leave" <?php echo $hr['leave_type'] == 'Vacation Leave' ? 'selected' : ''; ?>>Vacation Leave</option>
                                                    <option value="Maternity Leave" <?php echo $hr['leave_type'] == 'Maternity Leave' ? 'selected' : ''; ?>>Maternity Leave</option>
                                                    <option value="Paternity Leave" <?php echo $hr['leave_type'] == 'Paternity Leave' ? 'selected' : ''; ?>>Paternity Leave</option>
                                                    <option value="Bereavement Leave" <?php echo $hr['leave_type'] == 'Bereavement Leave' ? 'selected' : ''; ?>>Bereavement Leave</option>
                                                    <option value="Emergency Leave" <?php echo $hr['leave_type'] == 'Emergency Leave' ? 'selected' : ''; ?>>Emergency Leave</option>
                                                    <option value="Personal Leave" <?php echo $hr['leave_type'] == 'Personal Leave' ? 'selected' : ''; ?>>Personal Leave</option>
                                                    <option value="Unpaid Leave" <?php echo $hr['leave_type'] == 'Unpaid Leave' ? 'selected' : ''; ?>>Unpaid Leave</option>
                                                </select>
                                            </td>
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
                                    <?php endforeach; ?>
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
                                    <!-- Example static row, replace with PHP fetching from DB -->
                                    <tr>
                                        <td>1</td>
                                        <td>101</td>
                                        <td>admin</td>
                                        <td>Login</td>
                                        <td>User logged in</td>
                                        <td>192.168.1.10</td>
                                        <td>2024-06-10 09:15:00</td>
                                    </tr>
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

            // Function to update leave type
            function updateLeaveType(selectElement, employeeId) {
                const newLeaveType = selectElement.value;
                // Here you would typically make an AJAX call to update the database
                console.log(`Updating leave type for employee ${employeeId} to ${newLeaveType}`);
                
                // Example AJAX call (uncomment and implement when ready):
                /*
                fetch('update_leave_type.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        leave_type: newLeaveType
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Leave type updated successfully');
                    } else {
                        alert('Error updating leave type');
                        // Optionally revert the selection
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating leave type');
                    // Optionally revert the selection
                });
                */
            }
        });
    </script>
</body>

</html>

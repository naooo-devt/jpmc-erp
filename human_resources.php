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

// Fetch employees from database
$employees = [];
$sql = "SELECT * FROM employees ORDER BY id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Get selected employee
$selected_employee_id = isset($_GET['employee']) ? $_GET['employee'] : ($employees[0]['id'] ?? null);
$selected_employee = null;

foreach ($employees as $employee) {
    if ($employee['id'] === $selected_employee_id) {
        $selected_employee = $employee;
        break;
    }
}

if (!$selected_employee && count($employees) > 0) {
    $selected_employee = $employees[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Human Resources - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="images/logo.png">
    <style>
        body {
            background: #f6f8fb;
        }
        .hr-content {
            display: flex;
            gap: 32px;
            padding: 32px 24px;
            min-height: calc(100vh - 140px);
        }
        .employee-directory-panel {
            flex: 1.2;
            background: #fff;
            border-radius: 18px;
            padding: 32px 24px;
            box-shadow: 0 4px 24px 0 rgba(37,99,235,0.07);
            display: flex;
            flex-direction: column;
            min-width: 340px;
        }
        .directory-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        .directory-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #23408e;
            letter-spacing: 0.5px;
        }
        .directory-search {
            flex: 0 0 180px;
            position: relative;
        }
        .directory-search input {
            width: 100%;
            padding: 8px 32px 8px 12px;
            border-radius: 8px;
            border: 1px solid #e3e7ef;
            background: #f7f9fc;
            font-size: 1rem;
            outline: none;
            transition: border 0.2s;
        }
        .directory-search i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #b0b8c9;
        }
        .employee-directory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 18px;
            overflow-y: auto;
            padding-bottom: 8px;
        }
        .employee-directory-card {
            background: #f8fafc;
            border: 2px solid transparent;
            border-radius: 14px;
            padding: 18px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: border 0.2s, box-shadow 0.2s, background 0.2s;
            box-shadow: 0 1px 4px 0 rgba(37,99,235,0.03);
        }
        .employee-directory-card.selected,
        .employee-directory-card:hover {
            border-color: #2563eb;
            background: #eaf1ff;
            box-shadow: 0 4px 16px 0 rgba(37,99,235,0.10);
        }
        .directory-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #e3e7ef;
            margin-bottom: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .directory-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .directory-avatar i {
            font-size: 2rem;
            color: #b0b8c9;
        }
        .directory-emp-name {
            font-weight: 700;
            color: #23408e;
            font-size: 1.05rem;
            text-align: center;
            margin-bottom: 2px;
            word-break: break-word;
        }
        .directory-emp-position {
            color: #6b7280;
            font-size: 0.93rem;
            text-align: center;
            margin-bottom: 0;
        }
        .employee-detail-panel {
            flex: 1;
            background: #fff;
            border-radius: 18px;
            padding: 36px 32px;
            box-shadow: 0 4px 24px 0 rgba(37,99,235,0.10);
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .employee-detail-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e3e7ef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .employee-detail-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .employee-detail-avatar i {
            font-size: 3.5rem;
            color: #b0b8c9;
        }
        .employee-detail-name {
            font-size: 1.45rem;
            font-weight: 800;
            color: #23408e;
            margin-bottom: 2px;
            text-align: center;
        }
        .employee-detail-position {
            color: #6b7280;
            font-size: 1.05rem;
            margin-bottom: 18px;
            text-align: center;
        }
        .employee-detail-info {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 13px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .detail-label {
            font-weight: 700;
            color: #7b809a;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .detail-value {
            color: #23408e;
            font-size: 1rem;
            line-height: 1.4;
            word-break: break-word;
        }
        .detail-value.status {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .detail-value.status.probationary {
            background: #fff7e6;
            color: #d97706;
        }
        .detail-value.status.regular {
            background: #e6fff5;
            color: #059669;
        }
        @media (max-width: 1200px) {
            .hr-content {
                flex-direction: column;
                min-height: unset;
            }
            .employee-detail-panel {
                max-width: none;
                margin-top: 24px;
            }
        }
        @media (max-width: 768px) {
            .employee-directory-grid {
                grid-template-columns: 1fr;
            }
            .hr-content {
                padding: 12px 4px;
            }
            .employee-directory-panel, .employee-detail-panel {
                padding: 16px 6px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
        <!--SideBar MENU -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <i class="fas fa-users" style="font-size: 1.5rem; color: var(--dark-blue);"></i>
                <h1 class="header-title">Human Resources</h1>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="module-content active" id="human-resources">
                <div class="hr-content">
                    <!-- Employee Directory Panel -->
                    <div class="employee-directory-panel">
                        <div class="directory-header">
                            <span class="directory-title">Employee Directory</span>
                            <div class="directory-search">
                                <input type="text" id="employeeSearch" placeholder="Search employee...">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                        <div class="employee-directory-grid" id="employeeDirectoryGrid">
                            <?php foreach ($employees as $employee): ?>
                            <div class="employee-directory-card <?php echo ($employee['id'] === $selected_employee['id']) ? 'selected' : ''; ?>"
                                 data-emp-name="<?php echo strtolower($employee['name']); ?>"
                                 data-emp-position="<?php echo strtolower($employee['position']); ?>"
                                 onclick="selectEmployee('<?php echo $employee['id']; ?>')">
                                <div class="directory-avatar">
                                    <?php if (!empty($employee['image']) && file_exists($employee['image'])): ?>
                                        <img src="<?php echo $employee['image']; ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="directory-emp-name"><?php echo htmlspecialchars($employee['name']); ?></div>
                                <div class="directory-emp-position"><?php echo htmlspecialchars($employee['position']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Employee Detail Panel -->
                    <div class="employee-detail-panel">
                        <div class="employee-detail-avatar">
                            <?php if (!empty($selected_employee['image']) && file_exists($selected_employee['image'])): ?>
                                <img src="<?php echo $selected_employee['image']; ?>" alt="<?php echo htmlspecialchars($selected_employee['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="employee-detail-name"><?php echo htmlspecialchars($selected_employee['name']); ?></div>
                        <div class="employee-detail-position"><?php echo htmlspecialchars($selected_employee['position']); ?></div>
                        <div class="employee-detail-info">
                            <div class="detail-item">
                                <div class="detail-label">ID</div>
                                <div class="detail-value"><?php echo htmlspecialchars($selected_employee['id']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">NAME</div>
                                <div class="detail-value"><?php echo htmlspecialchars($selected_employee['name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">MOBILE</div>
                                <div class="detail-value"><?php echo htmlspecialchars($selected_employee['mobile']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">POSITION</div>
                                <div class="detail-value"><?php echo htmlspecialchars($selected_employee['position']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">EMP STATUS</div>
                                <div class="detail-value status <?php echo strtolower($selected_employee['emp_status']); ?>">
                                    <?php echo htmlspecialchars($selected_employee['emp_status']); ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">APPOINTED AS</div>
                                <div class="detail-value"><?php echo htmlspecialchars($selected_employee['appointed_as']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">BIRTHDATE</div>
                                <div class="detail-value"><?php echo htmlspecialchars($selected_employee['birthdate']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">CIVIL STATUS</div>
                                <div class="detail-value"><?php echo htmlspecialchars($selected_employee['civil_status']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">ADDRESS</div>
                                <div class="detail-value"><?php echo htmlspecialchars($selected_employee['address']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Initialize sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');
            const supplyChainDropdown = document.getElementById('supplyChainDropdown');
            const supplyChainDropdownMenu = document.getElementById('supplyChainDropdownMenu');

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

            // Supply Chain dropdown functionality
            if (supplyChainDropdown) {
                supplyChainDropdown.addEventListener('click', function() {
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
        });

        // Directory search filter
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('employeeSearch');
            const cards = document.querySelectorAll('.employee-directory-card');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const val = this.value.trim().toLowerCase();
                    cards.forEach(card => {
                        const name = card.getAttribute('data-emp-name');
                        const position = card.getAttribute('data-emp-position');
                        if (name.includes(val) || position.includes(val)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });

        // Function to select employee
        function selectEmployee(employeeId) {
            const url = new URL(window.location);
            url.searchParams.set('employee', employeeId);
            window.history.pushState({}, '', url);
            window.location.reload();
        }
    </script>
</body>
</html>
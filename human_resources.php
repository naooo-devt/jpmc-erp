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

// Handle Add/Edit/Delete Employee (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'add') {
        // Prevent duplicate ID
        $stmt = $conn->prepare("SELECT id FROM employees WHERE id=?");
        $stmt->bind_param("s", $_POST['id']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Employee ID already exists.']);
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO employees (id, name, position, mobile, emp_status, appointed_as, birthdate, civil_status, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssssss",
            $_POST['id'], $_POST['name'], $_POST['position'], $_POST['mobile'],
            $_POST['emp_status'], $_POST['appointed_as'], $_POST['birthdate'],
            $_POST['civil_status'], $_POST['address']
        );
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'edit') {
        // Allow changing the employee ID by using old_id as the WHERE clause
        $old_id = $_POST['old_id'] ?? $_POST['id'];
        // If the ID is being changed, check for duplicate
        if ($_POST['id'] !== $old_id) {
            $stmt = $conn->prepare("SELECT id FROM employees WHERE id=?");
            $stmt->bind_param("s", $_POST['id']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Employee ID already exists.']);
                exit;
            }
            $stmt->close();
        }
        $stmt = $conn->prepare("UPDATE employees SET id=?, name=?, position=?, mobile=?, emp_status=?, appointed_as=?, birthdate=?, civil_status=?, address=? WHERE id=?");
        $stmt->bind_param(
            "ssssssssss",
            $_POST['id'], $_POST['name'], $_POST['position'], $_POST['mobile'],
            $_POST['emp_status'], $_POST['appointed_as'], $_POST['birthdate'],
            $_POST['civil_status'], $_POST['address'], $old_id
        );
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM employees WHERE id=?");
        $stmt->bind_param("s", $_POST['id']);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }
}

// Fetch employees from the database
$employees = [];
$sql = "SELECT * FROM employees ORDER BY id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Get selected employee
$selected_employee_id = isset($_GET['employee']) ? $_GET['employee'] : null;
$selected_employee = null;

if ($selected_employee_id) {
    foreach ($employees as $employee) {
        if ($employee['id'] === $selected_employee_id) {
            $selected_employee = $employee;
            break;
        }
    }
}
// If no employee is selected or invalid, default to the first employee
if (!$selected_employee && count($employees) > 0) {
    $selected_employee = $employees[0];
    $selected_employee_id = $selected_employee['id'];
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
        .hr-content {
            display: flex;
            gap: 20px;
            padding: 20px;
            height: calc(100vh - 140px);
        }

        .employee-grid {
            flex: 1;
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow);
            overflow-y: auto;
        }

        .employee-grid-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .employee-grid-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-blue);
        }

        .add-employee-btn {
            padding: 10px 20px;
            background: var(--primary-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .add-employee-btn:hover {
            background: var(--dark-blue);
        }

        .employee-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .employee-card {
            background: var(--white);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .employee-card:hover {
            border-color: var(--primary-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .employee-card.selected {
            border-color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
        }

        .employee-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .employee-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .employee-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .employee-avatar i {
            font-size: 1.5rem;
            color: var(--gray);
        }

        .employee-info {
            flex: 1;
        }

        .employee-name {
            font-weight: 700;
            color: var(--dark-blue);
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .employee-position {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .employee-detail-panel {
            flex: 1;
            background: var(--white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            display: none; /* Initially hidden */
        }

        .employee-detail-panel.active {
            display: block; /* Show when active */
        }

        /* Add/Update: HR page navigation buttons */
        .hr-nav-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
        }
        .hr-nav-btn {
            background: var(--primary-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .hr-nav-btn:hover {
            background: var(--dark-blue);
        }

        /* Responsive Add Employee Modal */
        .add-employee-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--white);
            border-radius: 12px;
            padding: 22px 18px;
            box-shadow: var(--shadow-lg);
            width: 95vw;
            max-width: 350px;
            min-width: 240px;
            z-index: 1000;
            display: none;
            border: 2px solid rgba(37,99,235,0.45); /* Thinner, more transparent blue border */
            outline: 1.5px solid rgba(30,64,175,0.25); /* Slightly visible, thinner outline */
            background-color: rgba(255,255,255,0.97); /* Slightly see-through white */
        }
        @media (min-width: 600px) {
            .add-employee-modal {
                max-width: 370px;
            }
        }
        @media (min-width: 900px) {
            .add-employee-modal {
                max-width: 400px;
            }
        }
        @media (min-width: 1200px) {
            .add-employee-modal {
                max-width: 420px;
            }
        }
        .add-employee-modal.active {
            display: block;
        }

        .modal-header {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-blue);
            margin-bottom: 20px;
            text-align: center;
        }

        .modal-form-group {
            margin-bottom: 15px;
        }

        .modal-form-label {
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 5px;
            display: block;
        }

        .modal-form-input, .modal-form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--dark-blue);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--dark-blue);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: var(--gray);
        }

        .employee-detail-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .employee-detail-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            overflow: hidden;
        }

        .employee-detail-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .employee-detail-avatar i {
            font-size: 3rem;
            color: var(--gray);
        }

        .employee-detail-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-weight: 700;
            color: var(--dark-gray);
            font-size: 0.85rem;
        }

        .detail-value {
            color: var(--dark-blue);
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .detail-value.status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .detail-value.status.probationary {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .detail-value.status.regular {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .card-menu-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 2;
        }
        .card-menu-btn:hover {
            color: #2563eb;
        }
        .card-menu-dropdown {
            display: none;
            position: absolute;
            top: 36px;
            right: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 16px 0 rgba(37,99,235,0.10);
            min-width: 120px;
            z-index: 10;
        }
        .card-menu-dropdown.active {
            display: block;
        }
        .card-menu-dropdown button {
            width: 100%;
            background: none;
            border: none;
            padding: 10px 16px;
            text-align: left;
            color: #23408e;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .card-menu-dropdown button:hover {
            background: #f1f5f9;
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
            <!-- HR Navigation Buttons -->
            <div class="hr-nav-buttons">
                <button class="hr-nav-btn" onclick="window.location.href='payroll.php'"><i class="fas fa-money-check-alt"></i> Payroll</button>
                <button class="hr-nav-btn" onclick="window.location.href='attendance.php'"><i class="fas fa-calendar-check"></i> Attendance Sheet</button>
                <!-- Add more buttons as needed -->
            </div>
            <div class="hr-content">
                <!-- Employee Grid Section -->
                <div class="employee-grid">
                    <div class="employee-grid-header">
                        <h2 class="employee-grid-title">Employee Records</h2>
                        <button class="add-employee-btn" id="addEmployeeBtn">Add New Employee</button>
                    </div>
                    <div class="employee-cards" id="employeeCards">
                        <?php foreach ($employees as $employee): ?>
                        <div class="employee-card<?php if ($employee['id'] === $selected_employee_id) echo ' selected'; ?>"
                             style="position:relative"
                             data-emp-id="<?php echo $employee['id']; ?>"
                             onclick="toggleEmployeeDetail('<?php echo $employee['id']; ?>', event)">
                            <div class="employee-card-header">
                                <div class="employee-avatar">
                                    <?php if (!empty($employee['image']) && file_exists($employee['image'])): ?>
                                        <img src="<?php echo $employee['image']; ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="employee-info">
                                    <div class="employee-name"><?php echo htmlspecialchars($employee['name']); ?></div>
                                    <div class="employee-position"><?php echo htmlspecialchars($employee['position']); ?></div>
                                </div>
                                <button class="card-menu-btn" type="button" onclick="event.stopPropagation();toggleCardMenu(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="card-menu-dropdown">
                                    <button type="button" onclick="event.stopPropagation();openEditEmployeeModal('<?php echo $employee['id']; ?>')">Edit</button>
                                    <button type="button" onclick="event.stopPropagation();openDeleteEmployeeModal('<?php echo $employee['id']; ?>')">Delete</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Employee Detail Panel -->
                <div class="employee-detail-panel" id="employeeDetailPanel" style="<?php echo isset($_GET['employee']) ? 'display:block;' : 'display:none;'; ?>">
                    <div class="employee-detail-header">
                        <div class="employee-detail-avatar">
                            <?php if (!empty($selected_employee['image']) && file_exists($selected_employee['image'])): ?>
                                <img src="<?php echo $selected_employee['image']; ?>" alt="<?php echo htmlspecialchars($selected_employee['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($selected_employee['name']); ?></h3>
                        <p><?php echo htmlspecialchars($selected_employee['position']); ?></p>
                    </div>
                    <div class="employee-detail-info">
                        <div class="detail-item">
                            <div class="detail-label">ID</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_employee['id']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Mobile</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_employee['mobile']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Employment Status</div>
                            <div class="detail-value status <?php echo strtolower($selected_employee['emp_status']); ?>">
                                <?php echo htmlspecialchars($selected_employee['emp_status']); ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Appointed As</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_employee['appointed_as']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Birthdate</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_employee['birthdate']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Civil Status</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_employee['civil_status']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selected_employee['address']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div class="add-employee-modal" id="addEmployeeModal">
        <div class="modal-header" id="employeeModalTitle">Add New Employee</div>
        <form id="addEmployeeForm">
            <input type="hidden" name="action" value="add" id="employeeFormAction">
            <input type="hidden" name="old_id" id="employeeFormOldId">
            <div class="modal-form-group">
                <label class="modal-form-label">Employee ID</label>
                <input type="text" name="id" class="modal-form-input" id="employeeFormId" required>
            </div>
            <div class="modal-form-group">
                <label class="modal-form-label">Name</label>
                <input type="text" name="name" class="modal-form-input" id="employeeFormName" required>
            </div>
            <div class="modal-form-group">
                <label class="modal-form-label">Position</label>
                <input type="text" name="position" class="modal-form-input" id="employeeFormPosition" required>
            </div>
            <div class="modal-form-group">
                <label class="modal-form-label">Mobile</label>
                <input type="text" name="mobile" class="modal-form-input" id="employeeFormMobile">
            </div>
            <div class="modal-form-group">
                <label class="modal-form-label">Employment Status</label>
                <select name="emp_status" class="modal-form-select" id="employeeFormEmpStatus">
                    <option value="PROBATIONARY">Probationary</option>
                    <option value="REGULAR">Regular</option>
                </select>
            </div>
            <div class="modal-form-group">
                <label class="modal-form-label">Appointed As</label>
                <input type="text" name="appointed_as" class="modal-form-input" id="employeeFormAppointedAs">
            </div>
            <div class="modal-form-group">
                <label class="modal-form-label">Birthdate</label>
                <input type="text" name="birthdate" class="modal-form-input" id="employeeFormBirthdate">
            </div>
            <div class="modal-form-group">
                <label class="modal-form-label">Civil Status</label>
                <input type="text" name="civil_status" class="modal-form-input" id="employeeFormCivilStatus">
            </div>
            <div class="modal-form-group">
                <label class="modal-form-label">Address</label>
                <input type="text" name="address" class="modal-form-input" id="employeeFormAddress">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="closeModalBtn">Cancel</button>
                <button type="submit" class="btn-primary" id="employeeModalSubmitBtn">Save</button>
            </div>
        </form>
    </div>
    <!-- Delete Employee Modal -->
    <div class="add-employee-modal" id="deleteEmployeeModal">
        <div class="modal-header">Delete Employee</div>
        <form id="deleteEmployeeForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteEmployeeId">
            <p>Are you sure you want to delete this employee?</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeDeleteEmployeeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Delete</button>
            </div>
        </form>
    </div>

    <script>
        // Card menu (3-dot) logic
        function toggleCardMenu(btn) {
            document.querySelectorAll('.card-menu-dropdown').forEach(menu => menu.classList.remove('active'));
            btn.nextElementSibling.classList.toggle('active');
            document.addEventListener('click', closeMenusOnClick, { once: true });
        }
        function closeMenusOnClick(e) {
            if (!e.target.classList.contains('card-menu-btn')) {
                document.querySelectorAll('.card-menu-dropdown').forEach(menu => menu.classList.remove('active'));
            }
        }

        // Show Add Employee Modal
        document.getElementById('addEmployeeBtn').addEventListener('click', function() {
            document.getElementById('employeeModalTitle').innerText = 'Add New Employee';
            document.getElementById('employeeFormAction').value = 'add';
            document.getElementById('addEmployeeForm').reset();
            document.getElementById('employeeFormId').readOnly = false;
            document.getElementById('employeeFormOldId').value = '';
            document.getElementById('addEmployeeModal').classList.add('active');
        });

        // Hide Add/Edit Employee Modal
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            document.getElementById('addEmployeeModal').classList.remove('active');
        });

        // Open Edit Modal
        function openEditEmployeeModal(empId) {
            const employees = <?php echo json_encode($employees); ?>;
            const emp = employees.find(e => e.id === empId);
            if (!emp) return;
            document.getElementById('employeeModalTitle').innerText = 'Edit Employee';
            document.getElementById('employeeFormAction').value = 'edit';
            document.getElementById('employeeFormId').value = emp.id;
            document.getElementById('employeeFormId').readOnly = false; // Allow editing ID
            document.getElementById('employeeFormOldId').value = emp.id; // Store old ID
            document.getElementById('employeeFormName').value = emp.name;
            document.getElementById('employeeFormPosition').value = emp.position;
            document.getElementById('employeeFormMobile').value = emp.mobile;
            document.getElementById('employeeFormEmpStatus').value = emp.emp_status;
            document.getElementById('employeeFormAppointedAs').value = emp.appointed_as;
            document.getElementById('employeeFormBirthdate').value = emp.birthdate;
            document.getElementById('employeeFormCivilStatus').value = emp.civil_status;
            document.getElementById('employeeFormAddress').value = emp.address;
            document.getElementById('addEmployeeModal').classList.add('active');
        }

        // Open Delete Modal
        function openDeleteEmployeeModal(empId) {
            document.getElementById('deleteEmployeeId').value = empId;
            document.getElementById('deleteEmployeeModal').classList.add('active');
        }
        function closeDeleteEmployeeModal() {
            document.getElementById('deleteEmployeeModal').classList.remove('active');
        }

        // Add/Edit Employee Form Submission (AJAX)
        document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to save employee.');
                }
            })
            .catch(() => alert('Failed to save employee.'));
        });

        // Delete Employee Form Submission (AJAX)
        document.getElementById('deleteEmployeeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete employee.');
                }
            })
            .catch(() => alert('Failed to delete employee.'));
        });

        // Toggle employee detail panel on card click
        function toggleEmployeeDetail(empId, event) {
            // Prevent toggle if clicking on menu buttons
            if (event.target.classList.contains('card-menu-btn') || event.target.closest('.card-menu-dropdown')) return;

            const url = new URL(window.location);
            const currentId = url.searchParams.get('employee');
            if (currentId === empId) {
                // If already open, close it
                url.searchParams.delete('employee');
                window.history.pushState({}, '', url);
                document.getElementById('employeeDetailPanel').style.display = 'none';
                // Remove highlight from all cards
                document.querySelectorAll('.employee-card').forEach(card => card.classList.remove('selected'));
            } else {
                // Open the panel for this employee
                url.searchParams.set('employee', empId);
                window.history.pushState({}, '', url);
                location.reload(); // Reload to update PHP-rendered details and highlight
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Show/hide detail panel based on URL param (for direct loads)
            const url = new URL(window.location);
            if (!url.searchParams.get('employee')) {
                document.getElementById('employeeDetailPanel').style.display = 'none';
                document.querySelectorAll('.employee-card').forEach(card => card.classList.remove('selected'));
            } else {
                document.getElementById('employeeDetailPanel').style.display = 'block';
            }
        });
    </script>
</body>
</html>
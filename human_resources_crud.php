<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}
require_once 'db_connect.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    // Add new employee
    $id = trim($_POST['emp_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $emp_status = trim($_POST['emp_status'] ?? '');
    $appointed_as = trim($_POST['appointed_as'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $civil_status = trim($_POST['civil_status'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $image = trim($_POST['image'] ?? '');

    if (!$id || !$name || !$position || !$mobile || !$emp_status || !$appointed_as || !$birthdate || !$civil_status || !$address) {
        echo json_encode(['success' => false, 'message' => 'All fields except image are required.']);
        exit;
    }

    // Check for duplicate ID
    $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Employee ID already exists.']);
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO employees (id, name, position, mobile, emp_status, appointed_as, birthdate, civil_status, address, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $id, $name, $position, $mobile, $emp_status, $appointed_as, $birthdate, $civil_status, $address, $image);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add employee.']);
    }
    $stmt->close();
    exit;
}

if ($action === 'edit') {
    // Edit employee
    $id = trim($_POST['id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $emp_status = trim($_POST['emp_status'] ?? '');
    $appointed_as = trim($_POST['appointed_as'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $civil_status = trim($_POST['civil_status'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $image = trim($_POST['image'] ?? '');

    if (!$id || !$name || !$position || !$mobile || !$emp_status || !$appointed_as || !$birthdate || !$civil_status || !$address) {
        echo json_encode(['success' => false, 'message' => 'All fields except image are required.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE employees SET name=?, position=?, mobile=?, emp_status=?, appointed_as=?, birthdate=?, civil_status=?, address=?, image=? WHERE id=?");
    $stmt->bind_param("ssssssssss", $name, $position, $mobile, $emp_status, $appointed_as, $birthdate, $civil_status, $address, $image, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee.']);
    }
    $stmt->close();
    exit;
}

if ($action === 'delete') {
    // Delete employee
    $id = trim($_POST['id'] ?? '');
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee ID.']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM employees WHERE id=?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete employee.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);

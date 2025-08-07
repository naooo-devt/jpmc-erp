<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_number = $_POST['order_number'];
    $supplier_id = $_POST['supplier_id'];
    $order_date = $_POST['order_date'];
    $expected_delivery = $_POST['expected_delivery'];
    $total_amount = $_POST['total_amount'];
    $notes = $_POST['notes'];

    // Default status for new PO
    $status = 'Processing';

    $sql = "INSERT INTO purchase_orders (order_number, supplier_id, order_date, expected_delivery, status, total_amount, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisssds", $order_number, $supplier_id, $order_date, $expected_delivery, $status, $total_amount, $notes);

    if ($stmt->execute()) {
        header('Location: transactions.php?success=po_added');
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
} else {
    header('Location: transactions.php');
    exit;
}
?>

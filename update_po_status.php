<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    http_response_code(403);
    exit('Unauthorized');
}
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['po_id'], $_POST['status'])) {
    $po_id = intval($_POST['po_id']);
    $status = $_POST['status'];
    $allowed = ['Processing', 'Packing', 'In Transit', 'Out for Delivery', 'Delivered'];
    if (!in_array($status, $allowed)) {
        http_response_code(400);
        exit('Invalid status');
    }
    $stmt = $conn->prepare("UPDATE purchase_orders SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $po_id);
    if ($stmt->execute()) {
        echo "OK";
    } else {
        http_response_code(500);
        echo "Error";
    }
    $stmt->close();
} else {
    http_response_code(400);
    exit('Bad request');
}
?>

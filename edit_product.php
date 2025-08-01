<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $raw_material_id = $_POST['raw_material_id'];
    $stock_quantity = $_POST['stock_quantity'];
    $unit_cost = $_POST['unit_cost'];
    $status = $_POST['status'];
    
    // Update product in product_materials table
    $sql = "UPDATE product_materials SET name=?, raw_material_id=?, stock_quantity=?, unit_cost=?, status=? WHERE product_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siddsi", $name, $raw_material_id, $stock_quantity, $unit_cost, $status, $product_id);
    
    if ($stmt->execute()) {
        header('Location: transactions.php?success=4');
        exit;
    } else {
        header('Location: transactions.php?error=update_failed');
        exit;
    }
} else {
    header('Location: transactions.php');
    exit;
}
?> 
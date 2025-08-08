<?php
// reject_quotation.php
require_once 'db_connect.php'; // or whatever your DB connection file is

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Update status in DB
    $stmt = $conn->prepare("UPDATE quotations SET status = 'Rejected' WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "Quotation has been rejected successfully.";
    } else {
        echo "Error rejecting quotation.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}

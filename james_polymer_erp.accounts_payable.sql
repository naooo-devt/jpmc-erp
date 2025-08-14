<?php
header('Content-Type: application/json');
require 'db_connect.php';

$range = $_GET['range'] ?? 'monthly';
$labels = [];
$amounts = [];

// Example: pulling from `invoices` table
// Only get supplier invoices that are unpaid or partially paid
if ($range === 'monthly') {
    $sql = "SELECT DATE_FORMAT(due_date, '%b %Y') AS label, 
                   SUM(amount_due) AS total
            FROM invoices
            WHERE status IN ('unpaid', 'partial')
            GROUP BY YEAR(due_date), MONTH(due_date)
            ORDER BY due_date";
} elseif ($range === 'weekly') {
    $sql = "SELECT CONCAT('Week ', WEEK(due_date)) AS label, 
                   SUM(amount_due) AS total
            FROM invoices
            WHERE status IN ('unpaid', 'partial')
            GROUP BY YEAR(due_date), WEEK(due_date)
            ORDER BY due_date";
}

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['label'];
    $amounts[] = (float)$row['total'];
}

echo json_encode([
    "labels" => $labels,
    "amounts" => $amounts
]);

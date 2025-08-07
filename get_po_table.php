<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    http_response_code(403);
    exit;
}
require_once 'db_connect.php';

$sql_file = __DIR__ . '/sql/purchase_orders.sql';
$purchase_orders_sql = file_exists($sql_file) ? file_get_contents($sql_file) : '';
if ($purchase_orders_sql) {
    $purchase_orders_result = $conn->query($purchase_orders_sql);
    if ($purchase_orders_result && $purchase_orders_result->num_rows > 0) {
        while ($row = $purchase_orders_result->fetch_assoc()) {
            $status_class = strtolower($row['status']);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['supplier_name']) . "</td>";
            echo "<td>" . date('m/d/Y', strtotime($row['order_date'])) . "</td>";
            echo "<td>" . date('m/d/Y', strtotime($row['expected_delivery'])) . "</td>";
            echo "<td>" . $row['total_items'] . " items</td>";
            echo "<td>₱" . number_format($row['total_amount'], 2) . "</td>";
            echo "<td>
                <span class='status-badge $status_class'>" . htmlspecialchars($row['status']) . "</span>
                <button class='btn btn-outline btn-sm' onclick='openUpdateStatusModal(" . $row['id'] . ", \"" . htmlspecialchars($row['status']) . "\")'>
                    <i class='fas fa-sync-alt'></i> Update
                </button>
            </td>";
            echo "<td>";
            echo "<button class='btn btn-outline' onclick='viewOrder(" . $row['id'] . ")'><i class='fas fa-eye'></i></button> ";
            echo "<button class='btn btn-success' onclick='createDelivery(" . $row['id'] . ")'><i class='fas fa-truck'></i></button>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8' style='text-align:center;'>No purchase orders found.</td></tr>";
    }
} else {
    echo "<tr><td colspan='8' style='text-align:center;'>SQL file not found.</td></tr>";
}
?>

<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    http_response_code(403);
    exit;
}
require_once 'db_connect.php';

$statuses = ['Processing', 'Packing', 'In Transit', 'Out for Delivery', 'Delivered'];
$po_counts = [];
foreach ($statuses as $status) {
    $result = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = '$status'");
    $po_counts[$status] = $result ? $result->fetch_assoc()['count'] : 0;
}
?>
<div class="stat-card">
    <div class="stat-header">
        <div>
            <div class="stat-title">Processing</div>
            <div class="stat-subtitle">Orders being processed</div>
        </div>
        <div class="stat-icon orange"><i class="fas fa-cogs"></i></div>
    </div>
    <div class="stat-value"><?= $po_counts['Processing'] ?></div>
</div>
<div class="stat-card">
    <div class="stat-header">
        <div>
            <div class="stat-title">Packing</div>
            <div class="stat-subtitle">Orders being packed</div>
        </div>
        <div class="stat-icon blue"><i class="fas fa-box"></i></div>
    </div>
    <div class="stat-value"><?= $po_counts['Packing'] ?></div>
</div>
<div class="stat-card">
    <div class="stat-header">
        <div>
            <div class="stat-title">In Transit</div>
            <div class="stat-subtitle">On the way</div>
        </div>
        <div class="stat-icon green"><i class="fas fa-truck-moving"></i></div>
    </div>
    <div class="stat-value"><?= $po_counts['In Transit'] ?></div>
</div>
<div class="stat-card">
    <div class="stat-header">
        <div>
            <div class="stat-title">Out for Delivery</div>
            <div class="stat-subtitle">Ready to deliver</div>
        </div>
        <div class="stat-icon purple"><i class="fas fa-shipping-fast"></i></div>
    </div>
    <div class="stat-value"><?= $po_counts['Out for Delivery'] ?></div>
</div>
<div class="stat-card">
    <div class="stat-header">
        <div>
            <div class="stat-title">Delivered</div>
            <div class="stat-subtitle">Completed orders</div>
        </div>
        <div class="stat-icon teal"><i class="fas fa-check-circle"></i></div>
    </div>
    <div class="stat-value"><?= $po_counts['Delivered'] ?></div>
</div>

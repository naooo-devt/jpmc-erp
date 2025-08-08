<?php
$conn = new mysqli('localhost', 'root', '', 'james_polymer_erp');
$po_no = $_GET['po_no'] ?? '';
$data = [];
if ($po_no) {
    $stmt = $conn->prepare("SELECT * FROM purchase_orders_sample WHERE order_number=?");
    $stmt->bind_param("s", $po_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    if ($order) {
        $data = $order;
        $items = [];
        $item_result = $conn->query("SELECT * FROM purchase_order_items WHERE purchase_order_id=" . intval($order['id']));
        while ($row = $item_result->fetch_assoc()) {
            $items[] = $row;
        }
        $data['items'] = $items;
    }
}
header('Content-Type: application/json');
echo json_encode($data);
$conn->close();
?>

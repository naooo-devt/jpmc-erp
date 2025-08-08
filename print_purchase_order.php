<?php
$conn = new mysqli('localhost', 'root', '', 'james_polymer_erp');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$po_no = $_GET['po_no'] ?? '';
$order = null;
$items = [];

if ($po_no !== '') {
    $stmt = $conn->prepare("SELECT po.*, s.name AS supplier_name FROM purchase_orders_sample po LEFT JOIN suppliers s ON po.supplier_id = s.id WHERE po.order_number = ?");
    $stmt->bind_param("s", $po_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if ($order) {
        $po_id = $order['id'];
        $item_result = $conn->query("SELECT * FROM purchase_order_items WHERE purchase_order_id = $po_id");
        while ($row = $item_result->fetch_assoc()) {
            $items[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Purchase Order</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f9f9f9; }
        .po-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 24px 32px 32px; border: 1px solid #ccc; min-height: 100vh; display: flex; flex-direction: column; }
        .po-header { text-align: center; }
        .po-header h2 { margin: 0; }
        .po-info-table { width: 100%; margin-top: 18px; margin-bottom: 18px; }
        .po-info-table td { padding: 6px 8px; font-size: 15px; }
        .po-info-label { width: 120px; }
        .po-info-box { border: 1px solid #000; min-width: 180px; min-height: 28px; padding: 4px 8px; display: inline-block; }
        .po-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .po-table th, .po-table td { border: 1px solid #000; padding: 10px 6px; text-align: center; font-size: 15px; }
        .po-table th { background: #f1f1f1; }
        .terms.card {
            background: #fefefe;
            border: 1px solid #aaa;
            padding: 16px 20px;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.1);
            border-radius: 6px;
            font-size: 14px;
            margin-top: 24px;
        }
        .po-sign-table { width: 100%; margin-top: auto; border-collapse: separate; border-spacing: 18px 0; }
        .po-sign-box { border: 1.5px solid #000; border-radius: 7px; width: 140px; height: 60px; vertical-align: bottom; text-align: center; padding-top: 8px; }
        .po-sign-label { font-size: 13px; font-weight: bold; margin-bottom: 10px; display: block; }
        .po-sign-name { margin-top: 18px; font-size: 14px; }
        @media print {
            .no-print { display: none; }
            .po-container { border: none; box-shadow: none; padding: 0; }
            body { background: none; margin: 0; padding: 0; }
            .terms.card {
                box-shadow: none;
                border: 1px solid #000;
            }
        }
        .underline-text {
            display: inline-block;
            min-width: 140px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            font-size: 15px;
        }
    </style>
</head>
<body>
<div class="po-container">
    <div class="po-header">
        <h2>JAMES POLYMERS MFG. CORP.</h2>
        <div>16 Aguinaldo H-Way, Panapaan 2, Bacoor, Cavite</div>
        <div>VAT Reg. TIN: 007-165-671-000</div>
        <div>Tel. Nos.: (046) 417-1097 -- Fax: (046) 417-3566 -- Direct Line: 529-8978</div>
        <h3 style="margin: 18px 0 0 0; font-weight: normal;">Purchase Order</h3>
    </div>
    <?php if ($order): ?>
    <table class="po-info-table" style="width: 100%;">
        <tr>
            <!-- Left: Supplier in box -->
            <td style="vertical-align: top; width: 50%;">
                <strong>SUPPLIER:</strong><br>
                <div class="po-info-box"><?= htmlspecialchars($order['supplier_name']) ?></div>
            </td>

            <!-- Right: PO No, Date, Terms, Ship Via with underline, aligned right -->
            <td style="vertical-align: top; width: 50%;">
                <div style="margin-left: 200px; margin-bottom: 6px;">
                    <strong>P.O. NO:</strong> <span class="underline-text"><?= htmlspecialchars($order['order_number']) ?></span>
                </div>
                <div style="margin-left: 200px; margin-bottom: 6px;">
                    <strong>DATE:</strong> <span class="underline-text"><?= date('F j, Y', strtotime($order['order_date'])) ?></span>
                </div>
                <div style="margin-left: 200px; margin-bottom: 6px;">
                    <strong>TERMS:</strong> <span class="underline-text"><?= htmlspecialchars($order['terms']) ?></span>
                </div>
                <div style="margin-left: 200px;">
                    <strong>SHIP VIA:</strong> <span class="underline-text"><?= htmlspecialchars($order['ship_via']) ?></span>
                </div>
            </td>
        </tr>
    </table>

    <table class="po-table">
        <thead>
            <tr>
                <th>Description<br>Part No.</th>
                <th>Delivery Date</th>
                <th>Quantity</th>
                <th>Net Price</th>
                <th>Sales Tax</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $max_rows = 3;
        $row_count = 0;
        foreach ($items as $item): $row_count++; ?>
            <tr>
                <td><?= htmlspecialchars($item['description']) ?><br><?= htmlspecialchars($item['part_no']) ?></td>
                <td><?= htmlspecialchars($item['delivery_date']) ?></td>
                <td><?= htmlspecialchars($item['quantity']) ?></td>
                <td><?= htmlspecialchars($item['net_price']) ?></td>
                <td><?= htmlspecialchars($item['sales_tax']) ?></td>
                <td><?= htmlspecialchars($item['amount']) ?></td>
            </tr>
        <?php endforeach;
        for ($i = $row_count; $i < max($max_rows, count($items)); $i++): ?>
            <tr>
                <td style="height:32px;"></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;"><strong>Total Amount</strong></td>
                <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="terms card">
        <strong style="display: block; margin-bottom: 10px;">TERMS AND CONDITIONS:</strong>
        <ol>
            <li>Acceptance subject to JAMES POLYMERS quality check and inspection.</li>
            <li>Delivery schedule must be strictly followed. Failure to comply with the above schedule shall be sufficient grounds for JAMES POLYMERS to cancel part or entire order or impose a penalty percent per day of delay.</li>
            <li>Packing procedure must strictly be followed as per our submitted specifications.</li>
            <li>Materials shall be received on or before 4:00 P.M. from Monday to Friday.</li>
        </ol>
    </div>

    <table class="po-sign-table">
        <tr>
            <td style="padding-top: 16px; text-align: center;">
                <div style="border-top: 1.5px solid #000; width: 140px; margin: 0 auto;"></div>
                <span class="po-sign-label">CONFORME</span>
                <span class="po-sign-name"><?= htmlspecialchars($order['conforme']) ?></span>
            </td>
            <td class="po-sign-box">
                <span class="po-sign-label">PREPARED BY</span>
                <span class="po-sign-name"><?= htmlspecialchars($order['prepared_by']) ?></span>
            </td>
            <td class="po-sign-box">
                <span class="po-sign-label">APPROVED BY</span>
                <span class="po-sign-name"><?= htmlspecialchars($order['approved_by']) ?></span>
            </td>
            <td class="po-sign-box">
                <span class="po-sign-label">ACCOUNTING</span>
                <span class="po-sign-name"><?= htmlspecialchars($order['accounting']) ?></span>
            </td>
            <td class="po-sign-box">
                <span class="po-sign-label">MANAGER</span>
                <span class="po-sign-name"><?= htmlspecialchars($order['manager']) ?></span>
            </td>
        </tr>
    </table>
    <?php else: ?>
        <div style="color:red; text-align:center; margin:40px 0;">Purchase Order not found.</div>
    <?php endif; ?>
    <div class="no-print" style="margin-top:20px; text-align:center;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>

<?php
// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'james_polymer_erp');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch suppliers for dropdown
$suppliers = $conn->query("SELECT id, name FROM suppliers");

// Fetch the latest P.O. No for auto-increment
$latest_po_no = '';
$latest_po_result = $conn->query("SELECT order_number FROM purchase_orders_sample ORDER BY id DESC LIMIT 1");
if ($latest_po_result && $row = $latest_po_result->fetch_assoc()) {
    $latest_po_no = $row['order_number'];
    // Try to extract numeric part and increment
    if (preg_match('/(\d+)$/', $latest_po_no, $matches)) {
        $next_number = str_pad((int)$matches[1] + 1, strlen($matches[1]), '0', STR_PAD_LEFT);
        $auto_po_no = preg_replace('/\d+$/', $next_number, $latest_po_no);
    } else {
        $auto_po_no = $latest_po_no . '1';
    }
} else {
    $auto_po_no = '001'; // Default if no PO yet
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $order_number = trim($_POST['order_number'] ?? '');
    $order_date = trim($_POST['order_date'] ?? '');
    $supplier_id = trim($_POST['supplier_id'] ?? '');

    // Detect edit mode (add a hidden input in the form for edit mode)
    $is_edit = isset($_POST['edit_mode']) && $_POST['edit_mode'] === '1';
    $is_cancel = isset($_POST['cancel_order']) && $_POST['cancel_order'] === '1';

    // Check for duplicate P.O. No only if adding
    if (!$is_edit) {
        $dup_check = $conn->prepare("SELECT COUNT(*) FROM purchase_orders_sample WHERE order_number = ?");
        $dup_check->bind_param("s", $order_number);
        $dup_check->execute();
        $dup_check->bind_result($dup_count);
        $dup_check->fetch();
        $dup_check->close();
        if ($dup_count > 0) {
            $error_message = "P.O. No '$order_number' already exists. Please use a unique number.";
        }
    }

    if ($is_cancel && $order_number !== '') {
        // Set status to Cancelled for the selected PO
        $stmt = $conn->prepare("UPDATE purchase_orders_sample SET status='Cancelled' WHERE order_number=?");
        $stmt->bind_param("s", $order_number);
        $stmt->execute();
        $stmt->close();
        // Redirect to avoid resubmission on refresh
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    // Only require PO fields for insert, NOT quotation_id/quotation_no
    if (empty($error_message) && $order_number !== '' && $order_date !== '' && $supplier_id !== '') {
        $terms = trim($_POST['terms'] ?? '');
        $ship_via = trim($_POST['ship_via'] ?? '');
        $conforme = trim($_POST['conforme'] ?? '');
        $prepared_by = trim($_POST['prepared_by'] ?? '');
        $approved_by = trim($_POST['approved_by'] ?? '');
        $accounting = trim($_POST['accounting'] ?? '');
        $manager = trim($_POST['manager'] ?? '');

        // Get arrays from POST
        $description_arr = $_POST['description'] ?? [];
        $part_no_arr = $_POST['part_no'] ?? [];
        $delivery_date_arr = $_POST['delivery_date'] ?? [];
        $quantity_arr = $_POST['quantity'] ?? [];
        $net_price_arr = $_POST['net_price'] ?? [];
        $sales_tax_arr = $_POST['sales_tax'] ?? [];
        $amount_arr = $_POST['amount'] ?? [];

        // Filter out incomplete item rows
        $items = [];
        for ($i = 0; $i < count($description_arr); $i++) {
            if (
                !empty($description_arr[$i]) &&
                !empty($part_no_arr[$i]) &&
                !empty($delivery_date_arr[$i]) &&
                !empty($quantity_arr[$i]) &&
                $net_price_arr[$i] !== '' &&
                $sales_tax_arr[$i] !== '' &&
                $amount_arr[$i] !== ''
            ) {
                $items[] = [
                    'description' => trim($description_arr[$i]),
                    'part_no' => trim($part_no_arr[$i]),
                    'delivery_date' => trim($delivery_date_arr[$i]),
                    'quantity' => (int) $quantity_arr[$i],
                    'net_price' => (float) $net_price_arr[$i],
                    'sales_tax' => (float) $sales_tax_arr[$i],
                    'amount' => (float) $amount_arr[$i]
                ];
            }
        }

        // Only insert if at least one complete item row exists
        if (count($items) > 0) {
            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += (float)$item['amount'];
            }

            if ($is_edit) {
                // Update existing purchase order
                $stmt = $conn->prepare("UPDATE purchase_orders_sample SET order_date=?, terms=?, ship_via=?, supplier_id=?, conforme=?, prepared_by=?, approved_by=?, accounting=?, manager=?, total_amount=? WHERE order_number=?");
                $stmt->bind_param(
                    "sssssssssd" . "s",
                    $order_date,
                    $terms,
                    $ship_via,
                    $supplier_id,
                    $conforme,
                    $prepared_by,
                    $approved_by,
                    $accounting,
                    $manager,
                    $total_amount,
                    $order_number
                );
                $stmt->execute();
                $stmt->close();

                // Remove old items and insert new ones (including any newly added items)
                $po_id_result = $conn->query("SELECT id FROM purchase_orders_sample WHERE order_number = '" . $conn->real_escape_string($order_number) . "' LIMIT 1");
                $po_id_row = $po_id_result->fetch_assoc();
                $purchase_order_id = $po_id_row['id'];
                $conn->query("DELETE FROM purchase_order_items WHERE purchase_order_id = $purchase_order_id");

                $item_stmt = $conn->prepare("INSERT INTO purchase_order_items 
                    (purchase_order_id, description, part_no, delivery_date, quantity, net_price, sales_tax, amount)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($items as $item) {
                    $item_stmt->bind_param(
                        "isssiddd",
                        $purchase_order_id,
                        $item['description'],
                        $item['part_no'],
                        $item['delivery_date'],
                        $item['quantity'],
                        $item['net_price'],
                        $item['sales_tax'],
                        $item['amount']
                    );
                    $item_stmt->execute();
                }
                $item_stmt->close();

                // Redirect to avoid resubmission on refresh
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                // Insert main purchase order (add total_amount column)
                $stmt = $conn->prepare("INSERT INTO purchase_orders_sample 
                    (order_number, order_date, terms, ship_via, supplier_id, conforme, prepared_by, approved_by, accounting, manager, total_amount)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "ssssisssssd",
                    $order_number,
                    $order_date,
                    $terms,
                    $ship_via,
                    $supplier_id,
                    $conforme,
                    $prepared_by,
                    $approved_by,
                    $accounting,
                    $manager,
                    $total_amount
                );
                $stmt->execute();
                $purchase_order_id = $stmt->insert_id;
                $stmt->close();

                // After successful PO insert, update quotation status if quotation_id is present
                if (!empty($_POST['quotation_id'])) {
                    $quotation_id = (int)$_POST['quotation_id'];
                    $update = $conn->prepare("UPDATE quotations SET status = 'Approved' WHERE id = ?");
                    $update->bind_param("i", $quotation_id);
                    $update->execute();
                    $update->close();
                }

                // Insert each item row
                $item_stmt = $conn->prepare("INSERT INTO purchase_order_items 
                    (purchase_order_id, description, part_no, delivery_date, quantity, net_price, sales_tax, amount)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($items as $item) {
                    $item_stmt->bind_param(
                        "isssiddd",
                        $purchase_order_id,
                        $item['description'],
                        $item['part_no'],
                        $item['delivery_date'],
                        $item['quantity'],
                        $item['net_price'],
                        $item['sales_tax'],
                        $item['amount']
                    );
                    $item_stmt->execute();
                }
                $item_stmt->close();

                // Redirect to avoid resubmission on refresh
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
    }
}

// Fetch purchase orders and their items
$sql = "SELECT po.*, s.name AS supplier_name 
        FROM purchase_orders_sample po 
        LEFT JOIN suppliers s ON po.supplier_id = s.id 
        ORDER BY po.order_date DESC";
$result = $conn->query($sql);

// Prefill logic for quotations.item column and fetch quotation_no
$prefill_description = '';
$quotation_id = '';
$quotation_no = '';
if (isset($_GET['quotation_id'])) {
    $quotation_id = (int) $_GET['quotation_id'];
    // Fetch 'item' and 'quotation_no' columns from quotations table
    $stmt = $conn->prepare("SELECT item, quotation_no FROM quotations WHERE id = ?");
    $stmt->bind_param("i", $quotation_id);
    $stmt->execute();
    $stmt->bind_result($item_value, $quotation_no_value);
    if ($stmt->fetch()) {
        $prefill_description = $item_value;
        $quotation_no = $quotation_no_value;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    body {
        margin: 0;
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f4f6f8;
    }

    .main-content {
        margin-left: 220px;
        padding: 40px 20px;
        min-height: 100vh;
        box-sizing: border-box;
        transition: margin-left 0.2s;
    }

    @media (max-width: 900px) {
        .main-content {
            margin-left: 0;
            padding: 20px 5px;
        }

        .container {
            padding: 10px;
        }
    }

    .container {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        padding: 30px;
        max-width: 1200px;
        margin: auto;
        box-sizing: border-box;
    }

    h1 {
        margin-top: 0;
        font-size: 2rem;
        color: #1e293b;
        letter-spacing: 1px;
    }

    .order-form {
        margin: 0 auto 30px;
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
        max-width: 950px;
    }

    .order-form label {
        font-weight: 500;
        margin-bottom: 4px;
        display: block;
        color: #334155;
    }

    .order-form input,
    .order-form select,
    .order-form textarea {
        width: 100%;
        padding: 6px 8px;
        margin-bottom: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        font-size: 0.95rem;
        background: #fff;
        box-sizing: border-box;
    }

    .order-form .form-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .order-form button {
        width: 100%;
        background: #2563eb;
        color: #fff;
        border: none;
        padding: 10px 0;
        border-radius: 6px;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 8px;
    }

    .order-form button:hover {
        background: #1e40af;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
        overflow-x: auto;
        display: block;
    }

    th,
    td {
        padding: 10px 8px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        white-space: nowrap;
    }

    th {
        background: #f1f5f9;
        color: #334155;
        font-weight: 600;
    }

    tr:hover {
        background: #f8fafc;
    }

    @media (max-width: 1100px) {
        .order-form {
            max-width: 100%;
            padding: 8px;
        }

        .order-form .form-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 700px) {
        .container {
            padding: 5px;
        }

        .order-form .form-row {
            grid-template-columns: 1fr;
        }

        table,
        thead,
        tbody,
        th,
        td,
        tr {
            display: block;
        }

        th,
        td {
            padding: 8px 4px;
        }
    }
        input:focus, select:focus {
        outline: 2px solid #3b82f6; /* Tailwind blue-500 */
        background-color: #e0f2fe; /* light blue */
    }

</style>

</head>
<body>
    <!--SideBar MENU -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <header style="text-align: center; margin-bottom: 20px;">
                <h2>JAMES POLYMERS MFG. CORP.</h2>
                <p>
                    16 Aguinaldo H-Way, Panapaan 2, Bacoor, Cavite<br>
                    VAT Reg. TIN: 007-165-671-000<br>
                    Tel. Nos.: (046) 417-1097 -- Fax: (046) 417-3566 -- Direct Line: 529-8978
                </p>
            </header>

            <h1>Purchase Orders</h1>
            <?php if (!empty($error_message)): ?>
                <div style="color: #fff; background: #ef4444; padding: 10px 16px; border-radius: 6px; margin-bottom: 18px; font-weight: bold;">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <form class="order-form" method="POST">
                <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation_id) ?>">
                <input type="hidden" name="quotation_no" value="<?= htmlspecialchars($quotation_no) ?>">
                <input type="hidden" name="edit_mode" id="edit_mode" value="0">
                <div class="form-row">
                    <div>
                        <label for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" required>
                            <option value="">Select Supplier</option>
                            <?php
                            // Re-query suppliers since the previous result set may be exhausted
                            $suppliers2 = $conn->query("SELECT id, name FROM suppliers");
                            if ($suppliers2 && $suppliers2->num_rows > 0) {
                                while ($sup = $suppliers2->fetch_assoc()) {
                                    echo "<option value='{$sup['id']}'>" . htmlspecialchars($sup['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="order_number">P.O. No</label>
                        <input type="text" name="order_number" id="order_number" required
                            value="<?= isset($_POST['order_number']) ? htmlspecialchars($_POST['order_number']) : htmlspecialchars($auto_po_no) ?>"
                            readonly
                            style="background:#f3f4f6; font-weight:bold;">
                    </div>
                    <div>
                        <label for="order_date">Date</label>
                        <input type="date" name="order_date" id="order_date" required>
                    </div>
                    <div>
                        <label for="terms">Terms</label>
                        <input type="text" name="terms" id="terms">
                    </div>
                    <div>
                        <label for="ship_via">Ship Via</label>
                        <input type="text" name="ship_via" id="ship_via">
                    </div>
                </div>

                <h3>Item Details</h3>
                <div style="overflow-x: auto;">
                    <table id="line-items-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Part No</th>
                                <th>Delivery Date</th>
                                <th>Quantity</th>
                                <th>Net Price</th>
                                <th>Sales Tax</th>
                                <th>Amount</th>
                                <th></th> <!-- Delete Button -->
                            </tr>
                        </thead>
                        <tbody id="line-items-body">
                            <tr>
                                <td><input type="text" name="description[]" value="<?= htmlspecialchars($prefill_description) ?>"></td>
                                <td><input type="text" name="part_no[]"></td>
                                <td><input type="date" name="delivery_date[]"></td>
                                <td><input type="number" name="quantity[]" min="1"></td>
                                <td><input type="number" name="net_price[]" step="0.01"></td>
                                <td><input type="number" name="sales_tax[]" step="0.01"></td>
                                <td><input type="number" name="amount[]" step="0.01"></td>
                                <td style="text-align:center;">
                                    <button type="button" class="delete-row-btn" onclick="deleteRow(this)" style="background:#ef4444;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;margin:auto;display:block;">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" onclick="addRow()">Add Another Item</button>
                </div>

                <div style="margin-top: 30px; padding: 15px; background: #f1f5f9; border-radius: 8px;">
                    <strong>TERMS AND CONDITIONS:</strong>
                    <ol style="padding-left: 20px; font-size: 14px; color: #334155;">
                        <li>Acceptance subject to JAMES POLYMERS quality check and inspection.</li>
                        <li>
                            Delivery schedule must be strictly followed. Failure to comply with the above schedule shall be sufficient grounds for JAMES POLYMERS to cancel part or entire order or impose a 
                            <input type="number" name="penalty_percent" placeholder="%" style="width: 40px; padding: 4px; margin: 0 5px; font-size: 13px;"> percent per day of delay.
                        </li>
                        <li>Packing procedure must strictly be followed as per our submitted specifications.</li>
                        <li>Materials shall be received on or before 4:00 P.M. from Monday to Friday.</li>
                    </ol>
                </div>

                <div class="form-row" style="margin-top: 30px;">
                    <div>
                        <label for="conforme">Conforme</label>
                        <input type="text" name="conforme" id="conforme" class="signature">
                    </div>
                    <div>
                        <label for="prepared_by">Prepared By</label>
                        <input type="text" name="prepared_by" id="prepared_by" class="signature" >
                    </div>
                    <div>
                        <label for="approved_by">Approved By</label>
                        <input type="text" name="approved_by" id="approved_by" class="signature">
                    </div>
                    <div>
                        <label for="accounting">Accounting</label>
                        <input type="text" name="accounting" id="accounting" class="signature">
                    </div>
                    <div>
                        <label for="manager">Manager</label>
                        <input type="text" name="manager" id="manager" class="signature">
                    </div>
                </div>

                <!-- Add/Edit/Cancel/Clear Buttons -->
                <div style="display: flex; gap: 12px; margin-top: 18px;">
                    <button type="submit" id="addBtn" style="background:#2563eb; color:#fff; border:none; padding:10px 0; border-radius:6px; font-size:1rem; cursor:pointer; width:100%;">Add Purchase Order</button>
                    <button type="button" id="editBtn" style="background:#22c55e; color:#fff; border:none; padding:10px 0; border-radius:6px; font-size:1rem; cursor:pointer; width:100%; display:none;">Confirm Edit</button>
                    <button type="button" id="cancelBtn" style="background:#ef4444; color:#fff; border:none; padding:10px 0; border-radius:6px; font-size:1rem; cursor:pointer; width:100%; display:none;">Cancel Order</button>
                    <button type="button" id="clearBtn" style="background:#fbbf24; color:#fff; border:none; padding:10px 0; border-radius:6px; font-size:1rem; cursor:pointer; width:100%;">Clear</button>
                    <input type="hidden" name="cancel_order" id="cancel_order" value="0">
                </div>
            </form>
            
        </div>
            <div class="container" style="margin-top: 30px;">
                <h3 style="text-align: center; margin-top: 20px;">Purchase Order History</h3>
                <div style="display:flex; justify-content:flex-end; align-items:center; margin-bottom:10px;">
        <!-- Filter Dropdowns -->
        <form id="filterForm" style="display:flex; gap:12px; align-items:center;">
            <label for="sortOrder" style="font-weight:600; font-size:1rem;">Sort By:</label>
            <select id="sortOrder" name="sortOrder" style="padding:6px; border-radius:5px;">
                <option value="desc">Newest to Oldest</option>
                <option value="asc">Oldest to Newest</option>
            </select>
            <label for="statusFilter" style="font-weight:600; font-size:1rem;">Status:</label>
            <select id="statusFilter" name="statusFilter" style="padding:6px; border-radius:5px;">
                <option value="">All</option>
                <option value="Pending">Pending</option>
                <option value="To-Deliver">To-Deliver</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            <button type="button" id="applyFilterBtn" style="background:#2563eb; color:#fff; border:none; border-radius:6px; padding:8px 18px; font-weight:600; cursor:pointer;">Apply</button>
        </form>
    </div>
    <div>
        <table id="po-history-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>P.O. No</th>
                    <th>Date</th>
                    <th>Terms</th>
                    <th>Ship Via</th>
                    <th>Supplier</th>
                    <th>Description</th>
                    <th>Part No</th>
                    <th>Delivery Date</th>
                    <th>Quantity</th>
                    <th>Net Price</th>
                    <th>Sales Tax</th>
                    <th>Amount</th>
                    <th>Total Amount</th>
                    <th>Status</th> <!-- Added Status column -->
                    <th>Conforme</th>
                    <th>Prepared By</th>
                    <th>Approved By</th>
                    <th>Accounting</th>
                    <th>Manager</th>
                </tr>
            </thead>
            <tbody id="po-history-body">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $po_id = $row['id'];
                        $items = $conn->query("SELECT * FROM purchase_order_items WHERE purchase_order_id = $po_id");
                        $item_rows = [];
                        if ($items && $items->num_rows > 0) {
                            while ($item = $items->fetch_assoc()) {
                                $item_rows[] = $item;
                            }
                        }
                        $rowspan = max(1, count($item_rows));
                        // Print main order info with rowspan for the first item
                        if (count($item_rows) > 0) {
                            $first = array_shift($item_rows);
                            echo "<tr class='main-po-row'>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['order_number']) . "</td>";
                            echo "<td rowspan='$rowspan'>" . date('m/d/Y', strtotime($row['order_date'])) . "</td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['terms']) . "</td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['ship_via']) . "</td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['supplier_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($first['description']) . "</td>";
                            echo "<td>" . htmlspecialchars($first['part_no']) . "</td>";
                            echo "<td>" . htmlspecialchars($first['delivery_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($first['quantity']) . "</td>";
                            echo "<td>" . htmlspecialchars($first['net_price']) . "</td>";
                            echo "<td>" . htmlspecialchars($first['sales_tax']) . "</td>";
                            echo "<td>" . htmlspecialchars($first['amount']) . "</td>";
                            echo "<td rowspan='$rowspan' style='font-weight:bold;'>₱" . number_format($row['total_amount'], 2) . "</td>";
                            // Status column with color
                            $status = strtolower(trim($row['status']));
                            $status_label = "Pending";
                            $status_bg = "#fde68a"; // yellow-200
                            $status_border = "#fbbf24"; // yellow-400
                            $status_text = "#92400e"; // yellow-900
                            if ($status === "to-deliver" || $status === "for-delivery") {
                                $status_label = "To-Deliver";
                                $status_bg = "#bbf7d0"; // green-200
                                $status_border = "#22c55e"; // green-500
                                $status_text = "#166534"; // green-900
                            } elseif ($status === "cancelled") {
                                $status_label = "Cancelled";
                                $status_bg = "#fecaca"; // red-200
                                $status_border = "#ef4444"; // red-500
                                $status_text = "#991b1b"; // red-900
                            }
                            echo "<td rowspan='$rowspan'><span style='display:inline-block; min-width:90px; text-align:center; font-size:0.97rem; font-weight:600; letter-spacing:0.5px; background:$status_bg; color:$status_text; border:2px solid $status_border; border-radius:18px; padding:4px 0 3px 0; box-shadow:0 1px 4px rgba(0,0,0,0.04);'>" . htmlspecialchars($status_label) . "</span></td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['conforme']) . "</td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['prepared_by']) . "</td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['approved_by']) . "</td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['accounting']) . "</td>";
                            echo "<td rowspan='$rowspan'>" . htmlspecialchars($row['manager']) . "</td>";
                            echo "</tr>";
                            // Print remaining item rows (do not add extra columns for these rows)
                            foreach ($item_rows as $item) {
                                echo "<tr class='item-po-row'>";
                                echo "<td>" . htmlspecialchars($item['description']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['part_no']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['delivery_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['net_price']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['sales_tax']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['amount']) . "</td>";
                                // Do NOT add empty <td> for columns with rowspan
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr class='main-po-row'>";
                            echo "<td colspan='7'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>"; // Status column empty
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "<td rowspan='$rowspan'></td>";
                            echo "</tr>";
                        }
                    }
                } else {
                    echo "<tr><td colspan='20' style='text-align:center;'>No purchase orders found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <!-- Print Button with PO selection Below History Table -->
    <div style="text-align:center; margin-top:20px;">
        <form id="printForm" action="print_purchase_order.php" method="get" target="_blank" style="display:inline-block;">
            <select name="po_no" id="po_no" required style="padding:8px 12px; border-radius:4px; border:1px solid #ccc; font-size:1rem;">
                <option value="">Select P.O. No to Print</option>
                <?php
                // Fetch all P.O. Numbers for dropdown
                $po_numbers = $conn->query("SELECT order_number FROM purchase_orders_sample ORDER BY order_date DESC");
                while ($po = $po_numbers->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($po['order_number']) . '">' . htmlspecialchars($po['order_number']) . '</option>';
                }
                ?>
            </select>
            <button type="submit" style="background:#22c55e; color:#fff; padding:10px 22px; border:none; border-radius:6px; font-size:1.1rem; font-weight:600; margin-left:8px; cursor:pointer;">
                <i class="fa fa-print" style="margin-right:8px;"></i>Print
            </button>
        </form>
    </div>
</div>
        </div>
    </div>  

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
        }

        .main-content {
            padding: 20px;
            background: #fff;
        }

        .container {
            max-width: 1000px; /* Limit width */
            margin: 0 auto;     /* Center horizontally */
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row > div {
            flex: 1 1 200px;
            display: flex;
            flex-direction: column;
        }

        input, select {
            padding: 6px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: center;
        }

        .signature {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
        }

        button[type="submit"],
        button[type="button"] {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
        }

        input, select {
    padding: 4px 6px;
    font-size: 13px;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 12px;
    align-items: flex-start;
}

.form-row > div {
    display: flex;
    flex-direction: column;
}

#line-items-table input {
    width: 100%;
    box-sizing: border-box;
    padding: 4px;
    font-size: 13px;
}

table th:nth-child(1), table td:nth-child(1) { width: 25%; } /* Description */
table th:nth-child(2), table td:nth-child(2) { width: 15%; } /* Part No */
table th:nth-child(3), table td:nth-child(3) { width: 15%; } /* Delivery Date */
table th:nth-child(4), table td:nth-child(4) { width: 10%; } /* Quantity */
table th:nth-child(5), table td:nth-child(5) { width: 10%; } /* Net Price */
table th:nth-child(6), table td:nth-child(6) { width: 10%; } /* Sales Tax */
table th:nth-child(7), table td:nth-child(7) { width: 15%; } /* Amount */

.main-content {
    padding: 15px;
    background: #fff;
}

.container {
    max-width: 900px;
    margin: auto;
    background: #fff;
    padding: 50px;
    border-radius: 10px;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.24);
}


    </style>

    <script>
        function addRow() {
            const tbody = document.getElementById("line-items-body");
            const newRow = tbody.rows[0].cloneNode(true);
            newRow.querySelectorAll("input").forEach(input => input.value = '');
            tbody.appendChild(newRow);
        }

        function deleteRow(btn) {
            const tbody = document.getElementById("line-items-body");
            if (tbody.rows.length > 1) {
                btn.closest('tr').remove();
            }
        }

        // Prevent Enter from submitting the form
        document.querySelector('.order-form').addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                const target = event.target;
                if (target.tagName !== 'TEXTAREA' && target.tagName !== 'BUTTON') {
                    event.preventDefault();
                    const formElements = Array.from(this.querySelectorAll('input, select, textarea'))
                        .filter(el => !el.disabled && el.offsetParent !== null);
                    const currentIndex = formElements.indexOf(target);
                    if (currentIndex > -1 && currentIndex < formElements.length - 1) {
                        formElements[currentIndex + 1].focus();
                    }
                }
            }
        });

        // Highlight row and fill form for editing
        document.addEventListener('DOMContentLoaded', function() {
            // Only allow clicking main rows (not item rows)
            const historyRows = document.querySelectorAll('tbody > tr.main-po-row');
            historyRows.forEach(function(row) {
                row.addEventListener('click', function() {
                    historyRows.forEach(r => r.style.background = '');
                    row.style.background = '#dbeafe';

                    // Get PO No from row
                    const poNoCell = row.querySelector('td:nth-child(2)');
                    if (!poNoCell) return;
                    const po_number = poNoCell.textContent.trim();

                    // Fetch PO data via AJAX
                    fetch('get_po_data.php?po_no=' + encodeURIComponent(po_number))
                        .then(response => response.json())
                        .then(data => {
                            // Fill form fields
                            document.getElementById('order_number').value = data.order_number;
                            document.getElementById('order_date').value = data.order_date;
                            document.getElementById('terms').value = data.terms;
                            document.getElementById('ship_via').value = data.ship_via;
                            document.getElementById('conforme').value = data.conforme;
                            document.getElementById('prepared_by').value = data.prepared_by;
                            document.getElementById('approved_by').value = data.approved_by;
                            document.getElementById('accounting').value = data.accounting;
                            document.getElementById('manager').value = data.manager;
                            document.getElementById('supplier_id').value = data.supplier_id;

                            // Fill item rows
                            const tbody = document.getElementById('line-items-body');
                            tbody.innerHTML = '';
                            data.items.forEach(function(item) {
                                const tr = document.createElement('tr');
                                tr.innerHTML =
                                    '<td><input type="text" name="description[]" value="' + item.description + '"></td>' +
                                    '<td><input type="text" name="part_no[]" value="' + item.part_no + '"></td>' +
                                    '<td><input type="date" name="delivery_date[]" value="' + item.delivery_date + '"></td>' +
                                    '<td><input type="number" name="quantity[]" min="1" value="' + item.quantity + '"></td>' +
                                    '<td><input type="number" name="net_price[]" step="0.01" value="' + item.net_price + '"></td>' +
                                    '<td><input type="number" name="sales_tax[]" step="0.01" value="' + item.sales_tax + '"></td>' +
                                    '<td><input type="number" name="amount[]" step="0.01" value="' + item.amount + '"></td>' +
                                    '<td style="text-align:center;"><button type="button" class="delete-row-btn" onclick="deleteRow(this)" style="background:#ef4444;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;margin:auto;display:block;"><i class="fa fa-trash"></i></button></td>';
                                tbody.appendChild(tr);
                            });

                            // Show Edit/Cancel, hide Add
                            document.getElementById('addBtn').style.display = 'none';
                            document.getElementById('editBtn').style.display = '';
                            document.getElementById('cancelBtn').style.display = '';
                        });
                });
            });

            // Confirm Edit and Cancel Order button logic
            document.getElementById('editBtn').addEventListener('click', function() {
                document.getElementById('edit_mode').value = '1';
                document.querySelector('.order-form').submit();
            });
            document.getElementById('cancelBtn').addEventListener('click', function() {
                document.getElementById('cancel_order').value = '1';
                document.querySelector('.order-form').submit();
            });
            document.getElementById('clearBtn').addEventListener('click', function() {
                const form = document.querySelector('.order-form');
                form.reset();
                // Clear all item rows except the first
                const tbody = document.getElementById('line-items-body');
                while (tbody.rows.length > 1) {
                    tbody.deleteRow(1);
                }
                // Clear first row's inputs
                tbody.querySelectorAll('input').forEach(input => input.value = '');
                // Reset buttons to Add mode
                document.getElementById('addBtn').style.display = '';
                document.getElementById('editBtn').style.display = 'none';
                document.getElementById('cancelBtn').style.display = 'none';
                document.getElementById('edit_mode').value = '0';
                document.querySelectorAll('tbody > tr').forEach(r => r.style.background = '');
            });
        });

        // --- Purchase Order History Filter Logic ---
    // Save original table rows for reliable filtering
    let poHistoryOriginalGroups = null;

    function getGroupedRows() {
        const tbody = document.getElementById('po-history-body');
        const allRows = Array.from(tbody.querySelectorAll('tr'));
        const groups = [];
        let i = 0;
        while (i < allRows.length) {
            const mainRow = allRows[i];
            if (!mainRow.classList.contains('main-po-row')) {
                i++;
                continue;
            }
            const group = [mainRow];
            let j = i + 1;
            while (j < allRows.length && allRows[j].classList.contains('item-po-row')) {
                group.push(allRows[j]);
                j++;
            }
            groups.push(group);
            i = j;
        }
        return groups;
    }

    // Save original groups on first load
    if (!poHistoryOriginalGroups) {
        poHistoryOriginalGroups = getGroupedRows();
    }

    // Prevent filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
    });

    function filterPOHistory() {
        const sortOrder = document.getElementById('sortOrder').value;
        const statusFilter = document.getElementById('statusFilter').value.trim().toLowerCase();
        const tbody = document.getElementById('po-history-body');

        let filteredGroups = poHistoryOriginalGroups.filter(group => {
            const mainRow = group[0];
            const statusSpan = mainRow.querySelector('td[rowspan] span');
            let statusText = statusSpan ? statusSpan.textContent.trim().toLowerCase() : '';
            if (!statusFilter) return true;
            if (statusFilter === "to-deliver") {
                return statusText === "to-deliver" || statusText === "for-delivery";
            }
            return statusText === statusFilter;
        });

        filteredGroups.sort(function(a, b) {
            const dateA = new Date(a[0].querySelector('td:nth-child(3)').textContent.trim());
            const dateB = new Date(b[0].querySelector('td:nth-child(3)').textContent.trim());
            return sortOrder === 'asc' ? dateA - dateB : dateB - dateA;
        });

        tbody.innerHTML = '';
        filteredGroups.forEach(group => {
            group.forEach(row => tbody.appendChild(row));
        });
    }

    document.getElementById('sortOrder').addEventListener('change', filterPOHistory);
    document.getElementById('statusFilter').addEventListener('change', filterPOHistory);

    // Optionally, remove the Apply button from the DOM
    const applyBtn = document.getElementById('applyFilterBtn');
    if (applyBtn) applyBtn.style.display = 'none';
    </script>

</body>
</html>

<?php
$conn->close();
?>
<?php
// delivery_receipt.php
// ==== CONFIG & DB CONNECTION ====
$host = 'localhost';
$db   = 'james_polymer_erp';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ==== HELPERS ====
// escape output
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Generate and insert a new receipt while holding an advisory lock to avoid duplicate DR numbers.
// Returns [new_dr_id, new_dr_no]
function createReceiptWithLockedDrNo(PDO $pdo, array $data) {
    // try to obtain lock for 10 seconds
    $got = (int)$pdo->query("SELECT GET_LOCK('dr_no_gen', 10)")->fetchColumn();
    if (!$got) {
        throw new Exception("Could not obtain lock for DR number generation.");
    }

    try {
        // get last DR (ordered by id desc) and increment numeric suffix
        $lastDr = $pdo->query("SELECT dr_no FROM delivery_receipts ORDER BY id DESC LIMIT 1")->fetchColumn();
        if ($lastDr && preg_match('/(\d+)$/', $lastDr, $m)) {
            $num = str_pad((int)$m[1] + 1, strlen($m[1]), '0', STR_PAD_LEFT);
            $newDr = preg_replace('/\d+$/', $num, $lastDr);
        } else {
            $newDr = $lastDr ? $lastDr . '1' : 'DR001';
        }

        // Insert new receipt using generated $newDr
        $stmt = $pdo->prepare("
            INSERT INTO delivery_receipts 
            (dr_no, deliver_to, address, tin_no, sc_tin_no, osca_pwd_id, po_number, terms, date, prepared_by, received_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $newDr,
            $data['deliver_to'] ?? '',
            $data['address'] ?? '',
            $data['tin_no'] ?? '',
            $data['sc_tin_no'] ?? '',
            $data['osca_pwd_id'] ?? '',
            $data['po_number'] ?? '',
            $data['terms'] ?? '',
            $data['date'] ?? date('Y-m-d'),
            $data['prepared_by'] ?? '',
            $data['received_by'] ?? '',
        ]);

        $newId = $pdo->lastInsertId();
    } finally {
        // always release lock
        $pdo->query("SELECT RELEASE_LOCK('dr_no_gen')");
    }

    return [$newId, $newDr];
}

// ==== REQUEST PARAMS ====
$dr_id     = isset($_GET['dr_id']) ? (int)$_GET['dr_id'] : null;
$dr_no_q   = $_GET['dr_no'] ?? null; // support lookup by dr_no (history links may pass dr_no)
$po_number = $_GET['po_number'] ?? null;
$mode      = $_GET['mode'] ?? 'edit';

// normalize mode
$mode = in_array($mode, ['view', 'edit', 'print']) ? $mode : 'edit';

$receiptData = [];
$itemData    = [];

// ==== LOAD DATA ====
// If dr_no provided, try to fetch record by dr_no and populate $dr_id
if ($dr_no_q && !$dr_id) {
    $stmt = $pdo->prepare("SELECT * FROM delivery_receipts WHERE dr_no = ? LIMIT 1");
    $stmt->execute([$dr_no_q]);
    $r = $stmt->fetch();
    if ($r) {
        $receiptData = $r;
        $dr_id = (int)$r['id'];
    }
}

// If dr_id (either from GET or from dr_no lookup) then load the receipt + items
if ($dr_id) {
    $stmt = $pdo->prepare("SELECT * FROM delivery_receipts WHERE id = ?");
    $stmt->execute([$dr_id]);
    $receiptData = $stmt->fetch() ?: [];

    $stmt = $pdo->prepare("SELECT * FROM delivery_receipt_items WHERE delivery_receipt_id = ?");
    $stmt->execute([$dr_id]);
    $itemData = $stmt->fetchAll();
} elseif ($po_number) {
    // Prepare based on PO if provided
    $stmt = $pdo->prepare("SELECT id, order_number FROM purchase_orders_sample WHERE order_number = ?");
    $stmt->execute([$po_number]);
    if ($po = $stmt->fetch()) {
        $receiptData = [
            'po_number' => $po['order_number'],
            'date' => date('Y-m-d'),
        ];
        $stmt_items = $pdo->prepare("SELECT quantity AS qty, '' AS unit, description FROM purchase_order_items WHERE purchase_order_id = ?");
        $stmt_items->execute([$po['id']]);
        $itemData = $stmt_items->fetchAll();
    } else {
        $receiptData['date'] = date('Y-m-d');
    }
} else {
    $receiptData['date'] = date('Y-m-d');
}

// ==== HANDLE SAVE (POST) ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // Ensure dr_id is passed back in POST for updates
        $posted_dr_id = !empty($_POST['dr_id']) ? (int)$_POST['dr_id'] : null;

        if (empty($posted_dr_id)) {
            // create new receipt using locked generator (safe)
            list($created_id, $created_dr_no) = createReceiptWithLockedDrNo($pdo, $_POST);
            $dr_id = $created_id;
        } else {
            // update existing
            $stmt = $pdo->prepare("
                UPDATE delivery_receipts 
                SET deliver_to = ?, address = ?, tin_no = ?, sc_tin_no = ?, osca_pwd_id = ?, po_number = ?, terms = ?, date = ?, prepared_by = ?, received_by = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['deliver_to'] ?? '',
                $_POST['address'] ?? '',
                $_POST['tin_no'] ?? '',
                $_POST['sc_tin_no'] ?? '',
                $_POST['osca_pwd_id'] ?? '',
                $_POST['po_number'] ?? '',
                $_POST['terms'] ?? '',
                $_POST['date'] ?? date('Y-m-d'),
                $_POST['prepared_by'] ?? '',
                $_POST['received_by'] ?? '',
                $posted_dr_id,
            ]);
            $dr_id = $posted_dr_id;

            // remove existing items, we'll re-insert
            $pdo->prepare("DELETE FROM delivery_receipt_items WHERE delivery_receipt_id = ?")->execute([$dr_id]);
        }

        // insert items (if any)
        if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
            $stmtItems = $pdo->prepare("INSERT INTO delivery_receipt_items (delivery_receipt_id, qty, unit, description) VALUES (?, ?, ?, ?)");
            foreach ($_POST['qty'] as $i => $qty) {
                $unit = $_POST['unit'][$i] ?? '';
                $desc = $_POST['description'][$i] ?? '';
                if (trim($qty) !== '' || trim($desc) !== '') {
                    $stmtItems->execute([$dr_id, $qty, $unit, $desc]);
                }
            }
        }

        // Update purchase order status to To-Deliver
        $updateMessage = '';
        if (!empty($_POST['po_number'])) {
            try {
                // First get the PO ID and current status
                $checkPO = $pdo->prepare("SELECT id, order_number, status FROM purchase_orders_sample WHERE order_number = ?");
                $checkPO->execute([$_POST['po_number']]);
                $currentPO = $checkPO->fetch();

                if ($currentPO !== false) {
                    // Debug: Log current PO details
                    error_log("Current PO details - ID: " . $currentPO['id'] . ", Order Number: " . $currentPO['order_number'] . ", Current Status: " . ($currentPO['status'] ?? 'NULL'));

                    // Update the PO status to For-Delivery according to the database ENUM
                    $stmtUpdatePO = $pdo->prepare("
                        UPDATE purchase_orders_sample 
                        SET status = 'For-Delivery'
                        WHERE order_number = ?
                    ");
                    $result = $stmtUpdatePO->execute([$_POST['po_number']]);
                    
                    // Verify the update and check for any SQL errors
                    if ($result) {
                        // Double check the new status
                        $verifyStmt = $pdo->prepare("SELECT status FROM purchase_orders_sample WHERE order_number = ?");
                        $verifyStmt->execute([$_POST['po_number']]);
                        $newStatus = $verifyStmt->fetchColumn();

                        if ($newStatus === 'For-Delivery') {
                            $updateMessage = "Successfully updated PO: " . $_POST['po_number'] . " - Status changed to: DELIVERED";
                        } else {
                            $updateMessage = "Warning: Status update verification failed for PO: " . $_POST['po_number'] . ". Current status: " . ($newStatus ?? 'NULL');
                        }
                    } else {
                        // Get any database error info
                        $errorInfo = $stmtUpdatePO->errorInfo();
                        $updateMessage = "Warning: Could not update status for PO: " . $_POST['po_number'] . 
                                       "\nDatabase message: " . ($errorInfo[2] ?? 'No specific error message');
                    }
                } else {
                    $updateMessage = "Error: Purchase Order " . $_POST['po_number'] . " not found in database";
                }
            } catch (PDOException $e) {
                $updateMessage = "Error updating PO status: " . $e->getMessage();
                throw $e;
            }
        } else {
            $updateMessage = "No Purchase Order number provided";
        }

        $pdo->commit();

        // Show message and redirect properly
        if (!empty($updateMessage)) {
            $_SESSION['message'] = $updateMessage;
        }
        header("Location: delivery_receipt.php?dr_id={$dr_id}&mode=view");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error saving: " . $e->getMessage());
    }
}

// mode label for UI
$modeLabel = strtoupper($mode) . " MODE";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Receipt - <?= esc($receiptData['dr_no'] ?? 'New') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid black; padding-bottom: 10px; position: relative; }
        .header h1 { margin: 0; font-size: 28px; font-weight: bold; }
        .company-address { font-size: 14px; margin-top: 5px; }
        .contact-info { font-size: 13px; margin-top: 3px; }
        .top-right { position: absolute; top: 0; right: 0; text-align: right; }
        .inline-fields { display: flex; gap: 10px; margin-top:10px; }
        .inline-fields label { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 5px; }
        input, textarea { width: 100%; box-sizing: border-box; }
        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold; cursor: pointer; display:inline-flex; align-items:center; gap:6px; }
        .btn-blue { background:#007BFF; color:#fff; border:none; }
        .btn-green { background:#28a745; color:#fff; border:none; }
        .btn-gray { background:#6c757d; color:#fff; border:none; }
        .no-border { border: none; background: transparent; }
        .mode-badge { position:absolute; left:0; top:0; padding:6px 10px; background:#222; color:#fff; border-radius:4px; font-weight:bold; }

        /* === CHANGES FOR PRINT MODE === */
        <?php if ($mode === 'print'): ?>
        body {
            margin: 0; /* reduced margin for wider content */
            font-size: 16px;
        }

        .header {
            position: relative !important; /* keep relative for print */
            padding-top: 25px !important; /* add space on top so DR No fits */
        }

        .top-right {
            position: absolute !important;
            top: 0 !important;
            left: 518px !important; /* move left to avoid overlap */
            right: 0 !important;
            text-align: right !important;
            margin: 0 !important;
            float: none !important;
            margin-bottom: 40px !important; /* add space below */
        }

        .form-section {
            max-width: 1000px !important; /* limit width in print */
            margin: 0 auto !important;
            padding: 2px 7px !important; /* less padding */
            background: #fff !important;
            border: none !important;
            box-shadow: none !important;
        }
        /* Hide the mode badge in print */
        .mode-badge {
            display: none !important;
        }
        /* Make all inputs and textarea readonly-like in print with no borders and no background */
        input, textarea {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            color: black !important;
            font-size: 15px !important; /* slightly larger for readability */
        }
        /* Hide all buttons and links in print */
        .btn, a.btn {
            display: none !important;
        }
        <?php endif; ?>

        /* Default print media - also hide buttons */
        @media print { 
            .btn, a.btn { display:none !important; } 
        }
    </style>
</head>
<body <?php if ($mode === 'print') echo 'onload="window.print()"'; ?>>

<div style="max-width:900px; margin:auto; padding:10px; display:flex; justify-content:flex-end; gap:10px;">
    <?php if ($mode === 'view'): ?>
        <a href="delivery_receipt.php?<?= $dr_id ? 'dr_id=' . (int)$dr_id . '&' : '' ?>mode=edit" class="btn btn-blue">✏️ Edit</a>
        <a href="delivery_receipt.php?<?= $dr_id ? 'dr_id=' . (int)$dr_id . '&' : '' ?>mode=print" class="btn btn-green" target="_blank">🖨️ Print</a>
        <a href="transactions.php" class="btn btn-gray">⬅️ Return</a>
    <?php elseif ($mode === 'edit'): ?>
        <button type="submit" form="drForm" class="btn btn-blue">💾 Save</button>
        <a href="delivery_receipt.php?<?= $dr_id ? 'dr_id=' . (int)$dr_id . '&' : '' ?>mode=view" class="btn btn-gray">❌ Cancel</a>
    <?php elseif ($mode === 'print'): ?>
        <a href="delivery_receipt.php?<?= $dr_id ? 'dr_id=' . (int)$dr_id . '&' : '' ?>mode=view" class="btn btn-gray">Close</a>
    <?php endif; ?>
</div>

<div class="form-section" style="max-width:900px; margin:auto; padding:20px; background:#fafafa; border:1px solid #000; position:relative;">
    <div class="mode-badge"><?= esc($modeLabel) ?></div>

    <form method="POST" id="drForm">
        <input type="hidden" name="dr_id" value="<?= esc($dr_id) ?>">
        <div class="header">
            <h1>JAMES POLYMERS MFG. CORP.</h1>
            <div class="company-address">16 Aguinaldo H-Way, Panapaan 2, Bacoor, Cavite</div>
            <div class="contact-info">Tel. Nos.: (046) 417-1097 -- Fax: (046) 417-3566 -- Direct Line: 529-8978<br>VAT Reg. TIN: 007-165-671-000</div>
            <div class="top-right">
                <label>D.R. No.:
                    <input type="text" name="dr_no_display" style="width:110px;" value="<?= esc($receiptData['dr_no'] ?? '') ?>" readonly class="no-border">
                </label>
            </div>
        </div>

        <h2 style="text-align:left; margin-top:10px;">DELIVERY RECEIPT</h2>

        <div style="text-align:right;">
            <label style="font-weight:bold;">Date:
                <input type="date" name="date" value="<?= esc($receiptData['date'] ?? date('Y-m-d')) ?>" style="width:320px; height:28px;" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>>
            </label>
        </div>

        <div class="inline-fields">
            <label>DELIVER TO:
                <input type="text" name="deliver_to" value="<?= esc($receiptData['deliver_to'] ?? '') ?>" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>>
            </label>
            <label>TIN:
                <input type="text" name="tin_no" value="<?= esc($receiptData['tin_no'] ?? '') ?>" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>>
            </label>
        </div>

        <div class="inline-fields">
            <label>Address:
                <textarea name="address" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>><?= esc($receiptData['address'] ?? '') ?></textarea>
            </label>
            <label>P.O. No.:
                <!-- PO number should not be editable -->
                <input type="text" name="po_number" value="<?= esc($receiptData['po_number'] ?? '') ?>" readonly class="no-border">
            </label>
        </div>

        <div class="inline-fields">
            <label>SC TIN No.:
                <input type="text" name="sc_tin_no" value="<?= esc($receiptData['sc_tin_no'] ?? '') ?>" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>>
            </label>
            <label>OSCA/PWD ID No. & Sig.:
                <input type="text" name="osca_pwd_id" value="<?= esc($receiptData['osca_pwd_id'] ?? '') ?>" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>>
            </label>
            <label>Terms:
                <input type="text" name="terms" value="<?= esc($receiptData['terms'] ?? '') ?>" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>>
            </label>
        </div>

        <h3>Items</h3>
        <table id="itemsTable">
            <thead>
                <tr><th>Qty</th><th>Unit</th><th>Description</th><?= $mode === 'edit' ? '<th>Action</th>' : '' ?></tr>
            </thead>
            <tbody>
                <?php
                $rows = max(count($itemData), 5);
                for ($i = 0; $i < $rows; $i++):
                    $qty = $itemData[$i]['qty'] ?? '';
                    $unit = $itemData[$i]['unit'] ?? '';
                    $desc = $itemData[$i]['description'] ?? '';
                ?>
                <tr>
                    <td><input type="text" name="qty[]" value="<?= esc($qty) ?>" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>></td>
                    <td><input type="text" name="unit[]" value="<?= esc($unit) ?>" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>></td>
                    <td><input type="text" name="description[]" value="<?= esc($desc) ?>" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>></td>
                    <?php if ($mode === 'edit'): ?><td><button type="button" class="btn btn-gray removeRowBtn">Remove</button></td><?php endif; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- REMOVED: Buttons for Add Row, Edit, Print inside the form -->

        <p style="font-weight:bold; margin-top:10px;">This Document is Not a Valid Source For Claiming Input Tax</p>

        <hr style="margin-top:10px; margin-bottom:10px;">

        <div style="display:flex; justify-content:space-between; text-align:center; gap:20px;">
            <div style="width:45%;">
                <input type="text" name="prepared_by" value="<?= esc($receiptData['prepared_by'] ?? '') ?>" style="width:100%; border:none; border-bottom:1px solid #000; text-align:center; font-size:14px;" placeholder="Checked by name/signature" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>>
                <div style="margin-top:5px;">Checked by (Authorized Signature)</div>
            </div>
            <div style="width:45%;">
                <input type="text" name="received_by" value="<?= esc($receiptData['received_by'] ?? '') ?>" style="width:100%; border:none; border-bottom:1px solid #000; text-align:center; font-size:14px;" placeholder="By name/signature" <?= ($mode === 'view' || $mode === 'print') ? 'readonly class="no-border"' : '' ?>>
                <div style="margin-top:5px;">By (Authorized Signature)</div>
            </div>
        </div>
    </form>
</div>

<?php if ($mode === 'edit'): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const tableBody = document.querySelector("#itemsTable tbody");
    const addRowBtn = document.getElementById("addRowBtn"); // no longer exists but harmless

    function bindRemoveButtons() {
        document.querySelectorAll(".removeRowBtn").forEach(btn => {
            btn.onclick = function () {
                if (tableBody.rows.length > 1) {
                    this.closest("tr").remove();
                } else {
                    alert("At least one row is required.");
                }
            };
        });
    }

    bindRemoveButtons();
});
</script>
<?php endif; ?>

</body>
</html>
<?php
require_once 'db_pdo.php';

$mode = $_GET['mode'] ?? 'view';
$id = $_GET['id'] ?? null;
$quotation = [];

$isView = $mode === 'view';
$isPrint = $mode === 'print';
$isEdit = $mode === 'edit';

// DELETE MODE
if ($mode === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM quotations WHERE id = ?");
    $stmt->execute([$id]);

    echo "<script>alert('Quotation deleted successfully.'); window.location.href='transactions.php?tab=quotation';</script>";
    exit;
}

// FETCH quotation if ID is provided (for view, edit, or print)
if ($id) {
    // Fetch main quotation
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
    $stmt->execute([$id]);
    $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quotation) {
        die("Quotation not found.");
    }

    // Fetch items for this quotation
    $item_stmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $item_stmt->execute([$id]);
    $quotation_items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch terms and agreements for this quotation
    $terms_stmt = $pdo->prepare("
        SELECT * FROM quotation_terms 
        WHERE quotation_id = ? 
        ORDER BY display_order ASC
    ");
    $terms_stmt->execute([$id]);
    $quotation_terms = $terms_stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    $quotation_items = [];
    $quotation_terms = [];
}



// UPDATE MODE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update main quotation
    // ...existing code for main quotation update (if needed)...

    $quotation_id = $_POST['id'];
    $pdo->beginTransaction();
    try {
        // Fetch previous items (to preserve images if not replaced/removed)
        $prevItems = [];
        $item_stmt = $pdo->prepare("SELECT item, product_image_path FROM quotation_items WHERE quotation_id = ?");
        $item_stmt->execute([$quotation_id]);
        foreach ($item_stmt->fetchAll(PDO::FETCH_ASSOC) as $idx => $row) {
            $prevItems[$idx] = $row;
        }

        // Delete old items
        $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$quotation_id]);

        // Prepare insert
        $itemSql = "INSERT INTO quotation_items (quotation_id, item, description, qty, unit, unit_price, total, product_image_path)
                    VALUES (:quotation_id, :item, :description, :qty, :unit, :unit_price, :total, :product_image_path)";
        $itemStmt = $pdo->prepare($itemSql);

        $itemArr = isset($_POST['item']) ? (array)$_POST['item'] : [];
        $descArr = isset($_POST['description']) ? (array)$_POST['description'] : [];
        $qtyArr = isset($_POST['qty']) ? (array)$_POST['qty'] : [];
        $unitArr = isset($_POST['unit']) ? (array)$_POST['unit'] : [];
        $unitPriceArr = isset($_POST['unit_price']) ? (array)$_POST['unit_price'] : [];
        $totalArr = isset($_POST['total']) ? (array)$_POST['total'] : [];

        // --- IMAGE UPLOAD/REPLACE/REMOVE LOGIC ---
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $dateStr = date('Ymd-His');
        $fileInputs = isset($_FILES['product_image']) ? $_FILES['product_image'] : null;
        $removeImages = isset($_POST['remove_image']) ? $_POST['remove_image'] : [];

        foreach ($itemArr as $i => $itemVal) {
            $imgPath = '';
            $prevImg = isset($prevItems[$i]['product_image_path']) ? $prevItems[$i]['product_image_path'] : '';

            // Remove image if requested (delete from uploads/)
            if (is_array($removeImages) && (in_array((string)$i, $removeImages, true) || in_array($i, $removeImages, true))) {
                if (!empty($prevImg) && file_exists($prevImg)) {
                    unlink($prevImg);
                }
                $imgPath = '';
            }
            // Replace image if new file uploaded
            elseif ($fileInputs && isset($fileInputs['name'][$i]) && $fileInputs['name'][$i] !== '') {
                $imgName = $fileInputs['name'][$i];
                $tmpName = $fileInputs['tmp_name'][$i];
                $itemNameSanitized = preg_replace('/[^A-Za-z0-9_\-]/', '_', $itemVal);
                $ext = pathinfo($imgName, PATHINFO_EXTENSION);
                $newFileName = $itemNameSanitized . '-' . $dateStr . '.' . $ext;
                $targetPath = $uploadDir . $newFileName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    // Remove old image if exists
                    if (!empty($prevImg) && file_exists($prevImg)) {
                        unlink($prevImg);
                    }
                    $imgPath = $targetPath;
                }
            }
            // Keep previous image if not removed or replaced
            else {
                $imgPath = $prevImg;
            }

            $qty = isset($qtyArr[$i]) ? floatval($qtyArr[$i]) : 0;
            $unit_price = isset($unitPriceArr[$i]) ? floatval($unitPriceArr[$i]) : 0;
            $total = isset($totalArr[$i]) && $totalArr[$i] !== '' ? $totalArr[$i] : number_format($qty * $unit_price, 2, '.', '');

            $itemStmt->execute([
                ':quotation_id' => $quotation_id,
                ':item' => $itemVal,
                ':description' => $descArr[$i] ?? '',
                ':qty' => $qty,
                ':unit' => $unitArr[$i] ?? '',
                ':unit_price' => $unit_price,
                ':total' => $total,
                ':product_image_path' => $imgPath
            ]);
        }

                // Update terms
                $pdo->prepare("DELETE FROM quotation_terms WHERE quotation_id = ?")->execute([$quotation_id]);
                
                $termSql = "INSERT INTO quotation_terms (quotation_id, term_text, term_type, days_value, display_order) 
                            VALUES (:quotation_id, :term_text, :term_type, :days_value, :display_order)";
                $termStmt = $pdo->prepare($termSql);
                
                $term_texts = $_POST['term_texts'] ?? [];
                $term_types = $_POST['term_types'] ?? [];
                $days_values = $_POST['days_values'] ?? [];
                
                foreach ($term_texts as $index => $text) {
                    $termStmt->execute([
                        ':quotation_id' => $quotation_id,
                        ':term_text' => $text,
                        ':term_type' => $term_types[$index] ?? 'custom',
                        ':days_value' => $days_values[$index] ?? null,
                        ':display_order' => $index + 1
                    ]);
                }
                

        $pdo->commit();
        echo "<script>alert('Quotation updated successfully!'); window.location.href='quotation_entry.php?id={$_POST['id']}&mode=view';</script>";
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<pre>PDO Error: " . $e->getMessage() . "</pre>";
    }
}

// Auto print if print mode
if ($isPrint) {
    echo "<script>
        window.onload = function() { 
            window.print(); 
            window.onafterprint = function() { window.close(); };
        };
    </script>";

    
}

?>


<!DOCTYPE html>
<html>
<head>
    <title><?= $isView ? 'View' : 'Edit' ?> Quotation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .quote-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 20px;
            }

            .quote-info div {
            font-size: 13px;
            }

            .attention-table, .product-table, .terms {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            }

            .attention-table td, .product-table th, .product-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
            }

            .product-table th {
            background-color: #f0f0f0;
            text-align: left;
            }

            .image-section {
            margin-bottom: 20px;
            }

            .image-section img {
            width: 300px;
            height: 150px;
            background-color: #0033cc;
            display: block;
            }

            .terms {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 18px 18px 10px 18px;
    box-shadow: 0 1px 4px 0 #e2e8f0;
}
.terms p {
    font-size: 16px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 10px;
}
.terms ol {
    padding-left: 18px;
    margin: 0;
}
.terms li {
    margin-bottom: 10px;
    background: #fff;
    border-radius: 6px;
    padding: 10px 12px 10px 18px;
    font-size: 15px;
    color: #1e293b;
    position: relative;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px 0 #e2e8f0;
    transition: background 0.18s;
}
.terms li[contenteditable="true"]:focus {
    outline: 2px solid #2563eb;
    background: #e0e7ff;
}
.terms .delete-btn {
    position: absolute;
    right: 10px;
    top: 10px;
    background: #e11d48;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.85;
    transition: background 0.18s;
}
.terms .delete-btn:hover {
    background: #be123c;
    opacity: 1;
}
#addTermBtn {
    margin-top: 10px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 18px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s;
}
#addTermBtn:hover {
    background: #1d4ed8;
}

            .footer {
            margin-top: 40px;
            }

            .footer h4 {
            margin-bottom: 5px;
            }

            .totals {
            float: right;
            width: 300px;
            }

            .totals td {
            padding: 5px;
            }

            .bold {
            font-weight: bold;
            }

            .quotation-scroll-container {
                max-width: 1000px;
                margin: 0 auto;
                padding: 20px;
                overflow-x: auto;
            }

            body {
                font-family: Arial, sans-serif;
                background-color: #f9f9f9;
                margin: 0;
                padding: 0;
            }

            .container {
                max-width: 900px;
                height: 1880px;
                margin: 40px auto;
                padding: 30px;
                background: #fff;
                box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
            }

            input[type="text"],
            input[type="email"],
            input[type="number"],
            input[type="date"],
            textarea {
                width: 100%;
                padding: 8px;
                font-size: 14px;
                border-radius: 4px;
                border: 1px solid #ccc;
                box-sizing: border-box;
                margin-top: 4px;
            }

            textarea {
                resize: vertical;
            }

            .button-group {
                text-align: center;
                margin-top: 30px;
            }

            .button-group a,
            .button-group button {
                display: inline-block;
                padding: 10px 20px;
                margin: 5px;
                font-size: 14px;
                border: none;
                border-radius: 5px;
                text-decoration: none;
                color: white;
                background-color: #007BFF;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            .button-group a:hover,
            .button-group button:hover {
                background-color: #0056b3;
            }

            .button-group a:visited {
                color: white;
            }

            input[readonly],
            textarea[readonly] {
                background-color: #f1f1f1;
            }

            .header2 {
                display: flex;
                align-items: center;
                gap: 20px;
                margin-bottom: 20px;
                background-color: white;
                padding: 15px 25px;
                }

                .header2 img {
                width: 60px;
                height: auto;
                }

                .header-text {
                text-align: left;
                margin: 0; /* remove extra margins */
                }

                .header-text h2 {
                color: #003366;
                margin: 0 0 4px;
                font-size: 2rem;
                }

                .header-text p {
                margin: 0;
                font-size: 16px;
                }

                .print-btn {
                margin-top: 20px;
                padding: 10px 20px;
                background: #4caf50;
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: bold;
                cursor: pointer;
                }

                .print-btn:hover {
                background-color: #45a049;
                }

                @media print {
                    /* General layout */
                    html, body {
                        background: white !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        overflow: visible !important;
                        font-size: 12px !important; /* ← was 10px */
                        line-height: 1; /* ← was 1.2 */
                    }

                    input[type="text"],
                    input[type="number"],
                    input[type="email"],
                    input[type="date"],
                    textarea,
                    .product-table input,
                    .attention-table input,
                    .attention-table textarea,
                    .totals input {
                        font-size: 10px !important; /* ← was 9px */
                    }

                    .container {
                        max-width: 900px;
                        height: 1750px;
                        margin: 40px auto;
                        padding: 30px;
                        background: #fff;
                        box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
                        border-radius: 8px;
                    }


                    @page {
                        size: A4 portrait; /* or letter */
                        margin: 0.25in;
                    }

                    /* Hide everything by default */
                    body * {
                        visibility: hidden;
                    }

                    /* Show only the quotation paper */
                    .quotation-paper, .quotation-paper * {
                        visibility: visible;
                    }

                    .quotation-paper {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        margin: 0;
                        padding: 0;
                        font-size: 12px !important;
                        box-shadow: none !important;
                        border: none !important;
                    }

                    /* Reduce header size */
                    .header2 {
                        gap: 5px;
                        padding: 13px 9px;
                    }

                    .header2 img {
                        width: 35px !important;
                    }

                    .header-text h2 {
                        font-size: 13px !important;
                    }

                    .header-text p {
                        font-size: 10px !important;
                    }

                    /* Resize image */
                    .image-section img {
                        width: 190px !important;
                        height: auto !important;
                        max-height: 100px !important;
                    }

                    /* Tables */
                    .product-table th,
                    .product-table td,
                    .attention-table td,
                    .terms,
                    .footer,
                    .totals td {
                        font-size: 9px !important; /* ← was 9px */
                        padding: 5px 7px !important; /* ← slight padding increase */
                    }

                    .product-table input,
                    .attention-table input,
                    .attention-table textarea,
                    .totals input {
                        font-size: 9px !important;
                        padding: 3px !important;
                    }

                    /* Terms section */
                    .terms ol li {
                        margin-bottom: 2px;
                        font-size: 11px !important;
                    }

                    /* Quotation ID visible */
                    .quotation-id-print {
                        font-weight: bold;
                        font-size: 5px;
                        margin-bottom: 8px;
                    }

                    /* Prevent breaking */
                    .product-table,
                    .attention-table,
                    .footer,
                    .header2,
                    .terms,
                    .image-section {
                        page-break-inside: avoid;
                    }

                    /* Hide UI elements */
                    .print-btn,
                    .submit-btn,
                    .save-btn,
                    .back-btn,
                    .edit-btn,
                    .view-btn,
                    .file-upload,
                    .floating-chat,
                    .tabs,
                    nav,
                    .sidebar,
                    .chat-widget,
                    .floating-button,
                    .header,
                    .footer-bar {
                        display: none !important;
                    }

                    .product-preview-img {
                        width: 180px !important;
                        height: auto !important;
                        max-height: 80px !important;
                    }

                    .quotation-paper ol {
                        list-style-position: inside !important;
                    }

                    .quotation-paper ol li::marker {
                        visibility: visible !important;
                    }

                    /* Hide file inputs and remove checkboxes in print */
                    input[type="file"], .remove-image-checkbox, label[for^="remove_image"] {
                        display: none !important;
                        visibility: hidden !important;
                    }
                    /* Hide empty divs that only contain file input or remove checkbox */
                    .footer div > input[type="file"]:only-child,
                    .footer div > label.remove-image-checkbox:only-child {
                        display: none !important;
                    }
                    /* Prevent extra blank page: force single page */
                    html, body, .container, .quotation-paper {
                        page-break-after: avoid !important;
                        page-break-before: avoid !important;
                        page-break-inside: avoid !important;
                        break-after: avoid-page !important;
                        break-before: avoid-page !important;
                        break-inside: avoid-page !important;
                        height: auto !important;
                        min-height: 0 !important;
                        max-height: none !important;
                    }
                }         
    </style>
</head>
<body>
<div class="container">
    <div class="quotation-paper">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($quotation['id'] ?? '') ?>">
            <div class="header2">
                <img src="images/logo.png" alt="Company Logo" width="60">
                <div class="header-text">
                    <h2>JAMES POLYMERS MANUFACTURING CORPORATION</h2>
                    <p>16 AGUINALDO HIGHWAY, PANAPAAN II, BACOOR, CAVITE</p>
                </div>
            </div>

            <label><strong>Quotation No. / File ID:</strong></label><br>
            <input type="text" style="width:auto;" value="<?= htmlspecialchars($quotation['quotation_no'] ?? '') ?>" readonly><br>

            <label><strong>Date:</strong></label><br>
            <input type="date" name="quotation_date" style="width:auto;" value="<?= htmlspecialchars($quotation['quotation_date'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>><br>

            <br>
            <table class="attention-table">
                <tr>
                    <td><label><strong>ATTENTION TO:</strong></label><br>
                        <input type="text" name="attention_to" value="<?= htmlspecialchars($quotation['attention_to'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    <td><label><strong>POSITION:</strong></label><br>
                        <input type="text" name="position" value="<?= htmlspecialchars($quotation['position'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                </tr>
                <tr>
                    <td><label><strong>COMPANY NAME:</strong></label><br>
                        <input type="text" name="company_name" value="<?= htmlspecialchars($quotation['company_name'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    <td><label><strong>ADDRESS:</strong></label><br>
                        <textarea name="address" <?= $isView ? 'readonly' : '' ?>><?= htmlspecialchars($quotation['address'] ?? '') ?></textarea></td>
                </tr>
                <tr>
                    <td><label><strong>CONTACT NUMBER:</strong></label><br>
                        <input type="text" name="contact_number" value="<?= htmlspecialchars($quotation['contact_number'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    <td><label><strong>EMAIL:</strong></label><br>
                        <input type="email" name="email" value="<?= htmlspecialchars($quotation['email'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                </tr>
                <tr>
                    <td>
                        <label><strong>WEBSITE:</strong></label><br>
                        <input type="text" name="website" value="<?= htmlspecialchars($quotation['website'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    </td>
                    <td>
                        <label><strong>MODE OF PAYMENT:</strong></label><br>
                        <input type="text" name="mode_of_payment" value="<?= htmlspecialchars($quotation['mode_of_payment'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    </td>
                </tr>
            </table>
            <p>Dear Ma'am/Sir,</p>
            <p>In response to your request, we are pleased to submit our quotation on the following concrete spacer with labor, mold and materials supplied by James Polymers Mfg. Corp.</p>
            <br>
            <!-- Product table with dynamic rows (NO IMAGE COLUMN) -->
            <table id="itemsTable" class="product-table">
    <thead>
        <tr>
            <th>ITEM</th>
            <th>DESCRIPTION</th>
            <th>QTY</th>
            <th>U/M</th>
            <th>UNIT PRICE</th>
            <th>TOTAL</th>
            <?php if ($isEdit): ?>
                <th>ACTION</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($quotation_items)): ?>
            <?php foreach ($quotation_items as $i => $item): ?>
            <tr>
                <td><input type="text" name="item[]" value="<?= htmlspecialchars($item['item']) ?>" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)"></td>
                <td><input type="text" name="description[]" value="<?= htmlspecialchars($item['description']) ?>" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)"></td>
                <td><input type="number" name="qty[]" value="<?= htmlspecialchars($item['qty']) ?>" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)"></td>
                <td>
                    <input type="text" name="unit[]" list="units" value="<?= htmlspecialchars($item['unit']) ?>" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)">
                    <datalist id="units">
                        <option value="pcs">
                        <option value="meters">
                        <option value="centimeters">
                        <option value="millimeters">
                        <option value="inches">
                        <option value="feet">
                        <option value="kilograms">
                        <option value="grams">
                        <option value="liters">
                        <option value="milliliters">
                        <option value="boxes">
                        <option value="sheets">
                        <option value="packs">
                        <option value="sets">
                    </datalist>
                </td>
                <td><input type="number" name="unit_price[]" value="<?= htmlspecialchars($item['unit_price']) ?>" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)"></td>
                <td><input type="text" name="total[]" value="<?= htmlspecialchars($item['total']) ?>" readonly></td>
                <?php if ($isEdit): ?>
                    <td><button type="button" class="remove-row-btn" onclick="removeRow(this)">Remove</button></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback for no items -->
            <tr>
                <td><input type="text" name="item[]" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)"></td>
                <td><input type="text" name="description[]" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)"></td>
                <td><input type="number" name="qty[]" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)"></td>
                <td>
                    <input type="text" name="unit[]" list="units" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)">
                    <datalist id="units">
                        <option value="pcs">
                        <option value="meters">
                        <option value="centimeters">
                        <option value="millimeters">
                        <option value="inches">
                        <option value="feet">
                        <option value="kilograms">
                        <option value="grams">
                        <option value="liters">
                        <option value="milliliters">
                        <option value="boxes">
                        <option value="sheets">
                        <option value="packs">
                        <option value="sets">
                    </datalist>
                </td>
                <td><input type="number" name="unit_price[]" <?= $isView ? 'readonly' : '' ?> oninput="updateRowTotal(this)"></td>
                <td><input type="text" name="total[]" readonly></td>
                <?php if ($isEdit): ?>
                    <td><button type="button" class="remove-row-btn" onclick="removeRow(this)">Remove</button></td>
                <?php endif; ?>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($isEdit): ?>
    <button type="button" id="addItemBtn" style="margin-top:10px;">Add New Item</button>
    <script>
    // Add new item row (like the original form)
    document.getElementById('addItemBtn').addEventListener('click', function() {
        let tableBody = document.getElementById("itemsTable").querySelector("tbody");
        let newRow = tableBody.insertRow();

        // ITEM
        let cell1 = newRow.insertCell();
        cell1.innerHTML = `<input type="text" name="item[]" required class="auto-grow" oninput="updateRowTotal(this); updateImagePreview();">`;

        // DESCRIPTION
        let cell2 = newRow.insertCell();
        cell2.innerHTML = `<input type="text" name="description[]" required class="auto-grow" oninput="updateRowTotal(this)">`;

        // QTY
        let cell3 = newRow.insertCell();
        cell3.innerHTML = `<input type="number" name="qty[]" min="1" required class="auto-grow" oninput="updateRowTotal(this)">`;

        // U/M
        let cell4 = newRow.insertCell();
        cell4.innerHTML = `<input type="text" name="unit[]" list="units" required class="auto-grow" oninput="updateRowTotal(this)">
            <datalist id="units">
                <option value="pcs">
                <option value="meters">
                <option value="centimeters">
                <option value="millimeters">
                <option value="inches">
                <option value="feet">
                <option value="kilograms">
                <option value="grams">
                <option value="liters">
                <option value="milliliters">
                <option value="boxes">
                <option value="sheets">
                <option value="packs">
                <option value="sets">
            </datalist>`;

        // UNIT PRICE
        let cell5 = newRow.insertCell();
        cell5.innerHTML = `<input type="number" name="unit_price[]" step="0.01" required class="auto-grow" oninput="updateRowTotal(this)">`;

        // TOTAL
        let cell6 = newRow.insertCell();
        cell6.innerHTML = `<input type="number" name="total[]" step="0.01" readonly class="auto-grow">`;

        // ACTION
        let cell7 = newRow.insertCell();
        cell7.innerHTML = `<button type="button" onclick="removeRow(this)">Remove</button>`;

        // Attach image label update to new ITEM input
        cell1.querySelector('input[name="item[]"]').addEventListener('input', updateImagePreview);
    });

    // Remove row function (for dynamically added rows)
    function removeRow(btn) {
        btn.closest("tr").remove();
        calculateTotals();
    }

    // Update row total function (for dynamically added rows and all edits)
    function updateRowTotal(input) {
        let row = input.closest("tr");
        let qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
        let price = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
        row.querySelector('input[name="total[]"]').value = (qty * price).toFixed(2);
        calculateTotals();
    }

    // Calculate all totals (subtotal, vat, grand total)
    function calculateTotals() {
        let subtotal = 0;
        document.querySelectorAll('input[name="total[]"]').forEach(el => {
            subtotal += parseFloat(el.value) || 0;
        });

        let vat = subtotal * 0.12;
        let grandTotal = subtotal + vat;

        const formatNumber = (num) => num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById("subtotal").value = formatNumber(subtotal);
        document.getElementById("vat").value = formatNumber(vat);
        document.getElementById("grand_total").value = formatNumber(grandTotal);
    }

    // Initial calculation on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Attach updateRowTotal to all editable fields
        document.querySelectorAll('#itemsTable input[name="qty[]"], #itemsTable input[name="unit_price[]"]').forEach(function(input) {
            input.addEventListener('input', function() { updateRowTotal(input); });
        });
        // Initial calculation
        calculateTotals();
    });
    </script>
<?php endif; ?>

            <!-- Totals section: float right, sharp corners, solid black border -->
            <div style="display: flex; flex-direction: row; justify-content: flex-end; position: relative;">
                <div class="totals" style="width: 320px; background: #fff; border-radius: 0; padding: 16px 18px; z-index: 10; position: absolute; right: 0; top: 0;">
                    <table style="width:100%;">
                        <tr>
                            <td style="font-weight:bold;">Subtotal:</td>
                            <td>
                                <input type="text" id="subtotal" name="subtotal"
                                    value="<?= isset($quotation['subtotal']) ? htmlspecialchars(number_format($quotation['subtotal'], 2)) : '' ?>"
                                    readonly style="width:120px; text-align:right;">
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold;">VAT (12%):</td>
                            <td>
                                <input type="text" id="vat" name="vat"
                                    value="<?= isset($quotation['vat']) ? htmlspecialchars(number_format($quotation['vat'], 2)) : '' ?>"
                                    readonly style="width:120px; text-align:right;">
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold;">Total:</td>
                            <td>
                                <input type="text" id="grand_total" name="grand_total"
                                    value="<?= isset($quotation['grand_total']) ? htmlspecialchars(number_format($quotation['grand_total'], 2)) : '' ?>"
                                    readonly style="width:120px; text-align:right;">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <br><br>
            <!-- No clear:both here, so following content is not pushed down -->

            <!-- Product Images Section (exactly where it was originally) -->
            <div class="footer">
                <br><label for="product_image"><strong>PRODUCT IMAGE/S:</strong></label><br>
                <?php if ($isEdit): ?>
                    <div style="margin-bottom:10px;">
                        <button type="button" id="addImageBtn" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; background: #2563eb; color: #fff; border: none; border-radius: 5px; padding: 8px 18px; font-size: 15px;">
                            <i class="fas fa-plus"></i> Add Product Images
                        </button>
                        <input type="file" id="product_image" name="product_image[]" accept="image/*" style="display:none;" multiple>
                    </div>
                    <!-- Layout: Existing images first, then new images below -->
                    <div style="display:flex; flex-direction:column; gap:18px;">
                        <?php if (!empty($quotation_items)): ?>
                            <div id="existingImages" style="display:flex; gap:14px; flex-wrap:wrap; margin-bottom:10px;">
                                <?php foreach ($quotation_items as $i => $item): ?>
                                    <?php if (!empty($item['product_image_path'])): ?>
                                        <div style="display:inline-block; text-align:center;">
                                            <img src="<?= htmlspecialchars($item['product_image_path']) ?>" style="width:140px; height:90px; object-fit:cover; border:1px solid #ccc; border-radius:6px; margin-bottom:4px;"><br>
                                            <div style="font-size:14px; font-weight:500; color:#1e293b; margin-top:4px; max-width:140px; word-break:break-word;">
                                                <?= htmlspecialchars($item['item']) ?>
                                            </div>
                                            <?php if ($isEdit): ?>
                                                <!-- Change file button for existing image -->
                                                <input type="file" name="product_image[]" accept="image/*" style="margin-top:4px;">
                                                <label class="remove-image-checkbox" style="display:inline-block">
                                                    <input type="checkbox" name="remove_image[]" value="<?= $i ?>"> Remove
                                                </label>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div id="imagePreview" style="display:flex; gap:14px; flex-wrap:wrap; margin-top:0;"></div>
                    </div>
                    <script>
                    // --- Product Images Add/Preview/Remove (edit mode) ---
                    let selectedImages = [];

                    document.getElementById('addImageBtn').addEventListener('click', function() {
                        document.getElementById('product_image').click();
                    });

                    document.getElementById('product_image').addEventListener('change', function(event) {
                        const files = Array.from(event.target.files);
                        files.forEach(file => {
                            if (!file.type.startsWith('image/')) return;
                            selectedImages.push(file); // Always push to end
                        });
                        updateImagePreview();
                        event.target.value = '';
                    });

                    // Always use the latest ITEM fields for preview labels
                    function updateImagePreview() {
                        const preview = document.getElementById('imagePreview');
                        preview.innerHTML = '';
                        // Only get ITEM fields that are currently visible and editable (not readonly)
                        const itemInputs = Array.from(document.querySelectorAll('#itemsTable input[name="item[]"]'))
                            .filter(input => !input.readOnly);
                        const items = itemInputs.map(input => input.value.trim());
                        selectedImages.forEach((file, idx) => {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const imgWrapper = document.createElement('div');
                                imgWrapper.style.position = 'relative';
                                imgWrapper.style.display = 'inline-block';
                                imgWrapper.style.textAlign = 'center';
                                imgWrapper.style.marginRight = '8px';
                                // Image
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.style.width = '140px';
                                img.style.height = '90px';
                                img.style.objectFit = 'cover';
                                img.style.border = '1px solid #ccc';
                                img.style.borderRadius = '6px';
                                img.style.marginBottom = '4px';
                                // Label (product name)
                                const label = document.createElement('div');
                                // Use the last N item names for N new images (so new images match new items)
                                let labelText = '';
                                if (selectedImages.length > 0 && items.length >= selectedImages.length) {
                                    // Map: last image to last item, etc.
                                    labelText = items[items.length - selectedImages.length + idx] || 'Untitled Item';
                                } else {
                                    labelText = items[idx] || 'Untitled Item';
                                }
                                label.textContent = labelText;
                                label.style.fontSize = '14px';
                                label.style.fontWeight = '500';
                                label.style.color = '#1e293b';
                                label.style.marginTop = '4px';
                                label.style.maxWidth = '140px';
                                label.style.wordBreak = 'break-word';
                                // Remove button
                                const removeBtn = document.createElement('button');
                                removeBtn.type = 'button';
                                removeBtn.textContent = '×';
                                removeBtn.title = 'Remove';
                                removeBtn.style.position = 'absolute';
                                removeBtn.style.top = '4px';
                                removeBtn.style.right = '4px';
                                removeBtn.style.background = '#e11d48';
                                removeBtn.style.color = '#fff';
                                removeBtn.style.border = 'none';
                                removeBtn.style.borderRadius = '50%';
                                removeBtn.style.width = '24px';
                                removeBtn.style.height = '24px';
                                removeBtn.style.cursor = 'pointer';
                                removeBtn.style.display = 'flex';
                                removeBtn.style.alignItems = 'center';
                                removeBtn.style.justifyContent = 'center';
                                removeBtn.style.fontWeight = 'bold';
                                removeBtn.style.fontSize = '18px';
                                removeBtn.onclick = function() {
                                    selectedImages.splice(idx, 1);
                                    updateImagePreview();
                                };
                                imgWrapper.appendChild(img);
                                imgWrapper.appendChild(label);
                                imgWrapper.appendChild(removeBtn);
                                preview.appendChild(imgWrapper);
                            };
                            reader.readAsDataURL(file);
                        });
                    }

                    // Update image labels live when ITEM fields change
                    function attachItemInputListeners() {
                        document.querySelectorAll('#itemsTable input[name="item[]"]').forEach(input => {
                            input.removeEventListener('input', updateImagePreview);
                            input.addEventListener('input', updateImagePreview);
                        });
                    }
                    document.addEventListener('DOMContentLoaded', attachItemInputListeners);
                    document.getElementById('addItemBtn')?.addEventListener('click', attachItemInputListeners);

                    document.querySelector('form[enctype="multipart/form-data"]').addEventListener('submit', function(e) {
                        if (selectedImages.length > 0) {
                            let oldInput = document.getElementById('dynamicProductImages');
                            if (oldInput) oldInput.remove();
                            const input = document.createElement('input');
                            input.type = 'file';
                            input.name = 'product_image[]';
                            input.id = 'dynamicProductImages';
                            input.multiple = true;
                            input.style.display = 'none';
                            const dt = new DataTransfer();
                            selectedImages.forEach(file => dt.items.add(file));
                            input.files = dt.files;
                            this.appendChild(input);
                        }
                    });
                    </script>
                <?php else: ?>
                    <!-- VIEW MODE: Show all images with correct product names -->
                    <div style="display:flex; flex-wrap:wrap; gap:14px; margin-top:8px;">
                        <?php foreach ($quotation_items as $item): ?>
                            <?php if (!empty($item['product_image_path'])): ?>
                                <div style="display:inline-block; text-align:center;">
                                    <img src="<?= htmlspecialchars($item['product_image_path']) ?>" style="width:140px; height:90px; object-fit:cover; border:1px solid #ccc; border-radius:6px; margin-bottom:4px;"><br>
                                    <div style="font-size:14px; font-weight:500; color:#1e293b; margin-top:4px; max-width:140px; word-break:break-word;">
                                        <?= htmlspecialchars($item['item']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($isEdit): ?>
<script>
// Add this inside your existing script tags
function updateTermText(li) {
    const spans = li.querySelectorAll('.term-text');
    const input = li.querySelector('.term-text-input');
    let fullText = '';
    
    spans.forEach((span, index) => {
        fullText += span.textContent;
        const numInput = span.nextElementSibling;
        if (numInput && numInput.type === 'number') {
            fullText += numInput.value;
        }
    });
    
    input.value = fullText.trim();
}

function updateAllTerms() {
    document.querySelectorAll('#terms-list li').forEach(updateTermText);
}

// Update before form submission
document.querySelector('form').addEventListener('submit', function() {
    updateAllTerms();
});

// Update when contenteditable or number inputs change
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('term-text') || 
        (e.target.type === 'number' && e.target.closest('.terms'))) {
        updateTermText(e.target.closest('li'));
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateAllTerms);
</script>
<?php endif; ?>

            <!-- Terms and Agreements Section -->
            <div class="terms">
                <p><strong>TERMS AND AGREEMENTS:</strong></p>
                <ol id="terms-list">
                    <?php if (!empty($quotation_terms)): ?>
                        <?php foreach ($quotation_terms as $term): ?>
                            <li>
                                <?php if ($term['term_type'] === 'validity'): ?>
                                    <strong>VALIDITY:</strong>
                                    <span contenteditable="<?= $isEdit ? 'true' : 'false' ?>" class="term-text">
                                        This quotation is only available for 
                                    </span>
                                    <input type="number" name="days_values[]" 
                                        value="<?= htmlspecialchars($term['days_value'] ?? 30) ?>" 
                                        class="auto-grow-num" autocomplete="off" inputmode="numeric" 
                                        <?= $isView ? 'readonly' : '' ?>>
                                    <span contenteditable="<?= $isEdit ? 'true' : 'false' ?>" class="term-text">
                                        days starting upon the day of quotation request.
                                    </span>
                                <?php elseif ($term['term_type'] === 'delivery'): ?>
                                    <span contenteditable="<?= $isEdit ? 'true' : 'false' ?>" class="term-text">
                                        Delivery date of product shall be 
                                    </span>
                                    <input type="number" name="days_values[]" 
                                        value="<?= htmlspecialchars($term['days_value'] ?? 21) ?>" 
                                        class="auto-grow-num" autocomplete="off" inputmode="numeric" 
                                        <?= $isView ? 'readonly' : '' ?>>
                                    <span contenteditable="<?= $isEdit ? 'true' : 'false' ?>" class="term-text">
                                        working days after P.O. is issued.
                                    </span>
                                <?php else: ?>
                                    <span contenteditable="<?= $isEdit ? 'true' : 'false' ?>" class="term-text">
                                        <?= htmlspecialchars($term['term_text']) ?>
                                    </span>
                                <?php endif; ?>
                                
                                <input type="hidden" name="term_types[]" value="<?= htmlspecialchars($term['term_type']) ?>">
                                <input type="hidden" name="term_texts[]" class="term-text-input">
                                
                                <?php if ($isEdit): ?>
                                    <button type="button" class="delete-btn" title="Delete term">&#128465;</button>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Default terms if none exist -->
                        <li>
                            <strong>VALIDITY:</strong>
                            <span contenteditable="<?= $isEdit ? 'true' : 'false' ?>" class="term-text">
                                This quotation is only available for 
                            </span>
                            <input type="number" name="days_values[]" value="30" 
                                class="auto-grow-num" autocomplete="off" inputmode="numeric" 
                                <?= $isView ? 'readonly' : '' ?>>
                            <span contenteditable="<?= $isEdit ? 'true' : 'false' ?>" class="term-text">
                                days starting upon the day of quotation request.
                            </span>
                            <input type="hidden" name="term_types[]" value="validity">
                            <input type="hidden" name="term_texts[]" class="term-text-input">
                            <?php if ($isEdit): ?>
                                <button type="button" class="delete-btn" title="Delete term">&#128465;</button>
                            <?php endif; ?>
                        </li>
                        <!-- Add other default terms as needed -->
                    <?php endif; ?>
                </ol>
                
                <?php if ($isEdit): ?>
                    <button type="button" id="addTermBtn">New Term</button>
                <?php endif; ?>
            </div>
            
                <p>We hope you find our quotation satisfactory and look forward to being of service to you soon.</p><br>

                <p>Very truly yours,<br>
                    <strong>
                        <input type="text" name="sender_company" value="<?= htmlspecialchars($quotation['sender_company'] ?? 'James Polymers Mfg.') ?>" <?= $isView ? 'readonly' : '' ?> 
                            style="border: none; border-bottom: 1px solid #ccc; font-weight: bold; width: 100%; max-width: 400px;">
                    </strong>
                </p>
                <br>
                <input type="text" name="sender_name" placeholder="Enter sender name" value="<?= htmlspecialchars($quotation['sender_name'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>
                    style="margin-top: 10px; width: 100%; max-width: 400px; font-weight: bold; border: none; border-bottom: 1px solid #ccc;">
                <br><br>
                <p>
                    <input type="text" name="sender_position" placeholder="Enter sender position" value="<?= htmlspecialchars($quotation['sender_position'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>
                        style="width: 100%; max-width: 400px; font-weight: bold; border: none; border-bottom: 1px solid #ccc;">
                </p>
       



            <div class="button-group">
                <?php if ($isView): ?>
                    <!-- View Mode -->
                    <a href="transactions.php#quotation-tab" class="back-btn">🔙 Back to Quotations</a>
                    <a href="quotation_entry.php?id=<?= urlencode($quotation['id']) ?>&mode=edit" class="edit-btn">✏️ Edit</a>
                <?php elseif ($isEdit): ?>
                    <!-- Edit Mode -->
                    <button type="submit" class="save-btn">💾 Save Changes</button>
                    <a href="quotation_entry.php?id=<?= urlencode($quotation['id']) ?>&mode=view" class="view-btn">👁️ View</a>
                    <a href="transactions.php#quotation-tab" class="back-btn">🔙 Back to Quotations</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<style>
.auto-grow-num {
    width: 48px;
    min-width: 48px;
    padding: 4px 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
    text-align: right;
    font-size: 14px;
    transition: width 0.1s ease-out;
    background-color: transparent;
}

.auto-grow-num[readonly] {
    border: none;
    padding: 0;
    background: none;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: textfield;
}
</style>
<script>
// Move this outside the isEdit condition so it works in all modes
document.addEventListener('DOMContentLoaded', function() {
    const measureWidth = (value) => {
        let measure = document.getElementById('measure-width');
        if (!measure) {
            measure = document.createElement('div');
            measure.id = 'measure-width';
            measure.style.position = 'absolute';
            measure.style.visibility = 'hidden';
            measure.style.whiteSpace = 'pre';
            measure.style.fontSize = '14px';
            measure.style.paddingRight = '6px';
            document.body.appendChild(measure);
        }
        measure.textContent = value;
        return measure.getBoundingClientRect().width;
    };

    function adjustWidth(input) {
        const minWidth = 48;
        const value = input.value || '0';
        const contentWidth = measureWidth(value);
        const newWidth = Math.max(minWidth, contentWidth + 12); // Reduced padding for readonly
        input.style.width = newWidth + 'px';
    }

    // Attach to all number inputs regardless of mode
    document.querySelectorAll('.auto-grow-num').forEach(input => {
        input.addEventListener('input', (e) => adjustWidth(e.target));
        // Initial adjustment
        adjustWidth(input);
    });
});
</script>

<?php if ($isPrint): ?>
<!-- Print Preview Back Button -->
<style>
#print-back-btn {
    position: fixed;
    top: 24px;
    left: 24px;
    z-index: 9999;
    padding: 10px 22px;
    background: #222;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    opacity: 0.92;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
@media print {
    #print-back-btn { display: none !important; }

    .auto-grow-num {
        border: none !important;
        padding: 0 !important;
        background: none !important;
    }
}
</style>
<button id="print-back-btn" onclick="window.location.href='transactions.php#quotation-tab'; return false;">← Back</button>
<script>
window.onload = function() {
    window.print();
};
</script>
<?php endif; ?>


</body>
</html>
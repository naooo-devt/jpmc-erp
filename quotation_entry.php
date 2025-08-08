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
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
    $stmt->execute([$id]);
    $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quotation) {
        die("Quotation not found.");
    }
}

// UPDATE MODE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and compute totals
    $qty = floatval($_POST['qty']);
    $unit_price = floatval($_POST['unit_price']);
    $total = $qty * $unit_price;
    $subtotal = $total;
    $vat = $subtotal * 0.12;
    $grand_total = $subtotal + $vat;

    // Handle image upload
    $productImagePath = $quotation['product_image_path'] ?? '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $filename = basename($_FILES['product_image']['name']);
        $targetFile = $uploadDir . time() . '_' . $filename;

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
            // Delete old image
            if (!empty($quotation['product_image_path']) && file_exists($quotation['product_image_path'])) {
                unlink($quotation['product_image_path']);
            }
            $productImagePath = $targetFile;
        }
    }

    // Perform update
    $updateStmt = $pdo->prepare("
        UPDATE quotations SET
            quotation_date = ?, attention_to = ?, position = ?, company_name = ?, address = ?,
            contact_number = ?, email = ?, website = ?, item = ?, description = ?,
            qty = ?, unit = ?, unit_price = ?, total = ?, subtotal = ?, vat = ?, grand_total = ?,
            product_name = ?, product_image_path = ?, validity_days = ?, delivery_days = ?, 
            sender_company = ?, sender_name = ?, sender_position = ?
        WHERE id = ?
    ");

    $updateStmt->execute([
        $_POST['quotation_date'],
        $_POST['attention_to'], $_POST['position'], $_POST['company_name'], $_POST['address'],
        $_POST['contact_number'], $_POST['email'], $_POST['website'], $_POST['item'], $_POST['description'],
        $qty, $_POST['unit'], $unit_price, $total,
        $subtotal, $vat, $grand_total,
        $_POST['product_name'], $productImagePath, $_POST['validity_days'], $_POST['delivery_days'],
        $_POST['sender_company'], $_POST['sender_name'], $_POST['sender_position'],
        $_POST['id']
    ]);

    echo "<script>alert('Quotation updated successfully!'); window.location.href='quotation_entry.php?id={$_POST['id']}&mode=view';</script>";
    exit;
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

            .terms ul {
            padding-left: 20px;
            }

            .terms li {
            margin-bottom: 6px;
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
            </table>
            <p>Dear Ma'am/Sir,</p>
            <p>In response to your request, we are pleased to submit our quotation on the following concrete spacer with labor, mold and materials supplied by James Polymers Mfg. Corp.</p>
            <br>
            <table class="product-table">
                <tr>
                    <th>ITEM</th><th>DESCRIPTION</th><th>QTY</th><th>U/M</th><th>UNIT PRICE</th><th>TOTAL</th>
                </tr>
                <tr>
                    <td><input type="text" name="item" value="<?= htmlspecialchars($quotation['item'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    <td><input type="text" name="description" value="<?= htmlspecialchars($quotation['description'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    <td><input type="number" name="qty" value="<?= htmlspecialchars($quotation['qty'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    <td><input 
                    type="text" 
                    name="unit" 
                    list="units"
                    value="<?= htmlspecialchars($quotation['unit'] ?? '') ?>" 
                    <?= $isView ? 'readonly' : '' ?> 
                    class="standard-input">

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
                    <td><input type="number" name="unit_price" value="<?= htmlspecialchars($quotation['unit_price'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></td>
                    <td><input type="text" name="total" value="<?= htmlspecialchars($quotation['total'] ?? '') ?>" readonly></td>
                </tr>
            </table>

            <div class="totals">
                <label><strong>Subtotal:</strong></label>
                <input type="text" id="subtotal" name="subtotal" value="<?= htmlspecialchars($isView ? number_format($quotation['grand_total'] ?? 0, 2) : ($quotation['grand_total'] ?? '')) ?>" <?= $isView ? 'readonly' : '' ?>><br><br>

                <label><strong>VAT (12%):</strong></label>
                <input type="text" id="vat" name="vat" value="<?= htmlspecialchars($quotation['vat'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>><br><br>

                <label><strong>Total:</strong></label>
                <input type="text" id="grand_total" name="grand_total" value="<?= htmlspecialchars(number_format($quotation['grand_total'] ?? 0, 2)) ?>" <?= $isView ? 'readonly' : '' ?>><br>
            </div>

            <br><br><br>
            <div class="footer">
                <p><strong>PRODUCT NAME:</strong><br>
                    <input style="width:auto" type="text" name="product_name" value="<?= htmlspecialchars($quotation['product_name'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>></p>
                <br><label for="product_image"><strong>PRODUCT IMAGE/S:</strong></label><br>

                <?php if (!empty($quotation['product_image_path'])): ?>
                    <img id="imagePreview" class="product-preview-img" src="<?= htmlspecialchars($quotation['product_image_path']) ?>" 
                        alt="Image Preview" 
                        style="margin-top: 10px; width: 300px; height: auto; border: 1px solid #ccc;">
                <?php else: ?>
                    <img id="imagePreview" src="#" alt="Image Preview" style="display: none;">
                <?php endif; ?>

                <input type="file" id="product_image" name="product_image" accept="image/*" class="file-upload" <?= $isView ? 'disabled' : '' ?>>
            </div>

            <div class="terms">
                <p><strong>TERMS AND AGREEMENTS:</strong></p>
                <ol>
                    <li><strong>VALIDITY:</strong> This quotation is only available for 
                        <input style="width: 49px" type="number" name="validity_days" value="<?= htmlspecialchars($quotation['validity_days'] ?? 30) ?>" <?= $isView ? 'readonly' : '' ?> style="width: 60px"> 
                        days starting upon the day of quotation request; if the expiration ends, the item/s listed are subject to quotation.
                    </li>
                    <li>Plastic material to be used is exactly similar to your sample.</li>
                    <li>Mold, labor and plastic material are to be supplied by our company.</li>
                    <li>Delivery date of product shall be 
                        <input style="width: 49px" type="number" name="delivery_days" value="<?= htmlspecialchars($quotation['delivery_days'] ?? 21) ?>" <?= $isView ? 'readonly' : '' ?> style="width: 60px"> 
                        working days after P.O. is issued.
                    </li>
                </ol>
            </div>

            
                <p>We hope you find our quotation satisfactory and look forward to being of service to you soon.</p><br>

                <p>Very truly yours,<br>
                    <strong>
                        <input type="text" name="sender_company" value="<?= htmlspecialchars($quotation['sender_company'] ?? 'James Polymers Mfg.') ?>" <?= $isView ? 'readonly' : '' ?> 
                            style="border: none; border-bottom: 1px solid #ccc; font-weight: bold; width: 100%; max-width: 400px;">
                    </strong>
                </p>

                <input type="text" name="sender_name" placeholder="Enter sender name" value="<?= htmlspecialchars($quotation['sender_name'] ?? '') ?>" <?= $isView ? 'readonly' : '' ?>
                    style="margin-top: 10px; width: 100%; max-width: 400px; font-weight: bold; border: none; border-bottom: 1px solid #ccc;">

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
<script>
function recalculateTotals() {
    const qty = parseFloat(document.querySelector('[name="qty"]').value) || 0;
    const unitPrice = parseFloat(document.querySelector('[name="unit_price"]').value) || 0;

    const total = qty * unitPrice;
    const subtotal = total;
    const vatRate = 0.12;
    const vat = subtotal * vatRate;
    const grandTotal = subtotal + vat;

    document.querySelector('[name="total"]').value = total.toFixed(2);
    document.querySelector('[name="subtotal"]').value = subtotal.toFixed(2);
    document.querySelector('[name="vat"]').value = vat.toFixed(2);
    document.querySelector('[name="grand_total"]').value = grandTotal.toFixed(2);
}

// Only bind listeners if it's editable mode
<?php if (!$isView): ?>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelector('[name="qty"]').addEventListener('input', recalculateTotals);
        document.querySelector('[name="unit_price"]').addEventListener('input', recalculateTotals);
    });
<?php endif; ?>
</script>

<?php if ($isPrint): ?>
<script>
    window.onload = function() {
        window.print();
    }
</script>
<?php endif; ?>


</body>
</html>
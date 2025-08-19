<?php
// Hide PHP warnings and errors from being displayed to users
error_reporting(0);
ini_set('display_errors', 0);


session_start();
require_once 'db_connect.php';  // MySQLi (for rest of system)
require_once 'db_pdo.php';      // PDO (only for quotation section)

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Generate quotation_no in the format: JPMC YYYY-MM-XXXX
$year = date('Y');
$month = date('m');
$prefix = "JPMC $year-$month";

// Find the highest serial number for the current year-month
$stmt = $pdo->prepare("
    SELECT quotation_no 
    FROM quotations 
    WHERE quotation_no LIKE :prefix 
    ORDER BY quotation_no DESC 
    LIMIT 1
");
$stmt->execute([':prefix' => "$prefix%"]);
$lastQuotation = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lastQuotation) {
    // Extract the last 4 digits and increment
    preg_match('/(\d{4})$/', $lastQuotation['quotation_no'], $matches);
    $lastSerial = isset($matches[1]) ? intval($matches[1]) : 0;
    $serial = str_pad($lastSerial + 1, 4, '0', STR_PAD_LEFT);
} else {
    // First quotation of the month
    $serial = '0001';
}

$quotation_no = "$prefix-$serial"; // e.g., JPMC 2025-08-0004

    $quotation_no = "$prefix-$serial"; // e.g., JPMC 2025-08-0001

    $history_stmt = $pdo->prepare("SELECT * FROM quotations ORDER BY quotation_date DESC");
                    $history_stmt->execute();
                    $quotations = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Get today's date
                    $today = new DateTime();

// --- QUOTATION FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['action'])) {
    // Proceed with other form data
    $quotation_date = $_POST['quotation_date'];
    $attention_to = $_POST['attention_to'];
    $position = $_POST['position'];
    $company_name = $_POST['company_name'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $website = $_POST['website'];
    $mode_of_payment = $_POST['mode_of_payment']; 
    $item = $_POST['item'];
    $description = $_POST['description'];
    $qty = $_POST['qty'];
    $unit = $_POST['unit'];
    $unit_price = $_POST['unit_price'];
    $total = str_replace(',', '', $_POST['total']);
    $subtotal = str_replace(',', '', $_POST['subtotal']);
    $vat = str_replace(',', '', $_POST['vat']);
    $grand_total = str_replace(',', '', $_POST['grand_total']);
    $validity_days = $_POST['validity_days'];
    $delivery_days = $_POST['delivery_days'];
    $sender_company = $_POST['sender_company'];
    $sender_name = $_POST['sender_name'];
    $sender_position = $_POST['sender_position'];

    // --- IMAGE UPLOAD LOGIC FOR MULTIPLE IMAGES (PER ITEM) WITH CUSTOM NAME ---
    $imagePaths = [];
    $itemArr = isset($_POST['item']) ? (array)$_POST['item'] : [];
    if (
        isset($_FILES['product_image']) &&
        is_array($_FILES['product_image']['name']) &&
        count($_FILES['product_image']['name']) > 0
    ) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $dateStr = date('Ymd-His');
        foreach ($_FILES['product_image']['name'] as $idx => $imgName) {
            if (!empty($imgName)) {
                $tmpName = $_FILES['product_image']['tmp_name'][$idx];
                // Use item name for this image if available, else fallback to original filename
                $itemName = isset($itemArr[$idx]) ? $itemArr[$idx] : pathinfo($imgName, PATHINFO_FILENAME);
                // Sanitize item name for filename
                $itemNameSanitized = preg_replace('/[^A-Za-z0-9_\-]/', '_', $itemName);
                $ext = pathinfo($imgName, PATHINFO_EXTENSION);
                $newFileName = $itemNameSanitized . '-' . $dateStr . '.' . $ext;
                $targetPath = $uploadDir . $newFileName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $imagePaths[$idx] = $targetPath;
                } else {
                    $imagePaths[$idx] = '';
                }
            } else {
                $imagePaths[$idx] = '';
            }
        }
    }

    // Extract validity_days and delivery_days from terms
    $validity_days = null;
    $delivery_days = null;
    
    if (!empty($_POST['days_values'])) {
        foreach ($_POST['days_values'] as $index => $days) {
            $term_type = $_POST['term_types'][$index] ?? '';
            if ($term_type === 'validity' && is_numeric($days)) {
                $validity_days = (int)$days;
            } elseif ($term_type === 'delivery' && is_numeric($days)) {
                $delivery_days = (int)$days;
            }
        }
    }

    // 1. Insert into quotations table (NO image path here)
    $sql = "
        INSERT INTO quotations (
            quotation_no, quotation_date, attention_to, position, company_name, address,
            contact_number, email, website, mode_of_payment, subtotal, vat, grand_total, product_name,
            validity_days, delivery_days, sender_company, sender_name, sender_position
        ) VALUES (
            :quotation_no, :quotation_date, :attention_to, :position, :company_name, :address,
            :contact_number, :email, :website, :mode_of_payment, :subtotal, :vat, :grand_total, :product_name,
            :validity_days, :delivery_days, :sender_company, :sender_name, :sender_position
        )
    ";

        // Collect terms from the form
    $terms = [];
    $term_texts = isset($_POST['term_texts']) ? (array)$_POST['term_texts'] : [];
    $term_types = isset($_POST['term_types']) ? (array)$_POST['term_types'] : [];
    $days_values = isset($_POST['days_values']) ? (array)$_POST['days_values'] : [];

    // Validation before transaction
    if ($validity_days === null) {
        throw new Exception("Validity days must be specified in the terms");
    }

    if ($delivery_days === null) {
        throw new Exception("Delivery days must be specified in the terms");
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':quotation_no' => $quotation_no,
            ':quotation_date' => $quotation_date,
            ':attention_to' => $attention_to,
            ':position' => $position,
            ':company_name' => $company_name,
            ':address' => $address,
            ':contact_number' => $contact_number,
            ':email' => $email,
            ':website' => $website,
            ':mode_of_payment' => $mode_of_payment,
            ':subtotal' => $subtotal,
            ':vat' => $vat,
            ':grand_total' => $grand_total,
            ':product_name' => $product_name,
            ':validity_days' => $validity_days,
            ':delivery_days' => $delivery_days,
            ':sender_company' => $sender_company,
            ':sender_name' => $sender_name,
            ':sender_position' => $sender_position
        ]);
        $quotation_id = $pdo->lastInsertId();

        // 2. Insert each item into quotation_items table, with image path if available
        $itemArr = isset($_POST['item']) ? (array)$_POST['item'] : [];
        $descArr = isset($_POST['description']) ? (array)$_POST['description'] : [];
        $qtyArr = isset($_POST['qty']) ? (array)$_POST['qty'] : [];
        $unitArr = isset($_POST['unit']) ? (array)$_POST['unit'] : [];
        $unitPriceArr = isset($_POST['unit_price']) ? (array)$_POST['unit_price'] : [];
        $totalArr = isset($_POST['total']) ? (array)$_POST['total'] : [];

        foreach ($totalArr as $i => $t) {
            if ($t === '' || $t === null) {
                $qty = isset($qtyArr[$i]) ? floatval($qtyArr[$i]) : 0;
                $price = isset($unitPriceArr[$i]) ? floatval($unitPriceArr[$i]) : 0;
                $totalArr[$i] = number_format($qty * $price, 2, '.', '');
            }
        }

        $itemSql = "INSERT INTO quotation_items (quotation_id, item, description, qty, unit, unit_price, total, product_image_path)
                    VALUES (:quotation_id, :item, :description, :qty, :unit, :unit_price, :total, :product_image_path)";
        $itemStmt = $pdo->prepare($itemSql);

        for ($i = 0; $i < count($itemArr); $i++) {
            $imgPath = isset($imagePaths[$i]) ? $imagePaths[$i] : '';
            $itemStmt->execute([
                ':quotation_id' => $quotation_id,
                ':item' => $itemArr[$i],
                ':description' => $descArr[$i],
                ':qty' => $qtyArr[$i],
                ':unit' => $unitArr[$i],
                ':unit_price' => $unitPriceArr[$i],
                ':total' => $totalArr[$i],
                ':product_image_path' => $imgPath
            ]);
        }

                
        // Insert terms
        $termSql = "INSERT INTO quotation_terms (quotation_id, term_text, term_type, days_value, display_order) 
                    VALUES (:quotation_id, :term_text, :term_type, :days_value, :display_order)";
        $termStmt = $pdo->prepare($termSql);
        
        foreach ($term_texts as $index => $text) {
            $termStmt->execute([
                ':quotation_id' => $quotation_id,
                ':term_text' => $text,
                ':term_type' => $term_types[$index] ?? null,
                ':days_value' => $days_values[$index] ?? null,
                ':display_order' => $index + 1
            ]);
        }
        

        $pdo->commit();
        echo "<script>alert('Quotation submitted successfully!'); window.location.href='transactions.php';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        // Handle validation exceptions differently from PDO exceptions
        if ($e->getMessage() === "Validity days must be specified in the terms" ||
            $e->getMessage() === "Delivery days must be specified in the terms") {
            echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
        } else {
            // For other PDO errors, show technical details
            echo "<pre>Error: " . $e->getMessage() . "</pre>";
            if ($e instanceof PDOException) {
                var_dump($stmt->errorInfo());
            }
        }
    }
}

// ...existing code...


if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

// Fetch user details from session for display.
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

// --- FIX: Corrected the SQL query to use 'product_materials' table instead of 'products' ---
$all_transactions_sql = "
    SELECT t.transaction_date, rm.name as material_name, p.name as product_name, t.type, t.quantity, l.name as location_name, t.balance
    FROM transactions t
    LEFT JOIN raw_materials rm ON t.raw_material_id = rm.id
    LEFT JOIN product_materials p ON t.product_id = p.product_id
    LEFT JOIN locations l ON t.location_id = l.id
    ORDER BY t.transaction_date DESC";
$all_transactions_result = $conn->query($all_transactions_sql);


// For filters
$raw_materials_for_modals = $conn->query("SELECT id, name, code_color FROM raw_materials ORDER BY name");
if (!$raw_materials_for_modals) {
    echo "<!-- Error: " . $conn->error . " -->";
}

// Handle suppliers form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_supplier':
                $name = $_POST['name'];
                $contact_person = $_POST['contact_person'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $rating = $_POST['rating'];
                $status = $_POST['status'];
                
                $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address, rating, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssss", $name, $contact_person, $email, $phone, $address, $rating, $status);
                
                if ($stmt->execute()) {
                    header('Location: transactions.php?success=1');
                    exit;
                } else {
                    $error = "Error adding supplier: " . $conn->error;
                }
                break;
                
            case 'edit_supplier':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $contact_person = $_POST['contact_person'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $rating = $_POST['rating'];
                $status = $_POST['status'];
                
                $sql = "UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, address=?, rating=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssi", $name, $contact_person, $email, $phone, $address, $rating, $status, $id);
                
                if ($stmt->execute()) {
                    header('Location: transactions.php?success=2');
                    exit;
                } else {
                    $error = "Error updating supplier: " . $conn->error;
                }
                break;
                
            case 'delete_supplier':
                $id = $_POST['id'];
                $sql = "DELETE FROM suppliers WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    header('Location: transactions.php?success=3');
                    exit;
                } else {
                    $error = "Error deleting supplier: " . $conn->error;
                }
                break;
        }
    }
}

// Fetch suppliers data
$suppliers_sql = "
    SELECT s.id, s.name, s.contact_person, s.email, s.phone, s.address, s.status, s.rating, s.created_at,
           COUNT(po.id) as total_orders,
           SUM(CASE WHEN po.status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
           SUM(po.total_amount) as total_spent
    FROM suppliers s
    LEFT JOIN purchase_orders po ON s.id = po.supplier_id
    GROUP BY s.id
    ORDER BY s.name";
$suppliers_result = $conn->query($suppliers_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="images/logo.png">
    <style>
        /* Rating Stars */
        .rating {
            display: flex;
            gap: 0.125rem;
        }

        .star {
            color: #fbbf24;
            font-size: 0.875rem;
        }

        .star.empty {
            color: #d1d5db;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.inactive { background: #fef2f2; color: #991b1b; }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Improved Material Filter Styling */
        .filter-group {
            margin-bottom: 1rem;
        }
        .filter-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4b5563;
            font-size: 0.875rem;
        }
        .filter-select-wrapper {
            position: relative;
            width: 240px;
            max-width: 100%;
        }
        .filter-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 100%;
            padding: 0.625rem 1rem;
            padding-right: 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background-color: #fff;
            color: #374151;
            font-size: 0.95rem;
            cursor: pointer;
            transition: border 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            font-weight: 500;
        }
        .filter-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px #2563eb33;
        }
        .filter-select-arrow {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #6b7280;
            font-size: 1rem;
        }
        .filter-select option {
            padding: 0.5rem 1rem;
        }
        .filter-select option[data-color] {
            position: relative;
            padding-left: 1.75rem;
        }
        .filter-select option[data-color]::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            width: 0.875rem;
            height: 0.875rem;
            border-radius: 50%;
            background-color: var(--color, #d1d5db);
        }
        @media (max-width: 600px) {
            .filter-select-wrapper {
                width: 100%;
            }
        }

        /* --- Add to your <style> section or stylesheet --- */
        /* filepath: c:\xampp\htdocs\ERP\transactions.php */

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            flex: 1 1 0;
            padding: 0.9rem 1.5rem;
            border: 2px solid #2563eb;
            background: #fff;
            color: #2563eb;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, border 0.2s;
            outline: none;
            box-shadow: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .filter-btn.active,
        .filter-btn:focus,
        .filter-btn:hover {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        /* Responsive fix for Material Transactions filter tabs */
        /* filepath: c:\xampp\htdocs\ERP\transactions.php */

        .transaction-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            min-width: 180px;
            flex: 1 1 220px;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            width: 100%;
            flex-wrap: wrap;
        }

        .filter-btn {
            flex: 1 1 0;
            min-width: 120px;
            padding: 0.9rem 0.5rem;
            font-size: 1rem;
            border-radius: 0.75rem;
            white-space: nowrap;
            text-align: center;
            box-sizing: border-box;
        }

        @media (max-width: 1100px) {
            .transaction-filters {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            .filter-group {
                width: 100%;
                min-width: 0;
            }
            .filter-buttons {
                flex-direction: row;
                width: 100%;
                flex-wrap: wrap;
            }
            .filter-btn {
                width: 100%;
                font-size: 0.98rem;
                padding: 0.7rem 0.5rem;
                min-width: 0;
            }
        }

        @media (max-width: 700px) {
            .transaction-filters {
                flex-direction: column;
                gap: 0.75rem;
            }
            .filter-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            .filter-btn {
                width: 100%;
                font-size: 0.96rem;
                padding: 0.7rem 0.5rem;
            }
        }

        /* Auto-grow inputs */
        input.auto-grow {
            min-width: 60px;
            max-width: 100%;
            transition: width 0.15s;
            box-sizing: content-box;
            overflow-x: auto;
        }

        /* Auto-grow for number inputs in terms */
        input.auto-grow-num {
            min-width: 48px;
            max-width: 300px;
            transition: width 0.15s;
            box-sizing: content-box;
            text-align: right;
            font-size: 14px;
            padding: 4px 6px;
            width: auto;
        }

        /* Vertically center all table cells in product table */
        #itemsTable td {
            vertical-align: middle;
        }

        /* Make inputs/buttons inside cells align in the center */
        #itemsTable td > * {
            display: flex;
            align-items: center;
            height: 100%;
        }

        /* Ensure inputs and selects take minimal height but still center properly */
        #itemsTable input,
        #itemsTable select,
        #itemsTable button,
        #itemsTable textarea {
            margin: 0;
        }

        #itemsTable input[name="unit[]"] {
            text-align: center;
        }

        
    </style>
</head>
<body>
    <!--SideBar MENU -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="header-title">Transactions</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"> <?php echo ucfirst($role); ?> </span>
                </div>
            </div>
        </div>
        <div class="content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    switch ($_GET['success']) {
                        case '1': echo 'Supplier added successfully!'; break;
                        case '2': echo 'Supplier updated successfully!'; break;
                        case '3': echo 'Supplier deleted successfully!'; break;

                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Tabs for Transactions and Suppliers -->
            <div class="finance-tabs">
                <button class="finance-tab active" data-tab="transactions">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Material Transactions</span>
                </button>
                <button class="finance-tab" data-tab="suppliers">
                    <i class="fas fa-users"></i>
                    <span>Suppliers</span>
                </button>
                <button class="finance-tab" data-tab="quotations">
                    <i class="fas fa-file"></i>
                    <span>Quotations</span>
                </button>
                <!-- Add these new tabs after Suppliers -->
                <button class="finance-tab" data-tab="purchase-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Purchase Orders</span>
                </button>
                <button class="finance-tab" data-tab="deliveries">
                    <i class="fas fa-truck"></i>
                    <span>Deliveries</span>
                </button>
                <button class="finance-tab" data-tab="analytics">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </button>
            </div>

            <!-- Transactions Tab -->
            <div class="module-content active" id="transactions">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0;">Material Transactions</h2>
                    <div class="actions" style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" id="addTransactionBtn">
                            <i class="fas fa-plus"></i> New Transaction
                        </button>
                        <button class="btn btn-outline">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                    </div>
                </div>
                <div class="transaction-filters">
                    <div class="filter-group">
                        <label>Transaction Type:</label>
                        <div class="filter-buttons">
                            <button type="button" class="btn btn-outline filter-btn active" data-filter="all" data-type="transType">
                                <i class="fas fa-list"></i> All
                            </button>
                            <button type="button" class="btn btn-outline filter-btn" data-filter="in" data-type="transType">
                                <i class="fas fa-arrow-up"></i> Material In
                            </button>
                            <button type="button" class="btn btn-outline filter-btn" data-filter="out" data-type="transType">
                                <i class="fas fa-arrow-down"></i> Material Out
                            </button>
                        </div>
                        <input type="hidden" id="transTypeFilter" value="all">
                    </div>
                    <div class="filter-group">
                        <label for="transMaterialFilter" class="filter-label">Material:</label>
                        <div class="filter-select-wrapper">
                            <select id="transMaterialFilter" class="filter-select">
                                <option value="all">All Materials</option>
                                <?php
                                if ($raw_materials_for_modals && $raw_materials_for_modals->num_rows > 0) {
                                    $raw_materials_for_modals->data_seek(0);
                                    while($row = $raw_materials_for_modals->fetch_assoc()) {
                                        // Use code_color as a CSS color if available, fallback to gray
                                        $color = !empty($row['code_color']) ? htmlspecialchars($row['code_color']) : '#d1d5db';
                                        echo "<option value='" . htmlspecialchars($row['id']) . "' data-color='$color'>" . htmlspecialchars($row['name']) . "</option>";
                                    }
                                } else {
                                    echo "<option value='1'>Test Material 1</option>";
                                    echo "<option value='2'>Test Material 2</option>";
                                    echo "<option value='3'>Test Material 3</option>";
                                }
                                ?>
                            </select>
                            <span class="filter-select-arrow"><i class="fas fa-chevron-down"></i></span>
                        </div>
                    </div>

                    <button class="btn btn-outline" id="clearTransactionFilters">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
                <div id="filterStatus" class="filter-status" style="display: none; margin-top: 10px; padding: 8px 12px; background-color: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 6px; color: #0369a1; font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i> <span id="filterStatusText"></span>
                </div>
                <div class="transactions-table table-section">
                    <div class="table-responsive">
                        <table id="transactionTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Material</th>
                                    <th>Product Used</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Location</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($all_transactions_result && $all_transactions_result->num_rows > 0) {
                                    $all_transactions_result->data_seek(0);
                                    while ($row = $all_transactions_result->fetch_assoc()) {
                                        $badge_class = strtolower($row['type'] ?? '') === 'out' ? 'out' : 'in';
                                        echo "<tr>";
                                        echo "<td>" . date('m/d/Y', strtotime($row['transaction_date'] ?? '')) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['material_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['product_name'] ?? 'N/A') . "</td>";
                                        echo "<td><span class='badge " . $badge_class . "'>" . htmlspecialchars($row['type'] ?? 'N/A') . "</span></td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['quantity'] ?? 0)) . " Bags</td>";
                                        echo "<td>" . htmlspecialchars($row['location_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['balance'] ?? 0)) . " Bags</td>";
                                        echo "<td>";
                                        echo "<button class='btn btn-outline' onclick='editTransaction(" . ($row['id'] ?? 0) . ")'><i class='fas fa-edit'></i></button> ";
                                        echo "<button class='btn btn-danger' onclick='deleteTransaction(" . ($row['id'] ?? 0) . ")'><i class='fas fa-trash'></i></button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' style='text-align:center;'>No transactions found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pagination">
                    <button class="btn btn-outline" disabled><i class="fas fa-chevron-left"></i> Previous</button>
                    <span>Page 1 of 3</span>
                    <button class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>

            <!-- Suppliers Tab -->
                        
            <div class="module-content" id="suppliers">
                 <!-- Stat Cards (from supply_chain.php Product List tab) -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Suppliers</div>
                            <div class="stat-subtitle">Active vendors</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $total_suppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'];
                        echo $total_suppliers;
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>2 new this month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Pending Orders</div>
                            <div class="stat-subtitle">Awaiting delivery</div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $pending_orders = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
                        echo $pending_orders;
                        ?>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-up"></i>
                        <span>5 overdue</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Spent</div>
                            <div class="stat-subtitle">This year</div>
                        </div>
                        <div class="stat-icon green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        ₱<?php
                        $total_spent = $conn->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
                        echo number_format($total_spent, 2);
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>15.3% vs last year</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Avg Delivery Time</div>
                            <div class="stat-subtitle">Days to deliver</div>
                        </div>
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $avg_delivery_time = $conn->query("
                            SELECT AVG(DATEDIFF(d.delivery_date, po.order_date)) as avg_days 
                            FROM deliveries d 
                            JOIN purchase_orders po ON d.purchase_order_id = po.id 
                            WHERE d.status = 'Delivered'")->fetch_assoc()['avg_days'] ?? 0;
                        echo round($avg_delivery_time, 1); ?> days
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-down"></i>
                        <span>2.1 days faster</span>
                    </div>
                </div>
            </div>
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-title">Suppliers Overview</div>
                        <div class="table-actions">
                            <button class="btn btn-primary" onclick="openModal('addSupplierModal')">
                                <i class="fas fa-plus"></i>
                                Add Supplier
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Supplier Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Rating</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($suppliers_result && $suppliers_result->num_rows > 0) {
                                    while ($row = $suppliers_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['contact_person'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
                                        echo "<td>";
                                        echo "<div class='rating'>";
                                        for ($i = 1; $i <= 5; $i++) {
                                            $star_class = $i <= ($row['rating'] ?? 0) ? 'star' : 'star empty';
                                            echo "<i class='fas fa-star $star_class'></i>";
                                        }
                                        echo "</div>";
                                        echo "</td>";
                                        echo "<td>" . ($row['total_orders'] ?? 0) . " (" . ($row['completed_orders'] ?? 0) . " completed)</td>";
                                        echo "<td>₱" . number_format($row['total_spent'] ?? 0, 2) . "</td>";
                                        $status_class = ($row['status'] ?? '') === 'Active' ? 'active' : 'inactive';
                                        echo "<td><span class='status-badge $status_class'>" . htmlspecialchars($row['status'] ?? 'N/A') . "</span></td>";
                                        echo "<td>";
                                        echo "<button class='btn btn-outline' onclick='editSupplier(" . ($row['id'] ?? 0) . ")'><i class='fas fa-edit'></i></button> ";
                                        echo "<button class='btn btn-danger' onclick='deleteSupplier(" . ($row['id'] ?? 0) . ")'><i class='fas fa-trash'></i></button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9' style='text-align:center;'>No suppliers found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quoatations Tab -->
            <div class="module-content" id="quotations">
                <div class="quotation-paper">
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

                            @media screen and (max-width: 768px) {
                                .quotation-scroll-container {
                                padding: 10px;
                                font-size: 13px;
                                }

                                .product-table input,
                                .attention-table input,
                                .attention-table textarea {
                                width: 100%;
                                box-sizing: border-box;
                                }

                                .quote-info {
                                flex-direction: column;
                                }

                                .totals {
                                float: none;
                                width: 100%;
                                }
                            }

                            input[type="text"],
                            input[type="number"],
                            input[type="date"],
                            input[type="email"],
                            input[type="url"],
                            textarea {
                                width: 100%;
                                padding: 6px;
                                font-size: 14px;
                                border-radius: 4px;
                                border: 1px solid #ccc;
                                box-sizing: border-box;
                            }

                            button[type="submit"] {
                                margin-top: 20px;
                                padding: 10px 20px;
                                background: #1e3a8a;
                                color: #fff;
                                border: none;
                                border-radius: 5px;
                                font-weight: bold;
                                cursor: pointer;
                            }

                            button[type="submit"]:hover {
                                background: #2c4dad;
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
                            font-size: 1.5rem;
                            }

                            .header-text p {
                            margin: 0;
                            font-size: 14px;
                            }

                            .quotation-paper {
                            max-width: 900px;
                            margin: 40px auto;
                            padding: 40px;
                            background: #fff;
                            border: 1px solid #ccc;
                            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            }

                            @media print {
                                /* General layout */
                                html, body {
                                    background: white !important;
                                    margin: 0 !important;
                                    padding: 0 !important;
                                    overflow: visible !important;
                                    font-size: 10px !important;
                                    line-height: 1.2;
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
                                    margin: 0;
                                    padding: 0;
                                    font-size: 10px !important;
                                    box-shadow: none !important;
                                    border: none !important;
                                }

                                /* Reduce header size */
                                .header2 {
                                    gap: 10px;
                                    padding: 10px 15px;
                                }

                                .header2 img {
                                    width: 35px !important;
                                }

                                .header-text h2 {
                                    font-size: 12px !important;
                                }

                                .header-text p {
                                    font-size: 9px !important;
                                }

                                /* Resize image */
                                .image-section img {
                                    width: 180px !important;
                                    height: auto !important;
                                    max-height: 80px !important;
                                }

                                /* Tables */
                                .product-table th,
                                .product-table td,
                                .attention-table td,
                                .terms,
                                .footer,
                                .totals td {
                                    font-size: 9px !important;
                                    padding: 3px 5px !important;
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
                                    font-size: 9px !important;
                                }

                                /* Quotation ID visible */
                                .quotation-id-print {
                                    font-weight: bold;
                                    font-size: 9px;
                                    margin-bottom: 10px;
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
                            }              
                            /* Vertically center all table cells in product table */
                            #itemsTable td {
                            vertical-align: middle;
                            }

                            /* Make inputs/buttons inside cells align in the center */
                            #itemsTable td > * {
                            display: flex;
                            align-items: center;
                            height: 100%;
                            }

                            /* Ensure inputs and selects take minimal height but still center properly */
                            #itemsTable input,
                            #itemsTable select,
                            #itemsTable button,
                            #itemsTable textarea {
                            margin: 0;
                            }
                    </style>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="header2" style="height: 100px;">
                            <img src="images/logo.png" alt="Company Logo" width="60">
                            <div class="header-text">
                                <h2>JAMES POLYMERS MANUFACTURING CORPORATION</h2>
                                <p>16 AGUINALDO HIGHWAY, PANAPAAN II, BACOOR, CAVITE</p>
                            </div>
                        </div>

                        <!-- Quotation ID -->
                        <label><strong>Quotation No. / File ID:</strong></label><br>
                        <input type="text" name="quotation_no" value="<?= htmlspecialchars($quotation_no) ?>" readonly
                            style="padding: 6px 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 10px; width: auto;">

                        <br>
                        <label><strong>Date:</strong></label><br>
                        <input type="date" name="quotation_date" id="quotationDate" required
                            style="padding: 6px 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px; width: auto;">

                        <br><br>

                        <!-- Customer details table -->
                        <table class="attention-table">
                            <tr>
                                <td>
                                    <label><strong>ATTENTION TO:</strong></label><br>
                                    <input type="text" name="attention_to" required>
                                </td>
                                <td>
                                    <label><strong>POSITION:</strong></label><br>
                                    <input type="text" name="position" required>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label><strong>COMPANY NAME:</strong></label><br>
                                    <input type="text" name="company_name" required>
                                </td>
                                <td>
                                    <label><strong>ADDRESS:</strong></label><br>
                                    <textarea name="address" rows="2" required></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label><strong>CONTACT NUMBER:</strong></label><br>
                                    <input type="text" name="contact_number" required>
                                </td>
                                <td>
                                    <label><strong>EMAIL ADDRESS:</strong></label><br>
                                    <input type="email" name="email" required>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label><strong>WEBSITE:</strong></label><br>
                                    <input type="text" name="website" placeholder="Enter website or type N/A">
                                </td>
                                <td>
                                    <label><strong>MODE OF PAYMENT:</strong></label><br>
                                    <input type="text" name="mode_of_payment" placeholder="Enter mode of payment">
                                </td>
                            </tr>
                        </table>

                        <p>Dear Ma'am/Sir,</p>
                        <p>In response to your request, we are pleased to submit our quotation...</p>
                        <br>

                        <!-- Product table with dynamic rows -->
                        <table id="itemsTable" class="product-table">
                            <thead>
                                <tr>
                                    <th>ITEM</th>
                                    <th>DESCRIPTION</th>
                                    <th>QTY</th>
                                    <th>U/M</th>
                                    <th>UNIT PRICE</th>
                                    <th>TOTAL</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input id="itemInput" type="text" name="item[]" required class="auto-grow"></td>
                                    <td>
                                        <textarea name="description[]" rows="1" style="width:100%; resize:vertical;" required></textarea>
                                    </td>
                                    <td><input type="number" name="qty[]" min="1" required oninput="updateRowTotal(this)" class="auto-grow"></td>
                                    <td style="vertical-align: middle; text-align: center;">
                                        <div style="display: flex; align-items: center; justify-content: center; width: 100%;">
                                            <input list="unitOptions" name="unit[]" required
                                                style="padding: 6px 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px; width: 120px; text-align: center;"
                                                class="auto-grow">
                                            <datalist id="unitOptions">
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
                                        </div>
                                    </td>
                                    <td><input type="number" name="unit_price[]" step="0.01" required oninput="updateRowTotal(this)" class="auto-grow"></td>
                                    <td><input type="number" name="total[]" step="0.01" readonly class="auto-grow"></td>
                                    <td><button type="button" onclick="removeRow(this)">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>

                        <br>
                        <button type="button" onclick="addItem()">Add New Item</button>

                        <!-- Totals -->
                        <div class="totals">
                            <table>
                                <tr>
                                    <td>Subtotal:</td>
                                    <td><input type="text" id="subtotal" name="subtotal" readonly></td>
                                </tr>
                                <tr>
                                    <td>VAT (12%):</td>
                                    <td><input type="text" id="vat" name="vat" readonly></td>
                                </tr>
                                <tr>
                                    <td>Total:</td>
                                    <td><input type="text" id="grand_total" name="grand_total" readonly></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Product Images Section (moved and modified) -->
                        <div class="products-gallery" style="margin: 20px 0; display: flex; flex-wrap: wrap; gap: 20px;">
                            <div id="imagePreview" style="display: flex; flex-wrap: wrap; gap: 15px;"></div>
                            <input type="file" id="product_image" accept="image/*" style="display:none;" multiple>
                            <button type="button" id="addImageBtn" style="display: flex; align-items: center; gap: 6px; font-weight: 600; background: #2563eb; color: #fff; border: none; border-radius: 5px; padding: 8px 18px; font-size: 15px;">
                                <i class="fas fa-plus"></i> Add Product Images
                            </button>
                            <input type="hidden" name="product_image_files" id="product_image_files">
                        </div>

                        <!-- Terms and Agreements -->
                        <div class="terms">
                            <p><strong>TERMS AND AGREEMENTS:</strong></p>
                            <ol id="terms-list">
                                <li>
                                    <strong>VALIDITY:</strong>
                                    <span contenteditable="true" class="term-text">
                                        This quotation is only available for 
                                    </span>
                                    <input type="number" name="days_values[]" value="30" class="auto-grow-num" autocomplete="off" inputmode="numeric">
                                    <span contenteditable="true" class="term-text">
                                        days starting upon the day of quotation request; if the expiration ends, the item/s listed are subject for re-quotation.
                                    </span>
                                    <input type="hidden" name="term_types[]" value="validity">
                                    <input type="hidden" name="term_texts[]" class="term-text-input">
                                    <button type="button" class="delete-btn" onclick="deleteTerm(this)">🗑</button>
                                </li>
                                <li>
                                    <span contenteditable="true" class="term-text">
                                        Sample material to be used for your product sample.
                                    </span>
                                    <input type="hidden" name="term_types[]" value="material">
                                    <input type="hidden" name="term_texts[]" class="term-text-input">
                                    <input type="hidden" name="days_values[]" value="">
                                    <button type="button" class="delete-btn" onclick="deleteTerm(this)">🗑</button>
                                </li>
                                <li>
                                    <span contenteditable="true" class="term-text">
                                        All materials are to be supplied by our company.
                                    </span>
                                    <input type="hidden" name="term_types[]" value="supply">
                                    <input type="hidden" name="term_texts[]" class="term-text-input">
                                    <input type="hidden" name="days_values[]" value="">
                                    <button type="button" class="delete-btn" onclick="deleteTerm(this)">🗑</button>
                                </li>
                                <li>
                                    <span contenteditable="true" class="term-text">
                                        Delivery date of product shall be 
                                    </span>
                                    <input type="number" name="days_values[]" value="21" class="auto-grow-num" autocomplete="off" inputmode="numeric">
                                    <span contenteditable="true" class="term-text">
                                        working days after P.O. is issued.
                                    </span>
                                    <input type="hidden" name="term_types[]" value="delivery">
                                    <input type="hidden" name="term_texts[]" class="term-text-input">
                                    <button type="button" class="delete-btn" onclick="deleteTerm(this)">🗑</button>
                                </li>
                            </ol>

                            <button type="button" onclick="addTerm()">New Term</button>
                        </div>
                        <div class="footer">
                            <p>We hope you find our quotation satisfactory and look forward to being of service to you soon.</p><br>

                            <p>Very truly yours,<br>
                                <strong>
                                    <input type="text" name="sender_company" value="James Polymers Manufacturing Corporation." required 
                                        style="border: none; border-bottom: 1px solid #ccc; font-weight: bold; width: 100%; max-width: 400px;">
                                </strong>
                            </p>
                            <br>
                            <input type="text" name="sender_name" placeholder="Enter sender name" required 
                                style="margin-top: 10px; width: 100%; max-width: 400px; font-weight: bold; border: none; border-bottom: 1px solid #ccc;">
                            <br><br>
                            <p>
                                <input type="text" name="sender_position" placeholder="Enter sender position" required 
                                    style="width: 100%; max-width: 400px; font-weight: bold; border: none; border-bottom: 1px solid #ccc;">
                            </p>
                        </div>

                        <button type="submit" class="submit-btn">Save</button>
                    </form>

                    <script>
                    document.addEventListener('input', function (event) {
                            if (event.target.tagName.toLowerCase() === 'textarea') {
                                event.target.style.height = 'auto';
                                event.target.style.height = event.target.scrollHeight + 'px';
                            }
                        });
                    function autoGrowInput(input) {
                        // Create a temporary span to measure text width
                        const span = document.createElement("span");
                        span.style.visibility = "hidden";
                        span.style.position = "absolute";
                        span.style.whiteSpace = "pre";
                        span.style.font = window.getComputedStyle(input).font;
                        span.textContent = input.value || input.placeholder || "";
                        document.body.appendChild(span);

                        // Adjust input width based on span width
                        input.style.width = Math.min(span.offsetWidth + 12, 300) + "px";

                        // Clean up
                        document.body.removeChild(span);
                    }

                    // Attach to all inputs with the class
                    document.querySelectorAll("input.auto-grow-num").forEach(input => {
                        autoGrowInput(input); // set initial width
                        input.addEventListener("input", () => autoGrowInput(input));
                    });

                    function addTerm() {
                        const ol = document.getElementById("terms-list");
                        const newLi = document.createElement("li");
                        newLi.contentEditable = "true";
                        newLi.innerHTML = `New term here... <button type="button" class="delete-btn" onclick="deleteTerm(this)">🗑</button>`;
                        ol.appendChild(newLi);
                    }

                    function deleteTerm(button) {
                        const li = button.parentElement;
                        li.remove();
                    }

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

                    function addTerm() {
                        const ol = document.getElementById("terms-list");
                        const newLi = document.createElement("li");
                        
                        newLi.innerHTML = `
                            <span contenteditable="true" class="term-text">New term here...</span>
                            <input type="hidden" name="term_types[]" value="custom">
                            <input type="hidden" name="term_texts[]" class="term-text-input">
                            <input type="hidden" name="days_values[]" value="">
                            <button type="button" class="delete-btn" onclick="deleteTerm(this)">🗑</button>
                        `;
                        
                        ol.appendChild(newLi);
                        updateAllTerms();
                    }

                    function deleteTerm(button) {
                        button.closest('li').remove();
                        updateAllTerms();
                    }

                    function updateAllTerms() {
                        document.querySelectorAll('#terms-list li').forEach(updateTermText);
                    }

                    // Add listeners for contenteditable changes
                    document.addEventListener('input', function(e) {
                        if (e.target.classList.contains('term-text')) {
                            updateTermText(e.target.closest('li'));
                        }
                    });

                    // Initialize on page load
                    document.addEventListener('DOMContentLoaded', function() {
                        updateAllTerms();
                    });

                    // Add to form submit handler
                    document.querySelector('form').addEventListener('submit', function() {
                        updateAllTerms();
                    });

                    </script>


                    <style>
                    .terms [contenteditable="true"] {
                        background-color: #f9f9f9;
                        padding: 2px 4px;
                        border-radius: 4px;
                        outline: none;
                    }

                    .terms button {
                        margin-top: 8px;
                        padding: 6px 12px;
                        background-color: #007BFF;
                        color: white;
                        border: none;
                        cursor: pointer;
                        border-radius: 4px;
                    }

                    .terms button:hover {
                        background-color: #0056b3;
                    }

                    /* Delete Button Style */
                    .delete-btn {
                        background-color: #dc3545;
                        color: white;
   
                        padding: 4px 6px;
                        margin-left: 8px;
                        border: none;
                        cursor: pointer;
                        border-radius: 3px;
                        font-size: 14px;
                    }

                    .delete-btn:hover {
                        background-color: #a71d2a;
                    }
                    </style>
                    
                    <script>
                    // ===== IMAGE PREVIEW & ADD FUNCTIONALITY =====
                    // We'll keep a list of selected files in memory
                    let selectedImages = [];

                    document.getElementById('addImageBtn').addEventListener('click', function() {
                        document.getElementById('product_image').click();
                    });

                    document.getElementById('product_image').addEventListener('change', function(event) {
                        const files = Array.from(event.target.files);
                        // Add new files, avoiding duplicates by name+size
                        files.forEach(file => {
                            if (!file.type.startsWith('image/')) return;
                            if (!selectedImages.some(f => f.name === file.name && f.size === file.size)) {
                                selectedImages.push(file);
                            }
                        });
                        updateImagePreview();
                        // Reset input so the same file can be selected again if needed
                        event.target.value = '';
                    });

                    function updateImagePreview() {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    const itemInputs = document.querySelectorAll('input[name="item[]"]');
    const items = Array.from(itemInputs).map(input => input.value.trim()).filter(Boolean);
    
    if (selectedImages.length === 0) {
        preview.style.display = 'none';
        return;
    }
    
    preview.style.display = 'flex';
    selectedImages.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = function (e) {
            const imgWrapper = document.createElement('div');
            imgWrapper.style.position = 'relative';
            imgWrapper.style.display = 'inline-block';
            imgWrapper.style.flexDirection = 'column';
            imgWrapper.style.alignItems = 'center';
            imgWrapper.style.gap = '8px';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '200px';
            img.style.height = '150px';
            img.style.objectFit = 'cover';
            img.style.backgroundColor = '#f8fafc';
            img.style.borderRadius = '8px';
            img.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            
            // Add product name label below image
            const label = document.createElement('div');
            label.textContent = items[idx] || 'Untitled Item';
            label.style.fontSize = '14px';
            label.style.fontWeight = '500';
            label.style.color = '#1e293b';
            label.style.textAlign = 'center';
            label.style.marginTop = '8px';
            label.style.maxWidth = '200px';
            label.style.wordWrap = 'break-word';
            
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
            removeBtn.addEventListener('click', function() {
                selectedImages.splice(idx, 1);
                updateImagePreview();
            });

            imgWrapper.appendChild(img);
            imgWrapper.appendChild(label);
            imgWrapper.appendChild(removeBtn);
            preview.appendChild(imgWrapper);
        };
        reader.readAsDataURL(file);
    });
}

// Update attachItemInputListeners to also update image labels
function attachItemInputListeners() {
    document.querySelectorAll('input[name="item[]"]').forEach(input => {
        input.removeEventListener('input', updateImagePreview);
        input.addEventListener('input', updateImagePreview);
    });
}

                    // On form submit, append all images to FormData
                    document.querySelector('form[enctype="multipart/form-data"]').addEventListener('submit', function(e) {
                        if (selectedImages.length > 0) {
                            // Only works if submitting via AJAX, otherwise files must be in <input type="file" multiple>
                            // For classic POST, you need to dynamically create a file input with all files
                            // We'll do that here:
                            // Remove any previous dynamic input
                            let oldInput = document.getElementById('dynamicProductImages');
                            if (oldInput) oldInput.remove();

                            const input = document.createElement('input');
                            input.type = 'file';
                            input.name = 'product_image[]';
                            input.id = 'dynamicProductImages';
                            input.multiple = true;
                            input.style.display = 'none';

                            // Create a DataTransfer to hold the files
                            const dt = new DataTransfer();
                            selectedImages.forEach(file => dt.items.add(file));
                            input.files = dt.files;

                            this.appendChild(input);
                        }
                    });

                    // ===== UNIT LIST =====
                    const units = [
                        "pcs", "meters", "centimeters", "millimeters", "inches", "feet",
                        "kilograms", "grams", "liters", "milliliters", "boxes", "sheets",
                        "packs", "sets"
                    ];

                    // ===== APPLY UNIT AUTOCOMPLETE =====
                    function attachUnitAutocomplete(input) {
                        input.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                const inputValue = input.value.toLowerCase();
                                const match = units.find(unit => unit.startsWith(inputValue));
                                if (match) {
                                    input.value = match;
                                }
                            }
                        });
                    }

                    // ===== AUTO UPDATE PRODUCT NAME =====
                    function updateProductNameFromItems() {
                        const productNameField = document.getElementById('product_name');
                        if (!productNameField) return; // Only proceed if we're in quotation form context
                        
                        const itemInputs = document.querySelectorAll('input[name="item[]"]');
                        const names = [];
                        itemInputs.forEach(input => {
                            const val = input.value.trim();
                            if (val) names.push(val);
                        });
                        productNameField.value = names.join(', ');
                    }

                    function attachItemInputListeners() {
                        const productNameField = document.getElementById('product_name');
                        if (!productNameField) return; // Only attach listeners in quotation form context
                        
                        document.querySelectorAll('input[name="item[]"]').forEach(input => {
                            input.removeEventListener('input', updateProductNameFromItems);
                            input.addEventListener('input', updateProductNameFromItems);
                        });
                    }

                    // Only initialize if we're in the quotation form context
                    if (document.getElementById('product_name')) {
                        attachItemInputListeners();
                        updateProductNameFromItems();
                    }

                    // ===== ADD NEW ITEM =====
                    function addItem() {
                        let tableBody = document.getElementById("itemsTable").querySelector("tbody");
                        let newRow = tableBody.insertRow();

                        // Cell 1 - ITEM
                        let cell1 = newRow.insertCell();
                        cell1.innerHTML = `<input type="text" name="item[]" required class="auto-grow">`;

                        // Cell 2 - DESCRIPTION (textarea)
                        let cell2 = newRow.insertCell();
                        cell2.innerHTML = `<textarea name="description[]" rows="1" style="width:100%; resize:vertical;" required></textarea>`;

                        // Cell 3 - QTY
                        let cell3 = newRow.insertCell();
                        cell3.innerHTML = `<input type="number" name="qty[]" min="1" required oninput="updateRowTotal(this)" class="auto-grow">`;

                        // Cell 4 - U/M with vertical centering (reuses existing datalist)
                        let cell4 = newRow.insertCell();
                        cell4.style.verticalAlign = "middle";
                        cell4.style.textAlign = "center";
                        cell4.innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: center; width: 100%;">
                                <input list="unitOptions" name="unit[]" required
                                    style="padding: 6px 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px; width: 120px; text-align: center;"
                                    class="auto-grow">
                            </div>
                        `;

                        // Cell 5 - UNIT PRICE
                        let cell5 = newRow.insertCell();
                        cell5.innerHTML = `<input type="number" name="unit_price[]" step="0.01" required oninput="updateRowTotal(this)" class="auto-grow">`;

                        // Cell 6 - TOTAL
                        let cell6 = newRow.insertCell();
                        cell6.innerHTML = `<input type="number" name="total[]" step="0.01" readonly class="auto-grow">`;

                        // Cell 7 - ACTION
                        let cell7 = newRow.insertCell();
                        cell7.innerHTML = `<button type="button" onclick="removeRow(this)">Remove</button>`;
                    }


                    // ===== REMOVE ROW =====
                    function removeRow(button) {
                        button.closest("tr").remove();
                        calculateTotals();
                        updateProductNameFromItems();
                    }

                    // ===== UPDATE ROW TOTAL =====
                    function updateRowTotal(input) {
                        let row = input.closest("tr");
                        let qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
                        let price = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
                        row.querySelector('input[name="total[]"]').value = (qty * price).toFixed(2);
                        calculateTotals();
                    }

                    // ===== CALCULATE ALL TOTALS =====
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

                    // ===== INITIAL AUTOCOMPLETE FOR FIRST ROW =====
                    document.querySelectorAll('input[name="unit[]"]').forEach(attachUnitAutocomplete);

                </script>


                </div>

                <!-- Table -->
                <div id="quotations-tab-table">
                <!-- Filter Bar for Quotation Entry History -->
                <div class="quotation-filter-bar">
                    <div class="dropdown-row">
                        <label for="filter-status">Status:</label>
                        <select id="filter-status" class="filter-select">
                            <option value="all">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Invalid">Invalid</option>
                            <option value="Rejected">Rejected</option>
                        </select>

                        <label for="filter-sort">Sort:</label>
                        <select id="filter-sort" class="filter-select">
                            <option value="recent">Recent</option>
                            <option value="oldest">Oldest</option>
                        </select>
                    </div>

                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input 
                            type="text" 
                            id="filter-search" 
                            placeholder="Search company name..."
                        >
                    </div>
                <h2>Quotation Entry History</h2>
                <table id="quotations-table" border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse: collapse;">
                    <thead style="background-color: #f0f0f0;">
                        <tr>
                            <th>Quotation No</th>
                            <th>Company</th>
                            <th>Date</th>
                            <th>Validity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="quotation-body">
                        <?php foreach ($quotations as $q): ?>
                            <?php
                                $q_date = new DateTime($q['quotation_date']);
                                $validity_days = (int)$q['validity_days'];
                                $valid_until = (clone $q_date)->modify("+{$validity_days} days");

                                // Check DB status first (so we don't overwrite Approved)
                                if (isset($q['status'])) {
                                    if ($q['status'] === 'Rejected') {
                                        $status = 'Rejected';
                                    } elseif ($q['status'] === 'Approved') {
                                        $status = 'Approved';
                                    } else {
                                        // Fallback for any other status
                                        $status = ($today <= $valid_until) ? 'Pending' : 'Invalid';
                                    }
                                } else {
                                    // No status in DB — calculate based on date
                                    $status = ($today <= $valid_until) ? 'Pending' : 'Invalid';
                                }
                            ?>
                            <tr 
                                data-id="<?= $q['id'] ?>"
                                data-status="<?= $status ?>" 
                                data-date="<?= $q_date->format('Y-m-d') ?>"
                            >
                                <td><?= htmlspecialchars($q['quotation_no']) ?></td>
                                <td><?= htmlspecialchars($q['company_name']) ?></td>
                                <td><?= $q_date->format('Y-m-d') ?></td>
                                <td><?= $valid_until->format('Y-m-d') ?></td>
                                <td style="color: 
                                    <?= $status === 'Pending' ? '#ceab11' : 
                                    ($status === 'Rejected' ? 'red' : 
                                    ($status === 'Approved' ? 'green' : 'orange')) ?>; 
                                    font-weight: bold;">
                                    <?= htmlspecialchars($status) ?>
                                </td>
                                <td>
                                    <a href="quotation_entry.php?id=<?= $q['id'] ?>&mode=view" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="quotation_entry.php?id=<?= $q['id'] ?>&mode=edit" title="Edit"><i class="fas fa-pen-to-square"></i></a>
                                    <a href="quotation_entry.php?id=<?= $q['id'] ?>&mode=delete" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
                                    <a href="quotation_entry.php?id=<?= $q['id'] ?>&mode=print" target="_blank" title="Print"><i class="fas fa-print"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
                </div>

                <!-- JavaScript For Quotation Filtering and Sorting -->
                <script>
                let originalRows = [];

                window.addEventListener('DOMContentLoaded', function() {
                    // Save all original rows for future filtering
                    originalRows = Array.from(document.querySelectorAll("#quotation-body tr"));
                    filterAndSortQuotations();
                });

                function filterAndSortQuotations() {
                    const status = document.getElementById('filter-status').value;
                    const sortOrder = document.getElementById('filter-sort').value;
                    const searchTerm = document.getElementById('filter-search').value.trim().toLowerCase();
                    const tbody = document.getElementById("quotation-body");

                    // Always filter/sort from the original set
                    let filteredRows = originalRows;

                    // Filter by status
                    if (status !== 'all') {
                        filteredRows = filteredRows.filter(row => row.dataset.status === status);
                    }

                    // Filter by search term (company name is in the 2nd cell)
                    if (searchTerm !== '') {
                        filteredRows = filteredRows.filter(row => {
                            const companyName = row.cells[1].textContent.toLowerCase();
                            return companyName.includes(searchTerm);
                        });
                    }

                    // Sort by Quotation No (last 4 digits)
                    filteredRows.sort((a, b) => {
                        const getSerial = row => {
                            const text = row.cells[0].textContent || '';
                            const match = text.match(/(\d{4})$/);
                            return match ? parseInt(match[1], 10) : 0;
                        };
                        const serialA = getSerial(a);
                        const serialB = getSerial(b);
                        return sortOrder === 'recent' ? serialB - serialA : serialA - serialB;
                    });

                    // Clear and re-render
                    tbody.innerHTML = '';
                    filteredRows.forEach(row => tbody.appendChild(row));
                }

                document.getElementById('filter-status').addEventListener('change', filterAndSortQuotations);
                document.getElementById('filter-sort').addEventListener('change', filterAndSortQuotations);
                document.getElementById('filter-search').addEventListener('input', filterAndSortQuotations);
                </script>

                <!-- CSS for filter bar -->
                <style>
                .quotation-filter-bar {
                    display: flex;
                    flex-direction: column; /* stack dropdowns and search bar */
                    gap: 0.6rem;
                    margin-bottom: 15px;
                }

                .dropdown-row {
                    display: flex;
                    gap: 0.6rem;
                    align-items: center;
                    flex-wrap: wrap;
                }

                .quotation-filter-bar label {
                    font-weight: bold;
                    margin-right: 2px;
                    white-space: nowrap;
                }

                .filter-select {
                    font-size: 14px;
                    background-color: #1b54ceff;
                    color: white;
                    border: none;
                    font-weight: bold;
                    border-radius: 5px;
                    padding: 10px 8px; /* taller dropdowns */
                    width: 120px; /* shorter width */
                }

                .quotation-filter-bar select option {
                    color: white;
                }

                .search-container {
                    position: relative;
                    flex: 1;
                    min-width: 180px;
                }

                .search-container i {
                    position: absolute;
                    left: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #777;
                    font-size: 14px;
                }

                .search-container input {
                    width: 100%;
                    padding: 10px 8px 10px 28px; /* taller search bar + left padding for icon */
                    border-radius: 5px;
                    border: 1px solid #ccc;
                    font-size: 14px;
                }
                </style>
                </div>
        </div>

            <!-- Purchase Orders Tab -->
            <div class="module-content" id="purchase-orders">
                 <!-- Stat Cards (from supply_chain.php Product List tab) -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Suppliers</div>
                            <div class="stat-subtitle">Active vendors</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $total_suppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'];
                        echo $total_suppliers;
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>2 new this month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Pending Orders</div>
                            <div class="stat-subtitle">Awaiting delivery</div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $pending_orders = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
                        echo $pending_orders;
                        ?>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-up"></i>
                        <span>5 overdue</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Spent</div>
                            <div class="stat-subtitle">This year</div>
                        </div>
                        <div class="stat-icon green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        ₱<?php
                        $total_spent = $conn->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
                        echo number_format($total_spent, 2);
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>15.3% vs last year</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Avg Delivery Time</div>
                            <div class="stat-subtitle">Days to deliver</div>
                        </div>
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $avg_delivery_time = $conn->query("
                            SELECT AVG(DATEDIFF(d.delivery_date, po.order_date)) as avg_days 
                            FROM deliveries d 
                            JOIN purchase_orders po ON d.purchase_order_id = po.id 
                            WHERE d.status = 'Delivered'")->fetch_assoc()['avg_days'] ?? 0;
                        echo round($avg_delivery_time, 1); ?> days
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-down"></i>
                        <span>2.1 days faster</span>
                    </div>
                </div>
            </div>
                <div id="purchase-orders-tab-table">
                    <!-- Table will appear here when tab is active -->
                </div>
                <br>

            </div>

            <!-- Deliveries Tab -->
            <div class="module-content" id="deliveries">
                 <!-- Stat Cards (from supply_chain.php Product List tab) -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Suppliers</div>
                            <div class="stat-subtitle">Active vendors</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $total_suppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'];
                        echo $total_suppliers;
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>2 new this month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Pending Orders</div>
                            <div class="stat-subtitle">Awaiting delivery</div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $pending_orders = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
                        echo $pending_orders;
                        ?>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-up"></i>
                        <span>5 overdue</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Spent</div>
                            <div class="stat-subtitle">This year</div>
                        </div>
                        <div class="stat-icon green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        ₱<?php
                        $total_spent = $conn->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
                        echo number_format($total_spent, 2);
                        ?>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>15.3% vs last year</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Avg Delivery Time</div>
                            <div class="stat-subtitle">Days to deliver</div>
                        </div>
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?php
                        $avg_delivery_time = $conn->query("
                            SELECT AVG(DATEDIFF(d.delivery_date, po.order_date)) as avg_days 
                            FROM deliveries d 
                            JOIN purchase_orders po ON d.purchase_order_id = po.id 
                            WHERE d.status = 'Delivered'")->fetch_assoc()['avg_days'] ?? 0;
                        echo round($avg_delivery_time, 1); ?> days
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-down"></i>
                        <span>2.1 days faster</span>
                    </div>
                </div>
            </div>
                <!-- deliveries -->
                <h3 style="text-align: center; margin-top: 20px;">Purchase Order History</h3>
                <div id="purchaseOrdersHistory" class="scroll-container"></div>

                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        fetch("purchase_orders_sample.php")
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, "text/html");

                                const filterContainer = doc.querySelector("#filterContainer");
                                const tableContainer = doc.querySelector("#purchaseOrdersTable");
                                const paginationFromSource = doc.querySelector("#poPagination");

                                if (!filterContainer || !tableContainer) {
                                    console.warn("Missing filterContainer or purchaseOrdersTable in source");
                                    return;
                                }

                                // Debug log to check source table content
                                console.log("Source table rows:", tableContainer.querySelectorAll("tbody tr").length);
                                const mainPoRows = Array.from(tableContainer.querySelectorAll("tbody tr.main-po-row"));
                                console.log("Source main PO rows:", mainPoRows.length);
                                console.log("PO numbers in source:", mainPoRows.map(row => row.querySelector('td:nth-child(1)').textContent.trim()));

                                // Clone DOM elements
                                const clonedFilter = filterContainer.cloneNode(true);
                                const clonedTable = tableContainer.cloneNode(true);
                                const clonedPagination = paginationFromSource
                                    ? paginationFromSource.cloneNode(true)
                                    : document.createElement("div");

                                // Build structure
                                const mainContainer = document.getElementById("purchaseOrdersHistory");
                                mainContainer.innerHTML = "";
                                mainContainer.appendChild(clonedFilter);

                                const scrollWrapper = document.createElement("div");
                                scrollWrapper.classList.add("po-embedded-wrapper");
                                mainContainer.appendChild(scrollWrapper);
                                scrollWrapper.appendChild(clonedTable);

                                clonedPagination.id = "po-pagination";
                                clonedPagination.style.marginTop = "10px";
                                clonedPagination.style.textAlign = "center";
                                mainContainer.appendChild(clonedPagination);

                                // Store original groups before adding Actions column
                                const originalGroups = getGroupedRows(clonedTable.querySelector("tbody"));

                                // ============================================
                                // Initialize pagination state
                                // ============================================
                                let currentPage = parseInt(localStorage.getItem('poHistoryCurrentPage')) || 1;
                                const rowsPerPage = 10; // Back to 10 items per page

                                // ============================================
                                // Add "Actions" column to table header & rows
                                // ============================================
                                const headerRow = clonedTable.querySelector("thead tr");
                                if (headerRow) {
                                    const actionHeader = document.createElement("th");
                                    actionHeader.textContent = "Actions";
                                    headerRow.insertBefore(actionHeader, headerRow.firstChild);
                                }

                                clonedTable.querySelectorAll("tr").forEach(row => {
                                    // Skip header row
                                    if (row.closest('thead')) return;

                                    if (!row.classList.contains("main-po-row")) {
                                        // For item-po-row, don't add any cells, just leave it as is
                                        return;
                                    }

                                    // Set data-order_number from 2nd cell
                                    const poNumberCell = row.cells[0];  // First cell since we haven't added Actions yet
                                    if (poNumberCell) {
                                        row.dataset.order_number = poNumberCell.textContent.trim();
                                    }

                                    const actionCell = document.createElement("td");
                                    // Add rowspan to the action cell if this row has child rows
                                    let nextRow = row.nextElementSibling;
                                    let itemCount = 0;
                                    while (nextRow && nextRow.classList.contains("item-po-row")) {
                                        itemCount++;
                                        nextRow = nextRow.nextElementSibling;
                                    }
                                    if (itemCount > 0) {
                                        actionCell.setAttribute("rowspan", itemCount + 1);
                                    }

                                    const statusCell = row.querySelector('td[rowspan] span');
                                    const isCancelled = statusCell && statusCell.textContent.trim().toLowerCase() === 'cancelled';

                                    // To-Deliver button
                                    const toDeliverBtn = document.createElement("button");
                                    toDeliverBtn.textContent = "To-Deliver";
                                    toDeliverBtn.classList.add("btn-action", "btn-to-deliver");
                                    const statusText = statusCell ? statusCell.textContent.trim().toLowerCase() : '';
                                    console.log('Status:', statusText); // Debug log
                                    
                                    // Check for delivery-related statuses
                                    const isDelivered = statusText === 'for-delivery';
                                    
                                    if (isDelivered || isCancelled) {
                                        // Change display text to "DELIVERED" if needed
                                        if (isDelivered) {
                                            statusCell.textContent = "DELIVERED";
                                        }
                                        toDeliverBtn.disabled = true;
                                        toDeliverBtn.style.opacity = '0.5';
                                        toDeliverBtn.style.cursor = 'not-allowed';
                                        toDeliverBtn.title = isDelivered ? 'Already delivered' : 'Order cancelled';
                                    } else {
                                        toDeliverBtn.onclick = () => {
                                            const po_number = row.dataset.order_number;
                                            window.location.href = `delivery_receipt.php?po_number=${encodeURIComponent(po_number)}&mode=edit`;
                                        };
                                    }

                                    // Only add the To-Deliver button
                                    actionCell.appendChild(toDeliverBtn);
                                    row.insertBefore(actionCell, row.firstChild);
                                });

                                // =============================
                                // Filter + Pagination Logic
                                // =============================
                                // Restore pagination state from localStorage
                                const poState = {
                                    originalGroups: null,
                                    currentPage: currentPage,
                                    rowsPerPage: rowsPerPage,
                                    sortOrder: localStorage.getItem('poHistorySortOrder') || 'desc',
                                    statusFilter: localStorage.getItem('poHistoryStatusFilter') || ''
                                };

                                const tbody = clonedTable.querySelector("tbody");
                                const sortOrderSelect = clonedFilter.querySelector("#sortOrder");
                                const statusFilterSelect = clonedFilter.querySelector("#statusFilter");
                                const clearFilterBtn = clonedFilter.querySelector("#clearFilterBtn");

                                // Restore filter values
                                sortOrderSelect.value = poState.sortOrder;
                                statusFilterSelect.value = poState.statusFilter;

                                function getGroupedRows(tbodyElement) {
                                    const allRows = Array.from(tbodyElement.querySelectorAll("tr"));
                                    console.log("Total rows found:", allRows.length);
                                    const groups = [];
                                    let currentGroup = [];
                                    
                                    allRows.forEach(row => {
                                        if (row.classList.contains("main-po-row")) {
                                            if (currentGroup.length > 0) {
                                                groups.push(currentGroup);
                                            }
                                            currentGroup = [row];
                                            console.log("Found main PO row:", row.querySelector('td:nth-child(1)').textContent.trim());
                                        } else if (row.classList.contains("item-po-row") && currentGroup.length > 0) {
                                            currentGroup.push(row);
                                        }
                                    });
                                    
                                    // Don't forget to add the last group
                                    if (currentGroup.length > 0) {
                                        groups.push(currentGroup);
                                    }

                                    // Sort groups by PO number to ensure proper order
                                    groups.sort((a, b) => {
                                        // Get the PO number from the first column (before Actions is added)
                                        const poA = parseInt(a[0].querySelector('td:nth-child(1)').textContent.trim(), 10);
                                        const poB = parseInt(b[0].querySelector('td:nth-child(1)').textContent.trim(), 10);
                                        return poA - poB;
                                    });

                                    return groups;
                                }

                                // Use the groups we captured before adding Actions column
                                poState.originalGroups = originalGroups;

                                function filterPOHistory() {
                                    const sortOrder = sortOrderSelect.value;
                                    const statusFilter = statusFilterSelect.value.trim().toLowerCase();

                                    // Save filter state
                                    localStorage.setItem('poHistorySortOrder', sortOrder);
                                    localStorage.setItem('poHistoryStatusFilter', statusFilter);
                                    poState.sortOrder = sortOrder;
                                    poState.statusFilter = statusFilter;

                                    let filteredGroups = poState.originalGroups.filter(group => {
                                        const mainRow = group[0];
                                        const statusSpan = mainRow.querySelector('td[rowspan] span');
                                        let statusText = statusSpan ? statusSpan.textContent.trim().toLowerCase() : '';
                                        if (!statusFilter) return true;
                                        if (statusFilter === "to-deliver") {
                                            return statusText === "to-deliver" || statusText === "for-delivery";
                                        }
                                        return statusText === statusFilter;
                                    });

                                    filteredGroups.sort(function (a, b) {
                                        // Get PO number from the second column after Actions
                                        const idA = parseInt(a[0].querySelector('td:nth-child(2)').textContent.trim(), 10);
                                        const idB = parseInt(b[0].querySelector('td:nth-child(2)').textContent.trim(), 10);
                                        return sortOrder === 'asc' ? idA - idB : idB - idA;
                                    });

                                    return filteredGroups;
                                }

                                function renderTablePage(page) {
                                    const filteredGroups = filterPOHistory();
                                    console.log('Filtered groups:', filteredGroups.length, 'groups');
                                    const totalPages = Math.ceil(filteredGroups.length / poState.rowsPerPage);
                                    console.log('Total pages:', totalPages);

                                    if (page < 1) page = 1;
                                    if (page > totalPages) page = totalPages;

                                    poState.currentPage = page;
                                    localStorage.setItem('poHistoryCurrentPage', page);
                                    tbody.innerHTML = '';

                                    const startIndex = (page - 1) * poState.rowsPerPage;
                                    const endIndex = startIndex + poState.rowsPerPage;
                                    console.log('Showing groups from index', startIndex, 'to', endIndex);
                                    const pageGroups = filteredGroups.slice(startIndex, endIndex);

                                    pageGroups.forEach(group => {
                                        group.forEach(row => tbody.appendChild(row));
                                    });
                                    console.log('Rendered', pageGroups.length, 'groups on page', page);

                                    updatePaginationUI(totalPages);
                                }

                                function updatePaginationUI(totalPages) {
                                    if (!clonedPagination) return;
                                    clonedPagination.innerHTML = '';

                                    // Add "Showing X to Y of Z entries" text
                                    const infoDiv = document.createElement('div');
                                    infoDiv.className = 'pagination-info';
                                    const filteredGroups = filterPOHistory(); // Get current filtered groups
                                    const startEntry = ((poState.currentPage - 1) * poState.rowsPerPage) + 1;
                                    const endEntry = Math.min(poState.currentPage * poState.rowsPerPage, filteredGroups.length);
                                    infoDiv.textContent = `Showing ${startEntry} to ${endEntry} of ${filteredGroups.length} entries`;
                                    clonedPagination.appendChild(infoDiv);

                                    const buttonsDiv = document.createElement('div');
                                    buttonsDiv.className = 'pagination-buttons';

                                    const prevBtn = document.createElement('button');
                                    prevBtn.textContent = 'Previous';
                                    prevBtn.disabled = poState.currentPage === 1;
                                    prevBtn.onclick = () => renderTablePage(poState.currentPage - 1);
                                    buttonsDiv.appendChild(prevBtn);

                                    const maxVisiblePages = 5;
                                    let startPage = Math.max(1, poState.currentPage - Math.floor(maxVisiblePages / 2));
                                    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                                    
                                    // Adjust start if we're near the end
                                    if (endPage - startPage + 1 < maxVisiblePages) {
                                        startPage = Math.max(1, endPage - maxVisiblePages + 1);
                                    }

                                    // First page if not visible
                                    if (startPage > 1) {
                                        const firstBtn = document.createElement('button');
                                        firstBtn.textContent = '1';
                                        firstBtn.onclick = () => renderTablePage(1);
                                        buttonsDiv.appendChild(firstBtn);
                                        if (startPage > 2) {
                                            const ellipsis = document.createElement('span');
                                            ellipsis.textContent = '...';
                                            ellipsis.className = 'pagination-ellipsis';
                                            buttonsDiv.appendChild(ellipsis);
                                        }
                                    }

                                    // Page buttons
                                    for (let i = startPage; i <= endPage; i++) {
                                        const btn = document.createElement('button');
                                        btn.textContent = i;
                                        btn.className = (i === poState.currentPage) ? 'active-page-btn' : '';
                                        btn.onclick = () => renderTablePage(i);
                                        buttonsDiv.appendChild(btn);
                                    }

                                    // Last page if not visible
                                    if (endPage < totalPages) {
                                        if (endPage < totalPages - 1) {
                                            const ellipsis = document.createElement('span');
                                            ellipsis.textContent = '...';
                                            ellipsis.className = 'pagination-ellipsis';
                                            buttonsDiv.appendChild(ellipsis);
                                        }
                                        const lastBtn = document.createElement('button');
                                        lastBtn.textContent = totalPages;
                                        lastBtn.onclick = () => renderTablePage(totalPages);
                                        buttonsDiv.appendChild(lastBtn);
                                    }

                                    const nextBtn = document.createElement('button');
                                    nextBtn.textContent = 'Next';
                                    nextBtn.disabled = poState.currentPage === totalPages;
                                    nextBtn.onclick = () => renderTablePage(poState.currentPage + 1);
                                    buttonsDiv.appendChild(nextBtn);

                                    clonedPagination.appendChild(buttonsDiv);
                                }

                                // Bind events
                                sortOrderSelect.addEventListener("change", () => renderTablePage(1));
                                statusFilterSelect.addEventListener("change", () => renderTablePage(1));
                                clearFilterBtn.addEventListener("click", function () {
                                    sortOrderSelect.value = 'desc';
                                    statusFilterSelect.value = '';
                                    renderTablePage(1);
                                });

                                // Initial render
                                renderTablePage(1);
                            })
                            .catch(err => console.error("Error fetching or parsing:", err));
                    });
                </script>

                <style>
                    #purchaseOrdersHistory {
                        width: 1000px;
                        box-sizing: border-box;
                        margin-top: 1rem;
                        min-width: 0;
                    }

                    .po-embedded-wrapper {
                        overflow-x: auto;
                        overflow-y: visible;
                        max-width: 100%;
                        border-radius: 6px;
                        border: 1px solid #e0e0e0;
                        background: #ffffff;
                        padding: 6px;
                        box-sizing: border-box;
                        display: block;
                    }

                    .po-embedded-wrapper table {
                        border-collapse: collapse;
                        width: auto;
                        min-width: 1200px;
                        font-size: 0.88rem;
                        table-layout: auto;
                    }

                    .po-embedded-wrapper th,
                    .po-embedded-wrapper td {
                        border: 1px solid #e6e6e6;
                        padding: 6px 8px;
                        white-space: nowrap;
                        font-size: 0.86rem;
                    }

                    .po-embedded-wrapper th {
                        background: #fafafa;
                        font-weight: 600;
                    }

                    #po-pagination {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        margin-top: 12px;
                        gap: 12px;
                        padding: 12px;
                        background: #f8fafc;
                        border-radius: 8px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }

                    #po-pagination .pagination-info {
                        font-size: 0.875rem;
                        color: #6b7280;
                    }

                    #po-pagination .pagination-buttons {
                        display: flex;
                        gap: 4px;
                        align-items: center;
                        flex-wrap: wrap;
                        justify-content: center;
                    }

                    #po-pagination button {
                        min-width: 36px;
                        height: 36px;
                        padding: 0 12px;
                        border: 1px solid #e5e7eb;
                        background: white;
                        color: #374151;
                        font-size: 0.875rem;
                        font-weight: 500;
                        border-radius: 6px;
                        cursor: pointer;
                        transition: all 0.15s ease;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    #po-pagination button:hover:not(:disabled) {
                        background: #f3f4f6;
                        border-color: #d1d5db;
                    }

                    #po-pagination button.active-page-btn {
                        background: #2563eb;
                        color: white;
                        border-color: #2563eb;
                    }

                    #po-pagination button:disabled {
                        opacity: 0.5;
                        cursor: not-allowed;
                        background: #f9fafb;
                    }

                    #po-pagination .pagination-ellipsis {
                        padding: 0 8px;
                        color: #6b7280;
                        font-weight: 600;
                    }

                    #clearFilterBtn {
                        background-color: #0480e6ff;
                        color: white;
                        border: none;
                        padding: 6px 12px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 0.9rem;
                        font-weight: 500;
                        transition: background-color 0.2s ease, transform 0.1s ease;
                    }
                    #clearFilterBtn:hover {
                        background-color: #005aa3ff;
                    }
                    #clearFilterBtn:active {
                        transform: scale(0.97);
                    }

                    /* Action buttons */
                    .btn-action {
                        padding: 4px 8px;
                        margin-right: 4px;
                        font-size: 0.8rem;
                        border-radius: 4px;
                        border: none;
                        cursor: pointer;
                        color: white;
                        transition: background-color 0.2s ease, transform 0.1s ease;
                    }
                    .btn-action:active {
                        transform: scale(0.97);
                    }
                    .btn-action[disabled] {
                        opacity: 0.5;
                        cursor: not-allowed;
                        pointer-events: none;
                    }
                    .btn-to-deliver {
                        background-color: #28a745;
                    }
                    .btn-to-deliver:hover:not([disabled]) {
                        background-color: #218838;
                    }
                    .btn-cancel {
                        background-color: #dc3545;
                    }
                    .btn-cancel:hover:not([disabled]) {
                        background-color: #c82333;
                    }
                </style>

                <!-- Delivery Receipts History Table -->
                <h3 style="text-align:center; margin-top:20px;">Delivery Receipt History</h3>
                <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:center;">
                    <thead>
                        <tr>
                            <th>D.R. No</th>
                            <th>P.O. No</th>
                            <th>Date</th>
                            <th>Deliver To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT dr_no, po_number, date, deliver_to FROM delivery_receipts ORDER BY date DESC");

                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['dr_no']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['po_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['deliver_to']) . "</td>";
                            echo "<td>
                                    <a href='delivery_receipt.php?mode=view&dr_no={$row['dr_no']}' style='padding:5px 10px; background:#4CAF50; color:#fff; text-decoration:none; border-radius:4px;'>View</a>
                                    <a href='delivery_receipt.php?mode=edit&dr_no={$row['dr_no']}' style='padding:5px 10px; background:#FFC107; color:#000; text-decoration:none; border-radius:4px;'>Edit</a>
                                    <a href='delivery_receipt.php?mode=print&dr_no={$row['dr_no']}' target='_blank' style='padding:5px 10px; background:#2196F3; color:#fff; text-decoration:none; border-radius:4px;'>Print</a>
                            </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Analytics Tab -->
            <div class="module-content" id="analytics">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Top Supplier</div>
                                <div class="stat-subtitle">By order volume</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-trophy"></i>
                            </div>
                        </div>
                        <div class="stat-value">ABC Polymers</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>45 orders this year</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">On-Time Delivery</div>
                                <div class="stat-subtitle">This month</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value">87.5%</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+5.2% from last month</span>
                        </div>
                    </div>
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Quality Score</div>
                                <div class="stat-subtitle">Average rating</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                                               <div class="stat-value">4.2/5.0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+0.3 from last quarter</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- New Transaction Modal (direct child of body) -->
    <div id="addTransactionModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="cancelAddTransactionModal">&times;</span>
            <div class="form-section">
                <div class="form-header">
                    <h2>New Material Transaction</h2>
                </div>
                <form id="addTransactionForm" method="POST" action="add_transaction.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transDate">Date</label>
                            <input type="text" id="transDate" name="transaction_date" class="datepicker form-input" required placeholder="Select date...">
                        </div>
                        <div class="form-group">
                            <label>Transaction Type</label>
                            <div class="transaction-type-toggle">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="transactionTypeToggle">
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label-left">Material Out</span>
                                    <span class="toggle-label-right">Material In</span>
                                </label>
                            </div>
                            <input type="hidden" id="transType" name="type" value="OUT" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transMaterial">Material</label>
                            <select id="transMaterial" name="raw_material_id" class="form-input" required>
                                <option value="">Select Material</option>
                                <?php $materials = $conn->query("SELECT id, name FROM raw_materials ORDER BY name"); while ($row = $materials->fetch_assoc()): ?>
                                                                       <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transProduct">Product Used</label>
                            <!-- --- FIX: Query 'product_materials' table and use 'product_id' for the value --- -->
                            <select id="transProduct" name="product_id" class="form-input">
                                <option value="">Select Product (if applicable)</option>
                                <?php $products = $conn->query("SELECT product_id, name FROM product_materials ORDER BY name"); while ($row = $products->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['product_id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transQuantity">Quantity</label>
                            <input type="number" id="transQuantity" name="quantity" class="form-input" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="transLocation">Location</label>
                            <select id="transLocation" name="location_id" class="form-input" required>
                                <option value="">Select Location</option>
                                <?php $locs = $conn->query("SELECT id, name FROM locations ORDER BY name"); while ($row = $locs->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transOperator">Operator</label>
                            <input type="text" id="transOperator" name="operator" class="form-input" required placeholder="Enter operator name...">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="transNotes">Notes</label>
                            <textarea id="transNotes" name="notes" class="form-input" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" id="cancelAddTransactionBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div id="addSupplierModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('addSupplierModal')">&times;</span>
            <div class="form-section">
                <div class="form-header">
                    <h2>Add New Supplier</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_supplier">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Supplier Name</label>
                            <input type="text" name="name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-input" rows="3" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Rating</label>
                            <select name="rating" class="form-input" required>
                                <option value="">Select Rating</option>
                                <option value="1">1 Star</option>
                                <option value="2">2 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="5">5 Stars</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input" required>
                                <option value="">Select Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('addSupplierModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div id="editSupplierModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editSupplierModal')">&times;</span>
            <div class="form-section">
                <div class="form-header">
                    <h2>Edit Supplier</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_supplier">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Supplier Name</label>
                            <input type="text" name="name" id="edit_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="edit_contact_person" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" id="edit_phone" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="edit_address" class="form-input" rows="3" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Rating</label>
                            <select name="rating" id="edit_rating" class="form-input" required>
                                <option value="">Select Rating</option>
                                <option value="1">1 Star</option>
                                <option value="2">2 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="5">5 Stars</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-input" required>
                                <option value="">Select Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('editSupplierModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteSupplierModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deleteSupplierModal')">&times;</span>
            <div class="form-section">
                <div class="form-header">
                    <h2>Delete Supplier</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete this supplier? This action cannot be undone.</p>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('deleteSupplierModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
    
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.finance-tab');
    const modules = document.querySelectorAll('.module-content');
    const purchaseOrdersContainer = document.querySelector('#purchase-orders-tab-table');

    function copyQuotationsContentToPurchaseOrders() {
        const quotationsWrapper = document.querySelector('#quotations-tab-table');

        if (quotationsWrapper && purchaseOrdersContainer) {
            // Copy content
            purchaseOrdersContainer.innerHTML = quotationsWrapper.innerHTML;

            // Modify Actions column for PO tab
            purchaseOrdersContainer.querySelectorAll('#quotation-body tr').forEach(row => {
                const actionsCell = row.querySelector('td:last-child');

                const quotationId = row.getAttribute('data-id'); // get ID from <tr>

                if (actionsCell) {
                    actionsCell.innerHTML = ''; // Remove existing icons

                    // Create Purchase Order button
                    const poBtn = document.createElement('button');
                    poBtn.textContent = 'Purchase Order';
                    poBtn.className = 'btn btn-success btn-sm';
                    poBtn.style.marginRight = '5px';

                    // Disable visually if status is Rejected or Invalid
                    if (row.dataset.status === 'Rejected' || row.dataset.status === 'Invalid' || row.dataset.status === 'Approved') {
                        poBtn.disabled = true;
                        poBtn.classList.add('disabled');
                    }

                    poBtn.addEventListener('click', () => {
                        const status = row.dataset.status; // Get current status from <tr>
                        if (status === 'Rejected' || status === 'Invalid') {
                            alert(`Can't process this order, it's already ${status}.`);
                            return; // stop further action
                        }
                        window.location.href = 'purchase_orders_sample.php?quotation_id=' + quotationId;
                    });

                    // Reject button
                    const rejectBtn = document.createElement('button');
                    rejectBtn.textContent = 'Reject';
                    rejectBtn.className = 'btn btn-danger btn-sm';

                    // Disable visually if status is Rejected or Invalid
                    if (row.dataset.status === 'Rejected' || row.dataset.status === 'Invalid'  || row.dataset.status === 'Approved') {
                        rejectBtn.disabled = true;
                        rejectBtn.classList.add('disabled');
                    }

                    rejectBtn.addEventListener('click', () => {
                        const status = row.dataset.status;
                        if (status === 'Rejected' || status === 'Invalid') {
                            alert(`Can't process this order, it's already ${status}.`);
                            return;
                        }

                        if (confirm('Are you sure you want to reject this quotation?')) {
                            fetch('reject_quotation.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `id=${quotationId}`
                            })
                            .then(res => res.text())
                            .then(data => {
                                alert(data);
                                row.querySelector('td:nth-child(5)').textContent = 'Rejected';
                                row.querySelector('td:nth-child(5)').style.color = 'red';
                                row.dataset.status = 'Rejected'; // ✅ important for filter
                                filterAndSortQuotations(); // refresh the table immediately
                            });
                        }
                    });

                    actionsCell.appendChild(poBtn);
                    actionsCell.appendChild(rejectBtn);
                }
            });

            // Re-bind filter events for the PO tab
            initQuotationFilters(
                purchaseOrdersContainer.querySelector('#filter-status'),
                purchaseOrdersContainer.querySelector('#filter-sort'),
                purchaseOrdersContainer.querySelector('#filter-search'),
                purchaseOrdersContainer.querySelector('#quotation-body')
            );
        }
    }

    // Filter logic refactored into a reusable function
    function initQuotationFilters(statusEl, sortEl, searchEl, tbodyEl) {
        if (!statusEl || !sortEl || !searchEl || !tbodyEl) return;

        let originalRows = Array.from(tbodyEl.querySelectorAll("tr"));

        function filterAndSortQuotations() {
            const status = statusEl.value;
            const sortOrder = sortEl.value;
            const searchTerm = searchEl.value.trim().toLowerCase();
            let filteredRows = originalRows;

            if (status !== 'all') {
                filteredRows = filteredRows.filter(row => row.dataset.status === status);
            }

            if (searchTerm !== '') {
                filteredRows = filteredRows.filter(row =>
                    row.cells[1].textContent.toLowerCase().includes(searchTerm)
                );
            }

            filteredRows.sort((a, b) => {
                const getSerial = row => {
                    const match = (row.cells[0].textContent || '').match(/(\d{4})$/);
                    return match ? parseInt(match[1], 10) : 0;
                };
                return sortOrder === 'recent'
                    ? getSerial(b) - getSerial(a)
                    : getSerial(a) - getSerial(b);
            });

            tbodyEl.innerHTML = '';
            filteredRows.forEach(row => tbodyEl.appendChild(row));
        }

        statusEl.addEventListener('change', filterAndSortQuotations);
        sortEl.addEventListener('change', filterAndSortQuotations);
        searchEl.addEventListener('input', filterAndSortQuotations);

        filterAndSortQuotations(); // Initial run
    }

    // Init filters for Quotation tab
    initQuotationFilters(
        document.getElementById('filter-status'),
        document.getElementById('filter-sort'),
        document.getElementById('filter-search'),
        document.getElementById('quotation-body')
    );

    // Make it available to other scripts if needed
    window.copyQuotationsContentToPurchaseOrders = copyQuotationsContentToPurchaseOrders;

    // Initial copy on page load
    setTimeout(copyQuotationsContentToPurchaseOrders, 0);

    // Restore active tab
    const savedTabId = localStorage.getItem('activeFinanceTab');
    if (savedTabId) {
        const savedTab = document.querySelector(`.finance-tab[data-tab="${savedTabId}"]`);
        const savedModule = document.getElementById(savedTabId);
        if (savedTab && savedModule) {
            tabs.forEach(t => t.classList.remove('active'));
            modules.forEach(m => m.classList.remove('active'));
            savedTab.classList.add('active');
            savedModule.classList.add('active');
        }
    }

    // Tab click handling
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');
            localStorage.setItem('activeFinanceTab', target);

            tabs.forEach(t => t.classList.remove('active'));
            modules.forEach(m => m.classList.remove('active'));

            tab.classList.add('active');
            const targetModule = document.getElementById(target);
            if (targetModule) {
                targetModule.classList.add('active');
            }

            // If Purchase Orders is clicked, refresh its content from Quotation
            if (target === 'purchase-orders') {
                copyQuotationsContentToPurchaseOrders();
            }
        });
    });
});
</script>


</body>
</html>

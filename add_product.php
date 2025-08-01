<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();

    try {
        // Auto-generate Product Code
        $result = $conn->query("SELECT MAX(CAST(SUBSTRING(product_code, 4) AS UNSIGNED)) as max_code FROM products WHERE product_code LIKE 'FP-%'");
        $max_code = $result->fetch_assoc()['max_code'] ?? 0;
        $new_code_num = $max_code + 1;
        $product_code = 'FP-' . str_pad($new_code_num, 3, '0', STR_PAD_LEFT);

        // Sanitize and retrieve POST data
        $name = trim($_POST['name']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $status = ($stock_quantity > 0) ? 'Active' : 'Inactive';
        $raw_materials = $_POST['raw_materials'] ?? [];
        $category_id = null;

        // Handle Image Upload
        $image_url = null;
        if (isset($_FILES['image_url'])) {
            if ($_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['image_url']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('prodimg_') . "." . $ext;
                $target_dir = __DIR__ . "/images/";
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $target_file = $target_dir . $filename;
                if (move_uploaded_file($_FILES['image_url']['tmp_name'], $target_file)) {
                    $image_url = $filename;
                }
            }
        }

        // Insert into products table
        $stmt = $conn->prepare("INSERT INTO products (product_code, name, category_id, stock_quantity, status, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiss", $product_code, $name, $category_id, $stock_quantity, $status, $image_url);
        $stmt->execute();
        $product_id = $stmt->insert_id;
        $stmt->close();

        // Insert into product_materials table
        if (!empty($raw_materials) && $product_id) {
            $stmt_pm = $conn->prepare("INSERT INTO product_materials (product_id, raw_material_id) VALUES (?, ?)");
            foreach ($raw_materials as $material_id) {
                $material_id = intval($material_id);
                $stmt_pm->bind_param("ii", $product_id, $material_id);
                $stmt_pm->execute();
            }
            $stmt_pm->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Product added successfully!', 'product_id' => $product_id]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
    exit;
}
?>
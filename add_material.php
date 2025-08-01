<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve POST data
    $name = trim($_POST['name']);
    $code_color = trim($_POST['code_color']);
    $stock_quantity = floatval($_POST['stock_quantity']);
    $location_id = intval($_POST['location_id']);
    
    // Determine status based on stock quantity
    $status = ($stock_quantity > 0) ? 'In Stock' : 'Out of Stock';
    
    // The 'raw_materials' table has a 'category_id' column.
    // We will keep this logic as requested, assuming you might add a categories table later.
    $category_id = 1; // Default category
    
    // Get current timestamp
    $now = date('Y-m-d H:i:s');

    // Handle image uploads
    $imageNames = [null, null, null]; // Initialize with nulls
    $uploadDir = __DIR__ . "/images/";

    // Create images directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    for ($i = 1; $i <= 3; $i++) {
        $imgField = 'image' . $i;
        if (isset($_FILES[$imgField]) && $_FILES[$imgField]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$imgField]['name'], PATHINFO_EXTENSION);
            $filename = uniqid('matimg_') . "." . $ext;
            $target = $uploadDir . $filename;
            
            // Move the uploaded file
            if (move_uploaded_file($_FILES[$imgField]['tmp_name'], $target)) {
                $imageNames[$i - 1] = $filename;
            }
        }
    }
    list($image1, $image2, $image3) = $imageNames;

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO raw_materials (code_color, name, category_id, location_id, stock_quantity, status, image1, image2, image3, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // --- FIX: CORRECTED BIND_PARAM TYPE FOR stock_quantity (s -> d) ---
    // 'd' is for double (float/decimal values)
    $stmt->bind_param("ssiidssssss", $code_color, $name, $category_id, $location_id, $stock_quantity, $status, $image1, $image2, $image3, $now, $now);

    // Execute and respond
    if ($stmt->execute()) {
        // If the request was made via JavaScript (AJAX)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        // Standard form submission
        header("Location: raw_materials.php?success=1");
        exit;
    } else {
        // Handle errors
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $stmt->error]);
            exit;
        }
        header("Location: raw_materials.php?error=1");
        exit;
    }
}
?>

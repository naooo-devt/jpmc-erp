<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $code_color = trim($_POST['code_color']);
    $stock_quantity = floatval($_POST['stock_quantity']);
    $location_id = intval($_POST['location_id']);
    $status = ($stock_quantity > 0) ? 'In Stock' : 'Out of Stock';
    $now = date('Y-m-d H:i:s');

    // Fetch current image filenames
    $stmt = $conn->prepare("SELECT image1, image2, image3 FROM raw_materials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($cur_image1, $cur_image2, $cur_image3);
    $stmt->fetch();
    $stmt->close();

    // Handle image uploads (replace if new, keep old if not)
    $imageNames = [$cur_image1, $cur_image2, $cur_image3];
    for ($i = 1; $i <= 3; $i++) {
        $imgField = 'image' . $i;
        if (isset($_FILES[$imgField]) && $_FILES[$imgField]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$imgField]['name'], PATHINFO_EXTENSION);
            $filename = uniqid('matimg_') . "." . $ext;
            $target = __DIR__ . "/images/" . $filename;
            if (move_uploaded_file($_FILES[$imgField]['tmp_name'], $target)) {
                $imageNames[$i-1] = $filename;
            }
        }
    }
    list($image1, $image2, $image3) = $imageNames;

    $stmt = $conn->prepare("UPDATE raw_materials SET code_color=?, name=?, stock_quantity=?, location_id=?, status=?, image1=?, image2=?, image3=?, updated_at=? WHERE id=?");
    $stmt->bind_param("ssdiissssi", $code_color, $name, $stock_quantity, $location_id, $status, $image1, $image2, $image3, $now, $id);

    if ($stmt->execute()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => true]);
            exit;
        }
        header("Location: raw_materials.php?success=1");
        exit;
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => false]);
            exit;
        }
        header("Location: raw_materials.php?error=1");
        exit;
    }
}
?> 
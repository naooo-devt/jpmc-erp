<?php
// delete_product.php
// This script handles the deletion of a finished product.

session_start();
// Ensure the user is logged in before allowing deletion
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

require_once 'db_connect.php';

// Only proceed if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the product ID from the POST request
    $product_id = intval($_POST['id']);

    if ($product_id > 0) {
        $conn->begin_transaction();
        try {
            // The database is set up with ON DELETE CASCADE for product_materials,
            // so related entries in that table will be deleted automatically.
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            // Check if any row was actually deleted
            if ($stmt->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Product not found or already deleted.']);
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Product ID.']);
    }
    exit;
}
?>

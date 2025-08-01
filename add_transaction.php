<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize response array
    $response = [
        'status' => '',
        'message' => '',
        'errors' => []
    ];

    // Validate and sanitize input data
    $transaction_date = isset($_POST['transaction_date']) ? trim($_POST['transaction_date']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $raw_material_id = isset($_POST['raw_material_id']) ? (int)$_POST['raw_material_id'] : 0;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0;
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
    $operator = isset($_POST['operator']) ? trim($_POST['operator']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

    // Validate required fields
    if (empty($transaction_date)) {
        $response['errors']['transaction_date'] = 'Transaction date is required';
    }

    if (empty($type)) {
        $response['errors']['type'] = 'Transaction type is required';
    } elseif (!in_array($type, ['IN', 'OUT'])) {
        $response['errors']['type'] = 'Invalid transaction type';
    }

    if ($raw_material_id <= 0) {
        $response['errors']['raw_material_id'] = 'Please select a valid material';
    }

    if ($quantity <= 0) {
        $response['errors']['quantity'] = 'Quantity must be greater than 0';
    }

    if ($location_id <= 0) {
        $response['errors']['location_id'] = 'Please select a valid location';
    }

    if (empty($operator)) {
        $response['errors']['operator'] = 'Operator name is required';
    }

    // If no validation errors, proceed with database operation
    if (empty($response['errors'])) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // 1. Get current stock balance for the material
            $balance_stmt = $conn->prepare("
                SELECT balance 
                FROM transactions 
                WHERE raw_material_id = ? 
                ORDER BY transaction_date DESC, id DESC 
                LIMIT 1
            ");
            $balance_stmt->bind_param("i", $raw_material_id);
            $balance_stmt->execute();
            $balance_result = $balance_stmt->get_result();
            $current_balance = $balance_result->num_rows > 0 ? $balance_result->fetch_assoc()['balance'] : 0;
            $balance_stmt->close();

            // 2. Calculate new balance
            $new_balance = $current_balance;
            if ($type === 'IN') {
                $new_balance += $quantity;
            } else {
                // Check if we have enough stock for OUT transaction
                if ($current_balance < $quantity) {
                    throw new Exception("Insufficient stock. Current balance: $current_balance");
                }
                $new_balance -= $quantity;
            }

            // 3. Generate transaction ID
            $transaction_id_str = 'TRX-' . date('Ymd-His') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

            // 4. Insert the new transaction
            $insert_stmt = $conn->prepare("
                INSERT INTO transactions (
                    transaction_id_str, 
                    raw_material_id, 
                    product_id, 
                    user_id, 
                    type, 
                    quantity, 
                    location_id, 
                    balance, 
                    notes, 
                    transaction_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $user_id = $_SESSION['user_id'] ?? 1; // Default to admin if not set
            $insert_stmt->bind_param(
                "siiisdiiss", 
                $transaction_id_str,
                $raw_material_id,
                $product_id,
                $user_id,
                $type,
                $quantity,
                $location_id,
                $new_balance,
                $notes,
                $transaction_date
            );

            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to insert transaction: " . $insert_stmt->error);
            }
            $insert_stmt->close();

            // 5. Update raw material stock quantity
            $update_material_stmt = $conn->prepare("
                UPDATE raw_materials 
                SET stock_quantity = ? 
                WHERE id = ?
            ");
            $update_material_stmt->bind_param("di", $new_balance, $raw_material_id);
            
            if (!$update_material_stmt->execute()) {
                throw new Exception("Failed to update material stock: " . $update_material_stmt->error);
            }
            $update_material_stmt->close();

            // 6. Update material status based on new quantity
            $status = 'Normal';
            if ($new_balance <= 0) {
                $status = 'Out of Stock';
            } elseif ($new_balance < 100) { // Assuming 100 is the threshold for "Critical"
                $status = 'Critical';
            } elseif ($new_balance < 500) { // Assuming 500 is the threshold for "Low"
                $status = 'Low';
            }

            $update_status_stmt = $conn->prepare("
                UPDATE raw_materials 
                SET status = ? 
                WHERE id = ?
            ");
            $update_status_stmt->bind_param("si", $status, $raw_material_id);
            
            if (!$update_status_stmt->execute()) {
                throw new Exception("Failed to update material status: " . $update_status_stmt->error);
            }
            $update_status_stmt->close();

            // Commit transaction
            $conn->commit();

            $response['status'] = 'success';
            $response['message'] = 'Transaction recorded successfully!';
            $response['transaction_id'] = $transaction_id_str;

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            $response['status'] = 'error';
            $response['message'] = 'Error: ' . $e->getMessage();
            $response['errors']['database'] = $e->getMessage();
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Please fix the errors below';
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;

} else {
    // Not a POST request - redirect to transactions page
    header('Location: transactions.php');
    exit;
}
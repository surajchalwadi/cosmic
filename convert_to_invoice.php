<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include 'config/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['estimate_id']) || empty($input['estimate_id'])) {
    echo json_encode(['success' => false, 'message' => 'Estimate ID is required']);
    exit;
}

$estimate_id = (int)$input['estimate_id'];

try {
    // Check if estimate exists
    $estimate_query = "SELECT * FROM estimates WHERE estimate_id = ?";
    $estimate_stmt = mysqli_prepare($conn, $estimate_query);
    mysqli_stmt_bind_param($estimate_stmt, "i", $estimate_id);
    mysqli_stmt_execute($estimate_stmt);
    $estimate_result = mysqli_stmt_get_result($estimate_stmt);
    
    if (mysqli_num_rows($estimate_result) == 0) {
        throw new Exception('Quotation not found');
    }
    
    $estimate = mysqli_fetch_assoc($estimate_result);
    
    // Check if already converted to invoice
    if ($estimate['invoice_created']) {
        echo json_encode(['success' => true, 'message' => 'Invoice already created', 'already_created' => true]);
        exit;
    }
    
    // Update estimate to mark as invoice created
    $update_query = "UPDATE estimates SET invoice_created = TRUE WHERE estimate_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $estimate_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully marked as invoice created',
            'estimate_id' => $estimate_id
        ]);
    } else {
        throw new Exception('Failed to update quotation: ' . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>

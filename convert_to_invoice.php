<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include 'config/db.php';
include 'includes/mail_functions.php';

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
        // Generate PDF and send email to client
        $emailSent = false;
        $emailMessage = '';
        
        // Get client email
        $client_query = "SELECT c.email, c.client_name FROM clients c 
                        INNER JOIN estimates e ON c.client_id = e.client_id 
                        WHERE e.estimate_id = ?";
        $client_stmt = mysqli_prepare($conn, $client_query);
        mysqli_stmt_bind_param($client_stmt, "i", $estimate_id);
        mysqli_stmt_execute($client_stmt);
        $client_result = mysqli_stmt_get_result($client_stmt);
        
        if ($client_result && mysqli_num_rows($client_result) > 0) {
            $client = mysqli_fetch_assoc($client_result);
            
            if (!empty($client['email'])) {
                // Generate PDF
                try {
                    $pdfData = generateInvoicePDF($estimate_id);
                    
                    if ($pdfData) {
                        // Send email with PDF attachment
                        $emailSent = sendInvoiceEmail(
                            $client['email'],
                            $client['client_name'],
                            $pdfData['invoice'],
                            $pdfData['path']
                        );
                        
                        if ($emailSent) {
                            $emailMessage = ' Invoice email sent to ' . $client['email'];
                        } else {
                            $emailMessage = ' Warning: Failed to send invoice email to ' . $client['email'];
                        }
                    } else {
                        $emailMessage = ' Warning: Failed to generate PDF for email';
                    }
                } catch (Exception $emailError) {
                    error_log("Email/PDF generation error: " . $emailError->getMessage());
                    $emailMessage = ' Warning: Email system error - ' . $emailError->getMessage();
                }
            } else {
                $emailMessage = ' Warning: Client email not found, invoice email not sent';
            }
        }
        
        // Create admin notification for invoice conversion
        include 'includes/notification_functions.php';
        if ($_SESSION['user']['role'] === 'sales') {
            notifyInvoiceConverted($_SESSION['user']['id'], $_SESSION['user']['name'], $estimate);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully created invoice.' . $emailMessage,
            'estimate_id' => $estimate_id,
            'email_sent' => $emailSent
        ]);
    } else {
        throw new Exception('Failed to update quotation: ' . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    error_log("Invoice conversion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>

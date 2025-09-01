<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: quotation_list.php");
    exit;
}

$estimate_id = intval($_GET['id']);

// Fetch invoice data to verify it exists
$query = "SELECT e.*, c.client_name, c.email FROM estimates e 
          LEFT JOIN clients c ON e.client_id = c.client_id 
          WHERE e.estimate_id = ? AND e.invoice_created = 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $estimate_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: quotation_list.php");
    exit;
}

$invoice = mysqli_fetch_assoc($result);

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Invoice_' . $invoice['estimate_number'] . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Use the same browser-based PDF generation as quotations
// This will trigger the browser's "Save as PDF" functionality
header('Location: invoice_print.php?id=' . $estimate_id . '&download=1');
exit;
?>

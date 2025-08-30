<?php
// Simple test script to debug email functionality
session_start();

// Set up test session (bypass auth for testing)
$_SESSION['user'] = ['role' => 'admin'];

include 'config/db.php';
include 'includes/mail_functions.php';

echo "<h2>Email System Test</h2>";

// Test 1: Check configuration
echo "<h3>1. Configuration Check</h3>";
echo "SMTP Host: " . SMTP_HOST . "<br>";
echo "SMTP Port: " . SMTP_PORT . "<br>";
echo "SMTP Username: " . SMTP_USERNAME . "<br>";
echo "SMTP Password: " . (SMTP_PASSWORD ? "Set (length: " . strlen(SMTP_PASSWORD) . ")" : "Not set") . "<br>";
echo "From Email: " . FROM_EMAIL . "<br>";

// Test 2: Database connection
echo "<h3>2. Database Connection</h3>";
if ($conn) {
    echo "✅ Database connected<br>";
    
    // Check if we have any quotations
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM estimates");
    $count = mysqli_fetch_assoc($result)['count'];
    echo "Total quotations: $count<br>";
    
    // Check if we have clients
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM clients");
    $count = mysqli_fetch_assoc($result)['count'];
    echo "Total clients: $count<br>";
} else {
    echo "❌ Database connection failed<br>";
}

// Test 3: Directory check
echo "<h3>3. Directory Check</h3>";
$tempDir = 'temp/invoices/';
if (is_dir($tempDir)) {
    echo "✅ temp/invoices/ directory exists<br>";
    if (is_writable($tempDir)) {
        echo "✅ Directory is writable<br>";
    } else {
        echo "❌ Directory is not writable<br>";
    }
} else {
    echo "❌ temp/invoices/ directory does not exist<br>";
}

// Test 4: Simple SMTP connection test
echo "<h3>4. SMTP Connection Test</h3>";
$smtp = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
if ($smtp) {
    echo "✅ Can connect to " . SMTP_HOST . ":" . SMTP_PORT . "<br>";
    fclose($smtp);
} else {
    echo "❌ Cannot connect to " . SMTP_HOST . ":" . SMTP_PORT . " - $errstr ($errno)<br>";
}

// Test 5: Test with a real quotation if available
echo "<h3>5. Test Invoice Conversion</h3>";
$query = "SELECT e.estimate_id, e.estimate_number, c.email, c.client_name 
          FROM estimates e 
          LEFT JOIN clients c ON e.client_id = c.client_id 
          WHERE e.invoice_created = 0 AND c.email IS NOT NULL 
          LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $estimate = mysqli_fetch_assoc($result);
    echo "Found test quotation: " . $estimate['estimate_number'] . " for " . $estimate['client_name'] . " (" . $estimate['email'] . ")<br>";
    
    // Test PDF generation
    echo "<strong>Testing PDF generation...</strong><br>";
    try {
        $pdfData = generateInvoicePDF($estimate['estimate_id']);
        if ($pdfData) {
            echo "✅ PDF generated successfully: " . $pdfData['filename'] . "<br>";
            echo "File path: " . $pdfData['path'] . "<br>";
            if (file_exists($pdfData['path'])) {
                echo "✅ PDF file exists on disk<br>";
            } else {
                echo "❌ PDF file not found on disk<br>";
            }
        } else {
            echo "❌ PDF generation failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ PDF generation error: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "No suitable quotation found for testing (need quotation with client email that hasn't been converted yet)<br>";
}

echo "<br><a href='quotation_list.php'>← Back to Quotations</a>";
?>

<?php
require_once 'config/mail_config.php';

// Function to send email with attachment using SMTP
function sendInvoiceEmail($clientEmail, $clientName, $invoiceData, $pdfPath) {
    try {
        // Prepare email content
        $to = $clientEmail;
        $subject = str_replace('{INVOICE_NUMBER}', $invoiceData['estimate_number'], INVOICE_EMAIL_SUBJECT);
        
        // Replace placeholders in email body
        $body = INVOICE_EMAIL_BODY;
        $body = str_replace('{CLIENT_NAME}', $clientName, $body);
        $body = str_replace('{INVOICE_NUMBER}', $invoiceData['estimate_number'], $body);
        $body = str_replace('{INVOICE_DATE}', date('F j, Y', strtotime($invoiceData['estimate_date'])), $body);
        $body = str_replace('{TOTAL_AMOUNT}', formatCurrency($invoiceData['total_amount'], $invoiceData['currency']), $body);
        
        // Use SMTP to send email
        return sendSMTPEmail($to, $subject, $body, $pdfPath);
        
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

// Function to send SMTP email
function sendSMTPEmail($to, $subject, $body, $attachmentPath = null) {
    // Create socket connection to SMTP server
    $smtp = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
    if (!$smtp) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return false;
    }
    
    // Read server response
    $response = fgets($smtp, 512);
    if (substr($response, 0, 3) != '220') {
        error_log("SMTP server not ready: $response");
        fclose($smtp);
        return false;
    }
    
    // Send EHLO command
    fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($smtp, 512);
    
    // Start TLS if required
    if (SMTP_SECURE == 'tls') {
        fputs($smtp, "STARTTLS\r\n");
        $response = fgets($smtp, 512);
        if (substr($response, 0, 3) != '220') {
            error_log("STARTTLS failed: $response");
            fclose($smtp);
            return false;
        }
        
        // Enable crypto
        if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("TLS encryption failed");
            fclose($smtp);
            return false;
        }
        
        // Send EHLO again after TLS
        fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = fgets($smtp, 512);
    }
    
    // Authenticate
    fputs($smtp, "AUTH LOGIN\r\n");
    $response = fgets($smtp, 512);
    if (substr($response, 0, 3) != '334') {
        error_log("AUTH LOGIN failed: $response");
        fclose($smtp);
        return false;
    }
    
    // Send username
    fputs($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
    $response = fgets($smtp, 512);
    if (substr($response, 0, 3) != '334') {
        error_log("Username authentication failed: $response");
        fclose($smtp);
        return false;
    }
    
    // Send password
    fputs($smtp, base64_encode(SMTP_PASSWORD) . "\r\n");
    $response = fgets($smtp, 512);
    if (substr($response, 0, 3) != '235') {
        error_log("Password authentication failed: $response");
        fclose($smtp);
        return false;
    }
    
    // Send MAIL FROM
    fputs($smtp, "MAIL FROM: <" . FROM_EMAIL . ">\r\n");
    $response = fgets($smtp, 512);
    if (substr($response, 0, 3) != '250') {
        error_log("MAIL FROM failed: $response");
        fclose($smtp);
        return false;
    }
    
    // Send RCPT TO
    fputs($smtp, "RCPT TO: <$to>\r\n");
    $response = fgets($smtp, 512);
    if (substr($response, 0, 3) != '250') {
        error_log("RCPT TO failed: $response");
        fclose($smtp);
        return false;
    }
    
    // Send DATA command
    fputs($smtp, "DATA\r\n");
    $response = fgets($smtp, 512);
    if (substr($response, 0, 3) != '354') {
        error_log("DATA command failed: $response");
        fclose($smtp);
        return false;
    }
    
    // Prepare email headers and body
    $boundary = md5(time());
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    $headers .= "\r\n";
    
    // Email body
    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $body . "\r\n";
    
    // Add attachment if provided
    if ($attachmentPath && file_exists($attachmentPath)) {
        $filename = basename($attachmentPath);
        $fileContent = chunk_split(base64_encode(file_get_contents($attachmentPath)));
        
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
        $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= $fileContent . "\r\n";
    }
    
    $message .= "--$boundary--\r\n";
    
    // Send the email
    fputs($smtp, $headers . $message . "\r\n.\r\n");
    $response = fgets($smtp, 512);
    if (substr($response, 0, 3) != '250') {
        error_log("Email sending failed: $response");
        fclose($smtp);
        return false;
    }
    
    // Send QUIT
    fputs($smtp, "QUIT\r\n");
    fclose($smtp);
    
    return true;
}

// Function to format currency
function formatCurrency($amount, $currency = 'INR') {
    $symbols = [
        'INR' => '₹',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥'
    ];
    
    $symbol = isset($symbols[$currency]) ? $symbols[$currency] : $currency . ' ';
    return $symbol . number_format($amount, 2);
}

// Function to generate invoice PDF
function generateInvoicePDF($estimateId) {
    global $conn;
    
    // Fetch invoice data
    $query = "SELECT e.*, c.client_name, c.email, c.company, c.phone, c.address, c.city, c.state, c.country, c.postal
              FROM estimates e
              LEFT JOIN clients c ON e.client_id = c.client_id
              WHERE e.estimate_id = ? AND e.invoice_created = 1";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $estimateId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        return false;
    }
    
    $invoice = mysqli_fetch_assoc($result);
    
    // Fetch invoice items
    $items_query = "SELECT * FROM estimate_items WHERE estimate_id = ?";
    $items_stmt = mysqli_prepare($conn, $items_query);
    mysqli_stmt_bind_param($items_stmt, "i", $estimateId);
    mysqli_stmt_execute($items_stmt);
    $items_result = mysqli_stmt_get_result($items_stmt);
    
    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = $item;
    }
    
    // Generate PDF content
    $html = generateInvoiceHTML($invoice, $items);
    
    // Create PDF file path
    $pdfDir = 'temp/invoices/';
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }
    
    $pdfFileName = 'invoice_' . $invoice['estimate_number'] . '_' . date('Y-m-d') . '.pdf';
    $pdfPath = $pdfDir . $pdfFileName;
    
    // Use TCPDF or similar library to generate PDF
    // For now, we'll create a simple HTML file that can be converted to PDF
    file_put_contents($pdfPath . '.html', $html);
    
    return [
        'path' => $pdfPath . '.html',
        'filename' => $pdfFileName,
        'invoice' => $invoice,
        'items' => $items
    ];
}

// Function to generate invoice HTML
function generateInvoiceHTML($invoice, $items) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice - ' . htmlspecialchars($invoice['estimate_number']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; }
            .company-info { text-align: center; margin-bottom: 20px; }
            .invoice-details { margin-bottom: 30px; }
            .client-info { margin-bottom: 30px; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            .items-table th { background-color: #f5f5f5; }
            .totals { text-align: right; margin-top: 20px; }
            .total-row { margin: 5px 0; }
            .final-total { font-weight: bold; font-size: 1.2em; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>INVOICE</h1>
        </div>
        
        <div class="company-info">
            <h2>Cosmic Solutions</h2>
            <p>Your Business Address</p>
            <p>Phone: Your Phone | Email: Your Email</p>
        </div>
        
        <div class="invoice-details">
            <table style="width: 100%;">
                <tr>
                    <td><strong>Invoice Number:</strong> ' . htmlspecialchars($invoice['estimate_number']) . '</td>
                    <td><strong>Invoice Date:</strong> ' . date('F j, Y', strtotime($invoice['estimate_date'])) . '</td>
                </tr>
            </table>
        </div>
        
        <div class="client-info">
            <h3>Bill To:</h3>
            <p><strong>' . htmlspecialchars($invoice['client_name']) . '</strong></p>';
    
    if ($invoice['company']) {
        $html .= '<p>' . htmlspecialchars($invoice['company']) . '</p>';
    }
    
    if ($invoice['address']) {
        $html .= '<p>' . htmlspecialchars($invoice['address']) . '</p>';
    }
    
    $html .= '</div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($items as $item) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($item['product_description']) . '</td>
                    <td>' . number_format($item['quantity'], 2) . '</td>
                    <td>' . formatCurrency($item['unit_price'], $invoice['currency']) . '</td>
                    <td>' . formatCurrency($item['amount'], $invoice['currency']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="totals">
            <div class="total-row">Subtotal: ' . formatCurrency($invoice['subtotal'], $invoice['currency']) . '</div>';
    
    if ($invoice['tax_amount'] > 0) {
        $html .= '<div class="total-row">Tax: ' . formatCurrency($invoice['tax_amount'], $invoice['currency']) . '</div>';
    }
    
    if ($invoice['discount_amount'] > 0) {
        $html .= '<div class="total-row">Discount: -' . formatCurrency($invoice['discount_amount'], $invoice['currency']) . '</div>';
    }
    
    $html .= '
            <div class="total-row final-total">Total: ' . formatCurrency($invoice['total_amount'], $invoice['currency']) . '</div>
        </div>
        
        <div style="margin-top: 50px; text-align: center; color: #666;">
            <p>Thank you for your business!</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>

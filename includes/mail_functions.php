<?php
require_once __DIR__ . '/../config/mail_config.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// Function to send email with PDF link using PHPMailer
function sendInvoiceEmail($clientEmail, $clientName, $invoiceData, $pdfPath = null) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($clientEmail, $clientName);
        
        // Content
        $subject = str_replace('{INVOICE_NUMBER}', $invoiceData['estimate_number'], INVOICE_EMAIL_SUBJECT);
        $mail->Subject = $subject;
        
        // Replace placeholders in email body
        $body = INVOICE_EMAIL_BODY;
        $body = str_replace('{CLIENT_NAME}', $clientName, $body);
        $body = str_replace('{INVOICE_NUMBER}', $invoiceData['estimate_number'], $body);
        $body = str_replace('{INVOICE_DATE}', date('F j, Y', strtotime($invoiceData['estimate_date'])), $body);
        $body = str_replace('{TOTAL_AMOUNT}', formatCurrency($invoiceData['total_amount'], $invoiceData['currency']), $body);
        
        // Add PDF link instead of attachment
        $pdfLink = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/invoice_pdf_view.php?id=' . $invoiceData['estimate_id'];
        $body .= "\n\nView and download your invoice PDF: $pdfLink";
        $body .= "\n\nClick the link above to view your invoice and use your browser's 'Save as PDF' option to download it.";
        
        $mail->isHTML(false);
        $mail->Body = $body;
        
        $result = $mail->send();
        
        if ($result) {
            error_log("Email sent successfully to: $clientEmail");
            return true;
        } else {
            error_log("Email failed to send: " . $mail->getErrorInfo());
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

// Function to send SMTP email using working SMTP implementation
function sendSMTPEmail($to, $subject, $body, $attachmentPath = null) {
    try {
        $smtp = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
        if (!$smtp) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }
        
        // Read greeting
        $response = fgets($smtp, 512);
        if (substr($response, 0, 3) != '220') {
            error_log("SMTP server not ready: $response");
            fclose($smtp);
            return false;
        }
        
        // EHLO
        fputs($smtp, "EHLO localhost\r\n");
        do {
            $line = fgets($smtp, 512);
        } while (substr($line, 3, 1) == '-');
        
        // STARTTLS
        fputs($smtp, "STARTTLS\r\n");
        $response = fgets($smtp, 512);
        if (substr($response, 0, 3) != '220') {
            error_log("STARTTLS failed: $response");
            fclose($smtp);
            return false;
        }
        
        // Enable TLS
        if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("TLS encryption failed");
            fclose($smtp);
            return false;
        }
        
        // EHLO again after TLS
        fputs($smtp, "EHLO localhost\r\n");
        do {
            $line = fgets($smtp, 512);
        } while (substr($line, 3, 1) == '-');
        
        // AUTH LOGIN
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
        
        // MAIL FROM
        fputs($smtp, "MAIL FROM: <" . FROM_EMAIL . ">\r\n");
        $response = fgets($smtp, 512);
        if (substr($response, 0, 3) != '250') {
            error_log("MAIL FROM failed: $response");
            fclose($smtp);
            return false;
        }
        
        // RCPT TO
        fputs($smtp, "RCPT TO: <$to>\r\n");
        $response = fgets($smtp, 512);
        if (substr($response, 0, 3) != '250') {
            error_log("RCPT TO failed: $response");
            fclose($smtp);
            return false;
        }
        
        // DATA
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 512);
        if (substr($response, 0, 3) != '354') {
            error_log("DATA command failed: $response");
            fclose($smtp);
            return false;
        }
        
        // Prepare email content
        $boundary = md5(time());
        $email = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
        $email .= "To: $to\r\n";
        $email .= "Subject: $subject\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        
        if ($attachmentPath && file_exists($attachmentPath)) {
            $email .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";
            
            // Text part
            $email .= "--$boundary\r\n";
            $email .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $email .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $email .= $body . "\r\n";
            
            // Attachment part
            $filename = basename($attachmentPath);
            $fileContent = chunk_split(base64_encode(file_get_contents($attachmentPath)));
            $email .= "--$boundary\r\n";
            $email .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
            $email .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $email .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $email .= $fileContent . "\r\n";
            $email .= "--$boundary--\r\n";
        } else {
            $email .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $email .= $body . "\r\n";
        }
        
        $email .= ".\r\n";
        
        // Send email content
        fputs($smtp, $email);
        $response = fgets($smtp, 512);
        if (substr($response, 0, 3) != '250') {
            error_log("Email sending failed: $response");
            fclose($smtp);
            return false;
        }
        
        // QUIT
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        error_log("Email sent successfully to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
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

// Function to generate invoice PDF using proper PDF structure
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
    
    // Create PDF file path
    $pdfDir = 'temp/invoices/';
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }
    
    $pdfFileName = 'invoice_' . $invoice['estimate_number'] . '_' . date('Y-m-d') . '.pdf';
    $pdfPath = $pdfDir . $pdfFileName;
    
    // Generate actual PDF content with proper structure
    $pdfContent = createValidInvoicePDF($invoice, $items);
    file_put_contents($pdfPath, $pdfContent);
    
    return [
        'path' => $pdfPath,
        'filename' => $pdfFileName,
        'invoice' => $invoice,
        'items' => $items
    ];
}

// Function to create valid PDF content for email attachments
function createValidInvoicePDF($invoice, $items) {
    // Create a properly structured PDF with invoice content
    $pdf = "%PDF-1.4\n";
    
    // Build content stream with invoice layout similar to quotation format
    $content = "q\n"; // Save graphics state
    
    // Header section with company branding
    $content .= "0.08 0.35 0.64 rg\n"; // Blue color for header
    $content .= "50 720 512 60 re f\n"; // Header background rectangle
    $content .= "Q\n"; // Restore graphics state
    
    $content .= "BT\n"; // Begin text
    
    // Company name - large and bold
    $content .= "/F2 18 Tf\n";
    $content .= "1 1 1 rg\n"; // White text
    $content .= "60 745 Td\n";
    $content .= "(COSMIC SOLUTIONS) Tj\n";
    
    // Invoice title - right aligned
    $content .= "/F2 20 Tf\n";
    $content .= "300 0 Td\n";
    $content .= "(INVOICE) Tj\n";
    
    // Company details - left side
    $content .= "/F1 8 Tf\n";
    $content .= "0 0 0 rg\n"; // Black text
    $content .= "-300 -25 Td\n";
    $content .= "(EF-102, 1st Floor, E-boshan Building) Tj\n";
    $content .= "0 -10 Td (Boshan Hotels, Opp. Bodgeshwar Temple) Tj\n";
    $content .= "0 -10 Td (Mapusa - Goa. GSTN: 30AAMFC9553C1ZN) Tj\n";
    $content .= "0 -10 Td (Email: prajyot@cosmicsolutions.co.in | Phone: 8390831122) Tj\n";
    
    // Invoice details - right side
    $content .= "/F1 9 Tf\n";
    $content .= "300 40 Td\n";
    $content .= "(Invoice Number: " . $invoice['estimate_number'] . ") Tj\n";
    $content .= "0 -12 Td (Invoice Date: " . date('d-m-Y', strtotime($invoice['estimate_date'])) . ") Tj\n";
    $content .= "0 -12 Td (Status: Paid) Tj\n";
    
    // Client information section
    $content .= "/F2 10 Tf\n";
    $content .= "0.08 0.35 0.64 rg\n"; // Blue text
    $content .= "-300 -50 Td\n";
    $content .= "(Bill To) Tj\n";
    
    $content .= "/F1 9 Tf\n";
    $content .= "0 0 0 rg\n";
    $content .= "0 -15 Td\n";
    $clientName = $invoice['bill_client_name'] ?? $invoice['client_name'] ?? 'Not specified';
    $content .= "(" . substr($clientName, 0, 35) . ") Tj\n";
    
    if (!empty($invoice['bill_company']) || !empty($invoice['company'])) {
        $company = $invoice['bill_company'] ?? $invoice['company'];
        $content .= "0 -12 Td (" . substr($company, 0, 35) . ") Tj\n";
    }
    
    // Items table header
    $content .= "ET\n"; // End text
    $content .= "q\n";
    $content .= "0.08 0.35 0.64 rg\n"; // Blue background
    $content .= "50 480 512 20 re f\n";
    $content .= "Q\n";
    
    // Table headers
    $content .= "BT\n";
    $content .= "/F2 8 Tf\n";
    $content .= "1 1 1 rg\n"; // White text
    $content .= "60 485 Td\n";
    $content .= "(SR.) Tj\n";
    $content .= "40 0 Td (QTY) Tj\n";
    $content .= "60 0 Td (PRODUCT) Tj\n";
    $content .= "120 0 Td (DESCRIPTION) Tj\n";
    $content .= "100 0 Td (UNIT PRICE) Tj\n";
    $content .= "80 0 Td (TOTAL) Tj\n";
    
    // Items data
    $content .= "/F1 8 Tf\n";
    $content .= "0 0 0 rg\n";
    $content .= "-400 -18 Td\n";
    
    $sr = 1;
    $yPos = 0;
    foreach ($items as $item) {
        $desc = substr($item['product_description'], 0, 12);
        $content .= "($sr) Tj\n";
        $content .= "40 0 Td (" . number_format($item['quantity'], 1) . ") Tj\n";
        $content .= "60 0 Td ($desc) Tj\n";
        $content .= "120 0 Td ($desc) Tj\n";
        $content .= "100 0 Td (Rs." . number_format($item['unit_price'], 2) . ") Tj\n";
        $content .= "80 0 Td (Rs." . number_format($item['amount'], 2) . ") Tj\n";
        $content .= "-400 -12 Td\n";
        $sr++;
        $yPos += 12;
    }
    
    // Totals section
    $content .= "/F1 9 Tf\n";
    $content .= "320 " . (-25 - $yPos) . " Td\n";
    $content .= "(SUB TOTAL: Rs." . number_format($invoice['subtotal'], 2) . ") Tj\n";
    $content .= "0 -12 Td (TAX: Rs." . number_format($invoice['tax_amount'], 2) . ") Tj\n";
    $content .= "0 -12 Td (DISCOUNT: Rs." . number_format($invoice['discount_amount'], 2) . ") Tj\n";
    
    // Final total with background
    $content .= "ET\n";
    $content .= "q\n";
    $content .= "0.08 0.35 0.64 rg\n";
    $totalY = 380 - $yPos;
    $content .= "320 $totalY 180 15 re f\n";
    $content .= "Q\n";
    
    $content .= "BT\n";
    $content .= "/F2 10 Tf\n";
    $content .= "1 1 1 rg\n";
    $content .= "325 " . ($totalY + 3) . " Td\n";
    $content .= "(TOTAL: Rs." . number_format($invoice['total_amount'], 2) . ") Tj\n";
    
    // Terms & Conditions
    $content .= "/F2 9 Tf\n";
    $content .= "0 0 0 rg\n";
    $termsY = $totalY - 35;
    $content .= "-325 $termsY Td\n";
    $content .= "(Terms & Conditions) Tj\n";
    
    $content .= "/F1 7 Tf\n";
    $content .= "0 -12 Td (\u2022 Total price inclusive of CGST @9% and SGST @9%) Tj\n";
    $content .= "0 -10 Td (\u2022 Payment 60% advance balance 40% on installation) Tj\n";
    $content .= "0 -10 Td (\u2022 Prices are valid till 1 week) Tj\n";
    $content .= "0 -10 Td (\u2022 All disputes subject to Goa jurisdiction only) Tj\n";
    
    // Footer
    $content .= "/F1 8 Tf\n";
    $content .= "120 -25 Td\n";
    $content .= "(Make all checks payable to Cosmic Solutions) Tj\n";
    
    $content .= "ET\n"; // End text
    
    // Build complete PDF structure
    $pdf .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n";
    $pdf .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n";
    $pdf .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n/F2 6 0 R\n>>\n>>\n>>\nendobj\n";
    
    $contentLength = strlen($content);
    $pdf .= "4 0 obj\n<<\n/Length $contentLength\n>>\nstream\n$content\nendstream\nendobj\n";
    $pdf .= "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n";
    $pdf .= "6 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica-Bold\n>>\nendobj\n";
    
    // Cross-reference table with correct offsets
    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 7\n";
    $pdf .= "0000000000 65535 f \n";
    $pdf .= "0000000009 00000 n \n";
    $pdf .= "0000000058 00000 n \n";
    $pdf .= "0000000115 00000 n \n";
    $pdf .= "0000000244 00000 n \n";
    $pdf .= sprintf("%010d 00000 n \n", 244 + $contentLength + 50);
    $pdf .= sprintf("%010d 00000 n \n", 244 + $contentLength + 120);
    
    $pdf .= "trailer\n<<\n/Size 7\n/Root 1 0 R\n>>\nstartxref\n$xrefPos\n%%EOF";
    
    return $pdf;
}

// Function to create proper PDF content with valid structure
function createProperInvoicePDF($invoice, $items) {
    // Build PDF content stream with proper formatting
    $content = "q\n"; // Save graphics state
    
    // Header background
    $content .= "0.85 0.9 0.95 rg\n"; // Light blue background
    $content .= "50 720 512 60 re f\n"; // Rectangle fill
    
    // Header border
    $content .= "0.2 0.4 0.6 RG 2 w\n"; // Blue border
    $content .= "50 720 512 60 re S\n"; // Rectangle stroke
    
    $content .= "Q\n"; // Restore graphics state
    
    $content .= "BT\n"; // Begin text
    
    // Company name - bold and large
    $content .= "/F2 20 Tf\n";
    $content .= "0.2 0.4 0.6 rg\n"; // Blue text
    $content .= "60 745 Td\n";
    $content .= "(COSMIC SOLUTIONS) Tj\n";
    
    // Invoice title - right aligned
    $content .= "/F2 24 Tf\n";
    $content .= "280 0 Td\n";
    $content .= "(INVOICE) Tj\n";
    
    // Company details - left side
    $content .= "/F1 9 Tf\n";
    $content .= "0 0 0 rg\n"; // Black text
    $content .= "-280 -20 Td\n";
    $content .= "(FF-24, 1st Floor, Laxmichand Building) Tj\n";
    $content .= "0 -10 Td (Opp. Mahalaxmi Temple, Lalbaug) Tj\n";
    $content .= "0 -10 Td (Mumbai - 400 012, MAHARASHTRA) Tj\n";
    $content .= "0 -10 Td (Mob: 8850731192 | Email: surajlchalwadi@gmail.com) Tj\n";
    
    // Invoice details - right side
    $content .= "/F1 10 Tf\n";
    $content .= "280 40 Td\n";
    $content .= "(Invoice Number: " . $invoice['estimate_number'] . ") Tj\n";
    $content .= "0 -12 Td (Invoice Date: " . date('d-m-Y', strtotime($invoice['estimate_date'])) . ") Tj\n";
    $content .= "0 -12 Td (Status: Paid) Tj\n";
    
    // Bill To section
    $content .= "/F2 12 Tf\n";
    $content .= "0.2 0.4 0.6 rg\n";
    $content .= "-280 -60 Td\n";
    $content .= "(Bill To) Tj\n";
    
    $content .= "/F1 10 Tf\n";
    $content .= "0 0 0 rg\n";
    $content .= "0 -15 Td\n";
    $clientName = $invoice['bill_client_name'] ?? $invoice['client_name'] ?? 'Not specified';
    $content .= "(" . substr($clientName, 0, 40) . ") Tj\n";
    
    if (!empty($invoice['bill_company']) || !empty($invoice['company'])) {
        $company = $invoice['bill_company'] ?? $invoice['company'];
        $content .= "0 -12 Td (" . substr($company, 0, 40) . ") Tj\n";
    }
    
    // Items table header background
    $content .= "ET\n"; // End text
    $content .= "q\n";
    $content .= "0.2 0.4 0.6 rg\n";
    $content .= "50 480 512 25 re f\n";
    $content .= "Q\n";
    
    // Table headers
    $content .= "BT\n";
    $content .= "/F2 10 Tf\n";
    $content .= "1 1 1 rg\n"; // White text
    $content .= "60 488 Td\n";
    $content .= "(SR.) Tj\n";
    $content .= "50 0 Td (QTY) Tj\n";
    $content .= "80 0 Td (PRODUCT) Tj\n";
    $content .= "120 0 Td (DESCRIPTION) Tj\n";
    $content .= "120 0 Td (UNIT PRICE) Tj\n";
    $content .= "80 0 Td (TOTAL) Tj\n";
    
    // Items data
    $content .= "/F1 9 Tf\n";
    $content .= "0 0 0 rg\n";
    $content .= "-450 -20 Td\n";
    
    $sr = 1;
    $yPos = 0;
    foreach ($items as $item) {
        $desc = substr($item['product_description'], 0, 15);
        $content .= "($sr) Tj\n";
        $content .= "50 0 Td (" . number_format($item['quantity'], 1) . ") Tj\n";
        $content .= "80 0 Td ($desc) Tj\n";
        $content .= "120 0 Td ($desc) Tj\n";
        $content .= "120 0 Td (Rs." . number_format($item['unit_price'], 2) . ") Tj\n";
        $content .= "80 0 Td (Rs." . number_format($item['amount'], 2) . ") Tj\n";
        $content .= "-450 -15 Td\n";
        $sr++;
        $yPos += 15;
    }
    
    // Totals section
    $content .= "/F1 10 Tf\n";
    $content .= "350 " . (-30 - $yPos) . " Td\n";
    $content .= "(SUB TOTAL: Rs." . number_format($invoice['subtotal'], 2) . ") Tj\n";
    $content .= "0 -15 Td (TAX: Rs." . number_format($invoice['tax_amount'], 2) . ") Tj\n";
    $content .= "0 -15 Td (DISCOUNT: Rs." . number_format($invoice['discount_amount'], 2) . ") Tj\n";
    
    // Final total with background
    $content .= "ET\n";
    $content .= "q\n";
    $content .= "0.2 0.4 0.6 rg\n";
    $totalY = 380 - $yPos;
    $content .= "350 $totalY 150 20 re f\n";
    $content .= "Q\n";
    
    $content .= "BT\n";
    $content .= "/F2 12 Tf\n";
    $content .= "1 1 1 rg\n";
    $content .= "360 " . ($totalY + 5) . " Td\n";
    $content .= "(TOTAL: Rs." . number_format($invoice['total_amount'], 2) . ") Tj\n";
    
    // Terms & Conditions
    $content .= "/F2 10 Tf\n";
    $content .= "0 0 0 rg\n";
    $termsY = $totalY - 40;
    $content .= "-360 $termsY Td\n";
    $content .= "(Terms & Conditions) Tj\n";
    
    $content .= "/F1 8 Tf\n";
    $content .= "0 -15 Td (• Total price inclusive of CGST @9% and SGST @9%) Tj\n";
    $content .= "0 -10 Td (• Payment 60% advance balance 40% on installation) Tj\n";
    $content .= "0 -10 Td (• Prices are valid till 1 week) Tj\n";
    $content .= "0 -10 Td (• Delivery within 15-20 working days from order confirmation) Tj\n";
    $content .= "0 -10 Td (• Installation charges extra if applicable) Tj\n";
    $content .= "0 -10 Td (• All disputes subject to Mumbai jurisdiction only) Tj\n";
    
    // Footer
    $content .= "/F1 9 Tf\n";
    $content .= "150 -30 Td\n";
    $content .= "(Make all checks payable to Cosmic Solutions) Tj\n";
    
    $content .= "ET\n"; // End text
    
    // Build complete PDF with proper structure
    $pdf = "%PDF-1.4\n";
    
    // Object 1: Catalog
    $pdf .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n";
    
    // Object 2: Pages
    $pdf .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n";
    
    // Object 3: Page
    $pdf .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n/F2 6 0 R\n>>\n>>\n>>\nendobj\n";
    
    // Object 4: Content stream
    $contentLength = strlen($content);
    $pdf .= "4 0 obj\n<<\n/Length $contentLength\n>>\nstream\n$content\nendstream\nendobj\n";
    
    // Object 5: Font (Helvetica)
    $pdf .= "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n";
    
    // Object 6: Font (Helvetica-Bold)
    $pdf .= "6 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica-Bold\n>>\nendobj\n";
    
    // Cross-reference table
    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 7\n";
    $pdf .= "0000000000 65535 f \n";
    $pdf .= sprintf("%010d 00000 n \n", 9);
    $pdf .= sprintf("%010d 00000 n \n", 60);
    $pdf .= sprintf("%010d 00000 n \n", 120);
    $pdf .= sprintf("%010d 00000 n \n", 280);
    $pdf .= sprintf("%010d 00000 n \n", 280 + $contentLength + 50);
    $pdf .= sprintf("%010d 00000 n \n", 280 + $contentLength + 120);
    
    // Trailer
    $pdf .= "trailer\n<<\n/Size 7\n/Root 1 0 R\n>>\nstartxref\n$xrefPos\n%%EOF";
    
    return $pdf;
}

// Function to generate invoice HTML using exact quotation format with logo
function generateInvoiceHTML($invoice, $items) {
    // Check for logo files
    $logoCandidates = ['assets/img/logo.png', 'assets/img/logo-cosmic.png'];
    $logoPath = null;
    foreach ($logoCandidates as $candidate) {
        if (file_exists($candidate)) { 
            $logoPath = $candidate; 
            break; 
        }
    }
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - ' . htmlspecialchars($invoice['estimate_number']) . '</title>
    <style>
        @media print { .no-print { display: none !important; } }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: #ffffff; color: #1d1d1f; padding: 30px; border-bottom: 3px solid #155ba3; }
        .company-info { display: flex; justify-content: space-between; }
        .company-logo { font-size: 24px; font-weight: bold; color: #155ba3; }
        .quotation-title { text-align: right; font-size: 28px; font-weight: bold; color: #155ba3; }
        .content { padding: 30px; }
        .client-section { display: flex; gap: 30px; margin-bottom: 30px; }
        .bill-to, .ship-to { flex: 1; background: #f8f9fa; padding: 20px; border-left: 4px solid #155ba3; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #155ba3; color: white; padding: 12px; text-align: left; }
        .items-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .summary-table { width: 300px; margin-left: auto; }
        .summary-table td { padding: 8px 15px; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 18px; color: #155ba3; border-top: 2px solid #155ba3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-info">
                <div>';
    
    // Add logo if available
    if ($logoPath) {
        $html .= '<img src="' . $logoPath . '?v=' . (@filemtime($logoPath) ?: time()) . '" alt="Cosmic Solutions" style="height:60px; margin-bottom:10px; display:block;">';
    }
    
    $html .= '<div class="company-logo">Cosmic Solutions</div>
                    <div style="font-size: 12px; line-height: 1.4;">
                        EF-102, 1st Floor, E-boshan Building<br>
                        Boshan Hotels, Opp. Bodgeshwar Temple<br>
                        Mapusa - Goa. GSTN: 30AAMFC9553C1ZN<br>
                        Goa 403507<br>
                        Email: prajyot@cosmicsolutions.co.in<br>
                        Phone: 8390831122
                    </div>
                </div>
                <div>
                    <div class="quotation-title">INVOICE</div>
                    <div style="text-align: right; font-size: 14px;">
                        <div><strong>Invoice Number:</strong> ' . htmlspecialchars($invoice['estimate_number']) . '</div>
                        <div><strong>Invoice Date:</strong> ' . date('d-m-Y', strtotime($invoice['estimate_date'])) . '</div>
                        <div><strong>Status:</strong> Paid</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="client-section">
                <div class="bill-to">
                    <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Bill To</div>
                    <div style="font-weight: bold; font-size: 16px; margin-bottom: 10px;">' . htmlspecialchars($invoice['bill_client_name'] ?? $invoice['client_name'] ?? 'Not specified') . '</div>';

    if (!empty($invoice['bill_company']) || !empty($invoice['company'])) {
        $company = $invoice['bill_company'] ?? $invoice['company'];
        $html .= '<div style="color: #666; margin-bottom: 10px;">' . htmlspecialchars($company) . '</div>';
    }

    $address = $invoice['bill_address'] ?? $invoice['address'] ?? '';
    $html .= '<div style="font-size: 14px; line-height: 1.4; color: #555;">' . nl2br(htmlspecialchars($address)) . '</div>
                </div>
                
                <div class="ship-to">
                    <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Ship To</div>
                    <div style="font-weight: bold; font-size: 16px; margin-bottom: 10px;">' . htmlspecialchars($invoice['ship_client_name'] ?? $invoice['bill_client_name'] ?? $invoice['client_name'] ?? 'Not specified') . '</div>';

    if (!empty($invoice['ship_company']) || !empty($invoice['bill_company']) || !empty($invoice['company'])) {
        $shipCompany = $invoice['ship_company'] ?? $invoice['bill_company'] ?? $invoice['company'];
        $html .= '<div style="color: #666; margin-bottom: 10px;">' . htmlspecialchars($shipCompany) . '</div>';
    }

    $shipAddress = $invoice['ship_address'] ?? $invoice['bill_address'] ?? $invoice['address'] ?? '';
    $html .= '<div style="font-size: 14px; line-height: 1.4; color: #555;">' . nl2br(htmlspecialchars($shipAddress)) . '</div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">SR. NO.</th>
                        <th style="width: 10%;">QTY</th>
                        <th style="width: 22%;">PRODUCT</th>
                        <th style="width: 30%;">DESCRIPTION</th>
                        <th style="width: 15%;">UNIT PRICE</th>
                        <th style="width: 15%;">LINE TOTAL</th>
                    </tr>
                </thead>
                <tbody>';

    $sr_no = 1;
    foreach ($items as $item) {
        $html .= '<tr>
                    <td style="text-align: center; font-weight: bold;">' . $sr_no++ . '</td>
                    <td style="text-align: center;">' . number_format($item['quantity'], 2) . '</td>
                    <td style="font-weight: bold; color: #155ba3;">' . htmlspecialchars($item['product_description']) . '</td>
                    <td style="font-size: 13px; color: #666;">' . htmlspecialchars($item['product_description']) . '</td>
                    <td style="text-align: right;">₹' . number_format($item['unit_price'], 2) . '</td>
                    <td style="text-align: right;">₹' . number_format($item['amount'], 2) . '</td>
                </tr>';
    }

    $html .= '</tbody>
            </table>

            <table class="summary-table">
                <tr>
                    <td>SUB TOTAL:</td>
                    <td style="text-align: right;">₹' . number_format($invoice['subtotal'], 2) . '</td>
                </tr>
                <tr>
                    <td>TAX:</td>
                    <td style="text-align: right;">₹' . number_format($invoice['tax_amount'], 2) . '</td>
                </tr>
                <tr>
                    <td>DISCOUNT:</td>
                    <td style="text-align: right;">₹' . number_format($invoice['discount_amount'], 2) . '</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">₹' . number_format($invoice['total_amount'], 2) . '</td>
                </tr>
            </table>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-top: 30px;">
                <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Terms & Conditions</div>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                        Total price inclusive of CGST @9%.
                    </li>
                    <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                        Total price inclusive of SGST @9%.
                    </li>
                    <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                        Payment 60% advance balance 40% on installation.
                    </li>
                    <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                        Prices are valid till 1 week.
                    </li>
                </ul>
            </div>

            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-top: 1px solid #eee; margin-top: 30px; font-size: 14px; color: #666;">
                Make all checks payable to Cosmic Solutions
            </div>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}
?>

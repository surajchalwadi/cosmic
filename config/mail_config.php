<?php
// Email configuration settings - Choose one option below:

// OPTION 1: Gmail (Free) - Most Popular
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'surajlchalwadi@gmail.com'); // Replace with your Gmail address
define('SMTP_PASSWORD', 'ihju jxtq ijnm xawu'); // Replace with Gmail App Password (16 characters)
define('SMTP_SECURE', 'tls');

// OPTION 2: Outlook/Hotmail (Free)
/*
define('SMTP_HOST', 'smtp-mail.outlook.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@outlook.com'); // or @hotmail.com
define('SMTP_PASSWORD', 'your-outlook-password');
define('SMTP_SECURE', 'tls');
*/

// OPTION 3: Yahoo Mail (Free)
/*
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@yahoo.com');
define('SMTP_PASSWORD', 'your-yahoo-app-password');
define('SMTP_SECURE', 'tls');
*/

define('FROM_EMAIL', 'surajlchalwadi@gmail.com'); // Replace with your Gmail address (same as SMTP_USERNAME)
define('FROM_NAME', 'Cosmic Solutions');

// Email templates
define('INVOICE_EMAIL_SUBJECT', 'Invoice from Cosmic Solutions - #{INVOICE_NUMBER}');
define('INVOICE_EMAIL_BODY', '
Dear {CLIENT_NAME},

Thank you for your business! Please find attached your invoice.

Invoice Details:
- Invoice Number: {INVOICE_NUMBER}
- Invoice Date: {INVOICE_DATE}
- Total Amount: {TOTAL_AMOUNT}

If you have any questions about this invoice, please don\'t hesitate to contact us.

Best regards,
Cosmic Solutions Team
');
?>

# Email Setup Instructions for Invoice Automation

## Overview
The system now automatically sends invoice emails to clients when a quotation is converted to an invoice. The email includes a PDF attachment of the invoice.

## Configuration Steps

### 1. Email Configuration
Edit the file: `config/mail_config.php`

```php
// Update these settings with your email provider details:
define('SMTP_HOST', 'smtp.gmail.com'); // Your SMTP server
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Your email
define('SMTP_PASSWORD', 'your-app-password'); // Your app password
define('SMTP_SECURE', 'tls'); // or 'ssl'

define('FROM_EMAIL', 'your-email@gmail.com'); // Your email
define('FROM_NAME', 'Cosmic Solutions');
```

### 2. Free Email Provider Setup

#### Option A: Gmail (Recommended - Free)
1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to [Google Account Settings](https://myaccount.google.com/)
   - Security → 2-Step Verification → App passwords
   - Select "Mail" and generate password
   - Copy the 16-character password (use this in `SMTP_PASSWORD`)
3. **Update config**:
   ```php
   define('SMTP_USERNAME', 'youremail@gmail.com');
   define('SMTP_PASSWORD', 'abcd efgh ijkl mnop'); // App password
   define('FROM_EMAIL', 'youremail@gmail.com');
   ```

#### Option B: Outlook/Hotmail (Free)
1. **Use your regular password** (no app password needed)
2. **Update config** (uncomment Outlook section):
   ```php
   define('SMTP_HOST', 'smtp-mail.outlook.com');
   define('SMTP_USERNAME', 'youremail@outlook.com');
   define('SMTP_PASSWORD', 'your-regular-password');
   ```

#### Option C: Yahoo Mail (Free)
1. **Enable App Passwords** in Yahoo Account Security
2. **Generate App Password** for Mail
3. **Update config** (uncomment Yahoo section):
   ```php
   define('SMTP_HOST', 'smtp.mail.yahoo.com');
   define('SMTP_USERNAME', 'youremail@yahoo.com');
   define('SMTP_PASSWORD', 'your-yahoo-app-password');
   ```

### 3. Directory Permissions
Ensure the `temp/invoices/` directory exists and is writable:
```bash
mkdir -p temp/invoices
chmod 755 temp/invoices
```

### 4. PHP Mail Configuration
Ensure your server has mail functionality enabled. For local development with XAMPP:
1. Configure `php.ini` for mail settings
2. Or use a mail service like SendGrid, Mailgun, etc.

## How It Works

1. **Client Management**: Clients must have valid email addresses in the client database
2. **Invoice Creation**: When a quotation is converted to an invoice via the "Convert to Invoice" button
3. **Automatic Email**: The system:
   - Generates a PDF invoice
   - Sends an email to the client's registered email
   - Includes the PDF as an attachment
   - Shows success/error messages

## Email Template

The email includes:
- Professional subject line with invoice number
- Client's name and invoice details
- PDF attachment with complete invoice
- Company branding

## Troubleshooting

### Common Issues:
1. **Email not sending**: Check SMTP credentials and server settings
2. **PDF not generating**: Ensure `temp/invoices/` directory exists and is writable
3. **Client email missing**: Verify client has email address in database
4. **Permission errors**: Check file/directory permissions

### Error Messages:
- "Client email not found" - Client doesn't have email in database
- "Failed to generate PDF" - File system or permission issue
- "Failed to send email" - SMTP configuration issue

## Testing

1. Add a client with a valid email address
2. Create a quotation for that client
3. Convert the quotation to an invoice
4. Check if email is sent and received

## Security Notes

- Never commit real email credentials to version control
- Use environment variables for production
- Consider using OAuth2 for Gmail instead of app passwords
- Regularly rotate email passwords

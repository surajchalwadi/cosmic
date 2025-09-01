<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * Simplified version for this project
 */

namespace PHPMailer\PHPMailer;

class PHPMailer
{
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';
    
    public $isSMTP = false;
    public $Host = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $Port = 587;
    public $setFromData = [];
    public $addressList = [];
    public $Subject = '';
    public $Body = '';
    public $isHTMLContent = false;
    public $attachmentList = [];
    
    private $smtp_connection;
    private $error_info = '';
    
    public function __construct($exceptions = null) {
        // Constructor
    }
    
    public function isSMTP() {
        $this->isSMTP = true;
    }
    
    public function setFrom($address, $name = '') {
        $this->setFromData = ['address' => $address, 'name' => $name];
    }
    
    public function addAddress($address, $name = '') {
        $this->addressList[] = ['address' => $address, 'name' => $name];
    }
    
    public function addAttachment($path, $name = '') {
        if (file_exists($path)) {
            $this->attachmentList[] = ['path' => $path, 'name' => $name ?: basename($path)];
        }
    }
    
    public function isHTML($isHtml = true) {
        $this->isHTMLContent = $isHtml;
    }
    
    public function send() {
        if (!$this->isSMTP) {
            return $this->sendMail();
        }
        
        return $this->sendSMTP();
    }
    
    private function sendSMTP() {
        try {
            // Connect to SMTP server
            $this->smtp_connection = fsockopen($this->Host, $this->Port, $errno, $errstr, 30);
            if (!$this->smtp_connection) {
                $this->error_info = "Connection failed: $errstr ($errno)";
                return false;
            }
            
            // Read greeting
            $response = $this->getResponse();
            if (!$this->checkResponse($response, '220')) {
                return false;
            }
            
            // Send EHLO
            $this->sendCommand("EHLO localhost");
            $response = $this->getResponse();
            
            // Start TLS if required
            if ($this->SMTPSecure === self::ENCRYPTION_STARTTLS) {
                $this->sendCommand("STARTTLS");
                $response = $this->getResponse();
                if (!$this->checkResponse($response, '220')) {
                    return false;
                }
                
                if (!stream_socket_enable_crypto($this->smtp_connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $this->error_info = "TLS encryption failed";
                    return false;
                }
                
                // Send EHLO again after TLS
                $this->sendCommand("EHLO localhost");
                $this->getResponse();
            }
            
            // Authenticate
            if ($this->SMTPAuth) {
                $this->sendCommand("AUTH LOGIN");
                $response = $this->getResponse();
                if (!$this->checkResponse($response, '334')) {
                    return false;
                }
                
                $this->sendCommand(base64_encode($this->Username));
                $response = $this->getResponse();
                if (!$this->checkResponse($response, '334')) {
                    return false;
                }
                
                $this->sendCommand(base64_encode($this->Password));
                $response = $this->getResponse();
                if (!$this->checkResponse($response, '235')) {
                    $this->error_info = "Authentication failed";
                    return false;
                }
            }
            
            // Send MAIL FROM
            $this->sendCommand("MAIL FROM: <{$this->setFromData['address']}>");
            $response = $this->getResponse();
            if (!$this->checkResponse($response, '250')) {
                return false;
            }
            
            // Send RCPT TO for each recipient
            foreach ($this->addressList as $recipient) {
                $this->sendCommand("RCPT TO: <{$recipient['address']}>");
                $response = $this->getResponse();
                if (!$this->checkResponse($response, '250')) {
                    return false;
                }
            }
            
            // Send DATA
            $this->sendCommand("DATA");
            $response = $this->getResponse();
            if (!$this->checkResponse($response, '354')) {
                return false;
            }
            
            // Send email content
            $emailContent = $this->buildEmailContent();
            $this->sendCommand($emailContent . "\r\n.");
            $response = $this->getResponse();
            if (!$this->checkResponse($response, '250')) {
                return false;
            }
            
            // Send QUIT
            $this->sendCommand("QUIT");
            fclose($this->smtp_connection);
            
            return true;
            
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return false;
        }
    }
    
    private function sendCommand($command) {
        fputs($this->smtp_connection, $command . "\r\n");
    }
    
    private function getResponse() {
        if (!is_resource($this->smtp_connection)) {
            return '';
        }
        
        $data = '';
        $endtime = time() + 30; // 30 second timeout
        
        while (is_resource($this->smtp_connection) && !feof($this->smtp_connection)) {
            $str = @fgets($this->smtp_connection, 512);
            
            if ($str === false) {
                break;
            }
            
            $data .= $str;
            
            // Check if this is the final line (space after code instead of dash)
            if (strlen($str) >= 4 && substr($str, 3, 1) === ' ') {
                break;
            }
            
            if (time() > $endtime) {
                break;
            }
        }
        
        return trim($data);
    }
    
    private function checkResponse($response, $expectedCode) {
        if (substr($response, 0, 3) != $expectedCode) {
            $this->error_info = "Unexpected response: $response";
            return false;
        }
        return true;
    }
    
    private function buildEmailContent() {
        $boundary = md5(time());
        $content = "";
        
        // Headers
        $content .= "From: {$this->setFromData['name']} <{$this->setFromData['address']}>\r\n";
        $content .= "To: ";
        $recipients = [];
        foreach ($this->addressList as $recipient) {
            $recipients[] = $recipient['name'] ? "{$recipient['name']} <{$recipient['address']}>" : $recipient['address'];
        }
        $content .= implode(', ', $recipients) . "\r\n";
        $content .= "Subject: {$this->Subject}\r\n";
        $content .= "MIME-Version: 1.0\r\n";
        
        if (!empty($this->attachmentList)) {
            $content .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        } else {
            $content .= "Content-Type: " . ($this->isHTMLContent ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        }
        
        $content .= "\r\n";
        
        // Body
        if (!empty($this->attachmentList)) {
            $content .= "--$boundary\r\n";
            $content .= "Content-Type: " . ($this->isHTMLContent ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
            $content .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        }
        
        $content .= $this->Body . "\r\n";
        
        // Attachments
        foreach ($this->attachmentList as $attachment) {
            if (file_exists($attachment['path'])) {
                $fileContent = chunk_split(base64_encode(file_get_contents($attachment['path'])));
                $content .= "--$boundary\r\n";
                $content .= "Content-Type: application/octet-stream; name=\"{$attachment['name']}\"\r\n";
                $content .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
                $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $content .= $fileContent . "\r\n";
            }
        }
        
        if (!empty($this->attachmentList)) {
            $content .= "--$boundary--\r\n";
        }
        
        return $content;
    }
    
    private function sendMail() {
        // Fallback to PHP mail() function
        $to = $this->addressList[0]['address'];
        $subject = $this->Subject;
        $message = $this->Body;
        $headers = "From: {$this->setFromData['name']} <{$this->setFromData['address']}>\r\n";
        $headers .= "Content-Type: " . ($this->isHTMLContent ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
    
    public function getErrorInfo() {
        return $this->error_info;
    }
}

class Exception extends \Exception {}
?>

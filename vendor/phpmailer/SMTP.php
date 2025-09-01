<?php
/**
 * PHPMailer SMTP class.
 * Simplified SMTP implementation
 */

namespace PHPMailer\PHPMailer;

class SMTP
{
    const VERSION = '6.8.0';
    const DEFAULT_PORT = 25;
    const MAX_LINE_LENGTH = 998;
    const MAX_REPLY_LENGTH = 512;
    
    public $SMTP_PORT = 25;
    public $CRLF = "\r\n";
    public $do_debug = 0;
    public $Debugoutput = 'echo';
    public $do_verp = false;
    public $Timeout = 300;
    public $Timelimit = 300;
    
    protected $smtp_conn;
    protected $error = [];
    protected $helo_rply;
    
    public function __construct() {
        $this->smtp_conn = false;
        $this->error = [];
    }
    
    public function connect($host, $port = null, $timeout = 30, $options = []) {
        if (null === $port) {
            $port = self::DEFAULT_PORT;
        }
        
        $this->smtp_conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if (!$this->smtp_conn) {
            $this->setError("Failed to connect to server: $errstr ($errno)");
            return false;
        }
        
        // Set timeout
        stream_set_timeout($this->smtp_conn, $timeout, 0);
        
        // Get the greeting
        $announce = $this->get_lines();
        
        if (!$this->checkResponse($announce, '220')) {
            $this->setError("SMTP server did not respond with 220: $announce");
            return false;
        }
        
        return true;
    }
    
    public function startTLS() {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }
        
        if (!stream_socket_enable_crypto(
            $this->smtp_conn,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        )) {
            $this->setError('Failed to enable TLS encryption');
            return false;
        }
        
        return true;
    }
    
    public function authenticate($username, $password, $authtype = null) {
        if (!$this->sendCommand('AUTH', 'AUTH LOGIN', 334)) {
            return false;
        }
        
        if (!$this->sendCommand('Username', base64_encode($username), 334)) {
            return false;
        }
        
        if (!$this->sendCommand('Password', base64_encode($password), 235)) {
            return false;
        }
        
        return true;
    }
    
    public function hello($host = '') {
        return $this->sendCommand('EHLO', "EHLO $host", 250);
    }
    
    public function mail($from) {
        return $this->sendCommand('MAIL FROM', "MAIL FROM:<$from>", 250);
    }
    
    public function recipient($to) {
        return $this->sendCommand('RCPT TO', "RCPT TO:<$to>", [250, 251]);
    }
    
    public function data($msg_data) {
        if (!$this->sendCommand('DATA', 'DATA', 354)) {
            return false;
        }
        
        $lines = explode($this->CRLF, $msg_data);
        foreach ($lines as $line) {
            if (strlen($line) > 0 && $line[0] === '.') {
                $line = '.' . $line;
            }
            $this->client_send($line . $this->CRLF);
        }
        
        return $this->sendCommand('DATA END', '.', 250);
    }
    
    public function quit($close_on_error = true) {
        $result = $this->sendCommand('QUIT', 'QUIT', 221);
        $this->close();
        return $result;
    }
    
    public function close() {
        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = false;
        }
    }
    
    protected function sendCommand($command, $commandstring, $expect) {
        if (!$this->connected()) {
            $this->setError("Called $command without being connected");
            return false;
        }
        
        $this->client_send($commandstring . $this->CRLF);
        
        $reply = $this->get_lines();
        
        if (!$this->checkResponse($reply, $expect)) {
            $this->setError("$command command failed: $reply");
            return false;
        }
        
        return true;
    }
    
    protected function client_send($data) {
        return fwrite($this->smtp_conn, $data);
    }
    
    protected function get_lines() {
        if (!is_resource($this->smtp_conn)) {
            return '';
        }
        
        $data = '';
        $endtime = time() + $this->Timeout;
        
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            $str = @fgets($this->smtp_conn, self::MAX_REPLY_LENGTH);
            
            if ($str === false) {
                break;
            }
            
            $data .= $str;
            
            if (strlen($str) >= 4 && substr($str, 3, 1) === ' ') {
                break;
            }
            
            if (time() > $endtime) {
                break;
            }
        }
        
        return trim($data);
    }
    
    protected function checkResponse($response, $expect) {
        if (!is_array($expect)) {
            $expect = [$expect];
        }
        
        $code = substr($response, 0, 3);
        
        return in_array($code, $expect);
    }
    
    protected function connected() {
        return is_resource($this->smtp_conn);
    }
    
    protected function setError($message) {
        $this->error[] = $message;
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function getLastReply() {
        return isset($this->error[count($this->error) - 1]) ? $this->error[count($this->error) - 1] : '';
    }
}
?>

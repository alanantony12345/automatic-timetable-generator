<?php
class SimpleSMTP
{
    private $host;
    private $port;
    private $username;
    private $password;
    public $error = ""; // Public error property

    public function __construct($host, $port, $username, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function send($to, $subject, $message, $fromName = "AutoTimetable")
    {
        $socket = fsockopen("ssl://{$this->host}", $this->port, $errno, $errstr, 10);
        if (!$socket) {
            $this->error = "Connection failed: $errstr ($errno)";
            return false;
        }

        $this->read($socket);
        $this->write($socket, "EHLO {$this->host}");
        $this->read($socket);
        $this->write($socket, "AUTH LOGIN");
        $this->read($socket);
        $this->write($socket, base64_encode($this->username));
        $res = $this->read($socket);
        if (strpos($res, '334') === false) {
            $this->error = "Auth Login failed: $res";
            return false;
        }

        $this->write($socket, base64_encode($this->password));
        $res = $this->read($socket);
        if (strpos($res, '235') === false) {
            $this->error = "Authentication failed: $res";
            return false;
        }

        $this->write($socket, "MAIL FROM: <{$this->username}>");
        $res = $this->read($socket);
        if (strpos($res, '250') === false) {
            $this->error = "MAIL FROM failed: $res";
            return false;
        }

        $this->write($socket, "RCPT TO: <$to>");
        $res = $this->read($socket);
        if (strpos($res, '250') === false) {
            $this->error = "RCPT TO failed: $res";
            return false;
        }

        $this->write($socket, "DATA");
        $res = $this->read($socket);
        if (strpos($res, '354') === false) {
            $this->error = "DATA failed: $res";
            return false;
        }

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: $fromName <{$this->username}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        $this->write($socket, "$headers\r\n$message\r\n.");
        $response = $this->read($socket);

        $this->write($socket, "QUIT");
        fclose($socket);

        if (strpos($response, "250") !== false) {
            return true;
        } else {
            $this->error = "Message not accepted: $response";
            return false;
        }
    }

    private function write($socket, $data)
    {
        fputs($socket, $data . "\r\n");
    }

    private function read($socket)
    {
        $response = "";
        while ($str = fgets($socket, 4096)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ")
                break;
        }
        return $response;
    }
}
?>
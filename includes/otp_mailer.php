<?php
require_once __DIR__ . '/../config/email.php';

function smtp_send_mail($toEmail, $toName, $subject, $htmlBody) {
    $fromEmail = SMTP_FROM_EMAIL;
    $fromName = SMTP_FROM_NAME;

    $socket = stream_socket_client('tcp://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 30);
    if (!$socket) {
        throw new Exception('SMTP connection failed: ' . $errstr);
    }

    $read = function() use ($socket) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    };

    $expect = function($response, $codePrefix) {
        $code = substr(trim($response), 0, 3);
        if ($code !== $codePrefix) {
            throw new Exception('SMTP error: ' . trim($response));
        }
    };

    $write = function($command, $expected = null) use ($socket, $read, $expect) {
        fwrite($socket, $command . "\r\n");
        $response = $read();
        if ($expected) {
            $expect($response, $expected);
        }
        return $response;
    };

    $response = $read();
    $expect($response, '220');

    $write('EHLO localhost', '250');
    $write('STARTTLS', '220');
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        throw new Exception('Failed to start TLS');
    }
    $write('EHLO localhost', '250');
    $write('AUTH LOGIN', '334');
    $write(base64_encode(SMTP_USER), '334');
    $smtpPass = preg_replace('/\s+/', '', SMTP_PASS);
    $write(base64_encode($smtpPass), '235');
    $write('MAIL FROM: <' . $fromEmail . '>', '250');
    $write('RCPT TO: <' . $toEmail . '>', '250');
    $write('DATA', '354');

    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'To: ' . $toName . ' <' . $toEmail . '>';

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n";
    fwrite($socket, $message . "\r\n.\r\n");
    $response = $read();
    $expect($response, '250');
    $write('QUIT', '221');

    fclose($socket);
    return true;
}

function sendOtpEmail($toEmail, $toName, $otpCode) {
    $subject = 'Your HCM System Verification Code';
    $htmlBody = "
        <div style=\"font-family: Arial, sans-serif; color: #111827;\">
            <h2 style=\"color:#1b68ff; margin-bottom: 8px;\">HCM System</h2>
            <p>Hello <strong>" . htmlspecialchars($toName ?: 'User') . "</strong>,</p>
            <p>Your one-time verification code is:</p>
            <div style=\"font-size: 24px; font-weight: bold; letter-spacing: 4px; margin: 16px 0;\">" . htmlspecialchars($otpCode) . "</div>
            <p>This code will expire in 10 minutes. If you did not request this, please ignore this email.</p>
            <p style=\"margin-top: 24px; color:#6b7280; font-size: 12px;\">HCM System Security Team</p>
        </div>
    ";

    return smtp_send_mail($toEmail, $toName, $subject, $htmlBody);
}

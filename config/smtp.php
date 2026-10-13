<?php
declare(strict_types=1);

require_once __DIR__ . '/load-env.php';

function smtp_send(string $to, string $subject, string $body, array $env): void
{
    $host = (string)env_value($env, 'SMTP_HOST', '');
    $port = (int)env_value($env, 'SMTP_PORT', '587');
    $username = (string)env_value($env, 'SMTP_USERNAME', env_value($env, 'SMTP_USER', ''));
    $password = (string)env_value($env, 'SMTP_PASS', '');
    $encryption = strtolower((string)env_value($env, 'SMTP_ENCRYPTION', 'tls'));
    $from = (string)env_value($env, 'MAIL_FROM', $username);
    $fromName = (string)env_value($env, 'MAIL_FROM_NAME', 'AA Associate');

    if ($host === '' || $username === '' || $password === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('SMTP configuration is incomplete.');
    }

    $socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        throw new RuntimeException('SMTP connection failed: ' . $errno);
    }
    stream_set_timeout($socket, 15);

    $read = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        if ($response === '' || !preg_match('/^([245])[0-9]{2}/', $response)) {
            throw new RuntimeException('SMTP server rejected command.');
        }
        return $response;
    };
    $write = static function (string $command) use ($socket, $read): void {
        fwrite($socket, $command . "\r\n");
        $read();
    };

    try {
        $read();
        $write('EHLO aaspacedesign.com');
        if ($encryption === 'tls') {
            $write('STARTTLS');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP TLS negotiation failed.');
            }
            $write('EHLO aaspacedesign.com');
        }
        $write('AUTH LOGIN');
        $write(base64_encode($username));
        $write(base64_encode($password));
        $write('MAIL FROM:<' . $from . '>');
        $write('RCPT TO:<' . $to . '>');
        $write('DATA');

        $safeName = str_replace(["\r", "\n"], '', $fromName);
        $safeSubject = str_replace(["\r", "\n"], '', $subject);
        $headers = 'From: ' . $safeName . ' <' . $from . ">\r\n"
            . 'To: ' . $to . "\r\n"
            . 'Subject: ' . $safeSubject . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message = str_replace(["\r\n", "\r"], "\n", $headers . $body);
        $message = str_replace("\n", "\r\n", $message);
        $message = preg_replace('/^\./m', '..', $message) ?? $message;
        fwrite($socket, $message . "\r\n.\r\n");
        $read();
        $write('QUIT');
    } finally {
        fclose($socket);
    }
}

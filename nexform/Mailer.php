<?php
namespace NexForm;

/**
 * Mailer – sends notification and auto-responder emails.
 * Uses PHP's built-in mail() or a simple SMTP socket implementation.
 * Drop in PHPMailer if you prefer (see comments below).
 */
class Mailer
{
    /**
     * Send the admin notification email.
     *
     * @param  array       $fields      Filtered/sanitised field values
     * @param  array|null  $attachment  Result from FileHandler::handle(), or null
     * @param  string      $formId      Form identifier
     * @return bool
     */
    public function sendNotification(array $fields, ?array $attachment = null, string $formId = 'default'): bool
    {
        $subject = NF_MAIL_SUBJECT_PREFIX . ucfirst($formId) . ' Form Submission';
        $body    = $this->buildNotificationBody($fields, $formId);

        return $this->send(
            to:          NF_MAIL_TO,
            subject:     $subject,
            body:        $body,
            fromName:    NF_MAIL_FROM_NAME,
            fromEmail:   NF_MAIL_FROM_EMAIL,
            attachment:  $attachment
        );
    }

    /**
     * Send the auto-responder email to the visitor.
     *
     * @param  string $toEmail   Visitor's email address
     * @param  string $toName    Visitor's name (optional)
     * @return bool
     */
    public function sendAutoResponder(string $toEmail, string $toName = ''): bool
    {
        if (!NF_AUTORESPONDER_ENABLED) return true;

        return $this->send(
            to:        $toEmail,
            subject:   NF_AUTORESPONDER_SUBJECT,
            body:      NF_AUTORESPONDER_MESSAGE,
            fromName:  NF_MAIL_FROM_NAME,
            fromEmail: NF_MAIL_FROM_EMAIL
        );
    }

    // -------------------------------------------------------
    // Core send method
    // -------------------------------------------------------

    private function send(
        string  $to,
        string  $subject,
        string  $body,
        string  $fromName,
        string  $fromEmail,
        ?array  $attachment = null
    ): bool {
        if (NF_MAIL_TRANSPORT === 'smtp') {
            return $this->sendSmtp($to, $subject, $body, $fromName, $fromEmail, $attachment);
        }
        return $this->sendPhpMail($to, $subject, $body, $fromName, $fromEmail, $attachment);
    }

    // -------------------------------------------------------
    // PHP mail() with multipart/mixed for attachments
    // -------------------------------------------------------

    private function sendPhpMail(
        string $to, string $subject, string $body,
        string $fromName, string $fromEmail, ?array $attachment
    ): bool {
        $boundary = '----=_NexForm_' . md5(uniqid());
        $from     = $this->encodeHeader($fromName) . " <{$fromEmail}>";

        if ($attachment && file_exists($attachment['path'])) {
            $headers  = "From: {$from}\r\n";
            $headers .= "Reply-To: {$fromEmail}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
            $headers .= "X-Mailer: NexForm\r\n";

            $message  = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($this->wrapHtml($body))) . "\r\n";

            $fileData = file_get_contents($attachment['path']);
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: {$attachment['mime']}; name=\"{$attachment['name']}\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($fileData)) . "\r\n";
            $message .= "--{$boundary}--";
        } else {
            $headers  = "From: {$from}\r\n";
            $headers .= "Reply-To: {$fromEmail}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n";
            $headers .= "X-Mailer: NexForm\r\n";
            $message  = chunk_split(base64_encode($this->wrapHtml($body)));
        }

        $encodedSubject = $this->encodeHeader($subject);
        return @mail($to, $encodedSubject, $message, $headers);
    }

    // -------------------------------------------------------
    // Minimal SMTP implementation (no external lib required)
    // -------------------------------------------------------

    private function sendSmtp(
        string $to, string $subject, string $body,
        string $fromName, string $fromEmail, ?array $attachment
    ): bool {
        /*
         * NOTE: For production use with complex auth or TLS we strongly
         * recommend swapping this method for PHPMailer. Install via:
         *   composer require phpmailer/phpmailer
         * Then replace this block with:
         *   $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
         *   $mail->isSMTP(); ...
         */

        $enc  = NF_SMTP_ENCRYPTION;
        $host = ($enc === 'ssl' ? 'ssl://' : '') . NF_SMTP_HOST;
        $port = NF_SMTP_PORT;

        $sock = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$sock) return false;

        $read = fn() => fgets($sock, 512);
        $send = function (string $cmd) use ($sock, &$read) {
            fwrite($sock, $cmd . "\r\n");
            return $read();
        };

        $read(); // greeting
        $send('EHLO ' . NF_SMTP_HOST);

        if ($enc === 'tls') {
            $send('STARTTLS');
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send('EHLO ' . NF_SMTP_HOST);
        }

        $send('AUTH LOGIN');
        $send(base64_encode(NF_SMTP_USERNAME));
        $send(base64_encode(NF_SMTP_PASSWORD));
        $send("MAIL FROM:<{$fromEmail}>");
        $send("RCPT TO:<{$to}>");
        $send('DATA');

        $htmlBody   = $this->wrapHtml($body);
        $boundary   = '----=_NexForm_' . md5(uniqid());
        $encodedSub = $this->encodeHeader($subject);
        $from       = $this->encodeHeader($fromName) . " <{$fromEmail}>";

        $msg  = "From: {$from}\r\n";
        $msg .= "To: {$to}\r\n";
        $msg .= "Subject: {$encodedSub}\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: base64\r\n";
        $msg .= "X-Mailer: NexForm\r\n\r\n";
        $msg .= chunk_split(base64_encode($htmlBody)) . "\r\n.\r\n";

        fwrite($sock, $msg);
        $resp = $read();
        $send('QUIT');
        fclose($sock);

        return str_starts_with($resp, '250');
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function buildNotificationBody(array $fields, string $formId): string
    {
        $rows = '';
        foreach ($fields as $key => $val) {
            if (is_array($val)) $val = implode(', ', $val);
            $k     = htmlspecialchars(ucfirst(str_replace(['_', '-'], ' ', $key)), ENT_QUOTES);
            $v     = nl2br(htmlspecialchars((string)$val, ENT_QUOTES));
            $rows .= "<tr><td style=\"padding:8px 12px;background:#f8f8f8;font-weight:600;width:160px;vertical-align:top\">{$k}</td>
                          <td style=\"padding:8px 12px\">{$v}</td></tr>";
        }

        $date = date('d M Y, H:i T');
        $ip   = htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        return "
        <table style=\"width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:14px\">
            <tr><td style=\"padding:16px;background:#4f46e5;color:#fff;font-size:18px;font-weight:bold\">
                New Form Submission – " . htmlspecialchars(ucfirst($formId)) . "
            </td></tr>
            <tr><td style=\"padding:0\">
                <table style=\"width:100%;border-collapse:collapse\">{$rows}</table>
            </td></tr>
            <tr><td style=\"padding:10px 12px;background:#f0f0f0;font-size:12px;color:#666\">
                Submitted: {$date} &bull; IP: {$ip}
            </td></tr>
        </table>";
    }

    private function wrapHtml(string $body): string
    {
        return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head><body>{$body}</body></html>";
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }
}

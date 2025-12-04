<?php

/**
 * Service for sending emails with UTF-8 support
 */
class MailService implements MailServiceInterface
{
    /**
     * Send an email with UTF-8 encoding
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param string $fromEmail From email address
     * @param string $fromName From name
     * @return bool Success status
     */
    public function send($to, $subject, $message, $fromEmail, $fromName)
    {
        // Encode from name and subject in UTF-8
        $fromNameEncoded = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
        $subjectEncoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        // Build headers
        $headers = "From: $fromNameEncoded <$fromEmail>\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-type: text/html; charset=UTF-8\r\n";

        // Send email
        return mail($to, $subjectEncoded, $message, $headers);
    }

    /**
     * Send email to multiple recipients
     *
     * @param array $recipients Array of email addresses
     * @param string $subject
     * @param string $message
     * @param string $fromEmail
     * @param string $fromName
     * @return bool True if all emails sent successfully
     */
    public function sendToMultiple(array $recipients, $subject, $message, $fromEmail, $fromName)
    {
        $allSuccess = true;

        foreach ($recipients as $recipient) {
            if (!$this->send($recipient, $subject, $message, $fromEmail, $fromName)) {
                $allSuccess = false;
            }
        }

        return $allSuccess;
    }
}

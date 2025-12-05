<?php

/**
 * Service for sending emails with UTF-8 support
 */
class MailService implements MailServiceInterface
{
    /**
     * @var array Stores all sent emails for testing (only populated in test mode)
     */
    private static $sentEmails = [];

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
    public function send(string $to, string $subject, string $message, string $fromEmail, string $fromName): bool
    {
        // In test environment, don't actually send emails but track them
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            // Store the email for testing purposes
            self::$sentEmails[] = [
                'to' => $to,
                'subject' => $subject,
                'message' => $message,
                'fromEmail' => $fromEmail,
                'fromName' => $fromName
            ];
            // Return true to simulate successful send
            // This prevents sendmail from being called during tests
            return true;
        }

        // Encode from name and subject in UTF-8
        $fromNameEncoded = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
        $subjectEncoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        // Build headers
        $headers = "From: $fromNameEncoded <$fromEmail>\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-type: text/html; charset=UTF-8\r\n";

        // Send email (only in production)
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
    public function sendToMultiple(array $recipients, string $subject, string $message, string $fromEmail, string $fromName): bool
    {
        $allSuccess = true;

        foreach ($recipients as $recipient) {
            if (!$this->send($recipient, $subject, $message, $fromEmail, $fromName)) {
                $allSuccess = false;
            }
        }

        return $allSuccess;
    }

    /**
     * Get all sent emails (for testing only)
     *
     * @return array
     */
    public static function getSentEmails(): array
    {
        return self::$sentEmails;
    }

    /**
     * Clear sent emails (for testing only)
     */
    public static function clearSentEmails(): void
    {
        self::$sentEmails = [];
    }

    /**
     * Get the last sent email (for testing only)
     *
     * @return array|null
     */
    public static function getLastSentEmail(): ?array
    {
        return !empty(self::$sentEmails) ? end(self::$sentEmails) : null;
    }
}

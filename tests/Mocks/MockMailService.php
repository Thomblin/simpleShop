<?php

/**
 * Mock MailService for testing - doesn't send real emails
 */
class MockMailService implements MailServiceInterface
{
    /**
     * @var array Stores all sent emails for testing
     */
    private $sentEmails = [];

    /**
     * Send an email (mock - doesn't actually send)
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param string $fromEmail From email address
     * @param string $fromName From name
     * @return bool Always returns true (simulated success)
     */
    public function send($to, $subject, $message, $fromEmail, $fromName)
    {
        // Store the email for testing purposes
        $this->sentEmails[] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'fromEmail' => $fromEmail,
            'fromName' => $fromName
        ];
        
        // Return true to simulate successful send
        return true;
    }

    /**
     * Send email to multiple recipients (mock)
     *
     * @param array $recipients Array of email addresses
     * @param string $subject
     * @param string $message
     * @param string $fromEmail
     * @param string $fromName
     * @return bool Always returns true
     */
    public function sendToMultiple(array $recipients, $subject, $message, $fromEmail, $fromName)
    {
        foreach ($recipients as $recipient) {
            $this->send($recipient, $subject, $message, $fromEmail, $fromName);
        }
        return true;
    }

    /**
     * Get all sent emails (for testing)
     *
     * @return array
     */
    public function getSentEmails()
    {
        return $this->sentEmails;
    }

    /**
     * Clear sent emails (for testing)
     */
    public function clearSentEmails()
    {
        $this->sentEmails = [];
    }

    /**
     * Get the last sent email (for testing)
     *
     * @return array|null
     */
    public function getLastSentEmail()
    {
        return !empty($this->sentEmails) ? end($this->sentEmails) : null;
    }
}


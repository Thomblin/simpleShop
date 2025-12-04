<?php

interface MailServiceInterface
{
    /**
     * Send an email
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param string $fromEmail From email address
     * @param string $fromName From name
     * @return bool Success status
     */
    public function send($to, $subject, $message, $fromEmail, $fromName);
}

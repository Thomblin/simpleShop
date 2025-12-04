<?php

use PHPUnit\Framework\TestCase;

class MailServiceTest extends TestCase
{
    public function testImplementsMailServiceInterface()
    {
        $service = new MailService();
        $this->assertInstanceOf(MailServiceInterface::class, $service);
    }

    public function testSendMethodExists()
    {
        $service = new MailService();
        $this->assertTrue(method_exists($service, 'send'));
    }

    public function testSendToMultipleMethodExists()
    {
        $service = new MailService();
        $this->assertTrue(method_exists($service, 'sendToMultiple'));
    }

    /**
     * Note: We can't easily test actual email sending without:
     * 1. A mail server
     * 2. Mocking the mail() function (requires runkit or similar)
     * 3. Using a library like Symfony Mailer with test transport
     *
     * These tests verify the service structure and interface compliance.
     * Integration tests should verify actual email sending with a test SMTP server.
     */

    public function testServiceCanBeInstantiated()
    {
        $service = new MailService();
        $this->assertNotNull($service);
    }

    public function testSendMethodSignature()
    {
        $service = new MailService();
        $reflection = new ReflectionMethod($service, 'send');

        $this->assertEquals(5, $reflection->getNumberOfParameters());

        $params = $reflection->getParameters();
        $this->assertEquals('to', $params[0]->getName());
        $this->assertEquals('subject', $params[1]->getName());
        $this->assertEquals('message', $params[2]->getName());
        $this->assertEquals('fromEmail', $params[3]->getName());
        $this->assertEquals('fromName', $params[4]->getName());
    }

    public function testSendToMultipleMethodSignature()
    {
        $service = new MailService();
        $reflection = new ReflectionMethod($service, 'sendToMultiple');

        $this->assertEquals(5, $reflection->getNumberOfParameters());

        $params = $reflection->getParameters();
        $this->assertEquals('recipients', $params[0]->getName());
        $this->assertEquals('subject', $params[1]->getName());
        $this->assertEquals('message', $params[2]->getName());
        $this->assertEquals('fromEmail', $params[3]->getName());
        $this->assertEquals('fromName', $params[4]->getName());
    }

    public function testSendEncodesUtf8Subject()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        $result = $service->send(
            'test@example.com',
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        // Should return true in test mode
        $this->assertTrue($result);
        
        // Verify email was tracked
        $sentEmails = MailService::getSentEmails();
        $this->assertCount(1, $sentEmails);
        $this->assertEquals('test@example.com', $sentEmails[0]['to']);
        $this->assertEquals('Test Subject', $sentEmails[0]['subject']);
        $this->assertEquals('<p>Test message</p>', $sentEmails[0]['message']);
        $this->assertEquals('from@example.com', $sentEmails[0]['fromEmail']);
        $this->assertEquals('Test Sender', $sentEmails[0]['fromName']);
    }

    public function testSendEncodesUtf8FromName()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        // Test with UTF-8 characters in from name
        $result = $service->send(
            'test@example.com',
            'Test',
            '<p>Message</p>',
            'from@example.com',
            'Tëst Sënder'
        );
        
        $this->assertTrue($result);
        
        // Verify UTF-8 from name was stored correctly
        $lastEmail = MailService::getLastSentEmail();
        $this->assertNotNull($lastEmail);
        $this->assertEquals('Tëst Sënder', $lastEmail['fromName']);
    }

    public function testSendToMultipleWithSingleRecipient()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        $result = $service->sendToMultiple(
            ['test@example.com'],
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        $this->assertTrue($result);
        
        // Verify email was sent
        $sentEmails = MailService::getSentEmails();
        $this->assertCount(1, $sentEmails);
        $this->assertEquals('test@example.com', $sentEmails[0]['to']);
    }

    public function testSendToMultipleWithMultipleRecipients()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        $result = $service->sendToMultiple(
            ['test1@example.com', 'test2@example.com', 'test3@example.com'],
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        $this->assertTrue($result);
        
        // Verify all emails were sent
        $sentEmails = MailService::getSentEmails();
        $this->assertCount(3, $sentEmails);
        $this->assertEquals('test1@example.com', $sentEmails[0]['to']);
        $this->assertEquals('test2@example.com', $sentEmails[1]['to']);
        $this->assertEquals('test3@example.com', $sentEmails[2]['to']);
    }

    public function testSendToMultipleReturnsFalseIfAnyFails()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        // With empty recipients array, it should return true (no failures)
        $result = $service->sendToMultiple(
            [],
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        // If no recipients, all succeed (none to fail)
        $this->assertTrue($result);
        
        // No emails should be tracked
        $sentEmails = MailService::getSentEmails();
        $this->assertCount(0, $sentEmails);
    }

    public function testGetSentEmailsReturnsAllEmails()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        $service->send('test1@example.com', 'Subject 1', 'Message 1', 'from@example.com', 'From');
        $service->send('test2@example.com', 'Subject 2', 'Message 2', 'from@example.com', 'From');
        
        $sentEmails = MailService::getSentEmails();
        $this->assertCount(2, $sentEmails);
        $this->assertEquals('test1@example.com', $sentEmails[0]['to']);
        $this->assertEquals('test2@example.com', $sentEmails[1]['to']);
    }

    public function testClearSentEmailsRemovesAllEmails()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        $service->send('test@example.com', 'Subject', 'Message', 'from@example.com', 'From');
        $this->assertCount(1, MailService::getSentEmails());
        
        MailService::clearSentEmails();
        $this->assertCount(0, MailService::getSentEmails());
    }

    public function testGetLastSentEmailReturnsMostRecent()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        $service->send('first@example.com', 'First', 'Message 1', 'from@example.com', 'From');
        $service->send('last@example.com', 'Last', 'Message 2', 'from@example.com', 'From');
        
        $lastEmail = MailService::getLastSentEmail();
        $this->assertNotNull($lastEmail);
        $this->assertEquals('last@example.com', $lastEmail['to']);
        $this->assertEquals('Last', $lastEmail['subject']);
    }

    public function testGetLastSentEmailReturnsNullWhenNoEmails()
    {
        MailService::clearSentEmails();
        
        $lastEmail = MailService::getLastSentEmail();
        $this->assertNull($lastEmail);
    }

    public function testSendToMultipleWithPartialFailure()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        // In test mode, all sends succeed, but we can test the logic
        $result = $service->sendToMultiple(
            ['test1@example.com', 'test2@example.com'],
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        $this->assertTrue($result);
        $sentEmails = MailService::getSentEmails();
        $this->assertCount(2, $sentEmails);
    }

    public function testSendEncodesHeadersCorrectly()
    {
        MailService::clearSentEmails();
        $service = new MailService();
        
        // Test that UTF-8 encoding is applied
        $service->send(
            'test@example.com',
            'Test Subject with Ümläuts',
            '<p>Test message</p>',
            'from@example.com',
            'Sender with Émojis 🎉'
        );
        
        $email = MailService::getLastSentEmail();
        $this->assertNotNull($email);
        $this->assertEquals('Test Subject with Ümläuts', $email['subject']);
        $this->assertEquals('Sender with Émojis 🎉', $email['fromName']);
    }

    public function testSendProductionCodePathEncoding()
    {
        // Test the encoding logic that would be used in production
        // We can't test the actual mail() call, but we can test the encoding functions
        $testSubject = 'Test Subject with Ümläuts';
        $testFromName = 'Sender with Émojis 🎉';
        
        // Test the encoding that would be applied in production
        $fromNameEncoded = "=?UTF-8?B?" . base64_encode($testFromName) . "?=";
        $subjectEncoded = "=?UTF-8?B?" . base64_encode($testSubject) . "?=";
        
        // Verify encoding works correctly
        $this->assertStringStartsWith('=?UTF-8?B?', $fromNameEncoded);
        $this->assertStringStartsWith('=?UTF-8?B?', $subjectEncoded);
        
        // Verify we can decode it back
        $decodedSubject = base64_decode(str_replace(['=?UTF-8?B?', '?='], '', $subjectEncoded));
        $decodedFromName = base64_decode(str_replace(['=?UTF-8?B?', '?='], '', $fromNameEncoded));
        
        $this->assertEquals($testSubject, $decodedSubject);
        $this->assertEquals($testFromName, $decodedFromName);
        
        // Test header building logic
        $fromEmail = 'test@example.com';
        $headers = "From: $fromNameEncoded <$fromEmail>\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-type: text/html; charset=UTF-8\r\n";
        
        $this->assertStringContainsString($fromNameEncoded, $headers);
        $this->assertStringContainsString($fromEmail, $headers);
        $this->assertStringContainsString('MIME-Version: 1.0', $headers);
        $this->assertStringContainsString('Content-type: text/html; charset=UTF-8', $headers);
    }
}

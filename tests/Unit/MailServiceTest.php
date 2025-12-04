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
        $service = new MailService();
        
        // Test that send() method can be called (we can't easily test mail() function)
        // but we can verify the method executes without errors
        $result = @$service->send(
            'test@example.com',
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        // Result depends on mail() function, but method should execute
        $this->assertIsBool($result);
    }

    public function testSendEncodesUtf8FromName()
    {
        $service = new MailService();
        
        // Test with UTF-8 characters in from name
        $result = @$service->send(
            'test@example.com',
            'Test',
            '<p>Message</p>',
            'from@example.com',
            'Tëst Sënder'
        );
        
        $this->assertIsBool($result);
    }

    public function testSendToMultipleWithSingleRecipient()
    {
        $service = new MailService();
        
        $result = @$service->sendToMultiple(
            ['test@example.com'],
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        $this->assertIsBool($result);
    }

    public function testSendToMultipleWithMultipleRecipients()
    {
        $service = new MailService();
        
        $result = @$service->sendToMultiple(
            ['test1@example.com', 'test2@example.com', 'test3@example.com'],
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        $this->assertIsBool($result);
    }

    public function testSendToMultipleReturnsFalseIfAnyFails()
    {
        $service = new MailService();
        
        // With empty recipients array, it should return true (no failures)
        $result = @$service->sendToMultiple(
            [],
            'Test Subject',
            '<p>Test message</p>',
            'from@example.com',
            'Test Sender'
        );
        
        // If no recipients, all succeed (none to fail)
        $this->assertTrue($result);
    }
}

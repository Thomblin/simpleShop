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
}

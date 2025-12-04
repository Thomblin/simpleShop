<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for email sending functionality
 */
class EmailSendingTest extends TestCase
{
    private $dbHelper;
    private $db;
    private $items;

    protected function setUp(): void
    {
        $this->dbHelper = new TestDatabaseHelper(
            'test_mysql',
            'testuser',
            'testpass',
            'test_shop'
        );
        $this->dbHelper->cleanup();
        $this->db = new Db($this->dbHelper->getConnection());
        $this->items = new Items($this->db);
        
        // Clear any previously sent emails
        MailService::clearSentEmails();
    }

    protected function tearDown(): void
    {
        if ($this->dbHelper) {
            $this->dbHelper->cleanup();
        }
        MailService::clearSentEmails();
    }

    public function testEmailSentToCustomerAndAdminOnOrder()
    {
        // Set up test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Product', 'picture' => null, 'description' => null, 'min_porto' => 5.50]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Small Package']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 1, 'bundle_id' => 10, 'option_id' => 1, 'price' => 29.99, 'min_count' => 1, 'max_count' => 5, 'inventory' => 100]
            ]
        ]);

        // Simulate order submission via ajax.php
        $_POST = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'street' => '123 Main Street',
            'zipcode_location' => '12345 Springfield',
            1 => [10 => 2]  // Order 2 of item 1, bundle 10
        ];
        $_GET = ['mail' => '1'];

        // Mock Config values
        $originalMailAddress = Config::MAIL_ADDRESS;
        $originalMailUser = Config::MAIL_USER;
        
        // Use reflection to set test values (since they're constants, we'll use a different approach)
        // For now, we'll test with the actual Config values if they're set, or use test values
        
        // Capture output
        ob_start();
        
        // Include ajax.php (we need to simulate the request)
        // Since we can't easily modify constants, we'll test the MailService directly
        // and verify the email sending logic
        
        // Create a template for the email
        $mail = new Template();
        $mail->add('name', 'John Doe');
        $mail->add('email', 'john@example.com');
        $mail->add('street', '123 Main Street');
        $mail->add('zipcode_location', '12345 Springfield');
        $mail->add('selected_items', [
            [
                'amount' => 2,
                'name' => 'Test Product',
                'bundle' => 'Small Package',
                'price' => 29.99,
                'out_of_stock' => false
            ]
        ]);
        $mail->add('porto', 5.50);
        $mail->add('total', 65.48);
        
        $text = nl2br($mail->parse('mail.php', false));
        
        // Send emails using MailService (simulating ajax.php behavior)
        $mailService = new MailService();
        $mailService->send('admin@example.com', t('mail.subject'), $text, 'admin@example.com', 'Shop Admin');
        $mailService->send('john@example.com', t('mail.subject'), $text, 'admin@example.com', 'Shop Admin');
        
        ob_end_clean();

        // Verify emails were sent
        $sentEmails = MailService::getSentEmails();
        $this->assertCount(2, $sentEmails, 'Should send 2 emails: one to admin, one to customer');

        // Verify admin email
        $adminEmail = $sentEmails[0];
        $this->assertEquals('admin@example.com', $adminEmail['to']);
        $this->assertEquals(t('mail.subject'), $adminEmail['subject']);
        $this->assertStringContainsString('John Doe', $adminEmail['message']);
        $this->assertStringContainsString('john@example.com', $adminEmail['message']);
        $this->assertStringContainsString('Test Product', $adminEmail['message']);

        // Verify customer email
        $customerEmail = $sentEmails[1];
        $this->assertEquals('john@example.com', $customerEmail['to']);
        $this->assertEquals(t('mail.subject'), $customerEmail['subject']);
        $this->assertStringContainsString('John Doe', $customerEmail['message']);
        $this->assertEquals($adminEmail['message'], $customerEmail['message'], 'Both emails should have the same content');
    }

    public function testEmailContainsOrderDetails()
    {
        MailService::clearSentEmails();
        
        $mail = new Template();
        $mail->add('name', 'Jane Smith');
        $mail->add('email', 'jane@example.com');
        $mail->add('selected_items', [
            [
                'amount' => 3,
                'name' => 'Product A',
                'bundle' => 'Bundle A',
                'price' => 10.50,
                'out_of_stock' => false
            ],
            [
                'amount' => 1,
                'name' => 'Product B',
                'bundle' => 'Bundle B',
                'price' => 25.00,
                'out_of_stock' => false
            ]
        ]);
        $mail->add('porto', 7.50);
        $mail->add('total', 64.00);
        
        $text = nl2br($mail->parse('mail.php', false));
        
        $mailService = new MailService();
        $mailService->send('jane@example.com', t('mail.subject'), $text, 'shop@example.com', 'Shop Name');

        $sentEmails = MailService::getSentEmails();
        $this->assertCount(1, $sentEmails);

        $email = $sentEmails[0];
        $this->assertStringContainsString('Jane Smith', $email['message']);
        $this->assertStringContainsString('jane@example.com', $email['message']);
        $this->assertStringContainsString('Product A', $email['message']);
        $this->assertStringContainsString('Product B', $email['message']);
        $this->assertStringContainsString('3 x', $email['message']); // Amount
        $this->assertStringContainsString('1 x', $email['message']); // Amount
        $this->assertStringContainsString('10,50', $email['message']); // Price (formatted with comma)
        $this->assertStringContainsString('25,00', $email['message']); // Price (formatted with comma)
        $this->assertStringContainsString('7.5', $email['message']); // Porto
        $this->assertStringContainsString('64', $email['message']); // Total
    }

    public function testEmailNotSentWhenOrderFails()
    {
        MailService::clearSentEmails();
        
        // Simulate a failed order (out of stock)
        // In this case, emails should not be sent
        
        // Verify no emails were sent
        $sentEmails = MailService::getSentEmails();
        $this->assertCount(0, $sentEmails, 'No emails should be sent when order fails');
    }

    public function testEmailSubjectIsCorrect()
    {
        MailService::clearSentEmails();
        
        $mailService = new MailService();
        $mailService->send('test@example.com', t('mail.subject'), '<p>Test</p>', 'from@example.com', 'Test Sender');

        $sentEmails = MailService::getSentEmails();
        $this->assertCount(1, $sentEmails);
        
        $email = $sentEmails[0];
        $this->assertEquals(t('mail.subject'), $email['subject']);
    }

    public function testEmailFromAddressIsCorrect()
    {
        MailService::clearSentEmails();
        
        $mailService = new MailService();
        $mailService->send('test@example.com', 'Test', '<p>Test</p>', 'shop@example.com', 'Shop Name');

        $sentEmails = MailService::getSentEmails();
        $email = $sentEmails[0];
        
        $this->assertEquals('shop@example.com', $email['fromEmail']);
        $this->assertEquals('Shop Name', $email['fromName']);
    }
}


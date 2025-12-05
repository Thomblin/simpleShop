<?php

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private $config;

    protected function setUp(): void
    {
        $this->config = new Config();
    }

    public function testImplementsConfigInterface()
    {
        $this->assertInstanceOf(ConfigInterface::class, $this->config);
    }

    public function testGetMysqlHost()
    {
        $this->assertIsString($this->config->getMysqlHost());
        $this->assertEquals('shop_mysql', $this->config->getMysqlHost());
    }

    public function testGetMysqlUser()
    {
        $this->assertIsString($this->config->getMysqlUser());
        $this->assertEquals('user', $this->config->getMysqlUser());
    }

    public function testGetMysqlPassword()
    {
        $this->assertIsString($this->config->getMysqlPassword());
        $this->assertEquals('user', $this->config->getMysqlPassword());
    }

    public function testGetMysqlDatabase()
    {
        $this->assertIsString($this->config->getMysqlDatabase());
        $this->assertEquals('shop', $this->config->getMysqlDatabase());
    }

    public function testGetLanguage()
    {
        $this->assertIsString($this->config->getLanguage());
        $this->assertEquals('de', $this->config->getLanguage());
    }

    public function testGetShowInventory()
    {
        $this->assertIsBool($this->config->getShowInventory());
        $this->assertTrue($this->config->getShowInventory());
    }

    public function testGetAllowedTextfields()
    {
        $fields = $this->config->getAllowedTextfields();
        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayHasKey('street', $fields);
        $this->assertArrayHasKey('zipcode_location', $fields);
    }

    public function testGetCurrency()
    {
        $this->assertIsString($this->config->getCurrency());
        $this->assertEquals('€', $this->config->getCurrency());
    }

    public function testGetMailAddress()
    {
        $this->assertIsString($this->config->getMailAddress());
    }

    public function testGetMailUser()
    {
        $this->assertIsString($this->config->getMailUser());
    }

    public function testRequiredFieldsAreMarkedCorrectly()
    {
        $fields = $this->config->getAllowedTextfields();
        $this->assertEquals(Config::REQUIRED, $fields['name']);
        $this->assertEquals(Config::REQUIRED, $fields['email']);
        $this->assertEquals(Config::OPTIONAL, $fields['comment']);
    }

    public function testLoadConfigFromSkeletonFile()
    {
        // Create a temporary config file from the skeleton
        $skeletonPath = __DIR__ . '/../../config.php.skeleton';
        $tmpConfigPath = sys_get_temp_dir() . '/test_config_' . uniqid() . '.php';

        $this->assertFileExists($skeletonPath, 'config.php.skeleton must exist');

        // Copy skeleton to temp file
        copy($skeletonPath, $tmpConfigPath);
        $this->assertFileExists($tmpConfigPath);

        try {
            // Load config from the temporary file
            $config = new Config($tmpConfigPath);

            // Verify all database configuration values
            $this->assertEquals('shop_mysql', $config->getMysqlHost());
            $this->assertEquals('user', $config->getMysqlUser());
            $this->assertEquals('user', $config->getMysqlPassword());
            $this->assertEquals('shop', $config->getMysqlDatabase());

            // Verify email configuration values
            $this->assertEquals('', $config->getMailAddress());
            $this->assertEquals('', $config->getMailUser());

            // Verify shop configuration values
            $this->assertEquals('€', $config->getCurrency());
            $this->assertEquals('de', $config->getLanguage());
            $this->assertTrue($config->getShowInventory());

            // Verify allowed textfields configuration
            $fields = $config->getAllowedTextfields();
            $this->assertIsArray($fields);
            $this->assertArrayHasKey('name', $fields);
            $this->assertArrayHasKey('email', $fields);
            $this->assertArrayHasKey('street', $fields);
            $this->assertArrayHasKey('zipcode_location', $fields);
            $this->assertArrayHasKey('comment', $fields);

            // Verify field requirements
            $this->assertEquals(1, $fields['name']);
            $this->assertEquals(1, $fields['email']);
            $this->assertEquals(1, $fields['street']);
            $this->assertEquals(1, $fields['zipcode_location']);
            $this->assertEquals(0, $fields['comment']);
        } finally {
            // Clean up temporary file
            if (file_exists($tmpConfigPath)) {
                unlink($tmpConfigPath);
            }
        }
    }
}

<?php
/**
 * Configuration class that stores database credentials, language settings, and shop parameters.
 * 
 * This class loads configuration from config.php file if it exists.
 * To create your config.php, copy config.php.skeleton and adjust the values.
 */

class Config implements ConfigInterface
{
    const REQUIRED = 1;
    const OPTIONAL = 0;

    private $config;

    public $allowedTextfields;
    public $mysqlHost;
    public $mysqlUser;
    public $mysqlPassword;
    public $mysqlDatabase;
    public $language;
    public $showInventory;

    public function __construct(string $configFile = __DIR__ . '/../../config/config.php')
    {
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            // Fallback to default values if config.php doesn't exist
            $this->config = [
                'mysql_host' => 'shop_mysql',
                'mysql_user' => 'user',
                'mysql_password' => 'user',
                'mysql_database' => 'shop',
                'mail_address' => '',
                'mail_user' => '',
                'currency' => '€',
                'language' => 'de',
                'show_inventory' => true,
                'allowed_textfields' => [
                    'name' => self::REQUIRED,
                    'email' => self::REQUIRED,
                    'street' => self::REQUIRED,
                    'zipcode_location' => self::REQUIRED,
                    'comment' => self::OPTIONAL
                ]
            ];
        }

        // Set properties from config
        $this->mysqlHost = $this->config['mysql_host'];
        $this->mysqlUser = $this->config['mysql_user'];
        $this->mysqlPassword = $this->config['mysql_password'];
        $this->mysqlDatabase = $this->config['mysql_database'];
        $this->language = $this->config['language'];
        $this->showInventory = $this->config['show_inventory'];
        $this->allowedTextfields = $this->config['allowed_textfields'];
    }

    public function getMysqlHost(): string
    {
        return $this->mysqlHost;
    }

    public function getMysqlUser(): string
    {
        return $this->mysqlUser;
    }

    public function getMysqlPassword(): string
    {
        return $this->mysqlPassword;
    }

    public function getMysqlDatabase(): string
    {
        return $this->mysqlDatabase;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getShowInventory(): bool
    {
        return $this->showInventory;
    }

    public function getAllowedTextfields(): array
    {
        return $this->allowedTextfields;
    }

    public function getCurrency(): string
    {
        return $this->config['currency'];
    }

    public function getMailAddress(): string
    {
        return $this->config['mail_address'];
    }

    public function getMailUser(): string
    {
        return $this->config['mail_user'];
    }
}




<?php

/**
 * Mock configuration for testing
 */
class MockConfig implements ConfigInterface
{
    private $data = [
        'mysqlHost' => 'localhost',
        'mysqlUser' => 'test',
        'mysqlPassword' => 'test',
        'mysqlDatabase' => 'test',
        'language' => 'en',
        'showInventory' => false,
        'allowedTextfields' => ['name' => 1, 'email' => 1],
        'currency' => '€',
        'mailAddress' => 'test@example.com',
        'mailUser' => 'Test User'
    ];

    public function __construct($overrides = [])
    {
        $this->data = array_merge($this->data, $overrides);
    }

    public function getMysqlHost()
    {
        return $this->data['mysqlHost'];
    }

    public function getMysqlUser()
    {
        return $this->data['mysqlUser'];
    }

    public function getMysqlPassword()
    {
        return $this->data['mysqlPassword'];
    }

    public function getMysqlDatabase()
    {
        return $this->data['mysqlDatabase'];
    }

    public function getLanguage()
    {
        return $this->data['language'];
    }

    public function getShowInventory()
    {
        return $this->data['showInventory'];
    }

    public function getAllowedTextfields()
    {
        return $this->data['allowedTextfields'];
    }

    public function getCurrency()
    {
        return $this->data['currency'];
    }

    public function getMailAddress()
    {
        return $this->data['mailAddress'];
    }

    public function getMailUser()
    {
        return $this->data['mailUser'];
    }
}

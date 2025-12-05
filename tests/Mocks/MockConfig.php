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

    public function __construct(array $overrides = [])
    {
        $this->data = array_merge($this->data, $overrides);
    }

    public function getMysqlHost(): string
    {
        return $this->data['mysqlHost'];
    }

    public function getMysqlUser(): string
    {
        return $this->data['mysqlUser'];
    }

    public function getMysqlPassword(): string
    {
        return $this->data['mysqlPassword'];
    }

    public function getMysqlDatabase(): string
    {
        return $this->data['mysqlDatabase'];
    }

    public function getLanguage(): string
    {
        return $this->data['language'];
    }

    public function getShowInventory(): bool
    {
        return $this->data['showInventory'];
    }

    public function getAllowedTextfields(): array
    {
        return $this->data['allowedTextfields'];
    }

    public function getCurrency(): string
    {
        return $this->data['currency'];
    }

    public function getMailAddress(): string
    {
        return $this->data['mailAddress'];
    }

    public function getMailUser(): string
    {
        return $this->data['mailUser'];
    }
}

<?php

class Config implements ConfigInterface
{
    const REQUIRED = 1;
    const OPTIONAL = 0;

    const MAIL_ADDRESS = ''; // your email address
    const MAIL_USER = ''; // name to be shown in email

    const CURRENCY = '€';

    public $allowedTextfields = array(
        'name' => self::REQUIRED,
        'email' => self::REQUIRED,
        'street' => self::REQUIRED,
        'zipcode_location' => self::REQUIRED,
        'comment' => self::OPTIONAL
    );

    public $mysqlHost = 'shop_mysql';
    public $mysqlUser = 'user';
    public $mysqlPassword = 'user';
    public $mysqlDatabase = 'shop';

    public $language = 'de';
    public $showInventory = true;

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
        return self::CURRENCY;
    }

    public function getMailAddress(): string
    {
        return self::MAIL_ADDRESS;
    }

    public function getMailUser(): string
    {
        return self::MAIL_USER;
    }
}




<?php

class Config
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
}




<?php
define('REQUIRED', 1);
define('OPTIONAL', 0);

define('MAIL_ADDRESS', ''); // your email address
define('MAIL_USER', ''); // name to be shown in email
define('MAIL_SUBJECT', ''); // email subject

define('CURRENCY', '€');

$allowed_textfields = array(
    'name' => REQUIRED,
    'email' => REQUIRED,
    'street' => REQUIRED,
    'zipcode_location' => REQUIRED,
    'comment' => OPTIONAL
);

$items = array(
    array(
        'id' => 'itemId', // dom id
        'name' => 'First Item', // title
        'picture' => '', // absolute URL to product image
        'description' => 'My first item', // description
        'min_porto' => 0, // minimum porto
        'bundles' => array(
            array(
                'subid' => 'itemBlue', // dom id
                'name' => 'blue', // subtitle
                'price' => 10.07, // price per
                'min_count' => 0, // min amount to buy
                'max_count' => 1  // max amount to buy
            ),
        ),
    ),
);


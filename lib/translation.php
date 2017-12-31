<?php

class Translation
{
    /**
     * @var array
     */
    private static $translations;

    /**
     * Translation constructor.
     * @param Config $config
     */
    public static function init(Config $config)
    {
        self::$translations = require __DIR__ . '/../translations/' . $config->language . '.php';
    }

    /**
     * @param string $key
     * @return string
     */
    public static function translate($key)
    {
        return isset(self::$translations[$key])
            ? self::$translations[$key]
            : $key;
    }
}

/**
 * @param string $key
 * @return string
 */
function t($key)
{
   return Translation::translate($key);
}
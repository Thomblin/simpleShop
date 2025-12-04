<?php

class Translation implements TranslationInterface
{
    /**
     * @var array
     */
    private $translations;

    /**
     * @var Translation Singleton instance for backward compatibility
     */
    private static $instance;

    /**
     * Translation constructor.
     *
     * @param TranslationLoader $loader
     * @param string $language
     */
    public function __construct(TranslationLoader $loader, $language)
    {
        $this->translations = $loader->load($language);
    }

    /**
     * Create from config (backward compatibility helper)
     *
     * @param ConfigInterface $config
     * @return Translation
     */
    public static function createFromConfig(ConfigInterface $config)
    {
        $loader = new TranslationLoader();
        return new self($loader, $config->getLanguage());
    }

    /**
     * Translate a key to the target language
     *
     * @param string $key
     * @return string
     */
    public function translate($key)
    {
        return isset($this->translations[$key])
            ? $this->translations[$key]
            : $key;
    }

    /**
     * Initialize the singleton instance (for backward compatibility)
     *
     * @param ConfigInterface $config
     * @deprecated Use constructor with dependency injection instead
     */
    public static function init(ConfigInterface $config)
    {
        self::$instance = self::createFromConfig($config);
    }

    /**
     * Get the singleton instance
     *
     * @return Translation
     * @throws RuntimeException if not initialized
     */
    private static function getInstance()
    {
        if (self::$instance === null) {
            throw new RuntimeException('Translation not initialized. Call Translation::init() first.');
        }
        return self::$instance;
    }

    /**
     * Static translate method for backward compatibility
     *
     * @param string $key
     * @return string
     * @deprecated Use instance method instead
     */
    public static function translateStatic($key)
    {
        return self::getInstance()->translate($key);
    }
}

/**
 * Global translation helper function
 *
 * @param string $key
 * @return string
 */
if (!function_exists('t')) {
    function t($key)
    {
        return Translation::translateStatic($key);
    }
}
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
    public function __construct(TranslationLoader $loader, string $language)
    {
        $this->translations = $loader->load($language);
    }

    /**
     * Create from config (backward compatibility helper)
     *
     * @param ConfigInterface $config
     * @return Translation
     */
    public static function createFromConfig(ConfigInterface $config): self
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
    public function translate(string $key): string
    {
        return isset($this->translations[$key])
            ? $this->translations[$key]
            : $key;
    }

    /**
     * Set the singleton instance (for global t() function)
     *
     * @param Translation $instance
     * @return void
     */
    public static function setInstance(Translation $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Get the singleton instance (public for t() function)
     *
     * @return Translation
     * @throws RuntimeException if not initialized
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Translation not initialized. Call Translation::setInstance() first.');
        }
        return self::$instance;
    }
}

/**
 * Global translation helper function
 *
 * @param string $key
 * @return string
 */
if (!function_exists('t')) {
    function t(string $key): string
    {
        return Translation::getInstance()->translate($key);
    }
}
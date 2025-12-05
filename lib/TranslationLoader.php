<?php

class TranslationLoader
{
    /**
     * @var string
     */
    private $translationsPath;

    /**
     * @param string|null $translationsPath Path to translations directory
     */
    public function __construct(?string $translationsPath = null)
    {
        $this->translationsPath = $translationsPath ?: __DIR__ . '/../translations/';
    }

    /**
     * Load translations for a given language
     *
     * @param string $language Language code (e.g., 'de', 'en')
     * @return array Translation key-value pairs
     * @throws RuntimeException If translation file doesn't exist
     */
    public function load(string $language): array
    {
        $filePath = $this->translationsPath . $language . '.php';

        if (!file_exists($filePath)) {
            throw new RuntimeException("Translation file not found: " . $filePath);
        }

        $translations = require $filePath;

        if (!is_array($translations)) {
            throw new RuntimeException("Translation file must return an array: " . $filePath);
        }

        return $translations;
    }
}

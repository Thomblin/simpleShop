<?php

/**
 * Mock translation loader for testing
 */
class MockTranslationLoader extends TranslationLoader
{
    private $translations = [];

    public function __construct(array $translations = [])
    {
        $this->translations = $translations;
    }

    public function load(string $language): array
    {
        return $this->translations;
    }

    public function setTranslations(array $translations): void
    {
        $this->translations = $translations;
    }
}

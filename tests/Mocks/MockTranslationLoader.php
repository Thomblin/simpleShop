<?php

/**
 * Mock translation loader for testing
 */
class MockTranslationLoader extends TranslationLoader
{
    private $translations = [];

    public function __construct($translations = [])
    {
        $this->translations = $translations;
    }

    public function load($language)
    {
        return $this->translations;
    }

    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }
}

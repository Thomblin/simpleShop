<?php
/**
 * Interface for translating text keys to localized strings.
 */

interface TranslationInterface
{
    /**
     * Translate a key to the target language
     *
     * @param string $key Translation key
     * @return string Translated string or key if translation not found
     */
    public function translate(string $key): string;
}

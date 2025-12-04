<?php

interface TranslationInterface
{
    /**
     * Translate a key to the target language
     *
     * @param string $key Translation key
     * @return string Translated string or key if translation not found
     */
    public function translate($key);
}

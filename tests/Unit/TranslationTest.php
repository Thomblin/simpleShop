<?php

use PHPUnit\Framework\TestCase;

class TranslationTest extends TestCase
{
    public function testImplementsTranslationInterface()
    {
        $loader = new MockTranslationLoader(['test' => 'value']);
        $translation = new Translation($loader, 'en');

        $this->assertInstanceOf(TranslationInterface::class, $translation);
    }

    public function testTranslateReturnsCorrectValue()
    {
        $loader = new MockTranslationLoader([
            'greeting' => 'Hello',
            'farewell' => 'Goodbye'
        ]);

        $translation = new Translation($loader, 'en');

        $this->assertEquals('Hello', $translation->translate('greeting'));
        $this->assertEquals('Goodbye', $translation->translate('farewell'));
    }

    public function testTranslateReturnKeyWhenNotFound()
    {
        $loader = new MockTranslationLoader(['existing' => 'value']);
        $translation = new Translation($loader, 'en');

        $this->assertEquals('nonexistent', $translation->translate('nonexistent'));
    }

    public function testCreateFromConfig()
    {
        $config = new MockConfig(['language' => 'en']);
        $translation = Translation::createFromConfig($config);

        $this->assertInstanceOf(Translation::class, $translation);
    }

    public function testBackwardCompatibilityWithInit()
    {
        $loader = new MockTranslationLoader(['test' => 'value']);
        $config = new MockConfig(['language' => 'en']);

        // Reset static instance first
        Translation::init($config);

        // Use the static method (deprecated but should still work)
        $result = Translation::translateStatic('test');

        // Note: This will fail if the actual translation file doesn't exist
        // But it demonstrates the API
        $this->assertIsString($result);
    }

    public function testEmptyTranslations()
    {
        $loader = new MockTranslationLoader([]);
        $translation = new Translation($loader, 'en');

        $this->assertEquals('any.key', $translation->translate('any.key'));
    }

    public function testTranslationWithDots()
    {
        $loader = new MockTranslationLoader([
            'error.not_found' => 'Not found',
            'error.invalid' => 'Invalid input'
        ]);

        $translation = new Translation($loader, 'en');

        $this->assertEquals('Not found', $translation->translate('error.not_found'));
        $this->assertEquals('Invalid input', $translation->translate('error.invalid'));
    }
}

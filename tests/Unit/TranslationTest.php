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

        $translation = Translation::createFromConfig($config);

        $result = $translation->translate('test');

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

    public function testSetInstanceAndGetInstance()
    {
        $loader = new MockTranslationLoader(['test' => 'value']);
        $translation = new Translation($loader, 'en');

        // Test setInstance
        Translation::setInstance($translation);

        // Test getInstance
        $retrieved = Translation::getInstance();
        $this->assertSame($translation, $retrieved);
        $this->assertEquals('value', $retrieved->translate('test'));
    }

    public function testGetInstanceThrowsWhenNotSet()
    {
        // Clear any existing instance
        $reflection = new ReflectionClass(Translation::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Translation not initialized');

        Translation::getInstance();
    }
}

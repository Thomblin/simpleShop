<?php

use PHPUnit\Framework\TestCase;

class TranslationLoaderTest extends TestCase
{
    private $tempDir;
    private $loader;

    protected function setUp(): void
    {
        // Create a temporary directory for test translations
        $this->tempDir = sys_get_temp_dir() . '/test_translations_' . uniqid() . '/';
        mkdir($this->tempDir, 0777, true);
        
        $this->loader = new TranslationLoader($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testLoadReturnsTranslationArray()
    {
        // Create a test translation file
        $content = "<?php return ['hello' => 'Hello', 'world' => 'World'];";
        file_put_contents($this->tempDir . 'en.php', $content);
        
        $result = $this->loader->load('en');
        
        $this->assertIsArray($result);
        $this->assertEquals('Hello', $result['hello']);
        $this->assertEquals('World', $result['world']);
    }

    public function testLoadThrowsExceptionForNonexistentFile()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Translation file not found');
        
        $this->loader->load('nonexistent');
    }

    public function testLoadThrowsExceptionForNonArrayReturn()
    {
        // Create a file that doesn't return an array
        $content = "<?php return 'not an array';";
        file_put_contents($this->tempDir . 'invalid.php', $content);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Translation file must return an array');
        
        $this->loader->load('invalid');
    }

    public function testConstructorWithDefaultPath()
    {
        $loader = new TranslationLoader();
        // Should use default path (translations directory)
        $this->assertInstanceOf(TranslationLoader::class, $loader);
    }

    public function testConstructorWithCustomPath()
    {
        $loader = new TranslationLoader($this->tempDir);
        $this->assertInstanceOf(TranslationLoader::class, $loader);
        
        // Verify it uses the custom path
        $content = "<?php return ['test' => 'value'];";
        file_put_contents($this->tempDir . 'test.php', $content);
        
        $result = $loader->load('test');
        $this->assertEquals('value', $result['test']);
    }

    public function testLoadWithComplexTranslationArray()
    {
        $content = "<?php return [
            'simple' => 'Simple',
            'nested' => [
                'key1' => 'Value 1',
                'key2' => 'Value 2'
            ],
            'numbers' => [1, 2, 3]
        ];";
        file_put_contents($this->tempDir . 'complex.php', $content);
        
        $result = $this->loader->load('complex');
        
        $this->assertIsArray($result);
        $this->assertEquals('Simple', $result['simple']);
        $this->assertIsArray($result['nested']);
        $this->assertEquals('Value 1', $result['nested']['key1']);
    }

    public function testLoadWithEmptyArray()
    {
        $content = "<?php return [];";
        file_put_contents($this->tempDir . 'empty.php', $content);
        
        $result = $this->loader->load('empty');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}


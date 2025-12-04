<?php

use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    private $templatePath;

    protected function setUp(): void
    {
        $this->templatePath = __DIR__ . '/../fixtures/';
    }

    public function testImplementsTemplateInterface()
    {
        $template = new Template();
        $this->assertInstanceOf(TemplateInterface::class, $template);
    }

    public function testAddSetsData()
    {
        $template = new Template();
        $template->add('key', 'value');

        $data = $template->getData();
        $this->assertEquals('value', $data['key']);
    }

    public function testAddMultipleValues()
    {
        $template = new Template();
        $template->add('name', 'John');
        $template->add('age', 30);

        $data = $template->getData();
        $this->assertEquals('John', $data['name']);
        $this->assertEquals(30, $data['age']);
    }

    public function testParseRendersTemplate()
    {
        $template = new Template($this->templatePath);
        $template->add('name', 'John');
        $template->add('total', '100.00 €');

        $result = $template->parse('test_template.php', false);

        $this->assertStringContainsString('Hello John!', $result);
        $this->assertStringContainsString('100.00 €', $result);
    }

    public function testParseWithPrintReturnsEmpty()
    {
        $template = new Template($this->templatePath);
        $template->add('name', 'John');
        $template->add('total', '100.00 €');

        ob_start();
        $result = $template->parse('test_template.php', true);
        $output = ob_get_clean();

        $this->assertEquals('', $result);
        $this->assertStringContainsString('Hello John!', $output);
    }

    public function testValidateFilePathThrowsExceptionForNonexistent()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Template file not found');

        $template = new Template($this->templatePath);
        $template->parse('nonexistent.php', false);
    }

    public function testValidateFilePathPreventsDirectoryTraversal()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Template file outside allowed directory');

        $template = new Template($this->templatePath);
        // Try to access a file outside the base path
        $template->parse('../../../etc/passwd', false);
    }

    public function testConstructorWithCustomBasePath()
    {
        $template = new Template($this->templatePath);
        $template->add('name', 'Test');
        $template->add('total', '50.00 €');

        $result = $template->parse('test_template.php', false);
        $this->assertStringContainsString('Hello Test!', $result);
    }

    public function testGetDataReturnsAllData()
    {
        $template = new Template();
        $template->add('key1', 'value1');
        $template->add('key2', 'value2');

        $data = $template->getData();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertEquals('value1', $data['key1']);
        $this->assertEquals('value2', $data['key2']);
    }

    public function testParseExtractsVariables()
    {
        $template = new Template($this->templatePath);
        $template->add('name', 'Alice');
        $template->add('total', '200.00 €');

        $result = $template->parse('test_template.php', false);

        // Verify variables were extracted and used
        $this->assertStringNotContainsString('<?php', $result);
        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('200.00', $result);
    }
}

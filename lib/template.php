<?php

class Template implements TemplateInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $basePath;

    /**
     * Template constructor.
     *
     * @param string $basePath Base path for template files (defaults to project root)
     */
    public function __construct($basePath = null)
    {
        $this->basePath = $basePath ?: __DIR__ . '/../';
    }

    /**
     * Add a variable to the template data
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function add($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Validate template file path for security
     *
     * @param string $file
     * @return string Validated absolute path
     * @throws InvalidArgumentException if file is invalid
     */
    private function validateFilePath($file)
    {
        // Convert to absolute path if relative
        if (!is_file($file)) {
            $file = $this->basePath . $file;
        }

        // Check if file exists
        if (!file_exists($file)) {
            throw new InvalidArgumentException("Template file not found: " . $file);
        }

        // Get real path to prevent directory traversal
        $realPath = realpath($file);
        $baseRealPath = realpath($this->basePath);

        // Ensure the file is within the base path (prevent directory traversal)
        if (strpos($realPath, $baseRealPath) !== 0) {
            throw new InvalidArgumentException("Template file outside allowed directory: " . $file);
        }

        return $realPath;
    }

    /**
     * Parse and render a template file
     *
     * NOTE: This method uses extract() for backward compatibility with existing templates.
     * For new templates, consider passing $this->data directly to avoid security risks.
     *
     * @param string $file Path to template file
     * @param bool $print Whether to print directly or return as string
     * @return string Rendered template (empty if $print is true)
     * @throws InvalidArgumentException if template file is invalid
     */
    public function parse($file, $print = true)
    {
        $result = '';

        // Validate file path
        $validatedFile = $this->validateFilePath($file);

        // WARNING: extract() is used for backward compatibility but poses security risks
        // New code should avoid this pattern
        extract($this->data);

        if (!$print) {
            ob_start();
        }

        include($validatedFile);

        if (!$print) {
            $result = ob_get_clean();
        }

        return $result;
    }

    /**
     * Get all template data (useful for testing)
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}

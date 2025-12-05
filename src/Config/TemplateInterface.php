<?php
/**
 * Interface for template rendering with variable assignment and file parsing.
 */

interface TemplateInterface
{
    /**
     * Add a variable to the template data
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function add(string $key, mixed $value): void;

    /**
     * Parse and render a template file
     *
     * @param string $file Path to template file
     * @param bool $print Whether to print directly or return as string
     * @return string Rendered template (empty if $print is true)
     */
    public function parse(string $file, bool $print = true): string;
}

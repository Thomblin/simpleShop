<?php

/**
 * PHPUnit test bootstrap file
 */

// Include the autoloader
require_once __DIR__ . '/../autoload.php';

// Auto-load test mocks
spl_autoload_register(function ($className) {
    $mockFile = __DIR__ . '/Mocks/' . $className . '.php';
    if (file_exists($mockFile)) {
        include $mockFile;
    }
});

// Define global t() function for tests if it doesn't exist
// This prevents redeclaration errors when translation.php is loaded
if (!function_exists('t')) {
    function t($key) {
        return $key;
    }
}

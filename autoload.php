<?php
/**
 * Autoloader that automatically loads class files from lib, models, services, and controllers directories.
 */

spl_autoload_register(function (string $className): void {
    // List of directories to search for class files
    $directories = [
        __DIR__ . '/lib/',
        __DIR__ . '/models/',
        __DIR__ . '/services/',
        __DIR__ . '/controllers/'
    ];

    // Filename matches class name (PascalCase)
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            include $file;
            return;
        }
    }
});
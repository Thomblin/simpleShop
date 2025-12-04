<?php

spl_autoload_register(function ($className) {
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
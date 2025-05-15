<?php

spl_autoload_register(function ($class) {
    // Namespace prefix
    $prefix = 'BemaGoalForge\\';

    // Base directory for the namespace
    $base_dir = GOALFORGE_PATH . 'includes/';

    // Check if the class uses the namespace prefix
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, strlen($prefix));

    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/**
 * PHPCap autoloader.
 * 
 * Modified version of: http://www.php-fig.org/psr/psr-4/examples/
 */

spl_autoload_register(function ($class) {

    // PHPCap namespace prefix
    $prefix = 'IU\\PHPCap\\';

    // Base directory for the namespace prefix
    $baseDirectory = __DIR__ . '/src/';

    // If the class uses the namespace prefix, then try to load the class
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {

        // get the relative class name
        $relativeClass = substr($class, $len);

        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $baseDirectory . str_replace('\\', '/', $relativeClass) . '.php';

        // if the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
});

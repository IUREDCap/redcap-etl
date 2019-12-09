#!/usr/bin/env php
<?php

/**
 * Generates transformation rules for the data source
 * specified in a properties file.
 */

require(__DIR__ . '/../dependencies/autoload.php');

use IU\PHPCap\RedCapProject;
use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\Logger;

$usage = "Usage: php {$argv[0]} [--complete-fields] <configuration-file>".PHP_EOL;
$addFormCompleteField = false;

if (count($argv) == 2) {
    $configurationFile = $argv[1];
} elseif (count($argv) == 3) {
    $option = $argv[1];
    $configurationFile = $argv[2];
    if ($option == '--complete-fields') {
        $addFormCompleteField = true;
    } else {
        print $usage;
        exit(1);
    }
} else {
    print $usage;
    exit(1);
}

try {
    $logger = new Logger($argv[0]);
    $redCapEtl = new RedCapEtl($logger, $configurationFile);
    $rules = $redCapEtl->autoGenerateRules($addFormCompleteField);
    print $rules;
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

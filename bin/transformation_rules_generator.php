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

if (count($argv) != 2) {
    print "Usage: php $argv[0] <configuration-file>\n";
    exit(1);
} else {
    $configurationFile = $argv[1];
}

try {
    $logger = new Logger($argv[0]);
    $redCapEtl = new RedCapEtl($logger, $configurationFile);
    $rules = $redCapEtl->autoGenerateRules();
    print $rules;
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

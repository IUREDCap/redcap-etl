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

$usage = "Usage: php {$argv[0]} [--complete-fields] [--dag] <configuration-file>".PHP_EOL;

$addDagField = false;
$addFormCompleteField = false;

$options  = "";
$longopts = ['complete-fields', 'dag'];
$optind = 0;

#--------------------------------------------------
# Process the command link options
#--------------------------------------------------
$opts = getopt($options, $longopts, $optind);

foreach ($opts as $opt => $value) {
    if ($opt === 'complete-fields') {
        $addFormCompleteField = true;
    } elseif ($opt === 'dag') {
        $addDagField = true;
    } else {
        print $usage;
        exit(1);
    }
}

if ($optind === count($argv) - 1) {
    $configurationFile = $argv[$optind];
} else {
    print $usage;
    exit(1);
}

#-----------------------------------------------------
# Run REDCap-ETL on the specified configuration file
#-----------------------------------------------------
try {
    $logger = new Logger($argv[0]);
    $redCapEtl = new RedCapEtl($logger, $configurationFile);
    $rules = $redCapEtl->autoGenerateRules($addFormCompleteField, $addDagField);
    print $rules;
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

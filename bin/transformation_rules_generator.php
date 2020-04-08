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

$usage = "Usage: php ".basename(__FILE__)." [OPTIONS] <configuration-file>".PHP_EOL
    .PHP_EOL
    ."    -c, --complete-fields   include form complete fields".PHP_EOL
    ."    -d, --dag-fields        include DAG (Data Access Group) fields".PHP_EOL
    ."    -f, --file-fields       include file fields".PHP_EOL
    .PHP_EOL
    ;

$addDagFields = false;
$addCompleteFields = false;
$addFileFields = false;

$options  = 'cdf';
$longopts = ['complete-fields', 'dag-fields', 'file-fields'];
$optind = 0;

#--------------------------------------------------
# Process the command link options
#--------------------------------------------------
$opts = getopt($options, $longopts, $optind);

foreach ($opts as $opt => $value) {
    if ($opt === 'c' || $opt === 'complete-fields') {
        $addCompleteFields = true;
    } elseif ($opt === 'd' || $opt === 'dag-fields') {
        $addDagFields = true;
    } elseif ($opt === 'f' || $opt === 'file-fields') {
        $addFileFields = true;
    } else {
        print $usage;
        exit(1);
    }
}

if ($optind === count($argv) - 1) {
    $configurationFile = $argv[$optind];

    # Check for invalid options (since getopt doesn't handle this!)
    for ($i = 1; $i < $optind; $i++) {
        $opt = $argv[$i];

        $matches = array();
        if (preg_match('/^--(.*)/', $opt, $matches) === 1) {
            # long option
            $longOption = $matches[1];
            if (!in_array($longOption, $longopts)) {
                print $usage;
                exit(1);
            }
        } elseif (preg_match('/^-(.*)/', $opt, $matches) === 1) {
            # short options
            $shortOptions = $matches[1];
            for ($j = 0; $j < strlen($shortOptions); $j++) {
                if (strpos($options, $shortOptions[$j]) === false) {
                    print $usage;
                    exit(1);
                }
            }
        }
    }
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
    $rules = $redCapEtl->autoGenerateRules($addCompleteFields, $addDagFields, $addFileFields);
    print $rules;
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

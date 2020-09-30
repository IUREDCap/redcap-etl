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

$usage = "Usage: php ".basename(__FILE__)." [OPTIONS]".PHP_EOL
    .PHP_EOL
    ."    -g, --config-file         full configuration file path/name (required)".PHP_EOL
    ."    -c, --complete-fields     include form complete fields".PHP_EOL
    ."    -d, --dag-fields          include DAG (Data Access Group) fields".PHP_EOL
    ."    -f, --file-fields         include file fields".PHP_EOL
    ."    -n, --notes-fields        remove notes fields".PHP_EOL
    ."    -i, --identifier-fields   remove identifier fields".PHP_EOL
    ."    -r, --nonrepeating-fields combine non-repeating fields".PHP_EOL
    ."    -t, --table-nonrepeating  table name (required when combining "
    ."non-repeating fields)".PHP_EOL
    .PHP_EOL
    ;

$configurationFile = null;
$addDagFields = false;
$addCompleteFields = false;
$addFileFields = false;
$removeNotesFields = false;
$removeIdentifierFields = false;
$combineNonRepeatingFields = false;
$nonRepeatingFieldsTable = '';

$options  = 'g:cdfnirt:';
$longopts = ['config-file:', 'complete-fields', 'dag-fields', 'file-fields',
             'notes-fields', 'identifier-fields', 'nonrepeating-fields',
             'table-nonrepeating:'
            ];
$optind = 0;
$optsWithTextArguments = array('-g','-t','--config-file','--table-nonrepeating');

#--------------------------------------------------
# Process the command link options
#--------------------------------------------------
$opts = getopt($options, $longopts, $optind);

foreach ($opts as $opt => $value) {
    if ($opt === 'g') {
        $configurationFile = $opts['g'];
    } elseif ($opt === 'config-file') {
        $configurationFile = $opts['config-file'];
    } elseif ($opt === 'c' || $opt === 'complete-fields') {
        $addCompleteFields = true;
        print "\n\naddCompleteFields is $addCompleteFields\n\n";
    } elseif ($opt === 'd' || $opt === 'dag-fields') {
        $addDagFields = true;
    } elseif ($opt === 'f' || $opt === 'file-fields') {
        $addFileFields = true;
    } elseif ($opt === 'n' || $opt === 'notes-fields') {
        $removeNotesFields = true;
    } elseif ($opt === 'i' || $opt === 'identifier-fields') {
        $removeIdentifierFields = true;
    } elseif ($opt === 'r' || $opt === 'nonrepeating-fields') {
        $combineNonRepeatingFields = true;
    } elseif ($opt === 't') {
        $nonRepeatingFieldsTable = $opts['t'];
    } elseif ($opt === 'table-nonrepeating') {
        $nonRepeatingFieldsTable = $opts['table-nonrepeating'];
    } else {
        print $usage;
        exit(1);
    }
}

# Stop if there is no configuration file
if (empty($configurationFile) || ($configurationFile[0] === '-')) {
    print $usage;
    exit(1);
}

# Stop if there is supposed to be a file/table name for combining non-repeating
# fields or if the a flag accidental got sucked in as the file/table name
if ($combineNonRepeatingFields) {
    if (empty($nonRepeatingFieldsTable) || ($nonRepeatingFieldsTable[0] === '-')) {
        print $usage;
        exit(1);
    }

# check to see if the user left the table name off, but added a flag instead
} elseif (!empty($nonRepeatingFieldsTable) && $nonRepeatingFieldsTable[0] === '-') {
    print $usage;
    exit(1);
} else {
    $nonRepeatingFieldsTable = '';
}
    
# Check for invalid options (since getopt doesn't handle this!)
$i = 1;
while ($i < $optind) {
    $opt = $argv[$i];
    $matches = array();
    if (preg_match('/^--(.*)/', $opt, $matches) === 1) {
        # long option
        $longOption = $matches[1];
        if (!in_array($longOption, str_replace(":", "", $longopts))) {
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

    $i++;

    # if the next option is a text argument such as a file name, skip checking it
    if (in_array($opt, $optsWithTextArguments)) {
        $i++;
    }
}


#-----------------------------------------------------
# Run REDCap-ETL on the specified configuration file
#-----------------------------------------------------
try {
    $logger = new Logger($argv[0]);
    $redCapEtl = new RedCapEtl($logger, $configurationFile);
    $rules = $redCapEtl->autoGenerateRules(
        $addCompleteFields,
        $addDagFields,
        $addFileFields,
        $removeNotesFields,
        $removeIdentifierFields,
        $combineNonRepeatingFields,
        $nonRepeatingFieldsTable
    );
    print $rules;
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

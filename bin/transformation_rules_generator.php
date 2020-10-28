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
    ."    -c, --complete-fields                    include form complete fields".PHP_EOL
    ."    -d, --dag-fields                         include DAG (Data Access Group) fields".PHP_EOL
    ."    -f, --file-fields                        include file fields".PHP_EOL
    ."    -s, --survey-fields                      include survey fields".PHP_EOL
    ."    -n, --notes-fields                       remove notes fields".PHP_EOL
    ."    -i, --identifier-fields                  remove identifier fields".PHP_EOL
    ."    -t, --table-nonrepeating <table-name>    combine non-repeating fields into table 'table-name'".PHP_EOL
    .PHP_EOL
    ;

$configurationFile = null;
$addDagFields = false;
$addCompleteFields = false;
$addFileFields = false;
$addSurveyFields = false;
$removeNotesFields = false;
$removeIdentifierFields = false;
$combineNonRepeatingFields = false;
$nonRepeatingFieldsTable = '';

$options  = 'cdfsnit:';
$longopts = ['complete-fields', 'dag-fields', 'file-fields', 'survey-fields',
             'notes-fields', 'identifier-fields',
             'table-nonrepeating:'
            ];
$optind = 0;
$optsWithTextArguments = array('-t','--table-nonrepeating');

#------------------------------------------------------------------------------
# Check that a configuration file was specified, and, if so, remove it from
# the command line arguments before the call to get the command line options.
#------------------------------------------------------------------------------
if (count($argv) <= 1) {
    print "No configuration file specified".PHP_EOL.PHP_EOL;
    print $usage;
    exit(1);
} else {
    $configurationFile = array_pop($argv);
    if ($configurationFile[0] === '-') {
        print "No configuration file specified".PHP_EOL.PHP_EOL;
        print $usage;
        exit(1);
    }
}

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
    } elseif ($opt === 'd' || $opt === 'dag-fields') {
        $addDagFields = true;
    } elseif ($opt === 'f' || $opt === 'file-fields') {
        $addFileFields = true;
    } elseif ($opt === 's' || $opt === 'survey-fields') {
        $addSurveyFields = true;
    } elseif ($opt === 'n' || $opt === 'notes-fields') {
        $removeNotesFields = true;
    } elseif ($opt === 'i' || $opt === 'identifier-fields') {
        $removeIdentifierFields = true;
    } elseif ($opt === 't') {
        $nonRepeatingFieldsTable = $opts['t'];
        $combineNonRepeatingFields = true;
    } elseif ($opt === 'table-nonrepeating') {
        $nonRepeatingFieldsTable = $opts['table-nonrepeating'];
        $combineNonRepeatingFields = true;
    } else {
        print 'Unrecognized option: "{$opt}"'.PHP_EOL.PHP_EOL;
        print $usage;
        exit(1);
    }
}

if ($combineNonRepeatingFields) {
    # if the table name was not included and another option was placed after it,
    # the table name will be set to the following option
    if (empty($nonRepeatingFieldsTable) || $nonRepeatingFieldsTable[0] === '-'
        || $nonRepeatingFieldsTable === $configurationFile) {
        print "No non-repeating fields table name was specified".PHP_EOL.PHP_EOL;
        print $usage;
        exit(1);
    }
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
            print 'Unrecogimized option "{$longOption}"'.PHP_EOL.PHP_EOL;
            print $usage;
            exit(1);
        }
    } elseif (preg_match('/^-(.*)/', $opt, $matches) === 1) {
        # short options
        $shortOptions = $matches[1];
        for ($j = 0; $j < strlen($shortOptions); $j++) {
            if (strpos($options, $shortOptions[$j]) === false) {
                print 'Unrecogimized option "'.$shortOptions[$j].'"'.PHP_EOL.PHP_EOL;
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

    $configuration = $redCapEtl->getConfiguration(0);
    $addCompleteFields         = $addCompleteFields         || $configuration->getAutogenIncludeCompleteFields();
    $addDagFields              = $addDagFields              || $configuration->getAutogenIncludeDagFields();
    $addFileFields             = $addFileFields             || $configuration->getAutogenIncludeFileFields();
    $removeNotesFields         = $removeNotesFields         || $configuration->getAutogenRemoveNotesFields();
    $removeIdentifierFields    = $removeIdentifierFields    || $configuration->getAutogenRemoveIdentifierFields();
    $combineNonRepeatingFields = $combineNonRepeatingFields || $configuration->getAutogenCombineNonRepeatingFields();

    if ($combineNonRepeatingFields) {
        if (empty($nonRepeatingFieldsTable)) {
            $nonRepeatingFieldsTable = $configuration->getAutogenNonRepeatingFieldsTable();
        }
    } else {
        $nonRepeatingFieldsTable = '';
    }

    $task = $redCapEtl->getEtlTask(0);

    $rules = $task->autoGenerateRules(
        $addCompleteFields,
        $addDagFields,
        $addFileFields,
        $addSurveyFields,
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

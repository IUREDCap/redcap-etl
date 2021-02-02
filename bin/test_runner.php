#!/usr/bin/env php
<?php

#---------------------------------------------------------------------------------
# Test Runner
#
# Runs the specified tests and outputs the results to a CSV file.
#
# Options:
#    -c <configuration-file>  (can specify this option multiple times)
#    -b <batch-size>          (can specify this option multiple times)
#    -n <number-of-runs>      (the number of runs for each config file/batch size, defaults to 1)
#    -o <output-file>         (optional, defaults to test.csv)
#
# Example:
#     php test_runner.php -c ../config/test.ini -b 10 -b 100 -n 3 -o test.csv
#---------------------------------------------------------------------------------

require(__DIR__.'/../dependencies/autoload.php');

$testEtl = __DIR__.'/test_etl.php';

#--------------------------
# Set default values
#--------------------------
$outputCsvFile = 'test.csv';
$numberOfRuns  = 1;


#----------------------------------------------
# Process command line arguments
#----------------------------------------------
$options = getopt('c:b:n:o:');
if (array_key_exists('c', $options) && array_key_exists('b', $options)) {
    # configuration file
    $configFiles = $options['c'];
    if (!is_array($configFiles)) {
        # if there is only a single config file value,
        # put it in an array to make the structure
        # of config data consistent
        $configFiles = array($configFiles);
    }

    # batch sizes
    $batchSizes = $options['b'];
    if (!is_array($batchSizes)) {
        $batchSizes = array($batchSizes);
    }
} else {
    print "Usage: test_runner.php -c <config-file> -b <batch-size> -n <number-of-runs> -o <output-file>\n";
    exit(1);
}

if (array_key_exists('o', $options)) {
    $outputCsvFile = $options['o'];
}

if (array_key_exists('n', $options)) {
    $numberOfRuns = $options['n'];
}

#------------------------------------------------------------------------
# Run tests
#------------------------------------------------------------------------
$results = array();

foreach ($configFiles as $configFile) {
    foreach ($batchSizes as $batchSize) {
        for ($runNumber = 1; $runNumber <= $numberOfRuns; $runNumber++) {
            print "PROCESSING CONFIG FILE: {$configFile} WITH BATCH SIZE {$batchSize} - RUN NUMBER {$runNumber}\n";
            $result = system("{$testEtl} -c {$configFile} -b {$batchSize}");
            print "RESULT: {$result}\n";
            $result = explode(',', $result);

            # Add the run number to the results returned
            array_splice($result, 4, 0, $runNumber);

            array_push($results, $result);
        }
        array_push($results, array('')); # Add blank line between groups of runs for same config file and batch size
    }
}

#------------------------------------------------------------
# Output test results to a CSV file
#------------------------------------------------------------
$header = [
    'start time', 'config file', 'record id count', 'batch size', 'run number',
    'REDCap version', 'REDCap-ETL version',
    'peak memory used (bytes)', 'total time (seconds)',
    'pre-processing time', 'extract time', 'transform time', 'load time', 'post-processing time',
    'overhead time'
];

$fh = fopen($outputCsvFile, 'w');
fputcsv($fh, $header);
foreach ($results as $result) {
    fputcsv($fh, $result);
}
fclose($fh);

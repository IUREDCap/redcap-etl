#!/usr/bin/env php
<?php

#-------------------------------------------------------------
# This is the batch script for REDCap-ETL: Extracting data
# from REDCap, transforming REDCap Records into Tables/Rows,
# and loading the data into a data store.
# This is the script that would typically be used for cron
# jobs to run ETL automatically at scheduled times.
#
# Options:
#    -c <configuration-file> [-b <batch-size>]
#
#-------------------------------------------------------------

$startTime = microtime(true);

require(__DIR__.'/../dependencies/autoload.php');

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Version;
use IU\REDCapETL\Logger;

$app = basename(__FILE__, '.php');
$logger = new Logger($app);

#---------------------------
# Set default values
#---------------------------
$configFile = null;
$batchSize  = null;

#----------------------------------------------
# Process the command line options
#----------------------------------------------
$options = getopt('c:b:');

if (array_key_exists('c', $options)) {
    # configuration file
    $configFile = $options['c'];
} else {
    print "Usage: test.php -c <configuration_file> [-b <batch-size>]\n";
    exit(1);
}

if (array_key_exists('b', $options)) {
    $batchSize = $options['b'];
}


try {
    $redCapEtl = new RedCapEtl($logger, $configFile);
    if (isset($batchSize)) {
        $redCapEtl->getConfiguration()->setBatchSize($batchSize);
    }

    $count = $redCapEtl->run();
} catch (EtlException $exception) {
    $logger->logException($exception);
    $logger->log('Processing failed.');
}

$endTime = microtime(true);

$batchSize = $redCapEtl->getConfiguration()->getBatchSize();
$time = $endTime - $startTime;
$memoryUsed = memory_get_peak_usage();
print "{$configFile},{$count},{$batchSize},{$memoryUsed},{$time}\n";

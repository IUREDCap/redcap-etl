#!/usr/bin/env php
<?php

#-------------------------------------------------------------
# This is the batch script for REDCap ETL: Extracting data
# from REDCap, transforming REDCap Records into Tables/Rows,
# and loading the data into a data store.
# This is the script that would typically be used for cron
# jobs to run ETL automatically at scheduled times.
#
# Options:
#    -c <configuration-file>
#
#-------------------------------------------------------------

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

#----------------------------------------------
# Process the command line options
#----------------------------------------------
$options = getopt('c:');

# properties file
if (array_key_exists('c', $options)) {
    $configFile = $options['c'];
} else {
    print "Usage: redap_etl.php -c <configuration_file>\n";
    exit(1);
}

try {
    $redCapEtl = new RedCapEtl($logger, $configFile);
    $logger = $redCapEtl->getLogger();
    $redCapEtl->run();
} catch (EtlException $exception) {
    $logger->logException($exception);
    $logger->logError('Processing failed.');
}

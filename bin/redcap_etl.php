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
#    -p <properties-file>
#
#-------------------------------------------------------------

require(__DIR__.'/../dependencies/autoload.php');

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Version;
use IU\REDCapETL\Logger2;

$app = basename(__FILE__, '.php');
$logger = new Logger2($app);

#---------------------------
# Set default values
#---------------------------
$propertiesFile = null;

#----------------------------------------------
# Process the command line options
#----------------------------------------------
$options = getopt('p:');

# properties file
if (array_key_exists('p', $options)) {
    $propertiesFile = $options['p'];
}

try {
    $redCapEtl = new RedCapEtl($logger, $propertiesFile);
    $logger = $redCapEtl->getLogger();
    $redCapEtl->run();
} catch (EtlException $exception) {
    $logger->logException($exception);
    $logger->logError('Processing failed.');
}

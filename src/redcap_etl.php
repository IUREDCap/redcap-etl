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
#    -t <timezone>
#
#-------------------------------------------------------------

require(__DIR__.'/../vendor/autoload.php');

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
$timezone       = date_default_timezone_get();
set_time_limit(0); # No limit on execution time

#----------------------------------------------
# Process the command line options
#----------------------------------------------
$options = getopt('p:t:l:');

# properties file
if (array_key_exists('p', $options)) {
    $propertiesFile = $options['p'];
}

# timzezone
if (array_key_exists('t', $options)) {
    $timezone = $options['t'];
    date_default_timezone_set($timezone);
}

# execution time limit (in seconds)
if (array_key_exists('l', $options)) {
    $limit = $options['l'];
    set_time_limit($limit);
}


try {
    $redCapEtl = new RedCapEtl($logger, true, null, null, $propertiesFile);

    $logger->logInfo("Starting processing.");

    //-------------------------------------------------------------------------
    // Parse Map
    //-------------------------------------------------------------------------
    // NOTE: The $result is not used in batch mode. It is used
    //       by the DET handler to give feedback within REDCap.
    list($parse_status,$result) = $redCapEtl->parseMap();

    if (RedCapEtl::PARSE_ERROR === $parse_status) {
        $logger->logError("Schema map not parsed. Processing stopped.");
    } else {
        //----------------------------------------------------------------------
        // Extract, Transform, and Load
        //
        // These three steps are joined together at this level so that
        // the data from REDCap can be worked on in batches
        //----------------------------------------------------------------------
        $redCapEtl->loadTables();
        $redCapEtl->extractTransformLoad();

        $logger->logInfo("Processing complete.");
    }
} catch (EtlException $exception) {
    $logger->logException($exception);
    $logger->logError('Processing failed.');
}

#!/usr/bin/env php
<?php

#-------------------------------------------------------------
# Test script for CSV zip file export.
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
} elseif (count($argv) === 2) {
    # if there is only one argument, assume it is the config file name
    $configFile = $argv[1];
} else {
    print "Usage: redcap_etl.php [-c] <configuration_file>\n";
    exit(1);
}

try {
    $redCapEtl = new RedCapEtl($logger, $configFile);
    $dir = $redCapEtl->exportEtlCsvZip();
    print "Zip file: {$dir}\n";
} catch (\Exception $exception) {
    $logger->logException($exception);
    $logger->log('Processing failed.');
}

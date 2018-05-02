#!/usr/bin/env php
<?php

require(__DIR__.'/../dependencies/autoload.php');

#-----------------------------------------------------------
# Script for checking configuration file
#
# Arguments: <properties-file>
#-----------------------------------------------------------

use IU\REDCapETL\ConfigProperties;
use IU\REDCapETL\Configuration;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Logger;

if (count($argv) != 2) {
    print "Usage: php $argv[0] <config-file>\n";
    exit(1);
} else {
    $configFile = $argv[1];
}

try {
    $app = basename(__FILE__, '.php');
    $logger = new Logger($app);

    $configuration = new Configuration($logger, null, $configFile);
    $properties = $configuration->getProperties();
    
    $warningsCount = 0;
    foreach ($properties as $property => $value) {
        if (!ConfigProperties::isValid($property)) {
            print "WARNING: property {$property} is not a valid"
                ." REDCap-ETL configuration property.\n";
            $warningsCount++;
        }
    }

    if ($warningsCount > 0) {
        print "Configuration file {$configFile} has {$warningsCount} warnings.\n";
    } else {
        print "Configuration file {$configFile} is OK.\n";
    }
} catch (EtlException $exception) {
    $cause = null;
    $previous = $exception->getPrevious();
    if (isset($previous)) {
        $cause = $previous->getMessage();
    }

    if (isset($cause)) {
        print "ERROR: ".($exception->getMessage())."; cause: ".$cause."\n";
    } else {
        print "ERROR: ".$exception->getMessage()."\n";
    }
}

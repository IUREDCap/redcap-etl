#!/usr/bin/env php
<?php

require(__DIR__.'/../dependencies/autoload.php');

#-----------------------------------------------------------
# Script for checking configuration file
#
# Arguments: <properties-file>
#-----------------------------------------------------------

use IU\REDCapETL\ConfigProperties;
use IU\REDCapETL\WorkflowConfig;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Logger;
use IU\REDCapETL\RedCapEtl;

if (count($argv) != 2) {
    print "Usage: php $argv[0] <config-file>\n";
    exit(1);
} else {
    $configFile = $argv[1];
}

try {
    $app = basename(__FILE__, '.php');
    $logger = new Logger($app);

    $redCapEtl = new RedCapEtl($logger, $configFile);

    $workflowConfig = new WorkflowConfig();
    $workflowConfig->set($logger, $configFile);
    
    $propertiesSet = array();
    $properties = $workflowConfig->getGlobalProperties();

    $propertiesSet[] = $properties;

    foreach ($workflowConfig->getTaskConfigs() as $taskConfig) {
        $properties = $taskConfig->getProperties();
        $propertiesSet[] = $properties;
    }

    $warningsCount = 0;
    foreach ($propertiesSet as $properties) {
        foreach ($properties as $property => $value) {
            if (!ConfigProperties::isValid($property)) {
                print "WARNING: property {$property} is not a valid"
                    ." REDCap-ETL configuration property.\n";
                $warningsCount++;
            }
        }
    }

    print("\n");
    if ($warningsCount > 0) {
        print "Configuration file {$configFile} has {$warningsCount} warnings.\n";
    } else {
        print "Configuration file {$configFile} is OK.\n";
    }
} catch (EtlException $exception) {
    print("\n");
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

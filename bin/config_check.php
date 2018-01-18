#!/usr/bin/env php
<?php

require(__DIR__.'/../dependencies/autoload.php');

#-----------------------------------------------------------
# Script for checking configuration file
#
# Arguments: <properties-file>
#-----------------------------------------------------------

use IU\REDCapETL\Configuration;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Logger2;

if (count($argv) != 2) {
    print "Usage: php $argv[0] <config-file>\n";
    exit(1);
} else {
    $configFile = $argv[1];
}

try {
    $app = basename(__FILE__, '.php');
    $logger = new Logger2($app);

    $configuration = new Configuration($logger, null, $configFile);
    print "Configuration file {$configFile} is OK.\n";
} catch (EtlException $exception) {
    $cause = null;
    $previous = $exception->getPrevious();
    if (isset($previous)) {
        $cause = $previous->getMessage();
    }

    if (isset($cause)) {
        print "Error found: ".($exception->getMessage())."; cause: ".$cause."\n";
    } else {
        print "Error found: ".$exception->getMessage()."\n";
    }
}

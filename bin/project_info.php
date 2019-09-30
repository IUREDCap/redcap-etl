#!/usr/bin/env php
<?php

require(__DIR__.'/../dependencies/autoload.php');

#-----------------------------------------------------------
# Script for getting information about the REDCap projects
# associated with a REDCap-ETL properties file. This
# script outputs the ID and title for the Config, Log,
# and Data projects.
#
# Arguments: <properties-file>
#-----------------------------------------------------------

use IU\REDCapETL\ConfigProperties;
use IU\REDCapETL\Configuration;
use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

if (count($argv) != 2) {
    print "Usage: php $argv[0] <configuration-file>\n";
    exit(1);
} else {
    $configFile = $argv[1];
}

try {
    $properties = parse_ini_file($configFile);
    if ($properties === false) {
        $message = 'Unable to read configuration file "'.$configFile.'"';
        throw new Exception($message);
    }

    $apiUrl   = $properties[ConfigProperties::REDCAP_API_URL];

    $dataToken = $properties[ConfigProperties::DATA_SOURCE_API_TOKEN];

    $dataProject = new RedCapProject($apiUrl, $dataToken);
    $dataInfo  = $dataProject->exportProjectInfo();
    $dataId    = $dataInfo['project_id'];
    $dataTitle = $dataInfo['project_title'];

    print "Project: ".$dataTitle." [ID = ".$dataId."]\n";
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

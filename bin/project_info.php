#!/usr/bin/env php
<?php

require(__DIR__.'/../dependencies/autoload.php');

#-----------------------------------------------------------
# Script for getting information about the REDCap projects
# associated with a REDCap ETL properties file. This
# script outputs the ID and title for the Config, Log,
# and Data projects.
#
# Arguments: <properties-file>
#-----------------------------------------------------------

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

if (count($argv) != 2) {
    print "Usage: php $argv[0] <properties-file>\n";
    exit(1);
} else {
    $propertiesFile = $argv[1];
}

try {
    $properties = parse_ini_file($propertiesFile);
    if ($properties === false) {
        $message = 'Unable to read properties file ';
        throw new Exception($message);
    }

    $apiUrl   = $properties['redcap_api_url'];
    $apiToken = $properties['api_token'];

    $configProject = new RedCapProject($apiUrl, $apiToken);
    $configInfo = $configProject->exportProjectInfo();
    $configId = $configInfo['project_id'];
    $configTitle = $configInfo['project_title'];

    $records = $configProject->exportRecords();

    $logToken = $records[0]['log_project_api_token'];
    if ($logToken !== '') {
        $logProject = new RedCapProject($apiUrl, $logToken);
        $logInfo  = $logProject->exportProjectInfo();
        $logId    = $logInfo['project_id'];
        $logTitle = $logInfo['project_title'];
    }

    $dataToken = $records[0]['data_source_api_token'];
    $dataProject = new RedCapProject($apiUrl, $dataToken);
    $dataInfo  = $dataProject->exportProjectInfo();
    $dataId    = $dataInfo['project_id'];
    $dataTitle = $dataInfo['project_title'];

    print "Config Project: [".$configId."] ".$configTitle."\n";
    if ($logToken === '') {
        print "Log Project:    not set\n";
    } else {
        print "Log Project:    [".$logId."] ".$logTitle."\n";
    }
    print "Data Project:   [".$dataId."] ".$dataTitle."\n";
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

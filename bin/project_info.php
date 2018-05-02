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

    $configApiToken = '';

    if (!array_key_exists(ConfigProperties::CONFIG_API_TOKEN, $properties)
            || trim($properties[ConfigProperties::CONFIG_API_TOKEN]) === '') {
        $dataToken = $properties[ConfigProperties::DATA_SOURCE_API_TOKEN];
        $logToken  = $properties[ConfigProperties::LOG_PROJECT_API_TOKEN];
    } else {
        $configApiToken = $properties[ConfigProperties::CONFIG_API_TOKEN];

        $configProject = new RedCapProject($apiUrl, $configApiToken);
        $configInfo = $configProject->exportProjectInfo();
        $configId = $configInfo['project_id'];
        $configTitle = $configInfo['project_title'];

        $records = $configProject->exportRecords();

        $config = $records[0];

        $dataToken = $config[ConfigProperties::DATA_SOURCE_API_TOKEN];
        $logToken = $config[ConfigProperties::LOG_PROJECT_API_TOKEN];
    }

    if ($logToken !== '') {
        $logProject = new RedCapProject($apiUrl, $logToken);
        $logInfo  = $logProject->exportProjectInfo();
        $logId    = $logInfo['project_id'];
        $logTitle = $logInfo['project_title'];
    }

    $dataProject = new RedCapProject($apiUrl, $dataToken);
    $dataInfo  = $dataProject->exportProjectInfo();
    $dataId    = $dataInfo['project_id'];
    $dataTitle = $dataInfo['project_title'];

    if ($configApiToken === '') {
        print "Config Project: not set\n";
    } else {
        print "Config Project: [ID = ".$configId."] ".$configTitle."\n";
    }

    print "Data Project:   [ID = ".$dataId."] ".$dataTitle."\n";

    if ($logToken === '') {
        print "Log Project:    not set\n";
    } else {
        print "Log Project:    [ID = ".$logId."] ".$logTitle."\n";
    }
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

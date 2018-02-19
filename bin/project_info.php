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

use IU\REDCapETL\Configuration;
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

    $apiUrl   = $properties[Configuration::REDCAP_API_URL_PROPERTY];

    $configApiToken = '';

    if (!array_key_exists(Configuration::CONFIG_API_TOKEN_PROPERTY, $properties)
            || trim($properties[Configuration::CONFIG_API_TOKEN_PROPERTY]) === '') {
        $dataToken = $properties[Configuration::DATA_SOURCE_API_TOKEN_PROPERTY];
        $logToken  = $properties[Configuration::LOG_PROJECT_API_TOKEN_PROPERTY];
    } else {
        $configApiToken = $properties[Configuration::CONFIG_API_TOKEN_PROPERTY];

        $configProject = new RedCapProject($apiUrl, $configApiToken);
        $configInfo = $configProject->exportProjectInfo();
        $configId = $configInfo['project_id'];
        $configTitle = $configInfo['project_title'];

        $records = $configProject->exportRecords();

        $config = $records[0];

        $dataToken = $config[Configuration::DATA_SOURCE_API_TOKEN_PROPERTY];
        $logToken = $config[Configuration::LOG_PROJECT_API_TOKEN_PROPERTY];
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

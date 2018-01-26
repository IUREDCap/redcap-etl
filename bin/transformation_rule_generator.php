#!/usr/bin/env php
<?php

/**
 * Generates transformation rules for the data source
 * specified in a properties file.
 */

require(__DIR__ . '/../dependencies/autoload.php');

use IU\PHPCap\RedCapProject;
use IU\REDCapETL\Configuration;
use IU\REDCapETL\TransformationRules;

if (count($argv) != 2) {
    print "Usage: php $argv[0] <configuration-file>\n";
    exit(1);
} else {
    $configurationFile = $argv[1];
}

try {
    $properties = parse_ini_file($configurationFile);
    if ($properties === false) {
        $message = 'Unable to read properties file ';
        throw new Exception($message);
    }

    $apiUrl   = $properties[Configuration::REDCAP_API_URL_PROPERTY];
    $apiToken = $properties[Configuration::CONFIG_API_TOKEN_PROPERTY];

    $configProject = new RedCapProject($apiUrl, $apiToken, true);
    $configInfo = $configProject->exportProjectInfo();

    $records = $configProject->exportRecords();

    $dataToken = $records[0][Configuration::DATA_SOURCE_API_TOKEN_PROPERTY];
    $dataProject = new RedCapProject($apiUrl, $dataToken, true);

    $transformationRules = new TransformationRules('');
    $rules = $transformationRules->generateDefaultRules($dataProject);

    print $rules;
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

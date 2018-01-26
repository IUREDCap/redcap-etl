#!/usr/bin/env php
<?php

/**
 * Generates transformation rules for the data source
 * specified in a properties file.
 */

require(__DIR__ . '/../dependencies/autoload.php');

use IU\PHPCap\RedCapProject;
use IU\REDCapETL\Configuration;

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

    $projectInfo = $dataProject->exportProjectInfo();
    $instruments = $dataProject->exportInstruments();
    $metadata    = $dataProject->exportMetadata();

    $recordId = $metadata[0]['field_name'];

    foreach ($instruments as $formName => $formLabel) {
        print "TABLE,".$formName.",".$recordId.",".'ROOT'."\n";

        foreach ($metadata as $field) {
            if ($field['form_name'] == $formName) {
                $type = 'string';
                
                $validationType = $field['text_validation_type_or_show_slider_number'];
                $fieldType      = $field['field_type'];
                
                if ($fieldType === 'checkbox') {
                    $type = 'checkbox';
                } elseif ($validationType === 'integer') { # value may be too large for db int
                    $type = 'string';
                } elseif ($fieldType === 'dropdown' || $fieldType === 'radio') {
                    $type = 'int';
                } elseif ($validationType === 'date_mdy') {
                    $type = 'date';
                }
                
                if ($fieldType === 'descriptive' || $fieldType === 'file') {
                    ; // Don't do anything
                } else {
                    print "FIELD,".$field['field_name'].",".$type."\n";
                }
            }
        }
        print "\n";
    }
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

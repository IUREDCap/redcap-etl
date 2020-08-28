#!/usr/bin/env php
<?php

# WORK IN PROGRESS
#

$configInitDir = __DIR__.'/../tests/config-init/';
$configDir     = __DIR__.'/../tests/config/';

# Configuration files that need to be copied and edited
$configFiles = [
    'basic-demography-2.ini',
    'basic-demography-3.ini',
    'basic-demography-bad-field-name.ini',
    'basic-demography-bad-rule.ini',
    'basic-demography-duplicate-primary-key-name.ini',
    'basic-demography.ini',
    'basic-demography.json',
    'basic-demography-rules-badFieldName.txt',
    'basic-demography-rules-badRule.txt',
    'basic-demography-rules-duplicate-primary-key-name.txt',
    'basic-demography-rules.txt',
    'mysql-ssl.ini',
    'repeating-events.ini',
    'repeating-events-mysql.ini',
    'repeating-events-mysql-rules.txt',
    'repeating-events-postgresql.ini',
    'repeating-events-rules.txt',
    'repeating-events-sqlite.ini',
    'repeating-events-sqlserver.ini',
    'sqlserver.ini',
    'sqlserver-ssl.ini',
    'visits-empty-rules.ini',
    'visits-empty-rules.txt',
    'visits.ini',
    'visits-missing-suffix.ini',
    'visits-missing-suffix-rules.txt',
    'visits-rules.txt',
    'visits.sql',
    'visits-sqlite.ini'
];

foreach ($configFiles as $configFile) {
    $path = $configInitDir . $configFile;

    if (!file_exists($path)) {
        print "ERROR - required configuration file \"{$path}\" could not be found.\n";
        exit(1);
    }

    $configFilePath = realpath($configInitDir . $configFile);
    print "{$configFilePath}\n";
}

$properties = parse_ini_file(__DIR__.'/../tests/config.ini', true);

$basicDemographyIniFiles = [
    'basic-demography-2.ini',
    'basic-demography-3.ini',
    'basic-demography-bad-field-name.ini',
    'basic-demography-bad-rule.ini',
    'basic-demography.ini'
];

$redcapApiUrl = $properties['basic-demography']['redcap_api_url'];
$apiToken     = $properties['basic-demography']['data_source_api_token'];

foreach ($basicDemographyIniFiles as $file) {
    $file = realpath($configDir.$file);
    $contents = file_get_contents($file);
    $contents = preg_replace('/redcap_api_url\s*=.*/', "redcap_api_url = {$redcapApiUrl}", $contents);
    $contents = preg_replace('/data_source_api_token\s*=.*/', "data_source_api_token = {$apiToken}", $contents);
    print "\n\n{$contents}\n";
}

#    'basic-demography.json',

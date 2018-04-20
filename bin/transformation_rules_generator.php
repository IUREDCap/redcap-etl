#!/usr/bin/env php
<?php

/**
 * Generates transformation rules for the data source
 * specified in a properties file.
 */

require(__DIR__ . '/../dependencies/autoload.php');

use IU\PHPCap\RedCapProject;
use IU\REDCapETL\Configuration;
use IU\REDCapETL\Logger;
use IU\REDCapETL\RulesGenerator;

if (count($argv) != 2) {
    print "Usage: php $argv[0] <configuration-file>\n";
    exit(1);
} else {
    $configurationFile = $argv[1];
}

try {
    $logger = new Logger($argv[0]);
    $config = new Configuration($logger, null, $configurationFile);

    $apiUrl   = $config->getRedCapApiUrl();
    $apiToken = $config->getDataSourceApiToken();

    $dataProject = new RedCapProject($apiUrl, $apiToken, true);

    $rulesGenerator = new RulesGenerator();
    $rules = $rulesGenerator->generate($dataProject);

    print $rules;
} catch (Exception $exception) {
    print "Error: ".$exception->getMessage()."\n";
    exit(1);
}

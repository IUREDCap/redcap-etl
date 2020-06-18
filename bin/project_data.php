#!/usr/bin/env php
<?php

#-------------------------------------------------------------
# This script is used to generate project information
# data files for a REDCap project. These files can be
# useful for unit testing.
#
# A REDCap-ETL configuration file is specified as the one
# input. The project generates one JSON (.json) file,
# one XML file (.xml), and one text (-rules.txt) file
# for the configuration file (using the same base file name).
#
# The JSON file contains:
#     * project info
#     * instruments
#     * metadata
#
# The XML file contains:
#     * project XML
#
# The text file contains:
#     * the auto-generated transformation rules
#
#-------------------------------------------------------------

require(__DIR__.'/../dependencies/autoload.php');

use IU\PHPCap\RedCapProject;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\Configuration;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Version;
use IU\REDCapETL\Logger;

$app = basename(__FILE__, '.php');
$logger = new Logger($app);

#---------------------------
# Set default values
#---------------------------
$configFile = null;

#----------------------------------------------
# Process the command line options
#----------------------------------------------
$options = getopt('c:');

# properties file
if (array_key_exists('c', $options)) {
    $configFile = $options['c'];
} elseif (count($argv) === 2) {
    # if there is only one argument, assume it is the config file name
    $configFile = $argv[1];
} else {
    print "Usage: redcap_etl.php [-c] <configuration_file>\n";
    exit(1);
}

try {
    $redCapEtl = new RedCapEtl($logger, $configFile);
    $config = $redCapEtl->getConfiguration();

    $apiUrl   = $config->getRedCapApiUrl();
    $apiToken = $config->getDataSourceApiToken();

    $project = new RedCapProjecT($apiUrl, $apiToken);

    #-------------------------------------
    # Get the JSON data for the project
    #-------------------------------------
    $data = array();

    $redCapVersion = $project->exportRedCapVersion();
    $data['redCapVersion'] = $redCapVersion;

    $projectInfo = $project->exportProjectInfo();
    $data['projectInfo'] = $projectInfo;

    $metadata = $project->exportMetadata();
    $data['metadata'] = $metadata;

    $fieldNames = $project->exportFieldNames();
    $data['fieldNames'] = $fieldNames;

    $instruments = $project->exportInstruments();
    $data['instruments'] = $instruments;

    if ($projectInfo['is_longitudinal']) {
        $instrumentEventMappings = $project->exportInstrumentEventMappings();
        $data['instrumentEventMappings'] = $instrumentEventMappings;
    }

    $records = $project->exportRecordsAp(
        ['exportSurveyFields' => true, 'exportDataAccessGroups' => true]
    );
    $data['records'] = $records;


    $json = json_encode($data, JSON_PRETTY_PRINT)."\n";

    $jsonFileName = basename($configFile, '.ini') . '.json';
    file_put_contents($jsonFileName, $json);

    #-------------------------------------
    # Get the XML data for the project
    #-------------------------------------
    $projectXml = $project->exportProjectXml($returnMetadataOnly = true)."\n";
    $xmlFileName = basename($configFile, '.ini') . '.xml';
    file_put_contents($xmlFileName, $projectXml);

    #----------------------------------------------
    # Get the auto-generated transformation rules
    #----------------------------------------------
    $transformationRules = $redCapEtl->autoGenerateRules();

    $rulesFileName = basename($configFile, '.ini') . '-rules.txt';
    file_put_contents($rulesFileName, $transformationRules);
} catch (EtlException $exception) {
    $logger->logException($exception);
    $logger->logError('Processing failed.');
}

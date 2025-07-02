#!/usr/bin/env php
<?php
#=========================================================================
# Script for setting up the automated REDCap-ETL tests.
#
# This script copies test configuration template files to the actual
# configuration directory and sets the installation-specific values
# for the properties in the copied files.
#
# Single task configuration files need to contain the name of the
# REDCap project they use in the file name.
#
# Workflow (i.e., multi-task) configuration files, need to contain
# "workflow" in the file name and have a comment with the name of
# the project after each project-specific # property in the file.
#=========================================================================

$configInitDir = __DIR__.'/../tests/config-init/';   # test config file template directory
$configDir     = __DIR__.'/../tests/config/';        # test config file directory


#---------------------------------------------------------------------
# Configuration files (map from configuration file name to database)
#---------------------------------------------------------------------
$configFiles = array_merge(glob($configInitDir.'*.ini'), glob($configInitDir.'*.json'));
for ($i = 0; $i < count($configFiles); $i++) {
    $configFiles[$i] = basename($configFiles[$i]);
}

$rulesFiles = glob($configInitDir.'*.txt');
for ($i = 0; $i < count($rulesFiles); $i++) {
    $rulesFiles[$i] = basename($rulesFiles[$i]);
}

$sqlFiles = [
    'visits.sql'
];
$sqlFiles = glob($configInitDir.'*.sql');
for ($i = 0; $i < count($sqlFiles); $i++) {
    $sqlFiles[$i] = basename($sqlFiles[$i]);
}

#----------------------------------------------
# Parse the test setup configuration file
#----------------------------------------------
$properties = parse_ini_file(__DIR__.'/../tests/config.ini', true);

$basicDemographyApiUrl   = $properties['basic-demography']['redcap_api_url'];
$basicDemographyApiToken = $properties['basic-demography']['data_source_api_token'];

$multipleChoiceApiUrl   = $properties['multiple-choice']['redcap_api_url'];
$multipleChoiceApiToken = $properties['multiple-choice']['data_source_api_token'];

$multipleRootInstrumentsApiUrl   = $properties['multiple-root-instruments']['redcap_api_url'];
$multipleRootInstrumentsApiToken = $properties['multiple-root-instruments']['data_source_api_token'];

$repeatingEventsApiUrl   = $properties['repeating-events']['redcap_api_url'];
$repeatingEventsApiToken = $properties['repeating-events']['data_source_api_token'];

$repeatingEventsExtendedApiUrl    = $properties['repeating-events-extended']['redcap_api_url'];
$repeatingEventsExtendedApiToken  = $properties['repeating-events-extended']['data_source_api_token'];

$repeatingFormsApiUrl    = $properties['repeating-forms']['redcap_api_url'];
$repeatingFormsApiToken  = $properties['repeating-forms']['data_source_api_token'];

$visitsApiUrl    = $properties['visits']['redcap_api_url'];
$visitsApiToken  = $properties['visits']['data_source_api_token'];

$dbConnection['mysql']         = $properties['mysql']['db_connection'];
$dbConnection['mysql-ssl']     = $properties['mysql-ssl']['db_connection'];
$dbConnection['postgresql']    = $properties['postgresql']['db_connection'];
$dbConnection['sqlite']        = $properties['sqlite']['db_connection'];
$dbConnection['sqlserver']     = $properties['sqlserver']['db_connection'];
$dbConnection['sqlserver-ssl'] = $properties['sqlserver-ssl']['db_connection'];

#-----------------------------------------------------------------------------------
# Copy the rules and SQL files that are not already in the test condig directory
#-----------------------------------------------------------------------------------
foreach (array_merge($rulesFiles, $sqlFiles) as $configFile) {
    $fromPath = realpath($configInitDir . $configFile);
    $toPath   = $configDir .$configFile;

    if (!file_exists($fromPath)) {
        print "ERROR - required configuration file \"{$fromPath}\" could not be found.\n";
        exit(1);
    }

    print "Copying file \"{$configFile}\" to \"{$configDir}\"\n";
    copy($fromPath, $toPath);
}


#------------------------------------------------------------
# For each configuration file, update the API URL and token,
# and, where applicable, the database connection
#------------------------------------------------------------
foreach ($configFiles as $configFile) {
    $fromPath = realpath($configInitDir . $configFile);
    $toPath   = $configDir . $configFile;

    $contents = file_get_contents($fromPath);

    if (empty($contents)) {
        print "ERROR - required configuration file \"{$fromPath}\" is empty.\n";
        exit(1);
    }

    #--------------------------------------------------
    # Check the configuration file name to see if it
    # contains a database name
    #--------------------------------------------------
    $db = null;
    if (preg_match('/mysql-ssl/', $configFile)) {
        $db = 'mysql-ssl';
    } elseif (preg_match('/mysql/', $configFile)) {
        $db = 'mysql';
    } elseif (preg_match('/postgresql/', $configFile)) {
        $db = 'postgresql';
    } elseif (preg_match('/sqlserver-ssl/', $configFile)) {
        $db = 'sqlserver-ssl';
    } elseif (preg_match('/sqlserver/', $configFile)) {
        $db = 'sqlserver';
    } elseif (preg_match('/sqlite/', $configFile)) {
        $db = 'sqlite';
    } elseif (preg_match('/multidb.*\.ini/', $configFile)) {
        # .ini configuration file with multiple databases

        # MySQL
        $contents = preg_replace(
            '/db_connection\s*=\s*;\s*mysql/',
            "db_connection = {$dbConnection['mysql']}",
            $contents
        );

        # PostgreSQL
        $contents = preg_replace(
            '/db_connection\s*=\s*;\s*postgresql/',
            "db_connection = {$dbConnection['postgresql']}",
            $contents
        );

        # Sqlite
        $contents = preg_replace(
            '/db_connection\s*=\s*;\s*sqlite/',
            "db_connection = {$dbConnection['sqlite']}",
            $contents
        );

        # SQL Server
        $contents = preg_replace(
            '/db_connection\s*=\s*;\s*sqlserver/',
            "db_connection = {$dbConnection['sqlserver']}",
            $contents
        );
    }

    if (!file_exists($fromPath)) {
        print "ERROR - required configuration file \"{$fromPath}\" could not be found.\n";
        exit(1);
    }

    # If the copied file doesn't exist, copy it
    if (!file_exists($toPath)) {
        # If the there is no database or the database is sqlite (which hard codes the
        # database connection) for this config file, or there is a database
        # and it has been set in the properties file
        if (empty($db) || $db === 'sqlite' || !empty($dbConnection[$db])) {
            print "Copying file \"{$configFile}\" to \"{$configDir}\"\n";
            copy($fromPath, $toPath);
        }
    } else {
        # The file does exist

        # If there is a database for this confile file, and its value has not been set,
        # delete the existing config file
        if (!empty($db) && $db !== 'sqlite' && empty($dbConnection[$db])) {
            print "Deleting config file \"{$configFile}\", because no \"{$db}\" property was set\n";
            unlink($toPath);
        } else {
            # Copy in case changes were made
            print "Copying file \"{$configFile}\" to \"{$configDir}\"\n";
            copy($fromPath, $toPath);
        }
    }

    if (file_exists($toPath)) {
        if (preg_match('/basic-demography-3.ini/', $toPath) === 1) {
            # Special case, test for properties not set
        } elseif (preg_match('/workflow.*\.ini/', $toPath) === 1) {
            # Workflow

            # basic-demography properties
            $contents = preg_replace(
                '/redcap_api_url\s*=\s*;\s*basic-demography/',
                "redcap_api_url = {$basicDemographyApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=\s*;\s*basic-demography/',
                "data_source_api_token = {$basicDemographyApiToken}",
                $contents
            );

            # repeating-events properties
            $contents = preg_replace(
                '/redcap_api_url\s*=.\s*;\s*repeating-events/',
                "redcap_api_url = {$repeatingEventsApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=\s*;\s*repeating-events/',
                "data_source_api_token = {$repeatingEventsApiToken}",
                $contents
            );

            # repeating-forms properties
            $contents = preg_replace(
                '/redcap_api_url\s*=.\s*;\s*repeating-forms/',
                "redcap_api_url = {$repeatingFormsApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=\s*;\s*repeating-forms/',
                "data_source_api_token = {$repeatingFormsApiToken}",
                $contents
            );
        } elseif (preg_match('/workflow.*\.json/', $toPath) === 1) {
            # Workflow

            # basic-demography properties
            $contents = preg_replace(
                '/"redcap_api_url"\s*:\s*"basic-demography"/',
                '"redcap_api_url": "'.$basicDemographyApiUrl.'"',
                $contents
            );
            $contents = preg_replace(
                '/"data_source_api_token"\s*:\s*"basic-demography"/',
                '"data_source_api_token": "'.$basicDemographyApiToken.'"',
                $contents
            );
        } elseif (preg_match('/basic-demography.*\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Basic demography files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$basicDemographyApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$basicDemographyApiToken}",
                $contents
            );
        } elseif (preg_match('/basic-demography.*\.json/', $toPath) === 1) {
            #-------------------------------------
            # Basic demography JSON files
            #-------------------------------------
            #    "redcap_api_url": "",
            # "data_source_api_token": "",

            $contents = preg_replace(
                '/"redcap_api_url"\s*:.*/',
                '"redcap_api_url" : "'.$basicDemographyApiUrl.'",',
                $contents
            );
            $contents = preg_replace(
                '/"data_source_api_token"\s*:.*/',
                '"data_source_api_token" : "'.$basicDemographyApiToken.'",',
                $contents
            );
        } elseif (preg_match('/multiple-choice.*\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Multiple choice files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$multipleChoiceApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$multipleChoiceApiToken}",
                $contents
            );
        } elseif (preg_match('/multiple-root-instruments.*\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Multiple root instrument files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$multipleRootInstrumentsApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$multipleRootInstrumentsApiToken}",
                $contents
            );
        } elseif (preg_match('/repeating-events.*\.ini/', $toPath) === 1
                && preg_match('/repeating-events-extended\.ini/', $toPath) !== 1) {
            #-------------------------------------
            # Repeating events files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$repeatingEventsApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$repeatingEventsApiToken}",
                $contents
            );
        } elseif (preg_match('/repeating-events-extended\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Repeating events files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$repeatingEventsExtendedApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$repeatingEventsExtendedApiToken}",
                $contents
            );
        } elseif (preg_match('/repeating-forms.*\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Repeating forms files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$repeatingFormsApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$repeatingFormsApiToken}",
                $contents
            );
        } elseif (preg_match('/visits.*\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Visits files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$visitsApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$visitsApiToken}",
                $contents
            );
        }

        if (!empty($db)) {
            $contents = preg_replace(
                '/db_connection\s*=.*/',
                "db_connection = ".$dbConnection[$db],
                $contents
            );
        }
        print "Updating file \"{$toPath}\"\n";
        file_put_contents($toPath, $contents);
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests of the REDCap-ETL script on the
 * dynamic-rules multiple-root-form project.
 */
class DynamicRulesMultipleRootFormTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/multiple-root-instruments-dynamic-rules.ini';
    const TEST_DATA_DIR   = __DIR__.'/../data/';    # directory with test data comparison files

    private static $config;
    private static $csvDir;
    private static $logger;
    private static $rootCsvFile;
    private static $resultsCsvFile;
    private static $locationCsvFile;
    private static $combinedFormsCsvFile;

    public static function setUpBeforeClass()
    {

        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('dynamic_rules_multiple_test');
            self::$config = new TaskConfig();
            self::$config->set(self::$logger, self::CONFIG_FILE);

            #-----------------------------
            # Get the CSV directory
            #-----------------------------
            self::$csvDir = str_ireplace(
                'CSV:',
                '',
                self::$config->getDbConnection()
            );
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR))
                !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
        }

        #-------------------------------------------
        # Set the files to use
        #-------------------------------------------
        self::$rootCsvFile = self::$csvDir . 'results_root.csv';
        self::$resultsCsvFile = self::$csvDir . 'results.csv';
        self::$locationCsvFile = self::$csvDir . 'location.csv';
        self::$combinedFormsCsvFile = self::$csvDir . 'combined_forms.csv';
    }

    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }

    public static function deleteOldResultsFiles()
    {
        if (file_exists(self::$rootCsvFile)) {
            unlink(self::$rootCsvFile);
        }

        if (file_exists(self::$resultsCsvFile)) {
            unlink(self::$resultsCsvFile);
        }

        if (file_exists(self::$locationCsvFile)) {
            unlink(self::$locationCsvFile);
        }

        if (file_exists(self::$combinedFormsCsvFile)) {
            unlink(self::$combinedFormsCsvFile);
        }
    }

    public function testAutoGenAllFalse()
    {
        self::deleteOldResultsFiles();

        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::DB_CONNECTION] = 'CSV:' . self::$csvDir;
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = TaskConfig::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = '';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }


        # Check to make sure that a files exists for each of the instruments
        $files = array(
           self::$rootCsvFile,
           self::$resultsCsvFile,
           self::$locationCsvFile
        );

        foreach ($files as $file) {
            $fileExists = file_exists($file);
            $this->assertTrue(
                $fileExists,
                'dynamicRulesTest multiple root form, autogen all false - '. $file . ' file should exist'
            );
        }
    }

    public function testAutoGenCombineNonRepeatingFields()
    {
        self::deleteOldResultsFiles();

        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::DB_CONNECTION] = 'CSV:' . self::$csvDir;
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = TaskConfig::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'true';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = 'combined_forms';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        # Check file with all non-repeating fields in it
        $this->assertFileExists(
            self::$combinedFormsCsvFile,
            'dynamicRulesMultipleRootForm check - combine non repeating fields, non-repeating forms file exists'
        );

        $expected = '"combined_forms_id","redcap_data_source","record_id","building_number"';
        $expected .= "\n";
        $a = fopen(self::$combinedFormsCsvFile, 'r');
        if ($a) {
            $actualHeader = fgets($a);
            fclose($a);
        }
        $this->assertEquals(
            $expected,
            $actualHeader,
            'dynamicRulesMultipleRootForm check - combine non repeating fields, contents check'
        );

        # Check to make sure that the form with repeating fields has its own file
        $this->assertFileExists(
            self::$resultsCsvFile,
            'dynamicRulesMultipleRootForm check - combine non repeating field, repeating forms file exists'
        );
    }
}

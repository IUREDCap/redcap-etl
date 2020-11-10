<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Integration test for the dynamic rules longitudinal test
 * using the repeating-events project.
 */
class DynamicRulesLongitudinalTest extends TestCase
{
    private static $redCapEtl;
    private static $config;

    private static $csvDir;
    private static $logger;
    private static $properties;


    private static $rootCsvFile;

    private static $enrollmentCsvFile;
    private static $enrollmentCsvLabelFile;

    private static $contactInformationCsvFile;
    private static $contactInformationCsvLabelFile;

    private static $emergencyContactsCsvFile;

    private static $weightCsvFile;
    private static $weightRepeatingInstrumentsCsvFile;
    private static $weightRepeatingEventsCsvFile;

    private static $cardiovascularCsvFile;
    private static $cardiovascularRepeatingInstrumentsCsvFile;
    private static $cardiovascularRepeatingEventsCsvFile;

    const CONFIG_FILE = __DIR__.'/../config/repeating-events-dynamic-rules.ini';
    const TEST_DATA_DIR   =__DIR__.'/../data/';

    public static function setUpBeforeClass()
    {
        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('dynamic_rules_longitudinal_test');
            self::$config = new TaskConfig(
                self::$logger,
                self::CONFIG_FILE
            );
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

            #-------------------------------------------
            # Set the files to use
            #-------------------------------------------
            self::$rootCsvFile = self::$csvDir . 'dr_root.csv';

            self::$enrollmentCsvFile = self::$csvDir . 'dr_enrollment.csv';
            self::$enrollmentCsvLabelFile = self::$csvDir . 'dr_enrollment'
                . self::$config->getlabelViewSuffix().'.csv';

            self::$contactInformationCsvFile = self::$csvDir
                . 'dr_contact_information.csv';
            self::$contactInformationCsvLabelFile = self::$csvDir
                . 'dr_contact_information'
                . self::$config->getlabelViewSuffix().'.csv';

            self::$emergencyContactsCsvFile = self::$csvDir
                . 'dr_emergency_contacts.csv';

            self::$weightCsvFile = self::$csvDir . 'dr_weight.csv';
            self::$weightRepeatingInstrumentsCsvFile = self::$csvDir
                . 'dr_weight_repeating_instruments.csv';
            self::$weightRepeatingEventsCsvFile = self::$csvDir
                . 'dr_weight_repeating_events.csv';

            self::$cardiovascularCsvFile = self::$csvDir . 'dr_cardiovascular.csv';
            self::$cardiovascularRepeatingInstrumentsCsvFile = self::$csvDir
                . 'dr_cardiovascular_repeating_events.csv';
            self::$cardiovascularRepeatingEventsCsvFile = self::$csvDir
                . 'dr_cardiovascular_repeating_instruments.csv';

            #-----------------------------------------------
            # Delete files from previous run (if any)
            #-----------------------------------------------
            if (file_exists(self::$rootCsvFile)) {
                unlink(self::$rootCsvFile);
            }

            if (file_exists(self::$enrollmentCsvFile)) {
                unlink(self::$enrollmentCsvFile);
            }

            if (file_exists(self::$enrollmentCsvLabelFile)) {
                unlink(self::$enrollmentCsvLabelFile);
            }

            if (file_exists(self::$contactInformationCsvFile)) {
                unlink(self::$contactInformationCsvFile);
            }

            if (file_exists(self::$contactInformationCsvLabelFile)) {
                unlink(self::$contactInformationCsvLabelFile);
            }

            if (file_exists(self::$weightCsvFile)) {
                unlink(self::$weightCsvFile);
            }

            if (file_exists(self::$weightRepeatingInstrumentsCsvFile)) {
                unlink(self::$weightRepeatingInstrumentsCsvFile);
            }

            if (file_exists(self::$weightRepeatingEventsCsvFile)) {
                unlink(self::$weightRepeatingEventsCsvFile);
            }

            if (file_exists(self::$cardiovascularCsvFile)) {
                unlink(self::$cardiovascularCsvFile);
            }

            if (file_exists(self::$cardiovascularRepeatingInstrumentsCsvFile)) {
                unlink(self::$cardiovascularRepeatingInstrumentsCsvFile);
            }
 
            if (file_exists(self::$cardiovascularRepeatingEventsCsvFile)) {
                unlink(self::$cardiovascularRepeatingEventsCsvFile);
            }
        }
    }

    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }

    public function testAutoGenCombineRepeatingLongitudinal()
    {
        #----------------------------------------------------------
        # Get the configuration properties and RedCapEtl object
        #----------------------------------------------------------
        self::$properties = self::$config->getProperties();
        self::$properties[ConfigProperties::DB_CONNECTION] = 'CSV:'
            . self::$csvDir;
        self::$properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = TaskConfig::TRANSFORM_RULES_DEFAULT;
        self::$properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';
        self::$properties[ConfigProperties::TRANSFORM_RULES_FILE] = '';

        self::$properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
        self::$properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
        self::$properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
        self::$properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
        self::$properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
        self::$properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'true';
        self::$properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = 'combine_non_repeating_longitudinal';
        self::$properties[ConfigProperties::TABLE_PREFIX] = 'dr_';

        self::$redCapEtl = new RedCapEtl(self::$logger, self::$properties);

        #-------------------------
        # Run the ETL process
        #------------------------
        try {
            self::$redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }


        # Check to make sure that a file having the combined-table name does not exist.
        $fileName = self::$csvDir
            . self::$properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE]
            . '.csv';

        $fileExists = file_exists($fileName);
        $this->assertFalse(
            $fileExists,
            'dynamicRulesTest combine repeating longitudal check - combined file should not exist'
        );

        # Check to make sure that a files exists for each of the instruments
        # (i.e., that the non-repeating forms haven't been copied to a single
        # file
        $files = array(
           self::$rootCsvFile,
           self::$enrollmentCsvFile,
           self::$enrollmentCsvLabelFile,
           self::$contactInformationCsvFile,
           self::$contactInformationCsvLabelFile,
           self::$emergencyContactsCsvFile,
           self::$weightCsvFile,
           self::$weightRepeatingInstrumentsCsvFile,
           self::$weightRepeatingEventsCsvFile,
           self::$cardiovascularCsvFile,
           self::$cardiovascularRepeatingInstrumentsCsvFile,
           self::$cardiovascularRepeatingEventsCsvFile
        );

        foreach ($files as $file) {
            $fileExists = file_exists($file);
            $this->assertTrue(
                $fileExists,
                'dynamicRulesTest combine repeating longitudal check - '. $file . ' file should exist'
            );
        }
    }
}

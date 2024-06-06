<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating events" tests using MySQL as the database.
 */
class BasicDemographyLfSystemTest extends TestCase
{
    const CONFIG_FILE = '';

    protected static $dbConnection;
    protected static $logger;

    
    public static function setUpBeforeClass(): void
    {
        if (file_exists(static::CONFIG_FILE)) {
            self::$logger = new Logger('basic_demography_lf_mysql_test');
        }
    }

    public function setUp(): void
    {
        if (!file_exists(static::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }

    public function runEtl($logger, $configFile)
    {
        try {
            $redCapEtl = new RedCapEtl($logger, $configFile);
            $redCapEtl->run();

            # Get the database connection.
            $firstTask = $redCapEtl->getTask(0);
            self::$dbConnection = $firstTask->getDbConnection();
        } catch (\Exception $exception) {
            print "\nEXCEPTION in test ".__FILE__.": {$exception->getMessage()}\n";
            # print "\n".$exception->getTraceAsString()."\n";
            $logger->logException($exception);
            $logger->log('Processing failed.');
        }
    }

    public function testData()
    {
        $this->runEtl(self::$logger, static::CONFIG_FILE);

        #---------------------------------------
        # Data table tests
        #---------------------------------------
        $data = self::$dbConnection->getData('basic_demography', 'basic_demography_id');

        # Data not mull check
        $this->assertNotNull($data, 'Data not null check');

        # Row count check
        $this->assertEquals(100, count($data), 'Row count');

        # Column names check
        $expectedColumns = [
            'basic_demography_id',
            'redcap_data_source',
            'record_id',
            'first_name',
            'last_name',
            'address',
            'phone',
            'email',
            'birthdate',
            'ethnicity',
            'ethnicity_label',
            'patient_race___0',
            'patient_race___0_label',
            'patient_race___1',
            'patient_race___1_label',
            'patient_race___2',
            'patient_race___2_label',
            'patient_race___3',
            'patient_race___3_label',
            'patient_race___4',
            'patient_race___4_label',
            'patient_race___5',
            'patient_race___5_label',
            'sex',
            'sex_label',
            'height',
            'weight',
            'bmi',
            'comments',
        ];
        $columns = self::$dbConnection->getTableColumnNames('basic_demography');

        $this->assertEquals($expectedColumns, $columns, 'Column name check');

        # Row ID check
        $expectedRowIds = range(1, 100);
        $rowIds = array_column($data, 'basic_demography_id');
        $this->assertEquals($expectedRowIds, $rowIds, 'Row IDs check');

        # REDCap data source check
        $expectedDataSources = array_fill(0, 100, 1);
        $dataSources = array_column($data, 'redcap_data_source');
        $this->assertEquals($expectedDataSources, $dataSources, 'REDCap data source check');

        # Record ID check
        $expectedRecordIds = range(1001, 1100);
        $recordIds = array_column($data, 'record_id');
        $this->assertEquals($expectedRecordIds, $recordIds, 'Record IDs check');

        $expectedFirstRow = [
            'basic_demography_id'    => '1',
            'redcap_data_source'     => '1',
            'record_id'              => '1001',
            'first_name'             => "Katherine",
            'last_name'              => "Huels",
            'address'                => "316 Goodwin Lights Suite 463\nPort Marietta, NV 35323-5627",
            'phone'                  => "(759) 257-3524",
            'email'                  => "katherine.huels@mailinator.com",
            'birthdate'              => "1955-05-07",
            'ethnicity'              => '0',
            'ethnicity_label'        => 'Hispanic or Latino',
            'patient_race___0'       => '0',
            'patient_race___0_label' => '',
            'patient_race___1'       => '0',
            'patient_race___1_label' => '',
            'patient_race___2'       => '0',
            'patient_race___2_label' => '',
            'patient_race___3'       => '0',
            'patient_race___3_label' => '',
            'patient_race___4'       => '1',
            'patient_race___4_label' => 'White',
            'patient_race___5'       => '0',
            'patient_race___5_label' => '',
            'sex'                    => '0',
            'sex_label'              => 'Female',
            'height'                 => '174.0',
            'weight'                 => '57.0',
            'bmi'                    => '18.8',
            'comments'               => ''
        ];

        $firstRow = $data[0];
        # rtrim phone because it has type char, and some databases right pad with blanks
        $firstRow['phone'] = rtrim($firstRow['phone']);

        SystemTestsUtil::convertCsvRowValues($expectedFirstRow);
        SystemTestsUtil::convertCsvRowValues($firstRow);

        $this->assertEquals($expectedFirstRow, $firstRow, 'First row check');

        $expectedLastRow = [
            'basic_demography_id'    => 100,
            'redcap_data_source'     => 1,
            'record_id'              => 1100,
            'first_name'             => "Ella",
            'last_name'              => "Kunze",
            'address'                => "26346 Kenyatta Fords\nStoltenbergville, MN 57828-4095",
            'phone'                  => "(714) 207-0230",
            'email'                  => "ella.kunze@mailinator.com",
            'birthdate'              => "1988-01-19",
            'ethnicity'              => '1',
            'ethnicity_label'        => 'NOT Hispanic or Latino',
            'patient_race___0'       => '0',
            'patient_race___0_label' => '',
            'patient_race___1'       => '0',
            'patient_race___1_label' => '',
            'patient_race___2'       => '0',
            'patient_race___2_label' => '',
            'patient_race___3'       => '0',
            'patient_race___3_label' => '',
            'patient_race___4'       => '1',
            'patient_race___4_label' => 'White',
            'patient_race___5'       => '0',
            'patient_race___5_label' => '',
            'sex'                    => '0',
            'sex_label'              => 'Female',
            'height'                 => '172',
            'weight'                 => '64',
            'bmi'                    => '21.6',
            'comments'               => ''
        ];

        $lastRow = $data[count($data) - 1];
        # rtrim phone because it has type char, and some databases right pad with blanks
        $lastRow['phone'] = rtrim($lastRow['phone']);

        SystemTestsUtil::convertCsvRowValues($expectedLastRow);
        SystemTestsUtil::convertCsvRowValues($lastRow);

        $this->assertEquals($expectedLastRow, $lastRow, 'Last row check');

        #----------------------------------------------
        # Label view tests
        #
        # Note: some db systems cause an empty array
        # to be returned, and some cause an error.
        #----------------------------------------------
        $data = array();
        try {
            $data = self::$dbConnection->getData('basic_demography_label_view', 'basic_demography_id');
        } catch (\Exception $e) {
            $data = array();
        }

        # Data null check (this view should not exist)
        $this->assertEquals(0, count($data), 'No label view check');
    }
}

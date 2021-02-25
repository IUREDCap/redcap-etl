<?php
#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "basic demography" in a workflow that stores the data in all supported databases.
 */
class WorkflowMultiDbTest extends TestCase
{
    const WEIGHT_TIME_FIELD_DECLARATION = "DATE_FORMAT(weight_time, '%Y-%m-%d %H:%i') as 'weight_time'";
        
    const CONFIG_FILE = __DIR__.'/../config/workflow-multidb.ini';

    protected static $mysqlDbConnection;
    protected static $postgresqlDbConnection;
    protected static $sqliteDbConnection;
    protected static $sqlserverDbConnection;
    protected static $csvDbConnection;

    protected static $logger;


    public static function setUpBeforeClass()
    {
        if (file_exists(static::CONFIG_FILE)) {
            self::$logger = new Logger(basename(static::CONFIG_FILE));
        }
    }

    public function setUp()
    {
        if (!file_exists(static::CONFIG_FILE)) {
            $this->markTestSkipped('Required configuration file "'.static::CONFIG_FILE.'"'
                .' does not exist for test "'.__FILE__.'".');
        }
    }

    public function runEtl($logger, $configFile)
    {
        try {
            $redCapEtl = new RedCapEtl($logger, $configFile);
            $redCapEtl->run();

            # Get the database connections.
            $mysqlTask = $redCapEtl->getWorkflow()->getTaskByName('basic-demography-mysql');
            self::$mysqlDbConnection = $mysqlTask->getDbConnection();

            $postgresqlTask = $redCapEtl->getWorkflow()->getTaskByName('basic-demography-postgresql');
            self::$postgresqlDbConnection = $postgresqlTask->getDbConnection();

            $sqliteTask = $redCapEtl->getWorkflow()->getTaskByName('basic-demography-sqlite');
            self::$sqliteDbConnection = $sqliteTask->getDbConnection();

            $sqlserverTask = $redCapEtl->getWorkflow()->getTaskByName('basic-demography-sqlserver');
            self::$sqlserverDbConnection = $sqlserverTask->getDbConnection();

            $csvTask = $redCapEtl->getWorkflow()->getTaskByName('basic-demography-csv');
            self::$csvDbConnection = $csvTask->getDbConnection();
        } catch (Exception $exception) {
            $logger->logException($exception);
            $logger->log('Processing failed.');
            throw $exception; // re-throw the exception
        }
    }

    public function testTables()
    {
        $hasException = false;
        $exceptionMessage = '';
        try {
            $this->runEtl(static::$logger, static::CONFIG_FILE);
        } catch (EtlException $exception) {
            $hasException = true;
            $exceptionMessage = $exception->getMessage();
        }
        $this->assertFalse($hasException, 'Run ETL exception check: '.$exceptionMessage);

        #-----------------------
        # Column names check
        #-----------------------
        $expectedColumns = [
            'demographics_id',
            'redcap_data_source',
            'record_id',
            'first_name',
            'last_name',
            'address',
            'telephone',
            'email',
            'dob',
            'ethnicity',
            'race___0',
            'race___1',
            'race___2',
            'race___3',
            'race___4',
            'race___5',
            'sex',
            'height',
            'weight',
            'bmi',
            'comments'
        ];

        $columns = self::$mysqlDbConnection->getTableColumnNames('multidb_demographics');
        $this->assertEquals($expectedColumns, $columns, 'MySQL column name check');

        $columns = self::$postgresqlDbConnection->getTableColumnNames('multidb_demographics');
        $this->assertEquals($expectedColumns, $columns, 'PostgreSQL column name check');

        $columns = self::$sqliteDbConnection->getTableColumnNames('multidb_demographics');
        $this->assertEquals($expectedColumns, $columns, 'SQLite column name check');

        $columns = self::$sqlserverDbConnection->getTableColumnNames('multidb_demographics');
        $this->assertEquals($expectedColumns, $columns, 'SQL Server column name check');

        $columns = self::$csvDbConnection->getTableColumnNames('multidb_demographics');
        $this->assertEquals($expectedColumns, $columns, 'CSV column name check');

        #-------------------------------------------
        # table "basic_demography" row count check
        #-------------------------------------------
        $mysqlData      = self::$mysqlDbConnection->getData('multidb_demographics', 'demographics_id');
        $postgresqlData = self::$postgresqlDbConnection->getData('multidb_demographics', 'demographics_id');
        $sqliteData     = self::$sqliteDbConnection->getData('multidb_demographics', 'demographics_id');
        $sqlserverData  = self::$sqlserverDbConnection->getData('multidb_demographics', 'demographics_id');
        $csvData        = self::$csvDbConnection->getData('multidb_demographics', 'demographics_id');

        $this->assertEquals(100, count($mysqlData), 'mysql demographics row count check');
        $this->assertEquals(100, count($postgresqlData), 'postgresql demographics row count check');
        $this->assertEquals(100, count($sqliteData), 'sqlite demographics row count check');
        $this->assertEquals(100, count($sqlserverData), 'sqlserver demographics row count check');
        $this->assertEquals(100, count($csvData), 'csv demographics row count check');

        #------------------------------------------------
        # Check the REDCap data sources
        #------------------------------------------------
        $dataSources = array_column($mysqlData, 'redcap_data_source');
        $expectedDataSources = array_fill(0, 100, 1);
        $this->assertEquals($expectedDataSources, $dataSources, 'MySQL data source check');

        $dataSources = array_column($postgresqlData, 'redcap_data_source');
        $expectedDataSources = array_fill(0, 100, 2);
        $this->assertEquals($expectedDataSources, $dataSources, 'PostgreSQL data source check');

        $dataSources = array_column($sqliteData, 'redcap_data_source');
        $expectedDataSources = array_fill(0, 100, 3);
        $this->assertEquals($expectedDataSources, $dataSources, 'SQLite data source check');

        $dataSources = array_column($sqlserverData, 'redcap_data_source');
        $expectedDataSources = array_fill(0, 100, 4);
        $this->assertEquals($expectedDataSources, $dataSources, 'Sql Server data source check');

        $dataSources = array_column($csvData, 'redcap_data_source');
        $expectedDataSources = array_fill(0, 100, 5);
        $this->assertEquals($expectedDataSources, $dataSources, 'CSV data source check');


        #-----------------------------------------
        # Demographics IDs check
        #-----------------------------------------
        $demographicsIds = array_column($mysqlData, 'demographics_id');
        $expectedIds = range(1, 100);
        $this->assertEquals($expectedIds, $demographicsIds, 'MySQL demographics IDs check.');

        #-----------------------------------------
        # Record IDs check
        #-----------------------------------------
        $expectedRecordIds = range(1001, 1100);
        $recordIds = array_column($mysqlData, 'record_id');
        $this->assertEquals($expectedRecordIds, $recordIds, 'MySQL record IDs check.');

        #--------------------------------
        # First row check
        #--------------------------------
        $expectedFirstRow = [
            'demographics_id'     => '1',
            'redcap_data_source'  => '1',
            'record_id'           => '1001',
            'first_name'          => "Katherine",
            'last_name'           => "Huels",
            'address'             => "316 Goodwin Lights Suite 463\nPort Marietta, NV 35323-5627",
            'telephone'           => "(759) 257-3524",
            'email'               => "katherine.huels@mailinator.com",
            'dob'                 => "1955-05-07",
            'ethnicity'           => '0',
            'race___0'            => '0',
            'race___1'            => '0',
            'race___2'            => '0',
            'race___3'            => '0',
            'race___4'            => '1',
            'race___5'            => '0',
            'sex'                 => '0',
            'height'              => '174.0',
            'weight'              => '57.0',
            'bmi'                 => '18.8',
            'comments'            => ''
        ];

        $actualFirstRow = $mysqlData[0];

        SystemTestsUtil::convertCsvRowValues($expectedFirstRow);
        SystemTestsUtil::convertCsvRowValues($actualFirstRow);

        $this->assertEquals($expectedFirstRow, $actualFirstRow, 'MySQL first row check');

        #--------------------------------
        # Last row check
        #--------------------------------
        $expectedLastRow = [
            "demographics_id"      => 100,
            "redcap_data_source"   => 1,
            "record_id"            => "1100",
            "first_name"           => "Ella",
            "last_name"            => "Kunze",
            "address"              => "26346 Kenyatta Fords\nStoltenbergville, MN 57828-4095",
            "telephone"            => "(714) 207-0230",
            "email"                => "ella.kunze@mailinator.com",
            "dob"                  => "1988-01-19",
            "ethnicity"            => 1,
            "race___0"             => 0,
            "race___1"             => 0,
            "race___2"             => 0,
            "race___3"             => 0,
            "race___4"             => 1,
            "race___5"             => 0,
            "sex"                  => 0,
            "height"               => 172,
            "weight"               => 64,
            "bmi"                  => 21.6,
            "comments"             => ""
        ];

        $actualLastRow = $mysqlData[count($mysqlData)-1];
        $this->assertEquals($expectedLastRow, $actualLastRow, 'MySQL last row check');


        #------------------------------------------------------------------------------------------------------
        # Set redcap_data_source to be equal for all data, so that they can be compared
        # (the redcap_data_source field is the only field that should be different for the different databases)
        #------------------------------------------------------------------------------------------------------
        for ($i = 0; $i < 100; $i++) {
            $postgresqlData[$i]['redcap_data_source'] = 1;
            $sqliteData[$i]['redcap_data_source']     = 1;
            $sqlserverData[$i]['redcap_data_source']  = 1;
            $csvData[$i]['redcap_data_source']        = 1;
        }

        SystemTestsUtil::convertMapValues($mysqlData);
        SystemTestsUtil::convertMapValues($postgresqlData);
        SystemTestsUtil::convertMapValues($sqliteData);
        SystemTestsUtil::convertMapValues($sqlserverData);
        SystemTestsUtil::convertMapValues($csvData);

        $this->assertEquals($mysqlData, $postgresqlData, 'MySQL vs. PostgreSQL data');
        $this->assertEquals($mysqlData, $sqliteData, 'MySQL vs. SQLite data');
        $this->assertEquals($mysqlData, $sqlserverData, 'MySQL vs. SQL Server data');
        $this->assertEquals($mysqlData, $csvData, 'MySQL vs. CSV data');

        #================================================================================
        # Label view tests
        #================================================================================

        # Check column names
        $columns = self::$mysqlDbConnection->getTableColumnNames('multidb_demographics_label_view');
        $this->assertEquals($expectedColumns, $columns, 'MySQL label view column name check');
    }
}

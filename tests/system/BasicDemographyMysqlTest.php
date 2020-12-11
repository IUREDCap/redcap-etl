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
class BasicDemographyMysqlTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/basic-demography-mysql.ini';

    const DATA_TABLE = 'basic_demography';

    protected static $dbConnection;
    protected static $logger;

    
    public static function setUpBeforeClass()
    {
        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('basic_demography_mysql_test');
        }
    }

    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
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
            'basic_demography_id' => 1,
            'redcap_data_source'  => 1,
            'record_id'           => '1001',
            'first_name'          => "Katherine",
            'last_name'           => "Huels",
            'address'             => "316 Goodwin Lights Suite 463\nPort Marietta, NV 35323-5627",
            'phone'               => "(759) 257-3524",
            'email'               => "katherine.huels@mailinator.com",
            'birthdate'           => "1955-05-07",
            'ethnicity'           => 0,
            'race___0'            => 0,
            'race___1'            => 0,
            'race___2'            => 0,
            'race___3'            => 0,
            'race___4'            => 1,
            'race___5'            => 0,
            'sex'                 => 0,
            'height'              => 174,
            'weight'              => 57,
            'bmi'                 => 18.8,
            'comments'            => ''
        ];

        $firstRow = $data[0];
        $this->assertEquals($expectedFirstRow, $firstRow, 'First row check');

        $expectedLastRow = [
            'basic_demography_id' => 100,
            'redcap_data_source'  => 1,
            'record_id'           => 1100,
            'first_name'          => "Ella",
            'last_name'           => "Kunze",
            'address'             => "26346 Kenyatta Fords\nStoltenbergville, MN 57828-4095",
            'phone'               => "(714) 207-0230",
            'email'               => "ella.kunze@mailinator.com",
            'birthdate'           => "1988-01-19",
            'ethnicity'           => 1,
            'race___0'            => 0,
            'race___1'            => 0,
            'race___2'            => 0,
            'race___3'            => 0,
            'race___4'            => 1,
            'race___5'            => 0,
            'sex'                 => 0,
            'height'              => 172,
            'weight'              => 64,
            'bmi'                 => 21.6,
            'comments'            => ''
        ];

        $lastRow = $data[count($data) - 1];
        $this->assertEquals($expectedLastRow, $lastRow, 'Last row check');

        #----------------------------------------------
        # Label view tests
        #----------------------------------------------
        $data = self::$dbConnection->getData('basic_demography_label_view', 'basic_demography_id');

        # Data not mull check
        $this->assertNotNull($data, 'Data not null check');

        # Row count check
        $this->assertEquals(100, count($data), 'Row count');

        # Column names check
        $columns = self::$dbConnection->getTableColumnNames('basic_demography_label_view');
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

        $expectedFirstRow['sex']       = 'Female';
        $expectedFirstRow['ethnicity'] = 'Hispanic or Latino';
        $expectedFirstRow['race___0']  = '0';
        $expectedFirstRow['race___1']  = '0';
        $expectedFirstRow['race___2']  = '0';
        $expectedFirstRow['race___3']  = '0';
        $expectedFirstRow['race___4']  = 'White';
        $expectedFirstRow['race___5']  = '0';
        $firstRow = $data[0];
        $this->assertEquals($expectedFirstRow, $firstRow, 'First row check');

        $lastRow = $data[count($data) - 1];
        $expectedLastRow['sex']       = 'Female';
        $expectedLastRow['ethnicity'] = 'NOT Hispanic or Latino';
        $expectedLastRow['race___0']  = '0';
        $expectedLastRow['race___1']  = '0';
        $expectedLastRow['race___2']  = '0';
        $expectedLastRow['race___3']  = '0';
        $expectedLastRow['race___4']  = 'White';
        $expectedLastRow['race___5']  = '0';
        $this->assertEquals($expectedLastRow, $lastRow, 'Last row check');
    }
}

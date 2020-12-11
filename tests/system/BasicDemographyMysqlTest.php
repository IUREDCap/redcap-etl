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
    const WEIGHT_TIME_FIELD_DECLARATION = "DATE_FORMAT(weight_time, '%Y-%m-%d %H:%i') as 'weight_time'";
        
    const CONFIG_FILE = __DIR__.'/../config/basic-demography-mysql.ini';

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
        $data = self::$dbConnection->getData('basic_demography', 'basic_demography_id');
        $this->assertNotNull($data, 'Data not null check');

        #print_r($data);

        $this->assertEquals(100, count($data), 'Row count');

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

/*
            [basic_demography_id] => 1
            [redcap_data_source] => 1
            [record_id] => 1001
            [first_name] => Katherine
            [last_name] => Huels
            [address] => 316 Goodwin Lights Suite 463
Port Marietta, NV 35323-5627
            [phone] => (759) 257-3524
            [email] => katherine.huels@mailinator.com
            [birthdate] => 1955-05-07
            [ethnicity] => 0
            [race___0] => 0
            [race___1] => 0
            [race___2] => 0
            [race___3] => 0
            [race___4] => 1
            [race___5] => 0
            [sex] => 0
            [height] => 174
            [weight] => 57
            [bmi] => 18.8
            [comments] =>
 */

    }
}

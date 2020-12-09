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
class WorkflowBasicDemographyMysqlTest extends TestCase
{
    const WEIGHT_TIME_FIELD_DECLARATION = "DATE_FORMAT(weight_time, '%Y-%m-%d %H:%i') as 'weight_time'";
        
    const CONFIG_FILE = __DIR__.'/../config/workflow-basic-demography-mysql.ini';

    protected static $dbConnection;
    protected static $logger;

    
    public static function setUpBeforeClass()
    {
        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('workflow-basic-demography-mysql-test');
        }
    }

    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped('Required configuration file "'.CONFIG_FILE.'"'
                .' does not exist for test "'.__FILE__.'".');
        }
    }

    public function runEtl($logger, $configFile)
    {
        try {
            $redCapEtl = new RedCapEtl($logger, $configFile);
            $redCapEtl->run();

            # Get the database connection. All tasks for this test use the same
            # one, so you can get it from any of the tasks.
            $firstTask = $redCapEtl->getTask(0);
            self::$dbConnection = $firstTask->getDbConnection();
        } catch (Exception $exception) {
            $logger->logException($exception);
            $logger->log('Processing failed.');
            throw $exception; // re-throw the exception
        }
    }

    public function testTables()
    {
        # $this->dropTablesAndViews(static::$dbConnection);

        $hasException = false;
        $exceptionMessage = '';
        try {
            $this->runEtl(static::$logger, static::CONFIG_FILE);
        } catch (EtlException $exception) {
            $hasException = true;
            $exceptionMessage = $exception->getMessage();
        }
        $this->assertFalse($hasException, 'Run ETL exception check: '.$exceptionMessage);

        #-------------------------------------------
        # table "basic_demography" row count check
        #-------------------------------------------
        $actualData = self::$dbConnection->getData('basic_demography');

        $this->assertEquals(300, count($actualData), 'basic_demography row count check');

        $basicDemographyIds = array_column($actualData, 'basic_demography_id');
        $expectedIds = range(1, 300);
        $this->assertEquals($expectedIds, $basicDemographyIds, 'Basic Demography ID check.');
    }


    public function dropTablesAndViews($dbConnection)
    {
        #$dbConnection->exec("DROP TABLE IF EXISTS basic_demography");
    }
}

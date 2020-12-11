<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * General tests class for the basic demography workflow tests.
 */
class WorkflowBasicDemographySystemTest extends TestCase
{
    const CONFIG_FILE ='';

    protected static $dbConnection;
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
        $actualData = self::$dbConnection->getData('basic_demography', 'basic_demography_id');

        $this->assertEquals(300, count($actualData), 'basic_demography row count check');

        #-----------------------------------------
        # Basic demography IDs check
        #-----------------------------------------
        $basicDemographyIds = array_column($actualData, 'basic_demography_id');
        $expectedIds = range(1, 300);
        $this->assertEquals($expectedIds, $basicDemographyIds, 'Basic Demography IDs check.');

        #-----------------------------------------
        # Record IDs check
        #-----------------------------------------
        $expectedRecordIds = array_merge(range(1001, 1100), range(1001, 1100), range(1001, 1100));
        $recordIds = array_column($actualData, 'record_id');
        $this->assertEquals($expectedRecordIds, $recordIds, 'Record IDs check.');

        # print "\nCLASS: ".get_class($this)."\n\n";

        /*
        if (!($this instanceOf WorkflowBasicDemographyCsvTest)) {
            $actualLastRow = $actualData[count($actualData)-1];
            $expectedLastRow = [
                "basic_demography_id" => 300,
                "redcap_data_source" => 4,
                "record_id" => "1100",
                "first_name"=> "Ella",
                "last_name" => "Kunze",
                "address" => "26346 Kenyatta Fords\nStoltenbergville, MN 57828-4095",
                "phone" => "(714) 207-0230",
                "email" => "ella.kunze@mailinator.com",
                "birthdate" => "1988-01-19",
                "ethnicity" => 1,
                "race___0" => 0,
                "race___1" => 0,
                "race___2" => 0,
                "race___3" => 0,
                "race___4" => 1,
                "race___5" => 0,
                "sex" => 0,
                "height" => 172,
                "weight" => 64,
                "bmi" => 21.6,
                "comments" => "",
                "dob" => "1988-01-19"
            ];
            $this->assertEquals($expectedLastRow, $actualLastRow, 'Last row check');
            print_r($actualLastRow);
        }
        */
    }
}

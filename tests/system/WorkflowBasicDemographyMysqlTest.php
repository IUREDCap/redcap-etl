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

    protected static $dbh;
    protected static $logger;

    
    public static function setUpBeforeClass()
    {
        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('workflow1-mysql-test');

            $configuration = new WorkflowConfig();
            $configuration->set(self::$logger, self::CONFIG_FILE);
            $taskConfigs = $configuration->getTaskConfigs();

            list($dbHost, $dbUser, $dbPassword, $dbName) = ($taskConfigs[0])->getMySqlConnectionInfo();
            $dsn = 'mysql:dbname='.$dbName.';host='.$dbHost;
            try {
                self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
            } catch (Exception $exception) {
                print "ERROR - database connection error: ".$exception->getMessage()."\n";
            }
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
        } catch (Exception $exception) {
            $logger->logException($exception);
            $logger->log('Processing failed.');
            throw $exception; // re-throw the exception
        }
    }

    public function testTables()
    {
        # $this->dropTablesAndViews(static::$dbh);

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
        # table "cardiovascular" row count check
        #-------------------------------------------
        $sql = 'SELECT COUNT(*) FROM cardiovascular';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchColumn(0);
        $this->assertEquals(100, $actualData, 'Cardiovascular row count check');

        #-----------------------------------------------
        # table "contact_information" row count check
        #-----------------------------------------------
        $sql = 'SELECT COUNT(*) FROM contact_information';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchColumn(0);
        $this->assertEquals(100, $actualData, 'Contact information row count check');
    }


    public function dropTablesAndViews($dbh)
    {
        $dbh->exec("DROP TABLE IF EXISTS basic_demography");
    }
}

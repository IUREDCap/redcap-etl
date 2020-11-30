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
class Workflow1MysqlTest extends RepeatingEventsTests
{
    const WEIGHT_TIME_FIELD_DECLARATION = "DATE_FORMAT(weight_time, '%Y-%m-%d %H:%i') as 'weight_time'";
        
    const CONFIG_FILE = __DIR__.'/../config/workflow1-mysql.ini';

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

        $sql = 'SELECT COUNT(*) FROM cardiovascular';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchColumn(0);
        $this->assertEquals(100, $actualData, 'Cardiovascular row count check');
    }


    public function dropTablesAndViews($dbh)
    {
        $dbh->exec("DROP TABLE IF EXISTS cardiovascular");
        $dbh->exec("DROP TABLE IF EXISTS cardiovascular_repeating_events");
        $dbh->exec("DROP TABLE IF EXISTS cardiovascular_repeating_instruments");
        $dbh->exec("DROP TABLE IF EXISTS contact_information");
        $dbh->exec("DROP TABLE IF EXISTS contact_information_label_view");
        $dbh->exec("DROP TABLE IF EXISTS demographics");
        $dbh->exec("DROP TABLE IF EXISTS demographics_label_view");
        $dbh->exec("DROP TABLE IF EXISTS emergency_contacts");
        $dbh->exec("DROP TABLE IF EXISTS enrollment");
        $dbh->exec("DROP TABLE IF EXISTS enrollment_label_view");
        $dbh->exec("DROP TABLE IF EXISTS etl_event_log");
        $dbh->exec("DROP TABLE IF EXISTS etl_log");
        $dbh->exec("DROP TABLE IF EXISTS redcap_metadata");
        $dbh->exec("DROP TABLE IF EXISTS redcap_metadataredcap_metadata");
        $dbh->exec("DROP TABLE IF EXISTS redcap_project_info");
        $dbh->exec("DROP TABLE IF EXISTS root");
        $dbh->exec("DROP TABLE IF EXISTS weight");
        $dbh->exec("DROP TABLE IF EXISTS weight_repeating_events");
        $dbh->exec("DROP TABLE IF EXISTS weight_repeating_instruments");
    }
}

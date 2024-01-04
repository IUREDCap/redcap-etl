<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs tests of auto-generation of transformation rules using MySQL as the database.
 */
class SqlServerAutoRulesTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/repeating-events-sqlserver.ini';

    const TEST_DATA_DIR   = __DIR__.'/../data/';     # directory with test data comparison files

    private static $dbh;
    private static $logger;

    public static function setUpBeforeClass(): void
    {
        if (extension_loaded('sqlsrv') && extension_loaded('pdo_sqlsrv') && file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('repeating_events_system_test');

            $configuration = new TaskConfig();
            $configuration->set(self::$logger, self::CONFIG_FILE);

            list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getSqlServerConnectionInfo();
            $dsn = 'sqlsrv:database=' . $dbName . ';server=' . $dbHost . ';Encrypt=0';
            try {
                self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
            } catch (Exception $exception) {
                print "ERROR - database connection error: ".$exception->getMessage()."\n";
            }

            self::dropTablesAndViews(self::$dbh);

            self::runEtl();
        }
    }


    public function setUp(): void
    {
        if (!extension_loaded('sqlsrv') || !extension_loaded('pdo_sqlsrv')) {
            $this->markTestSkipped('The sqlsrv and pdo_sqlsrv drivers are not available.');
        } elseif (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }

    private static function dropTablesAndViews($dbh)
    {
        $dbh->exec("DROP TABLE IF EXISTS re_baseline");
        $dbh->exec("DROP TABLE IF EXISTS re_home_weight_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_home_cardiovascular_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_baseline_and_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_baseline_and_home_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_visits_and_home_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_all_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_enrollment");
    }


    public static function runEtl()
    {
        try {
            $configuration = new TaskConfig();
            $configuration->set(self::$logger, self::CONFIG_FILE);
            
            $properties = $configuration->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = TaskConfig::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';
                    
            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }


    public function testEnrollmentTable()
    {
        $sql = 'SELECT '
            .' enrollment_id, redcap_data_source, record_id, registration_date, first_name, last_name, '
            .' birthdate, registration_age, gender, '
            .' race___0, race___1, race___2, race___3, race___4, race___5'
            .' FROM enrollment ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_enrollment.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testEnrollmentView()
    {
        $sql = 'SELECT '
            .' enrollment_id, redcap_data_source, record_id, registration_date, first_name, last_name, '
            .' birthdate, registration_age, gender, '
            .' race___0, race___1, race___2, race___3, race___4, race___5'
            .' FROM enrollment_label_view ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_enrollment_label_view.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }
}

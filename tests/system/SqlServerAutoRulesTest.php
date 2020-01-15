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

    public static function setUpBeforeClass()
    {
        if (!extension_loaded('sqlsrv') || !extension_loaded('pdo_sqlsrv')) {
            $this->markTestSkipped('The sqlsrv and pdo_sqlsrv drivers are not available.');
        }

        self::$logger = new Logger('repeating_events_system_test');

        $configuration = new Configuration(self::$logger, self::CONFIG_FILE);

        list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getSqlServerConnectionInfo();
        $dsn = 'sqlsrv:database='.$dbName.';server='.$dbHost;
        try {
            self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
        } catch (Exception $exception) {
            print "ERROR - database connection error: ".$exception->getMessage()."\n";
        }

        self::dropTablesAndViews(self::$dbh);

        self::runEtl();
    }

    private static function dropTablesAndViews($dbh)
    {
        $dbh->exec("DROP TABLE IF EXISTS root");
        $dbh->exec("DROP TABLE IF EXISTS all_visits");
        $dbh->exec("DROP TABLE IF EXISTS baseline");
        $dbh->exec("DROP TABLE IF EXISTS baseline_and_home_visits");
        $dbh->exec("DROP TABLE IF EXISTS baseline_and_visits");
        $dbh->exec("DROP TABLE IF EXISTS enrollment");
        $dbh->exec("DROP VIEW  IF EXISTS enrollment_label_view");
        $dbh->exec("DROP TABLE IF EXISTS home_cardiovascular_visits");
        $dbh->exec("DROP TABLE IF EXISTS home_weight_visits");
        $dbh->exec("DROP TABLE IF EXISTS visits");
        $dbh->exec("DROP TABLE IF EXISTS visits_and_home_visits");
    }


    public static function runEtl()
    {
        try {
            $configuration = new Configuration(self::$logger, self::CONFIG_FILE);
            
            $properties = $configuration->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = Configuration::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';
                    
            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }

    /**
     * Convert CSV data into a map format that matches the output
     * of PDO's fetchall method.
     */
    private function convertCsvToMap($csv)
    {
        $map = array();
        $header = $csv[0];
        for ($i = 1; $i < count($csv); $i++) {
            $row = array();
            for ($j = 0; $j < count($header); $j++) {
                $key   = $header[$j];
                $value = $csv[$i][$j];
                $row[$key] = $value;
            }
            array_push($map, $row);
        }
        return $map;
    }


    public function testEnrollmentTable()
    {
        $sql = 'SELECT '
            .' enrollment_id, record_id, registration_date, first_name, last_name, '
            .' birthdate, registration_age, gender, '
            .' race___0, race___1, race___2, race___3, race___4, race___5'
            .' FROM enrollment ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_enrollment.csv');
        $expectedData = $parser2->parse();

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testEnrollmentView()
    {
        $sql = 'SELECT '
            .' enrollment_id, record_id, registration_date, first_name, last_name, '
            .' birthdate, registration_age, gender, '
            .' race___0, race___1, race___2, race___3, race___4, race___5'
            .' FROM enrollment_label_view ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_enrollment_label_view.csv');
        $expectedData = $parser2->parse();

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }
}

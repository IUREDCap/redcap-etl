<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating events" tests using SQL Server as the database.
 */
class RepeatingEventsSqlServerTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/repeating-events-sqlserver.ini';

    const TEST_DATA_DIR   = __DIR__.'/../data/';     # directory with test data comparison files

    private static $dbh;
    private static $logger;

    public function setUp()
    {
        #These tests depend on the pdo_sqlsrv driver being installed.
        #If it isn't loaded in PHP, all tests will be skipped.
     
        if (!extension_loaded('pdo_sqlsrv')) {
            $this->markTestSkipped('The pdo_sqlsrv driver is not available.');
        }
    }

    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('repeating_events_system_test_sql_server');

        if (extension_loaded('pdo_sqlsrv')) {
            $configuration = new Configuration(self::$logger, self::CONFIG_FILE);
            $dbString = $configuration->getDbConnection();
            list($dbType, $dbHost, $dbUser, $dbPassword, $dbName) = explode(":", $dbString);
            $driver = 'sqlsrv';

            $dsn = "$driver:server=$dbHost ; Database=$dbName";

            try {
                self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
            } catch (Exception $exception) {
                print "ERROR - database connection error: ".$exception->getMessage()."\n";
            }

            self::dropTablesAndViews(self::$dbh);

            self::runEtl();
        }
    }


    private static function dropTablesAndViews($dbh)
    {
        $dbh->exec("DROP TABLE IF EXISTS re_all_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_baseline");
        $dbh->exec("DROP TABLE IF EXISTS re_baseline_and_home_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_baseline_and_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_enrollment");
        $dbh->exec("DROP VIEW  IF EXISTS re_enrollment_label_view");
        $dbh->exec("DROP TABLE IF EXISTS re_home_cardiovascular_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_home_weight_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_visits_and_home_visits");
    }


    public static function runEtl()
    {
        try {
            $redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }


    public function testAllVisitsTable()
    {
        $sql = 'SELECT '
            .' re_all_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .", FORMAT(weight_time, 'yyyy-MM-dd HH:mm') as 'weight_time' "
            .', weight_kg '
            .', height_m '
            .', cardiovascular_date '
            .', hdl_mg_dl '
            .', ldl_mg_dl '
            .', triglycerides_mg_dl '
            .', diastolic1 '
            .', diastolic2 '
            .', diastolic3 '
            .', systolic1 '
            .', systolic2 '
            .', systolic3 '
            .' FROM re_all_visits ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_all_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testAllVisitsTable');
    }

    public function testBaselineTable()
    {
        $sql = 'SELECT '
            .' re_baseline_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .", FORMAT(weight_time, 'yyyy-MM-dd HH:mm') as 'weight_time' "
            .', weight_kg '
            .', height_m '
            .', cardiovascular_date '
            .', hdl_mg_dl '
            .', ldl_mg_dl '
            .', triglycerides_mg_dl '
            .', diastolic1 '
            .', diastolic2 '
            .', diastolic3 '
            .', systolic1 '
            .', systolic2 '
            .', systolic3 '
            .' FROM re_baseline ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_baseline.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testBaselineTable');
    }

    public function testBaselineAndHomeVisitsTable()
    {
        $sql = 'SELECT '
            .' re_baseline_and_home_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .", FORMAT(weight_time, 'yyyy-MM-dd HH:mm') as 'weight_time' "
            .', weight_kg '
            .', height_m '
            .', cardiovascular_date '
            .', hdl_mg_dl '
            .', ldl_mg_dl '
            .', triglycerides_mg_dl '
            .', diastolic1 '
            .', diastolic2 '
            .', diastolic3 '
            .', systolic1 '
            .', systolic2 '
            .', systolic3 '
            .' FROM re_baseline_and_home_visits ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_baseline_and_home_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testBaselineAndHomeVisitsTable');
    }


    public function testBaselineAndVisitsTable()
    {
        $sql = 'SELECT '
            .' re_baseline_and_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instance '
            .", FORMAT(weight_time, 'yyyy-MM-dd HH:mm') as 'weight_time' "
            .', weight_kg '
            .', height_m '
            .', cardiovascular_date '
            .', hdl_mg_dl '
            .', ldl_mg_dl '
            .', triglycerides_mg_dl '
            .', diastolic1 '
            .', diastolic2 '
            .', diastolic3 '
            .', systolic1 '
            .', systolic2 '
            .', systolic3 '
            .' FROM re_baseline_and_visits ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_baseline_and_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testBaselineAndVisitsTable');
    }

    public function testEnrollmentTable()
    {
        $sql = 'SELECT '
            .' enrollment_id, record_id, registration_date, first_name, last_name, '
            .' birthdate, registration_age, gender, '
            .' race___0, race___1, race___2, race___3, race___4, race___5'
            .' FROM re_enrollment ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_enrollment.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testEnrollmentTable');
    }

    public function testEnrollmentView()
    {
        $sql = 'SELECT '
            .' enrollment_id, record_id, registration_date, first_name, last_name, '
            .' birthdate, registration_age, gender, '
            .' race___0, race___1, race___2, race___3, race___4, race___5'
            .' FROM re_enrollment_label_view ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_enrollment_label_view.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testEnrollmentView');
    }

    public function testHomeCardioVascularVisitsTable()
    {
        $sql = 'SELECT '
            .' re_home_cardiovascular_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .', cardiovascular_date '
            .', hdl_mg_dl '
            .', ldl_mg_dl '
            .', triglycerides_mg_dl '
            .', diastolic1 '
            .', diastolic2 '
            .', diastolic3 '
            .', systolic1 '
            .', systolic2 '
            .', systolic3 '
            .' FROM re_home_cardiovascular_visits ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_home_cardiovascular_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals(
            $expectedData,
            $actualData,
            'Sql Server Repeating Events testHomeCardioVascularVisitsTable'
        );
    }

    public function testHomeWeightVisitsTable()
    {
        $sql = 'SELECT '
            .' re_home_weight_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .", FORMAT(weight_time, 'yyyy-MM-dd HH:mm') as 'weight_time' "
            .', weight_kg '
            .', height_m '
            .' FROM re_home_weight_visits ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_home_weight_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testHomeWeightVisitsTable');
    }

    public function testVisitsTable()
    {
        $sql = 'SELECT '
            .' re_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instance '
            .", FORMAT(weight_time, 'yyyy-MM-dd HH:mm') as 'weight_time' "
            .', weight_kg '
            .', height_m '
            .', cardiovascular_date '
            .', hdl_mg_dl '
            .', ldl_mg_dl '
            .', triglycerides_mg_dl '
            .', diastolic1 '
            .', diastolic2 '
            .', diastolic3 '
            .', systolic1 '
            .', systolic2 '
            .', systolic3 '
            .' FROM re_visits ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testVisitsTable');
    }


    public function testVisitsAndHomeVisitsTable()
    {
        $sql = 'SELECT '
            .' re_visits_and_home_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .", FORMAT(weight_time, 'yyyy-MM-dd HH:mm') as 'weight_time' "
            .', weight_kg '
            .', height_m '
            .', weight_complete '
            .', cardiovascular_date '
            .', hdl_mg_dl '
            .', ldl_mg_dl '
            .', triglycerides_mg_dl '
            .', diastolic1 '
            .', diastolic2 '
            .', diastolic3 '
            .', systolic1 '
            .', systolic2 '
            .', systolic3 '
            .', cardiovascular_complete '
            .' FROM re_visits_and_home_visits ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_visits_and_home_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData, 'Sql Server Repeating Events testVisitsAndHomeVisitsTable');
    }
}

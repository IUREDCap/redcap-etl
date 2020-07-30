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
abstract class RepeatingEventsTests extends TestCase
{
    const CONFIG_FILE = '';

    const TEST_DATA_DIR   = __DIR__.'/../data/';     # directory with test data comparison files
    
    const WEIGHT_TIME_FIELD_DECLARATION = "weight_time";

    protected static $dbh;
    protected static $logger;

    public function dropTablesAndViews($dbh)
    {
        $dbh->exec("DROP TABLE IF EXISTS re_all_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_baseline");
        $dbh->exec("DROP TABLE IF EXISTS re_baseline_and_home_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_baseline_and_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_home_cardiovascular_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_home_weight_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_visits");
        $dbh->exec("DROP TABLE IF EXISTS re_visits_and_home_visits");
        $dbh->exec("DROP VIEW  IF EXISTS re_enrollment_label_view");
        $dbh->exec("DROP TABLE IF EXISTS re_enrollment");
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

    public function testRepeatingEvents()
    {
        $this->dropTablesAndViews(static::$dbh);

        $hasException = false;
        $exceptionMessage = '';
        try {
            $this->runEtl(static::$logger, static::CONFIG_FILE);
        } catch (EtlException $exception) {
            $hasException = true;
            $exceptionMessage = $exception->getMessage();
        }
        $this->assertFalse($hasException, 'Run ETL exception check: '.$exceptionMessage);
        
        $this->checkAllVisitsTable();
        $this->checkBaselineTable();
        $this->checkBaselineAndHomeVisitsTable();
        $this->checkBaselineAndVisitsTable();
        $this->checkEnrollmentTable();
        $this->checkEnrollmentView();
        $this->checkHomeCardioVascularVisitsTable();
        $this->checkHomeWeightVisitsTable();
        $this->checkVisitsTable();
        $this->checkVisitsAndHomeVisitsTable();
    }

    public function checkAllVisitsTable()
    {
        $sql = 'SELECT '
            .' re_all_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .", ".static::WEIGHT_TIME_FIELD_DECLARATION." "
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
            .' FROM re_all_visits '
            .' ORDER BY re_all_visits_id '
            ;

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_all_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function checkBaselineTable()
    {
        $sql = 'SELECT '
            .' re_baseline_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .", ".static::WEIGHT_TIME_FIELD_DECLARATION." "
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
            .' FROM re_baseline '
            .' ORDER BY re_baseline_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_baseline.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }


    public function checkBaselineAndHomeVisitsTable()
    {
        $sql = 'SELECT '
            .' re_baseline_and_home_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .", ".static::WEIGHT_TIME_FIELD_DECLARATION." "
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
            .' FROM re_baseline_and_home_visits '
            .' ORDER BY re_baseline_and_home_visits_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_baseline_and_home_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }


    public function checkBaselineAndVisitsTable()
    {
        $sql = 'SELECT '
            .' re_baseline_and_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instance '
            .", ".static::WEIGHT_TIME_FIELD_DECLARATION." "
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
            .' FROM re_baseline_and_visits '
            .' ORDER BY re_baseline_and_visits_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_baseline_and_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function checkEnrollmentTable()
    {
        $sql = 'SELECT '
            .' enrollment_id, record_id, registration_date, first_name, last_name, '
            .' birthdate, registration_age, gender, '
            .' race___0, race___1, race___2, race___3, race___4, race___5'
            .' FROM re_enrollment '
            .' ORDER BY enrollment_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_enrollment.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function checkEnrollmentView()
    {
        $sql = 'SELECT '
            .' enrollment_id, record_id, registration_date, first_name, last_name, '
            .' birthdate, registration_age, gender, '
            .' race___0, race___1, race___2, race___3, race___4, race___5'
            .' FROM re_enrollment_label_view '
            .' ORDER BY enrollment_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_enrollment_label_view.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function checkHomeCardioVascularVisitsTable()
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
            .' FROM re_home_cardiovascular_visits '
            .' ORDER BY re_home_cardiovascular_visits_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_home_cardiovascular_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function checkHomeWeightVisitsTable()
    {
        $sql = 'SELECT '
            .' re_home_weight_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .", ".static::WEIGHT_TIME_FIELD_DECLARATION." "
            .', weight_kg '
            .', height_m '
            .' FROM re_home_weight_visits '
            .' ORDER BY re_home_weight_visits_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_home_weight_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function checkVisitsTable()
    {
        $sql = 'SELECT '
            .' re_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instance '
            .", ".static::WEIGHT_TIME_FIELD_DECLARATION." "
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
            .' FROM re_visits '
            .' ORDER BY re_visits_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }


    public function checkVisitsAndHomeVisitsTable()
    {
        $sql = 'SELECT '
            .' re_visits_and_home_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .", ".static::WEIGHT_TIME_FIELD_DECLARATION." "
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
            .' FROM re_visits_and_home_visits '
            .' ORDER BY re_visits_and_home_visits_id';

        $statement  = static::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_visits_and_home_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = SystemTestsUtil::convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }
}

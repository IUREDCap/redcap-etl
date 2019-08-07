<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating events" tests using MySQL as the database.
 */
class RepeatingEventsSqliteTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/repeating-events-sqlite.ini';

    const TEST_DATA_DIR   = __DIR__.'/../data/';     # directory with test data comparison files

    private static $dbh;
    private static $logger;

    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('repeating_events_sqlite_system_test');

        $configuration = new Configuration(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($type, $db) = explode(':', $dbConnection, 2);

        $dsn = 'sqlite:'.realpath($db);

        try {
            self::$dbh = new \PDO($dsn, null, null);
            self::$dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $exception) {
            print "ERROR - database connection error for db {$dsn}: ".$exception->getMessage()."\n";
            exit(1);
        }

        self::dropTablesAndViews(self::$dbh);

        self::runEtl();
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

    public function testAllVisitsTable()
    {
        $sql = 'SELECT '
            .' re_all_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instrument '
            .', redcap_repeat_instance '
            .', weight_time '
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testBaselineTable()
    {
        $sql = 'SELECT '
            .' re_baseline_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', weight_time '
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
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
            .', weight_time '
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }


    public function testBaselineAndVisitsTable()
    {
        $sql = 'SELECT '
            .' re_baseline_and_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instance '
            .', weight_time '
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
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
            .', weight_time '
            .', weight_kg '
            .', height_m '
            .' FROM re_home_weight_visits ORDER BY record_id';

        $statement  = self::$dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(self::TEST_DATA_DIR.'re_home_weight_visits.csv');
        $expectedData = $parser2->parse();

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }


    public function testVisitsTable()
    {
        $sql = 'SELECT '
            .' re_visits_id '
            .', enrollment_id '
            .', record_id '
            .', redcap_event_name '
            .', redcap_repeat_instance '
            .', weight_time '
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
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
            .', weight_time '
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

        $expectedData = $this->convertCsvToMap($expectedData);

        $this->assertEquals($expectedData, $actualData);
    }
}

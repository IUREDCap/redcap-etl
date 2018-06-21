<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for repeating events.
 */
class RepeatingEventsTest extends TestCase
{
    private static $redCapEtl;
    private static $config;

    private static $csvDir;
    private static $logger;
    private static $properties;

    private static $enrollmentCsvFile;
    private static $enrollmentCsvLabelFile;
    private static $baselineCsvFile;
    private static $visitsCsvFile;
    private static $homeWeightVisitsCsvFile;
    private static $homeCardiovascularVisitsCsvFile;


    const CONFIG_FILE = __DIR__.'/../config/repeating-events.ini';

    public static function setUpBeforeClass()
    {
        $app = basename(__FILE__, '.php');
        self::$logger = new Logger($app);

        self::$redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);

        #-----------------------------
        # Get the CSV directory
        #-----------------------------
        self::$config = self::$redCapEtl->getConfiguration();
        self::$csvDir = str_ireplace('CSV:', '', self::$config->getDbConnection());
        if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
            self::$csvDir .= DIRECTORY_SEPARATOR;
        }

        #-------------------------------------------
        # Set the files to use
        #-------------------------------------------
        self::$enrollmentCsvFile      = self::$csvDir . 're_enrollment.csv';
        self::$enrollmentCsvLabelFile = self::$csvDir . 're_enrollment'.self::$config->getlabelViewSuffix().'.csv';
        self::$baselineCsvFile        = self::$csvDir . 're_baseline.csv';

        self::$visitsCsvFile                   = self::$csvDir . 're_visits.csv';
        self::$homeWeightVisitsCsvFile         = self::$csvDir . 're_home_weight_visits.csv';
        self::$homeCardiovascularVisitsCsvFile = self::$csvDir . 're_home_cardiovascular_visits.csv';


        #-----------------------------------------------
        # Delete files from previous run (if any)
        #-----------------------------------------------
        if (file_exists(self::$enrollmentCsvFile)) {
            unlink(self::$enrollmentCsvFile);
        }
        if (file_exists(self::$enrollmentCsvLabelFile)) {
            unlink(self::$enrollmentCsvLabelFile);
        }
        if (file_exists(self::$baselineCsvFile)) {
            unlink(self::$baselineCsvFile);
        }
        if (file_exists(self::$visitsCsvFile)) {
            unlink(self::$visitsCsvFile);
        }
        if (file_exists(self::$homeWeightVisitsCsvFile)) {
            unlink(self::$homeWeightVisitsCsvFile);
        }
        if (file_exists(self::$homeCardiovascularVisitsCsvFile)) {
            unlink(self::$homeCardiovascularVisitsCsvFile);
        }
 

        #-------------------------
        # Run the ETL process
        #-------------------------
        try {
            self::$redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->logError('Processing failed.');
        }
    }


    public function testProject()
    {
        $this->assertNotNull(self::$redCapEtl, 'redCapEtl not null');

        #----------------------------------------------
        # Test the data project
        #----------------------------------------------
        $dataProject = self::$redCapEtl->getDataProject();
        $this->assertNotNull($dataProject, 'data project not null');

        #-----------------------------------------
        # Check that the project is longitudinal
        #-----------------------------------------
        $isLongitudinal = $dataProject->isLongitudinal();
        $this->assertTrue($isLongitudinal, 'is longitudinal');
    }

    public function testEnrollmentTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$enrollmentCsvFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/re_enrollment.csv'
        );
        $expectedCsv = $parser2->parse();

        $header = $csv[0];
        $this->assertEquals($header[1], 'record_id', 'Record id header test.');
        $this->assertEquals(101, count($csv), 're_enrollment row count check.');

        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');

        #-------------------------------------
        # Check Label View
        #-------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$enrollmentCsvLabelFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/re_enrollment_label_view.csv'
        );
        $expectedCsv = $parser2->parse();

        $this->assertEquals(101, count($csv), 're_enrollment row count check.');
        $this->assertEquals($expectedCsv, $csv, 'CSV label file check.');
    }


    public function testBaselineTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$baselineCsvFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/re_baseline.csv'
        );
        $expectedCsv = $parser2->parse();

        $header = $csv[0];
        $this->assertEquals(101, count($csv), 're_baseline row count check.');
        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testVisitsTable()
    {
            
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$visitsCsvFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/re_visits.csv'
        );
        $expectedCsv = $parser2->parse();

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 're_visits row count check.');

        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testHomeWeightVisitsTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$homeWeightVisitsCsvFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/re_home_weight_visits.csv'
        );
        $expectedCsv = $parser2->parse();

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 're_home_weight_visits row count check.');

        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testHomeCardiovascularVisitsTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$homeCardiovascularVisitsCsvFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/re_home_cardiovascular_visits.csv'
        );
        $expectedCsv = $parser2->parse();

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 're_home_cardiovascular_visits row count check.');

        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }
}

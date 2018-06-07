<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for repeating events.
 */
class RepeatingEventsTest extends TestCase
{
    private static $redCapEtl;

    private static $csvDir;
    private static $enrollmentCsvFile;
    private static $enrollmentCsvLabelFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/repeating-events.ini';

    public static function setUpBeforeClass()
    {
        $app = basename(__FILE__, '.php');
        self::$logger = new Logger($app);

        self::$redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
    }


    public function testEnrollmentTable()
    {
        try {
            $this->assertNotNull(self::$redCapEtl, 'redCapEtl not null');

            $config = self::$redCapEtl->getConfiguration();
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
            
            self::$enrollmentCsvFile      = self::$csvDir . 're_enrollment.csv';
            self::$enrollmentCsvLabelFile = self::$csvDir . 're_enrollment'.$config->getlabelViewSuffix().'.csv';
            # Try to delete the output files in case they exists from a previous run
            if (file_exists(self::$enrollmentCsvFile)) {
                unlink(self::$enrollmentCsvFile);
            }
            if (file_exists(self::$enrollmentCsvLabelFile)) {
                unlink(self::$enrollmentCsvLabelFile);
            }
            
            #----------------------------------------------
            # Test the data project
            #----------------------------------------------
            $dataProject = self::$redCapEtl->getDataProject();
            $this->assertNotNull($dataProject, 'data project not null');
            
            $isLongitudinal = $dataProject->isLongitudinal();
            $this->assertTrue($isLongitudinal, 'is longitudinal');
            
            #-------------------------
            # Run the ETL process
            #-------------------------
            self::$redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->logError('Processing failed.');
        }

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
        try {
            $this->assertNotNull(self::$redCapEtl, 'redCapEtl not null');

            $config = self::$redCapEtl->getConfiguration();
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
            
            $baselineCsvFile      = self::$csvDir . 're_baseline.csv';
            # Try to delete the output files in case they exists from a previous run
            if (file_exists($baselineCsvFile)) {
                unlink($baselineCsvFile);
            }
            
            #----------------------------------------------
            # Test the data project
            #----------------------------------------------
            $dataProject = self::$redCapEtl->getDataProject();
            $this->assertNotNull($dataProject, 'data project not null');
            
            $isLongitudinal = $dataProject->isLongitudinal();
            $this->assertTrue($isLongitudinal, 'is longitudinal');
            
            #-------------------------
            # Run the ETL process
            #-------------------------
            self::$redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->logError('Processing failed.');
        }

        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile($baselineCsvFile);
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
        try {
            $this->assertNotNull(self::$redCapEtl, 'redCapEtl not null');

            $config = self::$redCapEtl->getConfiguration();
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
            
            $visitsCsvFile      = self::$csvDir . 're_visits.csv';
            # Try to delete the output files in case they exists from a previous run
            if (file_exists($visitsCsvFile)) {
                unlink($visitsCsvFile);
            }
            
            #----------------------------------------------
            # Test the data project
            #----------------------------------------------
            $dataProject = self::$redCapEtl->getDataProject();
            $this->assertNotNull($dataProject, 'data project not null');
            
            $isLongitudinal = $dataProject->isLongitudinal();
            $this->assertTrue($isLongitudinal, 'is longitudinal');
            
            #-------------------------
            # Run the ETL process
            #-------------------------
            self::$redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->logError('Processing failed.');
        }

        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile($visitsCsvFile);
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
        try {
            $this->assertNotNull(self::$redCapEtl, 'redCapEtl not null');

            $config = self::$redCapEtl->getConfiguration();
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
            
            $homeWeightVisitsCsvFile      = self::$csvDir . 're_home_weight_visits.csv';
            # Try to delete the output files in case they exists from a previous run
            if (file_exists($homeWeightVisitsCsvFile)) {
                unlink($homeWeightVisitsCsvFile);
            }
            
            #----------------------------------------------
            # Test the data project
            #----------------------------------------------
            $dataProject = self::$redCapEtl->getDataProject();
            $this->assertNotNull($dataProject, 'data project not null');
            
            $isLongitudinal = $dataProject->isLongitudinal();
            $this->assertTrue($isLongitudinal, 'is longitudinal');
            
            #-------------------------
            # Run the ETL process
            #-------------------------
            self::$redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->logError('Processing failed.');
        }

        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile($homeWeightVisitsCsvFile);
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
        try {
            $this->assertNotNull(self::$redCapEtl, 'redCapEtl not null');

            $config = self::$redCapEtl->getConfiguration();
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
            
            $homeCardiovascularVisitsCsvFile      = self::$csvDir . 're_home_cardiovascular_visits.csv';
            # Try to delete the output files in case they exists from a previous run
            if (file_exists($homeCardiovascularVisitsCsvFile)) {
                unlink($homeCardiovascularVisitsCsvFile);
            }
            
            #----------------------------------------------
            # Test the data project
            #----------------------------------------------
            $dataProject = self::$redCapEtl->getDataProject();
            $this->assertNotNull($dataProject, 'data project not null');
            
            $isLongitudinal = $dataProject->isLongitudinal();
            $this->assertTrue($isLongitudinal, 'is longitudinal');
            
            #-------------------------
            # Run the ETL process
            #-------------------------
            self::$redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->logError('Processing failed.');
        }

        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile($homeCardiovascularVisitsCsvFile);
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

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for repeating events.
 */
class RepeatingEventsExtendedTest extends TestCase
{
    private static $redCapEtl;
    private static $config;

    private static $csvDir;
    private static $logger;
    private static $properties;

    private static $enrollmentCsvFile;
    private static $weightEventsSuffixesCsvFile;
    private static $weightAllSuffixesCsvFile;

    private static $etlLookupCsvFile;

    #private static $baselineCsvFile;
    #private static $visitsCsvFile;
    #private static $homeWeightVisitsCsvFile;
    #private static $homeCardiovascularVisitsCsvFile;

    #private static $baselineAndVisitsCsvFile;
    #private static $baselineAndHomeVisitsCsvFile;
    #private static $visitsAndHomeVisitsCsvFile;
    #private static $allVisitsCsvFile;


    const CONFIG_FILE = __DIR__.'/../config/repeating-events-extended.ini';

    const TEST_DATA_DIR   = __DIR__.'/../data/repeating-events-extended/';  # directory with test data comparison files


    public static function setUpBeforeClass(): void
    {
        $app = basename(__FILE__, '.php');
        self::$logger = new Logger($app);

        self::$redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);

        #-----------------------------
        # Get the CSV directory
        #-----------------------------
        self::$config = self::$redCapEtl->getTaskConfig(0);
        self::$csvDir = str_ireplace('CSV:', '', self::$config->getDbConnection());
        if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
            self::$csvDir .= DIRECTORY_SEPARATOR;
        }

        #-------------------------------------------
        # Set the files to use
        #-------------------------------------------
        self::$enrollmentCsvFile      = self::$csvDir . 'enrollment.csv';

        self::$weightEventsSuffixesCsvFile = self::$csvDir . 'weight_events_suffixes.csv';
        self::$weightAllSuffixesCsvFile    = self::$csvDir . 'weight_all_suffixes.csv';

        self::$etlLookupCsvFile = self::$csvDir . 'etl_lookup.csv';

        #self::$baselineCsvFile        = self::$csvDir . 'baseline.csv';

        #self::$visitsCsvFile                   = self::$csvDir . 'visits.csv';
        #self::$homeWeightVisitsCsvFile         = self::$csvDir . 'home_weight_visits.csv';
        #self::$homeCardiovascularVisitsCsvFile = self::$csvDir . 'home_cardiovascular_visits.csv';
        #self::$baselineAndVisitsCsvFile        = self::$csvDir . 'baseline_and_visits.csv';
        #self::$baselineAndHomeVisitsCsvFile    = self::$csvDir . 'baseline_and_home_visits.csv';
        #self::$visitsAndHomeVisitsCsvFile      = self::$csvDir . 'visits_and_home_visits.csv';
        #self::$allVisitsCsvFile                = self::$csvDir . 'all_visits.csv';


        #-----------------------------------------------
        # Delete files from previous run (if any)
        #-----------------------------------------------
        foreach (glob(self::$csvDir . '*.csv') as $filePath) {
            unlink($filePath);
        }

        #-------------------------
        # Run the ETL process
        #-------------------------
        try {
            self::$redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }


    public function testProject()
    {
        $this->assertNotNull(self::$redCapEtl, 'redCapEtl not null check');

        #----------------------------------------------
        # Test the data project
        #----------------------------------------------
        $dataProject = self::$redCapEtl->getDataProject(0);
        $this->assertNotNull($dataProject, 'data project not null check');

        #-----------------------------------------
        # Check that the project is longitudinal
        #-----------------------------------------
        $isLongitudinal = $dataProject->isLongitudinal();
        $this->assertTrue($isLongitudinal, 'is longitudinal check');
    }

    public function testEnrollmentTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $csv = CsvUtil::csvFileToArray(self::$enrollmentCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR . 'enrollment.csv');

        $header = $csv[0];
        $this->assertEquals($header[3], 'record_id', 'Record id header test.');
        $this->assertEquals(101, count($csv), 'Row count check.');

        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }

    public function testWeightEventsSuffixesTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $csv = CsvUtil::csvFileToArray(self::$weightEventsSuffixesCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR . 'weight_events_suffixes.csv');

        $header = $csv[0];
        $this->assertEquals($header[3], 'record_id', 'Record id header test.');
        $this->assertEquals(4, count($csv), 'Row count check.');

        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }

    public function testWeightAllSuffixesTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $csv = CsvUtil::csvFileToArray(self::$weightAllSuffixesCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR . 'weight_all_suffixes.csv');

        $header = $csv[0];
        $this->assertEquals($header[3], 'record_id', 'Record id header test.');
        $this->assertEquals(10, count($csv), 'Row count check.');

        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }

    public function testEtlLookupTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $csv = CsvUtil::csvFileToArray(self::$etlLookupCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR . 'etl_lookup.csv');

        $header = $csv[0];
        $this->assertEquals($header[0], 'lookup_id', 'Lookup ID header test.');
        $this->assertEquals(52, count($csv), 'Row count check.');

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    /*
    public function testBaselineTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $csv = CsvUtil::csvFileToArray(self::$baselineCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR.'re_baseline.csv');

        $header = $csv[0];
        $this->assertEquals(101, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$visitsCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR.'re_visits.csv');

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testHomeWeightVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$homeWeightVisitsCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR.'re_home_weight_visits.csv');

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testHomeCardiovascularVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$homeCardiovascularVisitsCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR.'re_home_cardiovascular_visits.csv');

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 'Row count check.');

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testBaselineAndVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$baselineAndVisitsCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR.'re_baseline_and_visits.csv');

        $header = $csv[0];
        $this->assertEquals(301, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testBaselineAndHomeVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$baselineAndHomeVisitsCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR.'re_baseline_and_home_visits.csv');

        $header = $csv[0];
        $this->assertEquals(501, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testVisitsAndHomeVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$visitsAndHomeVisitsCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR.'re_visits_and_home_visits.csv');

        $header = $csv[0];
        $this->assertEquals(601, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testAllVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$allVisitsCsvFile);
        $expectedCsv = CsvUtil::csvFileToArray(self::TEST_DATA_DIR.'re_all_visits.csv');

        $header = $csv[0];
        $this->assertEquals(701, count($csv), 're_all_visits row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }
     */
}

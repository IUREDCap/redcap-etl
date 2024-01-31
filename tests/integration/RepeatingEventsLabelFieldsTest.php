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
class RepeatingEventsLabelFieldsTest extends TestCase
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

    private static $baselineAndVisitsCsvFile;
    private static $baselineAndHomeVisitsCsvFile;
    private static $visitsAndHomeVisitsCsvFile;
    private static $allVisitsCsvFile;


    const CONFIG_FILE = __DIR__.'/../config/repeating-events-label-fields.ini';

    const TEST_DATA_DIR   = __DIR__.'/../data/';     # directory with test data comparison files

    const TABLE_PREFIX = 'lf_';


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
        self::$enrollmentCsvFile      = self::$csvDir . self::TABLE_PREFIX . 're_enrollment.csv';
        self::$enrollmentCsvLabelFile = self::$csvDir . self::TABLE_PREFIX
            . 're_enrollment'.self::$config->getlabelViewSuffix().'.csv';
        self::$baselineCsvFile        = self::$csvDir . self::TABLE_PREFIX . 're_baseline.csv';

        self::$visitsCsvFile                   = self::$csvDir . self::TABLE_PREFIX . 're_visits.csv';
        self::$homeWeightVisitsCsvFile         = self::$csvDir . self::TABLE_PREFIX . 're_home_weight_visits.csv';
        self::$homeCardiovascularVisitsCsvFile = self::$csvDir . self::TABLE_PREFIX
            . 're_home_cardiovascular_visits.csv';
        self::$baselineAndVisitsCsvFile        = self::$csvDir . self::TABLE_PREFIX . 're_baseline_and_visits.csv';
        self::$baselineAndHomeVisitsCsvFile    = self::$csvDir . self::TABLE_PREFIX . 're_baseline_and_home_visits.csv';
        self::$visitsAndHomeVisitsCsvFile      = self::$csvDir . self::TABLE_PREFIX . 're_visits_and_home_visits.csv';
        self::$allVisitsCsvFile                = self::$csvDir . self::TABLE_PREFIX . 're_all_visits.csv';


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

        if (file_exists(self::$baselineAndVisitsCsvFile)) {
            unlink(self::$baselineAndVisitsCsvFile);
        }

        if (file_exists(self::$baselineAndHomeVisitsCsvFile)) {
            unlink(self::$baselineAndHomeVisitsCsvFile);
        }

        if (file_exists(self::$visitsAndHomeVisitsCsvFile)) {
            unlink(self::$visitsAndHomeVisitsCsvFile);
        }
 
        if (file_exists(self::$allVisitsCsvFile)) {
            unlink(self::$allVisitsCsvFile);
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

        $file = self::TEST_DATA_DIR . self::TABLE_PREFIX . 're_enrollment.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals($header[2], 'record_id', 'Record id header test.');
        $this->assertEquals(101, count($csv), 'Row count check.');

        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');

        #--------------------------------------
        # Check that Label View does not exist
        #--------------------------------------
        $this->assertFileDoesNotExist(self::$enrollmentCsvLabelFile, 'No label view check');
    }


    public function testBaselineTable()
    {
        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $csv = CsvUtil::csvFileToArray(self::$baselineCsvFile);

        $file = self::TEST_DATA_DIR . 're_baseline.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals(101, count($csv), 'Row count check.');
        
        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);
        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$visitsCsvFile);

        $file = self::TEST_DATA_DIR . 're_visits.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);
        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testHomeWeightVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$homeWeightVisitsCsvFile);

        $file = self::TEST_DATA_DIR . 're_home_weight_visits.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);
        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testHomeCardiovascularVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$homeCardiovascularVisitsCsvFile);

        $file = self::TEST_DATA_DIR . 're_home_cardiovascular_visits.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals(201, count($csv), 'Row count check.');
        
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testBaselineAndVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$baselineAndVisitsCsvFile);

        $file = self::TEST_DATA_DIR . 're_baseline_and_visits.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals(301, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testBaselineAndHomeVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$baselineAndHomeVisitsCsvFile);

        $file = self::TEST_DATA_DIR . 're_baseline_and_home_visits.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals(501, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testVisitsAndHomeVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$visitsAndHomeVisitsCsvFile);

        $file = self::TEST_DATA_DIR . 're_visits_and_home_visits_label_fields.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals(601, count($csv), 'Row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }


    public function testAllVisitsTable()
    {
        $csv = CsvUtil::csvFileToArray(self::$allVisitsCsvFile);

        $file = self::TEST_DATA_DIR . 're_all_visits.csv';
        $expectedCsv = CsvUtil::csvFileToArray($file);

        $header = $csv[0];
        $this->assertEquals(701, count($csv), 're_all_visits row count check.');

        SystemTestsUtil::convertCsvValues($expectedCsv);
        SystemTestsUtil::convertCsvValues($csv);

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }
}

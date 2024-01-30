<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class BasicDemographyTestWithMock extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $csvLabelFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/basic-demography.ini';

    public static function setUpBeforeClass(): void
    {
    }


    public function testDemographyTable()
    {
        try {
            $app = basename(__FILE__, '.php');
            self::$logger = new Logger($app);

            $redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE, 'IU\REDCapETL\BasicDemographyProjectMock');
            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            $config = $redCapEtl->getTaskConfig(0);
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
            
            self::$csvFile      = self::$csvDir . 'basic_demography.csv';
            self::$csvLabelFile = self::$csvDir . 'basic_demography'.$config->getlabelViewSuffix().'.csv';
            # Try to delete the output file in case it exists from a previous run
            if (file_exists(self::$csvFile)) {
                unlink(self::$csvFile);
            }
            
            #----------------------------------------------
            # Test the data project
            #----------------------------------------------
            $dataProject = $redCapEtl->getDataProject(0);
            $this->assertNotNull($dataProject, 'data project not null');
            
            $isLongitudinal = $dataProject->isLongitudinal();
            $this->assertFalse($isLongitudinal, 'is longitudinal');
            
            #-------------------------
            # Run the ETL process
            #-------------------------
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        #---------------------------------------------------------------------
        # Check standard table with (coded) values for multipl-choice answers
        #---------------------------------------------------------------------
        $csv = CsvUtil::csvFileToArray(self::$csvFile);
        $expectedCsv = CsvUtil::csvFileToArray(__DIR__.'/../data/BasicDemography.csv');

        $header = $csv[0];
        $this->assertEquals($header[2], 'record_id', 'Record id header test.');
        $this->assertEquals(101, count($csv), 'Demography row count check.');

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');

        #-------------------------------------
        # Check Label View
        #-------------------------------------
        $csv = CsvUtil::csvFileToArray(self::$csvLabelFile);
        $expectedCsv = CsvUtil::csvFileToArray(__DIR__.'/../data/BasicDemographyLabelView.csv');

        $this->assertEquals($expectedCsv, $csv, 'CSV label file check.');
    }
}

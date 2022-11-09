<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class BasicDemographyTest extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $csvLabelFile;
    private static $csvLookupFile;
    private static $csvMetadataFile;
    private static $csvProjectInfoFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/basic-demography.ini';

    public static function setUpBeforeClass(): void
    {
    }


    public function testTables()
    {
        try {
            $app = basename(__FILE__, '.php');
            self::$logger = new Logger($app);

            $redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            $workflow = $redCapEtl->getWorkflow();
            $isStandaloneTask = $workflow->isStandaloneTask();
            $this->assertTrue($isStandaloneTask, 'Standalone task check');

            $config = $redCapEtl->getTaskConfig(0);
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
            
            self::$csvFile       = self::$csvDir . 'basic_demography.csv';
            self::$csvLabelFile  = self::$csvDir . 'basic_demography'.$config->getlabelViewSuffix().'.csv';
            self::$csvLookupFile = self::$csvDir . LookupTable::DEFAULT_NAME . '.csv';

            self::$csvMetadataFile    = self::$csvDir . MetadataTable::DEFAULT_NAME . '.csv';
            self::$csvProjectInfoFile = self::$csvDir . ProjectInfoTable::DEFAULT_NAME . '.csv';

            # Try to delete the output files in case it exists from a previous run
            if (file_exists(self::$csvFile)) {
                unlink(self::$csvFile);
            }
            if (file_exists(self::$csvLabelFile)) {
                unlink(self::$csvLabelFile);
            }
            if (file_exists(self::$csvLookupFile)) {
                unlink(self::$csvLookupFile);
            }
            if (file_exists(self::$csvMetadataFile)) {
                unlink(self::$csvMetadataFile);
            }
            if (file_exists(self::$csvProjectInfoFile)) {
                unlink(self::$csvProjectInfoFile);
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
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$csvFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/BasicDemography.csv'
        );
        $expectedCsv = $parser2->parse();

        $header = $csv[0];
        $this->assertEquals($header[2], 'record_id', 'Record id header test.');
        $this->assertEquals(101, count($csv), 'Demography row count check.');

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');

        #-------------------------------------
        # Check Label View
        #-------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$csvLabelFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/BasicDemographyLabelView.csv'
        );
        $expectedCsv = $parser2->parse();
        $this->assertEquals($expectedCsv, $csv, 'CSV label file check.');

        #-------------------------------------
        # Check Lookup Table File
        #-------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$csvLookupFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__ . '/../data/' . LookupTable::DEFAULT_NAME . '.csv'
        );
        $expectedCsv = $parser2->parse();
        $this->assertEquals($expectedCsv, $csv, 'CSV lookup table file check.');

        #-------------------------------------
        # Check Metadata Table File
        #-------------------------------------
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$csvMetadataFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__ . '/../data/' . MetadataTable::DEFAULT_NAME . '.csv'
        );
        $expectedCsv = $parser2->parse();
        $this->assertEquals($expectedCsv, $csv, 'CSV metadata table file check.');

        #-------------------------------------
        # Check Project Info Table File
        #-------------------------------------
        $this->assertFileExists(self::$csvProjectInfoFile, 'CSV project info file check.');
    }
}

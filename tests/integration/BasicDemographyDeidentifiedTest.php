<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Tests the data export filter for the de-identified setting.
 * Compares output this tests generates using an API token with
 * "fill data access" and the de-dentified filter
 * with data previously generated using an API token that only
 * has de-identified permission.
 */
class BasicDemographyDeidentifiedTest extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $csvLabelFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/basic-demography.ini';

    public static function setUpBeforeClass()
    {
    }


    public function testDemographicsTable()
    {
        try {
            $app = basename(__FILE__, '.php');
            self::$logger = new Logger($app);
            $this->assertNotNull(self::$logger);

            $tablePrefix = 'deidentified_';
            $tableName   = 'demographics.csv';

            self::$properties = parse_ini_file(self::CONFIG_FILE);
            self::$properties['transform_rules_source'] = '3';
            self::$properties['transform_rules_file'] = '';
            self::$properties['table_prefix'] = $tablePrefix;
            self::$properties['db_connection'] = 'CSV:tests/output';
            self::$properties['data_export_filter'] = '2';   // de-identified

            $redCapEtl = new RedCapEtl(self::$logger, self::$properties);
            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            $config = $redCapEtl->getConfiguration();

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }

            self::$csvFile = self::$csvDir . $tablePrefix. $tableName;

            # Try to delete the output file in case it exists from a previous run
            if (file_exists(self::$csvFile)) {
                unlink(self::$csvFile);
            }

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
            __DIR__.'/../data/'.$tablePrefix.$tableName
        );
        $expectedCsv = $parser2->parse();
        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }
}

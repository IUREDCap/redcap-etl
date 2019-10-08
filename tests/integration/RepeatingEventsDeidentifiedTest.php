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
class RepeatingEventsDeidentifiedTest extends TestCase
{
    private static $csvDir;
    private static $csvLabelFile;
    private static $logger;
    private static $properties;
    private static $tablePrefix;
    private static $tables;

    const CONFIG_FILE = __DIR__.'/../config/repeating-events.ini';

    public static function setUpBeforeClass()
    {
        self::$csvDir = 'tests/output';
        self::$tablePrefix = 'deident_re_';

        self::$properties = parse_ini_file(self::CONFIG_FILE);
        self::$properties['transform_rules_source'] = '3';
        self::$properties['transform_rules_file'] = '';
        self::$properties['table_prefix'] = self::$tablePrefix;
        self::$properties['db_connection'] = 'CSV:'.self::$csvDir;
        self::$properties['data_export_filter'] = '2';   // de-identified

        $app = basename(__FILE__, '.php');
        self::$logger = new Logger($app);


        # Try to delete the output file in case it exists from a previous run
        $csvFile = self::$csvDir . '/'. self::$tablePrefix. 'emrollment.csv';
        if (file_exists($csvFile)) {
            unlink($csvFile);
        }

        $redCapEtl = new RedCapEtl(self::$logger, self::$properties);

        $redCapEtl->run();

        self::$tables = [
            'basic_lookup.csv',
            'cardiovascular.csv',
            'cardiovascular_repeating_events.csv',
            'cardiovascular_repeating_instruments.csv',
            'contact_information.csv',
            'contact_information_label_view.csv',
            'emergency_contacts.csv',
            'enrollment.csv',
            'enrollment_label_view.csv',
            'root.csv',
            'weight.csv',
            'weight_repeating_events.csv',
            'weight_repeating_instruments.csv'
        ];
    }


    public function testTables()
    {
        foreach (self::$tables as $table) {
            $tableName   = 'enrollment.csv';

            $csvFile = self::$csvDir . '/'. self::$tablePrefix. $tableName;

            #---------------------------------------------------------------------
            # Check standard table with (coded) values for multipl-choice answers
            #---------------------------------------------------------------------
            $parser = \KzykHys\CsvParser\CsvParser::fromFile($csvFile);
            $csv = $parser->parse();

            $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
                __DIR__.'/../data/'.self::$tablePrefix.$tableName
            );
            $expectedCsv = $parser2->parse();
            $this->assertEquals($expectedCsv, $csv, $tableName.' file check.');
        }
    }
}

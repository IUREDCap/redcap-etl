<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for events for the RedCapProject class.
 */
class EventsTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
    }
    
    public function testExportEvents()
    {
        $result = self::$longitudinalDataProject->exportEvents();
        $this->assertEquals(14, count($result), 'Number of results matched.');
        
        $result = self::$longitudinalDataProject->exportEvents($format = 'php', $arms = [1]);
        $this->assertEquals(8, count($result), 'Number of results matchedfor arm 1.');
        
        $result = self::$longitudinalDataProject->exportEvents($format = 'php', $arms = [2]);
        $this->assertEquals(6, count($result), 'Number of results matchedfor arm 2.');
        
        # Invalid format
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportEvents($format = 'txt');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid format argument.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid format exception caught.');

        # Invalid format type
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportEvents($format = [1,2,3]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid format type argument.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid format type exception caught.');
    }

    /**
     * Tests exporting instruments in CSV (Comma-Separated Values) format.
     */
    public function testExportInstrumentsAsCsv()
    {
        $result = self::$longitudinalDataProject->exportEvents($format = 'csv');
        
        $parser = \KzykHys\CsvParser\CsvParser::fromString($result);
        $csv = $parser->parse();
        
        # csv should have 15 rows (1 header row, and 14 data rows)
        $this->assertEquals(15, count($csv), 'Correct number of rows');
        
        $expectedHeader = [
            'event_name', 'arm_num', 'day_offset', 'offset_min', 'offset_max', 'unique_event_name', 'custom_event_label'
        ];
        $header = $csv[0];
        $this->assertEquals($expectedHeader, $header, 'CSV headers match.');
        
        $firstDataRow = $csv[1];
        
        $eventNames = array_column($csv, 0);
        array_shift($eventNames);
        
        $expectedEventNames = [
            'Enrollment', 'Dose 1', 'Visit 1', 'Dose 2', 'Visit 2',
            'Dose 3', 'Visit 3', 'Final visit',
            'Enrollment', 'First dose', 'First visit',
            'Second dose', 'Second visit', 'Final visit'
        ];
        
        $this->assertEquals($expectedEventNames, $eventNames, 'Event names comparison.');
    }
}

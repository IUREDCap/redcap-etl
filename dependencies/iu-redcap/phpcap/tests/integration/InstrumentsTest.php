<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

/**
 * PHPUnit tests for the RedCapProject class.
 */
class InstrumentsTest extends TestCase
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
    
    public function testExportInstruments()
    {
        $result = self::$longitudinalDataProject->exportInstruments();
        
        $expectedResult = [
            'demographics' => 'Demographics',
            'contact_info' => 'Contact Info',
            'lab_data'     => 'Lab Data',
            'patient_morale_questionnaire' => 'Patient Morale Questionnaire',
            'completion_data' => 'Completion Data'
        ];
        
        $this->assertEquals($expectedResult, $result, 'Results match expected.');
    }

    /**
     * Tests exporting instruments in CSV (Comma-Separated Values) format.
     */
    public function testExportInstrumentsAsCsv()
    {
        $result = self::$basicDemographyProject->exportInstruments($format = 'csv');
        
        $parser = \KzykHys\CsvParser\CsvParser::fromString($result);
        $csv = $parser->parse();
        
        $firstDataRow = $csv[1];
        
        $instrumentName  = $firstDataRow[0];
        $instrumentLabel = $firstDataRow[1];
        
        $this->assertEquals('demographics', $instrumentName, 'Instrument name match.');
        $this->assertEquals('Basic Demography Form', $instrumentLabel, 'Instrument label match.');
    }
}

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
 * PHPUnit tests for intrument-event mappings for the RedCapProject class.
 */
class InstrumentEventMappingsTest extends TestCase
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
    
    public function testExportInstrumentEventMappings()
    {
        $expectedResult = [
            ['arm_num' => 1, 'unique_event_name' => 'enrollment_arm_1',   'form' => 'demographics'],
            ['arm_num' => 1, 'unique_event_name' => 'enrollment_arm_1',   'form' => 'contact_info'],
            ['arm_num' => 1, 'unique_event_name' => 'enrollment_arm_1',   'form' => 'lab_data'],
            ['arm_num' => 1, 'unique_event_name' => 'dose_1_arm_1',       'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 1, 'unique_event_name' => 'visit_1_arm_1',      'form' => 'lab_data'],
            ['arm_num' => 1, 'unique_event_name' => 'visit_1_arm_1',      'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 1, 'unique_event_name' => 'dose_2_arm_1',       'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 1, 'unique_event_name' => 'visit_2_arm_1',      'form' => 'lab_data'],
            ['arm_num' => 1, 'unique_event_name' => 'visit_2_arm_1',      'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 1, 'unique_event_name' => 'dose_3_arm_1',       'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 1, 'unique_event_name' => 'visit_3_arm_1',      'form' => 'lab_data'],
            ['arm_num' => 1, 'unique_event_name' => 'visit_3_arm_1',      'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 1, 'unique_event_name' => 'final_visit_arm_1',  'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 1, 'unique_event_name' => 'final_visit_arm_1',  'form' => 'completion_data'],
            ['arm_num' => 2, 'unique_event_name' => 'enrollment_arm_2',   'form' => 'demographics'],
            ['arm_num' => 2, 'unique_event_name' => 'enrollment_arm_2',   'form' => 'contact_info'],
            ['arm_num' => 2, 'unique_event_name' => 'enrollment_arm_2',   'form' => 'lab_data'],
            ['arm_num' => 2, 'unique_event_name' => 'first_dose_arm_2',   'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 2, 'unique_event_name' => 'first_visit_arm_2',  'form' => 'lab_data'],
            ['arm_num' => 2, 'unique_event_name' => 'first_visit_arm_2',  'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 2, 'unique_event_name' => 'second_dose_arm_2',  'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 2, 'unique_event_name' => 'second_visit_arm_2', 'form' => 'lab_data'],
            ['arm_num' => 2, 'unique_event_name' => 'second_visit_arm_2', 'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 2, 'unique_event_name' => 'final_visit_arm_2',  'form' => 'lab_data'],
            ['arm_num' => 2, 'unique_event_name' => 'final_visit_arm_2',  'form' => 'patient_morale_questionnaire'],
            ['arm_num' => 2, 'unique_event_name' => 'final_visit_arm_2',  'form' => 'completion_data']
        ];
        
        $result = self::$longitudinalDataProject->exportInstrumentEventMappings();
        
        $this->assertEquals($expectedResult, $result, 'Results match expected.');
    }

    /**
     * Tests exporting instruments in CSV (Comma-Separated Values) format.
     */
    public function testExportInstrumentsAsCsv()
    {
        $result = self::$longitudinalDataProject->exportInstrumentEventMappings($format = 'csv');
        $parser = \KzykHys\CsvParser\CsvParser::fromString($result);
        $csv = $parser->parse();
        
        $header = $csv[0];
        
        $expectedHeader = ['arm_num', 'unique_event_name', 'form'];

        $this->assertEquals($expectedHeader, $header, 'Mapping header check.');
    }
}

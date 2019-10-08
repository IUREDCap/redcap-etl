<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for field names for the RedCapProject class.
 */
class FieldNamesTest extends TestCase
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
    
    public function testExportFieldNames()
    {
        $expectedFieldNames = [
            ['original_field_name' => 'record_id',  'choice_value' => '', 'export_field_name' => 'record_id'],
            ['original_field_name' => 'first_name', 'choice_value' => '', 'export_field_name' => 'first_name'],
            ['original_field_name' => 'last_name',  'choice_value' => '', 'export_field_name' => 'last_name'],
            ['original_field_name' => 'address',    'choice_value' => '', 'export_field_name' => 'address'],
            ['original_field_name' => 'telephone',  'choice_value' => '', 'export_field_name' => 'telephone'],
            ['original_field_name' => 'email',      'choice_value' => '', 'export_field_name' => 'email'],
            ['original_field_name' => 'dob',        'choice_value' => '', 'export_field_name' => 'dob'],
            ['original_field_name' => 'age',        'choice_value' => '', 'export_field_name' => 'age'],
            ['original_field_name' => 'ethnicity',  'choice_value' => '', 'export_field_name' => 'ethnicity'],
            ['original_field_name' => 'race',       'choice_value' => '', 'export_field_name' => 'race'],
            ['original_field_name' => 'sex',        'choice_value' => '', 'export_field_name' => 'sex'],
            ['original_field_name' => 'height',     'choice_value' => '', 'export_field_name' => 'height'],
            ['original_field_name' => 'weight',     'choice_value' => '', 'export_field_name' => 'weight'],
            ['original_field_name' => 'bmi',        'choice_value' => '', 'export_field_name' => 'bmi'],
            ['original_field_name' => 'comments',   'choice_value' => '', 'export_field_name' => 'comments'],
            [
                'original_field_name' => 'demographics_complete',
                'choice_value' => '',
                'export_field_name' => 'demographics_complete'
            ]
        ];
                        
        #------------------------------------
        # Test importing field names
        #------------------------------------
        $fieldNames = self::$basicDemographyProject->exportFieldNames();

        
        $this->assertEquals($expectedFieldNames, $fieldNames, 'Export results check.');
    }
    
    public function testExportSingleFieldName()
    {
        #------------------------------------
        # Test importing field names
        #------------------------------------
        $fieldNames = self::$basicDemographyProject->exportFieldNames($format = 'php', $field = 'record_id');
        
        $this->assertEquals(1, count($fieldNames), 'Field name count check.');
        
        $fieldName = $fieldNames[0];
        
        $expectedFieldName = [
            'original_field_name' => 'record_id',
            'choice_value' => '',
            'export_field_name' => 'record_id'
        
        ];
        
        $this->assertEquals($expectedFieldName, $fieldName, 'Export results check.');
    }
}

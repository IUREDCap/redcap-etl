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
 * PHPUnit integration tests for metadata.
 */
class MetadataTest extends TestCase
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

    public function testExportMetadata()
    {
        $result = self::$basicDemographyProject->exportMetadata();
         
        $this->assertArrayHasKey('field_name', $result[0], 'Metadata has field_name field test.');
        $this->assertEquals($result[0]['field_name'], 'record_id', 'Metadata has study_id field test.');
    }
    
    public function testExportMetadataWithForms()
    {
        $result = self::$longitudinalDataProject->exportMetadata(
            $format = 'php',
            $fields = [],
            $forms = ['lab_data']
        );
        
        $this->assertEquals(5, count($result), 'Number of fields check.');
        
        $expectedFields = ['prealbumin', 'creatinine', 'npcr', 'cholesterol', 'transferrin'];
        
        $actualFields = array_column($result, 'field_name');
        
        $this->assertEquals($expectedFields, $actualFields, 'Field names check.');
    }
    
    public function testExportMetadataWithFields()
    {
        $fields = ['study_id', 'age', 'bmi'];
        
        $result = self::$longitudinalDataProject->exportMetadata($format = 'php', $fields);
    
        $this->assertEquals(count($fields), count($result), 'Number of fields check.');
     
        $actualFields = array_column($result, 'field_name');
    
        $this->assertEquals($fields, $actualFields, 'Field names check.');
    }
    
    public function testExportRecordIdFieldName()
    {
        $result = self::$longitudinalDataProject->getRecordIdFieldName();
        $this->assertEquals('study_id', $result, 'Record ID field name check.');
    }
}

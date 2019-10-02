<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for project XML for the RedCapProject class.
 */
class ProjectXmlTest extends TestCase
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
    
    public function testExportProjectXmlBasic()
    {
        $projectXml = self::$basicDemographyProject->exportProjectXml();
        
        $this->assertNotNull($projectXml, 'Non-null XML check.');
        $this->assertGreaterThan(0, strlen($projectXml), 'Greater than zero XML size');
        
        $returnMetadataOnly = true;
        $projectXmlMetadata = self::$basicDemographyProject->exportProjectXml($returnMetadataOnly);
        $this->assertNotNull($projectXmlMetadata, 'Non-null XML metadata only check.');
        $this->assertGreaterThan(0, strlen($projectXmlMetadata), 'Greater than zero XML metadata only size');
        
        $this->assertGreaterThan(
            strlen($projectXmlMetadata),
            strlen($projectXml),
            'Project XML size greater than Project XML metadata only size check.'
        );
    }
    
    public function testExportProjectXmWithNonBooleanMetadataArgument()
    {
        
        $exceptionCaught = false;
        try {
            $projectXml = self::$basicDemographyProject->exportProjectXml($returnMetadataOnly = 1);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testExportProjectXmlWithFiles()
    {
        $file = __DIR__.'/../data/import-file.txt';
        $recordId = '1001';
        $field = 'patient_document';
        $event = 'enrollment_arm_1';
            
        # Import a file to make sure there is a at least one file.
        self::$longitudinalDataProject->importFile($file, $recordId, $field, $event);

        $recordIds = [$recordId];
        $projectXml = self::$longitudinalDataProject->exportProjectXml(
            $returnMetadataOnly = false,
            $recordIds = $recordIds
        );
        $projectXmlWithFiles = self::$longitudinalDataProject->exportProjectXml(
            $returnMetadataOnly = false,
            $recordIds = $recordIds,
            $fields = null,
            $events = null,
            $filterLogic = null,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $exportFiles = true
        );
        
        $this->assertGreaterThan(
            strlen($projectXml),
            strlen($projectXmlWithFiles),
            'Xml with files bigger check.'
        );

        
        # Delete file to clean up
        self::$longitudinalDataProject->deleteFile($recordId, $field, $event);
    }
    
    public function testExportProjectXmlWithNonBooleanExportFiles()
    {
        $exceptionCaught = false;
        try {
            $projectXmlWithFiles = self::$longitudinalDataProject->exportProjectXml(
                $returnMetadataOnly = false,
                $recordIds = null,
                $fields = null,
                $events = null,
                $filterLogic = null,
                $exportSurveyFields = false,
                $exportDataAccessGroups = false,
                $exportFiles = 1     # Invalid, non-boolean value
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
}

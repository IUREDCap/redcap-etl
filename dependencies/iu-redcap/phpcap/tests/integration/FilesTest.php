<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for files for the RedCapProject class.
 */
class FilesTest extends TestCase
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
    
    public function testFiles()
    {
        #------------------------------------
        # Test importing a file
        #------------------------------------
        $result = self::$longitudinalDataProject->importFile(
            $file = __DIR__.'/../data/import-file.txt',
            $recordId = '1001',
            $field = 'patient_document',
            $event = 'enrollment_arm_1'
        );
        
        $this->assertEquals('', $result, 'Blank import result.');
        
        #--------------------------------------------------
        # Test exporting the file that was just imported
        #--------------------------------------------------
        $result = self::$longitudinalDataProject->exportFile(
            $recordId = '1001',
            $field = 'patient_document',
            $event = 'enrollment_arm_1'
        );
        
        $this->assertEquals('test import', $result, 'Export file contents check.');
        
        #---------------------------------------------
        # Test deleting the file that was imported
        #---------------------------------------------
        $result = self::$longitudinalDataProject->deleteFile(
            $recordId = '1001',
            $field = 'patient_document',
            $event = 'enrollment_arm_1'
        );
        
        $this->assertEquals('', $result, 'Blank import result.');

        #---------------------------------------------------------
        # Test trying to export the file that was just deleted
        #---------------------------------------------------------
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportFile(
                $recordId = '1001',
                $field = 'patient_document',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::REDCAP_API_ERROR,
                $exception->getCode(),
                'Export non-existant file exception code check.'
            );
        }
        
        $this->assertTrue($exceptionCaught, 'Export non-existant file exception caught.');
    }
    
    public function testImportFileWithNullFilename()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = null,
                $recordId = '1001',
                $field = 'patient_document',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
 
    public function testImportFileWithFilenameWithInvalidType()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = 123,
                $recordId = '1001',
                $field = 'patient_document',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportFileNotFound()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/'.uniqid().'.txt',
                $recordId = '1001',
                $field = 'patient_document',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INPUT_FILE_NOT_FOUND, $code, 'File not found check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportFileUnreadable()
    {
        $exceptionCaught = false;
        SystemFunctions::setIsReadableToFail();
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/file.txt',
                $recordId = '1001',
                $field = 'patient_document',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INPUT_FILE_UNREADABLE, $code, 'File unreadable check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetIsReadable();
    }
 
    public function testImportFileWithNullRecordId()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/file.txt',
                $recordId = null,
                $field = 'patient_document',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
 
    public function testImportFileWithInvalidRecordId()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/file.txt',
                $recordId = true,
                $field = 'patient_document',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    
    public function testImportFileWithNullField()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/file.txt',
                $recordId = '1001',
                $field = null,
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Invalid argument.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportFileWithNonStringField()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/file.txt',
                $recordId = '1001',
                $field = 1,
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Invalid argument.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportFileWithNonStringEvent()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/file.txt',
                $recordId = '1001',
                $field = 'patient_document',
                $event = 1
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Invalid argument.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportFileWithInvalidRepeatInstance()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/file.txt',
                $recordId = '1001',
                $field = 'patient_document',
                $event = 'enrollment_arm_1',
                $repeatInstance = true
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Invalid argument.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportFileWithNonExistentField()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->importFile(
                $file = __DIR__.'/../data/file.txt',
                $recordId = '1001',
                $field = 'patient_doc',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::REDCAP_API_ERROR, $code, 'Invalid argument.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
}

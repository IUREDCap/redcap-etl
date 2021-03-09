<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit integration tests for PDF forms export for the RedCapProject class.
 * Currently we don't have a good way to check the PDF content generated,
 * so these tests are limited.
 */
class PdfFormsTest extends TestCase
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
    
    public function testPdfForms()
    {
        #------------------------------------
        # Test importing a file
        #------------------------------------
        $result = self::$longitudinalDataProject->exportPdfFileOfInstruments();
        
        $this->assertNotNull($result, 'Non-null result check.');
    }
    
    public function testPdfFormsAllRecordsFalse()
    {
        #------------------------------------
        # Test importing a file
        #------------------------------------
        $result = self::$longitudinalDataProject->exportPdfFileOfInstruments(null, null, null, null, false);
        
        $this->assertNotNull($result, 'Non-null result check.');
    }
    
    public function testPdfFormsToFile()
    {
        #------------------------------------
        # Test importing a file
        #------------------------------------
        $file = __DIR__.'/../local/test-blank.pdf';
        
        # Make sure that the file is deleted.
        if (file_exists($file)) {
            unlink($file);
        }
        $this->assertFileNotExists($file, 'PDF file deleted check.');
        
        $result = self::$longitudinalDataProject->exportPdfFileOfInstruments($file);
        
        $this->assertFileExists($file, 'PDF file exsists.');
    }
    
    public function testPdfFormsWithInvalidFileType()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportPdfFileOfInstruments(123);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testPdfFormsWithInvalidAllRecords()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportPdfFileOfInstruments(null, null, null, null, 1);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testPdfFormsWithNonStringForm()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportPdfFileOfInstruments(null, null, null, 1);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }

    public function testPdfFormsToFileCompact()
    {
        $file = __DIR__.'/../local/test-blank-compact.pdf';
        
        # Make sure that the file is deleted.
        if (file_exists($file)) {
            unlink($file);
        }
        $result = self::$longitudinalDataProject->exportPdfFileOfInstruments($file, null, null, null, null, true);
        $this->assertFileExists($file, 'Compacted PDF file exsists.');

        $regularSize = filesize(__DIR__.'/../local/test-blank.pdf');
        $compactSize = filesize($file);
        $this->assertGreaterThan($compactSize, $regularSize, 'Compacted PDF file size check.');
    }

    public function testPdfFormsToFileCompactInvalidCompactValue()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportPdfFileOfInstruments(null, null, null, null, null, 1);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $code,
                'Compacted PDF file Exception code check.'
            );
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
}

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
    private static $repeatingFormsProject;
    
    public static function setUpBeforeClass(): void
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

        self::$repeatingFormsProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['repeating.forms.api.token']
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
        $this->assertFileDoesNotExist($file, 'PDF file deleted check.');
        
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

    public function testPdfFormsWithRepeatInstance()
    {
        $records = FileUtil::fileToString(__DIR__.'/../data/repeating-forms-import.csv');

        # Import the test records
        $result = self::$repeatingFormsProject->importRecords(
            $records,
            $format = 'csv',
            null,
            null,
            $dateFormat = 'MDY'
        );
        $this->assertEquals(4, $result, 'Import record count check.');

        $file = null;
        $recordId = 1001;
        $event = null;
        $form = 'weight';
        $allRecords = null;
        $compactDisplay = true;
        $repeatInstance = 1;
        $result = self::$repeatingFormsProject->exportPdfFileOfInstruments(
            $file,
            $recordId,
            $event,
            $form,
            $allRecords,
            $compactDisplay,
            $repeatInstance
        );

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($result);

        #----------------------------------------------
        # Check PDF generated for repeat instance 1
        #----------------------------------------------
        # $text = preg_split("/\n/", $pdf->getText());
        $text = $pdf->getText();
        $text = preg_replace('/\s+/', ' ', $text);

        $this->assertStringContainsString('Record ID 1001', $text, 'Record string check');
        $this->assertStringContainsString('Weight (lbs.) 173.4', $text, 'Weight value check');

        $repeatInstance = 2;
        $result = self::$repeatingFormsProject->exportPdfFileOfInstruments(
            $file,
            $recordId,
            $event,
            $form,
            $allRecords,
            $compactDisplay,
            $repeatInstance
        );

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($result);

        #----------------------------------------------
        # Check PDF generated for repeat instance 2
        #----------------------------------------------
        $text = $pdf->getText();
        $text = preg_replace('/\s+/', ' ', $text);

        $this->assertStringContainsString('Record ID 1001', $text, 'Record string check 2');
        $this->assertStringContainsString('Weight (lbs.) 172.4', $text, 'Weight value check 2');

        # delete imported test records
        $recordsDeleted = self::$repeatingFormsProject->deleteRecords([1001, 1002, 1003, 1004]);
        $this->assertEquals(4, $recordsDeleted, 'Records deleted check.');
    }
}

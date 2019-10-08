<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for reports for the RedCapProject class.
 */
class ReportsTest extends TestCase
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
    
    public function testReports()
    {
        $reportId = self::$config['longitudinal.data.report.id'];
        if (isset($reportId) && trim($reportId) != '') {
            $result = self::$longitudinalDataProject->exportReports($reportId);
            
            $this->assertEquals(100, count($result), 'Number of records check.');
        }
    }
    
    public function testReportsWithIntegerReportId()
    {
        $reportId = self::$config['longitudinal.data.report.id'];
        if (isset($reportId) && trim($reportId) != '') {
            $result = self::$longitudinalDataProject->exportReports((int) $reportId);
    
            $this->assertEquals(100, count($result), 'Number of records check.');
        }
    }
    
    public function testExportRecordsApAsCsv()
    {
        $reportId = self::$config['longitudinal.data.report.id'];
        if (isset($reportId) && trim($reportId) != '') {
            #----------------------------------------------------------------------
            # Test checkbox export using defaults ('raw') for headers and labels
            #----------------------------------------------------------------------
            $format = 'csv';
            $records = self::$longitudinalDataProject->exportReports($reportId, $format);
    
            $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
            $csv = $parser->parse();
    
            $header = $csv[0];
    
            $this->assertEquals(101, count($csv), 'Header plus data rows count check.');
    
            # 16 (study_id generated 2 - study_id + redcap_event_name), and gym and aerobics
            # each generate 5 field
            $this->assertEquals(16, count($header), 'Header column count check.');
        
            $expectedHeader = [
                'study_id', 'redcap_event_name', 'age', 'ethnicity', 'race', 'sex',
                'gym___0', 'gym___1', 'gym___2', 'gym___3', 'gym___4',
                'aerobics___0', 'aerobics___1', 'aerobics___2', 'aerobics___3', 'aerobics___4'
            ];
        
            $this->assertEquals($expectedHeader, $header, 'Header names check.');
    
            /*
            for ($index = 1; $index <= 100; $index++) {
                $row = $csv[$index];
                $this->assertEquals(1, count($row), 'Column count check for row '.$index.'.');
                $this->assertContains($row[0], [0,1,2,3,4,5,6], 'Column value check for row '.$index.'.');
            }
            */
        }
    }
    
    
    public function testExportReportsWithNullReportId()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportReports($reportId = null);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Invalid argument check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testExportReportsWithReportIdWithInvalidType()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportReports($reportId = true);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Invalid argument check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testExportReportsWithInvalidStringReportId()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportReports($reportId = 'abc');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Invalid argument check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testExportReportsWithInvalidIntegerReportId()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportReports($reportId = -100);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Invalid argument check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
}

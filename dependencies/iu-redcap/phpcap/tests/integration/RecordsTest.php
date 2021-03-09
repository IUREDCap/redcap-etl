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
class RecordsTest extends TestCase
{
    # Wait times in seconds to give time for REDCap log the be updated.
    # (REDCap gets last updated information for records from its event log.)
    const DELETE_WAIT_TIME = 60;
    const INSERT_WAIT_TIME = 90;

    private static $configFile = __DIR__.'/../config.ini';
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(self::$configFile);
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );

        $oldIds = [1101, 1200, 1201, 1202, 1203];
        foreach ($oldIds as $id) {
            $exists = self::$basicDemographyProject->exportRecordsAp(['recordIds' => [$id]]);
            if (count($exists) > 0) {
                self::$basicDemographyProject->deleteRecords([$id]);
            }
        }
    }
    

    public function testExportRecordsWithNullFormat()
    {
        $callInfo = true;
        $result = self::$basicDemographyProject->exportRecords($format = null);
        $this->assertEquals(100, count($result), 'Number of records returned check.');
    }
    
    public function testExportRecordsWithInvalidType()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecords($format = 'php', $type = 'flt');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testExportRecordsApWithNonArrayRecordIds()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['recordIds' => 1001]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }

    public function testExportRecordsApWithInvalidRecordIds()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['recordIds' => [false, true]]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testExportRecordsApWithNonArrayFields()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['fields' => 'last_name']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testExportRecordsApWithInvalidFields()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['fields' => [123, true]]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
 
    public function testExportRecordsApWithNonArrayForms()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['forms' => 'basic_demography']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testExportRecordsApWithInvalidForms()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['forms' => [100, false]]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }


    public function testExportRecordsApWithNonArrayEvents()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['events' => 'enrollment']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testExportRecordsApWithInvalidEvents()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['events' => [100, false]]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testExportRecordsApWithInvalidRawOrLabel()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['rawOrLabel' => 'labels']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }

    public function testExportRecordsApWithInvalidRawOrLabelHeaders()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['rawOrLabelHeaders' => true]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }


    public function testExportRecordsApWithInvalidExportCheckboxLabel()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['exportCheckboxLabel' => 'true']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testExportRecordsApWithInvalidExportSurveyFields()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['exportSurveyFields' => 100]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testExportRecordsApWithInvalidExportDataAccessGroups()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['exportDataAccessGroups' => [true]]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testExportRecordsApWithInvalidFilterLogic()
    {
        $callInfo = true;
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['filterLogic' => 123]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
 
    
    public function testExportRecords()
    {
        $result = self::$basicDemographyProject->exportRecords();
        
        $this->assertEquals(count($result), 100, 'Number of records test.');
        
        $recordIds = array_column($result, 'record_id');
        $this->assertEquals(min($recordIds), 1001, 'Min record_id test.');
        $this->assertEquals(max($recordIds), 1100, 'Max record_id test.');
        
        $lastNameMap = array_flip(array_column($result, 'last_name'));
        $this->assertArrayHasKey('Braun', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Carter', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Hayes', $lastNameMap, 'Has last name test.');
    }
    
    public function testExportRecordsAp()
    {
        $result = self::$basicDemographyProject->exportRecordsAp([]);
    
        $this->assertEquals(count($result), 100, 'Number of records test.');
    
        $recordIds = array_column($result, 'record_id');
        $this->assertEquals(min($recordIds), 1001, 'Min record_id test.');
        $this->assertEquals(max($recordIds), 1100, 'Max record_id test.');
    
        $lastNameMap = array_flip(array_column($result, 'last_name'));
        $this->assertArrayHasKey('Braun', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Carter', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Hayes', $lastNameMap, 'Has last name test.');
    }
    
    public function testExportRecordsWithFilterLogic()
    {
        $result = self::$basicDemographyProject->exportRecords(
            'php',
            'flat',
            null,
            null,
            null,
            null,
            "[last_name] = 'Thiel'",
            null,
            null,
            null,
            null,
            null
        );
        $this->assertEquals(2, count($result), 'Got expected number of records.');
        $firstNameMap = array_flip(array_column($result, 'first_name'));
        $this->assertArrayHasKey('Suzanne', $firstNameMap, 'Has first name test.');
        $this->assertArrayHasKey('Kaia', $firstNameMap, 'Has first name test.');
    }

    public function testExportRecordsApWithFilterLogic()
    {
        $result = self::$basicDemographyProject->exportRecordsAp(['filterLogic' => "[last_name] = 'Thiel'"]);
        
        $this->assertEquals(2, count($result));
        $firstNameMap = array_flip(array_column($result, 'first_name'));
        $this->assertArrayHasKey('Suzanne', $firstNameMap, 'Has first name test.');
        $this->assertArrayHasKey('Kaia', $firstNameMap, 'Has first name test.');
    }
    
    public function testExportRecordsApWithNullArgument()
    {
        $result = self::$basicDemographyProject->exportRecordsAp(null);
        $this->assertNotNull($result);
    }
    
    public function testExportRecordsApWithTooManyArguments()
    {
        $caughtException = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['format' => 'php'], ['type' => 'eav']);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                ErrorHandlerInterface::TOO_MANY_ARGUMENTS,
                $exception->getCode(),
                'Too many arguments.'
            );
        }
        $this->assertTrue($caughtException, 'Caught exception.');
    }
    
    public function testExportRecordsApWithNonArrayArgument()
    {
        $caughtException = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp('php');
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode(), 'Invalid argument.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
    }
    
    public function testExportRecordsApWithNonStringArgumentName()
    {
        $caughtException = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp([123 => 'php']);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode(), 'Invalid argument.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
    }
    
    public function testExportRecordsApWithInvalidArgumentName()
    {
        $caughtException = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['typ' => 'eav']);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode(), 'Invalid argument.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
    }
    
    
    public function testExportRecordsApWithFormsAndEvents()
    {
        $result = self::$longitudinalDataProject->exportRecordsAp(
            ['forms' => ['completion_data'], 'events' => ['final_visit_arm_1', 'final_visit_arm_2']]
        );
        
        $this->assertEquals(100, count($result), 'Number of records check.');
    
        $expectedFields = [
            'study_comments', 'completed_study', 'withdraw_date', 'last_visit_date',
             'withdraw_reason', 'completion_data_complete'
        ];
        $actualFields = array_keys($result[0]);
        
        $this->assertEquals($expectedFields, $actualFields, 'Fields check.');
    }
    
    
    public function testExportRecordsApRecordIds()
    {
        $result = self::$basicDemographyProject->exportRecordsAp(['recordIds' => [1001, 1010, 1100]]);
    
        $this->assertEquals(3, count($result));
        $recordIdMap = array_flip(array_column($result, 'record_id'));
        $this->assertArrayHasKey(1001, $recordIdMap, 'Has record ID 1001.');
        $this->assertArrayHasKey(1001, $recordIdMap, 'Has record ID 1010.');
        $this->assertArrayHasKey(1100, $recordIdMap, 'Has record ID 1100.');
    }
    
    public function testExportRecordsApRecordIdsAsEav()
    {
        $result = self::$basicDemographyProject->exportRecordsAp(
            ['recordIds' => [1001, 1010, 1100], 'fields' => ['age', 'bmi'], 'type' => 'eav']
        );
        
        # 3 rows X 2 fields = 6 records (since EAV type is being used).
        $this->assertEquals(6, count($result), 'Correct number of records.');
        
        $expectedResult = [
            ['record' => 1001, 'field_name' => 'age', 'value' => 48],
            ['record' => 1001, 'field_name' => 'bmi', 'value' => 27.7],
            ['record' => 1010, 'field_name' => 'age', 'value' => 32],
            ['record' => 1010, 'field_name' => 'bmi', 'value' => 18.3],
            ['record' => 1100, 'field_name' => 'age', 'value' => 71],
            ['record' => 1100, 'field_name' => 'bmi', 'value' => 18.6]
        ];
        
        $this->assertEquals($expectedResult, $result, 'Results check.');
    }
    
    
    public function testExportRecordsAsCsv()
    {
        $recordIds = array ('1001');
    
        $records = self::$basicDemographyProject->exportRecords($format = 'csv', $type = null, $recordIds);

        $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
        $csv = $parser->parse();
        $this->assertEquals(2, count($csv), 'Correct number of records returned test.');

        $firstDataRow = $csv[1];
        
        $csvRecordId = $firstDataRow[0];
          
        $this->assertEquals($recordIds[0], $csvRecordId, 'Correct record ID returned test.');
    }

    public function testExportRecordsApAsCsvWithBlankCsvDelimiter()
    {
        $recordIds = array ('1001');
    
        $records = self::$basicDemographyProject->exportRecordsAp(
            ['format' => 'csv', 'recordIds' => $recordIds, 'csvDelimiter' => '']
        );

        $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
        $csv = $parser->parse();
        $this->assertEquals(2, count($csv), 'Correct number of records returned test.');

        $firstDataRow = $csv[1];
        
        $csvRecordId = $firstDataRow[0];
          
        $this->assertEquals($recordIds[0], $csvRecordId, 'Correct record ID returned test.');
    }

    public function testExportRecordsApAsCsvWithCsvDelimiterWithInvalidType()
    {
        $recordIds = array ('1001');
    
        $exceptionCaught = false;
        try {
            $records = self::$basicDemographyProject->exportRecordsAp(
                ['format' => 'csv', 'recordIds' => $recordIds, 'csvDelimiter' => true]
            );
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
        }

        $this->assertTrue($exceptionCaught, 'Exception caught check');
    }

    public function testExportRecordsApAsCsvWithInvalidCsvDelimiter()
    {
        $recordIds = array ('1001');
    
        $exceptionCaught = false;
        try {
            $records = self::$basicDemographyProject->exportRecordsAp(
                ['format' => 'csv', 'recordIds' => $recordIds, 'csvDelimiter' => 'X']
            );
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
        }

        $this->assertTrue($exceptionCaught, 'Exception caught check');
    }

    public function testExportRecordsAsOdm()
    {
        $recordIds = array ('1001');
        
        $records = self::$basicDemographyProject->exportRecords($format = 'odm', $type = null, $recordIds);
        
        $this->assertTrue(is_string($records), 'Correct type for records returned test.');
    
        $xml = new \DomDocument();
        $xml->loadXML($records);
        
        $xmlRecordId = null;
        $itemData = $xml->getElementsByTagName("ItemData");
        foreach ($itemData as $item) {
            if ($item->getAttribute('ItemOID') === 'record_id') {
                $xmlRecordId = $item->getAttribute('Value');
                break;
            }
        }
   
        $this->assertEquals($recordIds[0], $xmlRecordId, 'Correct record ID returned test.');
    }
    
    
    public function testExportRecordsAsXml()
    {
        $recordIds = array('1001');
        
        $records = self::$basicDemographyProject->exportRecords($format = 'xml', $type = null, $recordIds);
        
        $this->assertTrue(is_string($records), 1, 'Correct type for records returned test.');
        
        $xml = simplexml_load_string($records);
        
        $xmlRecordIdNodes = $xml->xpath("//record_id");
        $xmlRecordId = (string) $xmlRecordIdNodes[0];
        
        $this->assertEquals($recordIds[0], $xmlRecordId, 'Correct record ID returned test.');
    }
   
    
    public function testExportRecordsApAsCsv()
    {
        #----------------------------------------------------------------------
        # Test checkbox export using defaults ('raw') for headers and labels
        #----------------------------------------------------------------------
        $records = self::$longitudinalDataProject->exportRecordsAp(
            [
                'format'     => 'csv',
                'fields'     => ['race'],
                'events'     => ['enrollment_arm_1', 'enrollment_arm_2']
            ]
        );
        
        $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
        $csv = $parser->parse();

        $header = $csv[0];
        
        $this->assertEquals(101, count($csv), 'Header plus data rows count check.');
        
        $this->assertEquals(1, count($header), 'Header column count check.');
        $this->assertEquals('race', $header[0], 'Header column name check.');
        
        for ($index = 1; $index <= 100; $index++) {
            $row = $csv[$index];
            $this->assertEquals(1, count($row), 'Column count check for row '.$index.'.');
            $this->assertContains($row[0], [0,1,2,3,4,5,6], 'Column value check for row '.$index.'.');
        }
    }
    
    public function testExportRecordsApAsCsvWithLabels()
    {
        $records = self::$longitudinalDataProject->exportRecordsAp(
            [
                'format'            => 'csv',
                'fields'            => ['race'],
                'events'            => ['enrollment_arm_1', 'enrollment_arm_2'],
                'rawOrLabel'        => 'label',
                'rawOrLabelHeaders' => 'label'
            ]
        );
        
        $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
        $csv = $parser->parse();
        
        $header = $csv[0];
        
        $this->assertEquals(101, count($csv), 'Header plus data rows count check.');
        
        $this->assertEquals(1, count($header), 'Header column count check.');
        $this->assertEquals($header[0], 'Race', 'Header column name check.');
        
        $expectedLabels = [
            'American Indian/Alaska Native',
            'Asian',
            'Native Hawaiian or Other Pacific Islander',
            'Black or African American',
            'White',
            'More Than One Race',
            'Unknown / Not Reported'
        ];
        

        for ($index = 1; $index <= 100; $index++) {
            $row = $csv[$index];
            $this->assertEquals(1, count($row), 'Column count check for row '.$index.'.');
            $this->assertContains($row[0], $expectedLabels, 'Column value check for row '.$index.'.');
        }
    }
    
    /**
     * Test export records using labels with 'exportCheckboxLabel' unset
     * (which should set it to a default value of false).
     */
    public function testExportRecordsApAsCsvWithExportCheckboxLabelFalse()
    {
        $records = self::$longitudinalDataProject->exportRecordsAp(
            [
                'format'              => 'csv',
                'fields'              => ['gym'],
                'events'              => ['enrollment_arm_1', 'enrollment_arm_2'],
                'rawOrLabel'          => 'label',
                'rawOrLabelHeaders'   => 'label'
            ]
        );
        
        $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
        $csv = $parser->parse();
    
        $header = $csv[0];
        
        $this->assertEquals(101, count($csv), 'Header plus data rows count check.');
    
        $this->assertEquals(5, count($header), 'Header column count check.');
            
        for ($index = 1; $index <= 100; $index++) {
            $row = $csv[$index];
            $this->assertEquals(5, count($row), 'Column count check for row '.$index.'.');
            for ($col = 0; $col < 5; $col++) {
                $this->assertContains(
                    $row[$col],
                    ['Unchecked','Checked'],
                    'Column value check for row '.$index.', column '.$col.'.'
                );
            }
        }
    }
    
    public function testExportRecordsApAsCsvWithExportCheckboxLabelTrue()
    {
        $records = self::$longitudinalDataProject->exportRecordsAp(
            [
                'format'              => 'csv',
                'fields'              => ['gym'],
                'events'              => ['enrollment_arm_1', 'enrollment_arm_2'],
                'rawOrLabel'          => 'label',
                'rawOrLabelHeaders'   => 'label',
                'exportCheckboxLabel' => true
            ]
        );
        
        $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
        $csv = $parser->parse();
    
        $header = $csv[0];
    
        $this->assertEquals(101, count($csv), 'Header plus data rows count check.');
    
        $this->assertEquals(5, count($header), 'Header column count check.');
    
        for ($index = 1; $index <= 100; $index++) {
            $row = $csv[$index];
            $this->assertEquals(5, count($row), 'Column count check for row '.$index.'.');
            $this->assertContains(
                $row[0],
                ['','Monday'],
                'Column value check for row '.$index.', column 0.'
            );
            $this->assertContains(
                $row[1],
                ['','Tuesday'],
                'Column value check for row '.$index.', column 1.'
            );
            $this->assertContains(
                $row[2],
                ['','Wednesday'],
                'Column value check for row '.$index.', column 2.'
            );
            $this->assertContains(
                $row[3],
                ['','Thursday'],
                'Column value check for row '.$index.', column 3.'
            );
            $this->assertContains(
                $row[4],
                ['','Friday'],
                'Column value check for row '.$index.', column 4.'
            );
        }
    }

    public function testExportRecordsWithJsonResultError()
    {
        SystemFunctions::setJsonError();
        
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecords();
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::JSON_ERROR, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::clearJsonError();
    }
    
    public function testExportRecordsWithNoResults()
    {
        $result = self::$basicDemographyProject->exportRecordsAp(['recordIds' => [20000]]);
        $this->assertEquals(0, count($result), 'Record count check.');
    }
    
    public function testExportRecordsWithRedcapApiError()
    {
        SystemFunctions::setJsonDecodeToError();
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecords($format = 'php');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::REDCAP_API_ERROR, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        
        SystemFunctions::resetJsonDecode();
    }
    
    public function testImportNullRecords()
    {
        $records = null;
    
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords($records);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportAndDeleteRecords()
    {
        
        $record = [
            'record_id'  => '1101',
            'first_name' => 'Joe',
            'last_name'  => 'Schmidt',
            'address'    => '72133 Joelle Grove Suite 055\nHayesburgh, AZ 84047-5437',
            'telephone'  => '(753) 406-4137',
            'email'      => 'joe.schmidt@mailinator.com',
            'dob'        => '1944-01-17',
            'ethnicity'  => 1,
            'race'       => 4,
            'sex'        => 1,
            'height'     => 197,
            'weight'     => 88,
            'comments'   => '',
            'demographics_complete' => 2
        ];
        
        $records = [$record];
        
        $result = self::$basicDemographyProject->importRecords($records, null, null, null, null, 'count');
        $this->assertEquals(1, $result, 'Record count.');
        
        $result = self::$basicDemographyProject->exportRecords();
        $this->assertEquals(101, count($result), 'Record count after import.');
        
        $recordIds = [$record['record_id']];
        $result = self::$basicDemographyProject->deleteRecords($recordIds);
        
        $result = self::$basicDemographyProject->exportRecords();
        $this->assertEquals(100, count($result), 'Record count after delete.');
    }
    
    
    public function testImportAndDeleteRecordsCsvFormat()
    {
        $records = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import.csv');
   
        $result = self::$basicDemographyProject->importRecords(
            $records,
            $format = 'csv',
            $type = null,
            $overwriteBehavior = null,
            $dateFormat = null,
            $returnContent = 'ids'
        );
        $this->assertEquals(1, count($result), 'Record count.');

        $recordIds = [1101];
        
        $this->assertEquals($recordIds, $result, 'Import IDs check.');
        
        $result = self::$basicDemographyProject->exportRecords();
        $this->assertEquals(101, count($result), 'Record count after import.');
    
        $result = self::$basicDemographyProject->deleteRecords($recordIds);
    
        $result = self::$basicDemographyProject->exportRecords();
        $this->assertEquals(100, count($result), 'Record count after delete.');
    }

    public function testImportAndDeleteRecordsCsvFormatWithForceAutoNumber()
    {
        $records = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import.csv');
        
        # import the same records 3 times
        for ($num = 0; $num <= 3; $num++) {
            $result = self::$basicDemographyProject->importRecords(
                $records,
                $format = 'csv',
                $type = null,
                $overwriteBehavior = null,
                $dateFormat = null,
                $returnContent = 'auto_ids',
                $forceAutoNumber = true
            );
        }

        $records = self::$basicDemographyProject->exportRecords();
        
        # These should be OK, because this project does not support auto-generated records
        $this->assertEquals(1, count($result), 'Record count.');
        
        $recordIds = ['1101,1101'];
        $deleteRecordIds = [1101];
        
        $this->assertEquals($recordIds, $result, 'Import IDs check.');
        
        $result = self::$basicDemographyProject->exportRecords();
        $this->assertEquals(101, count($result), 'Record count after import.');
        
        $result = self::$basicDemographyProject->deleteRecords($deleteRecordIds);
        
        $result = self::$basicDemographyProject->exportRecords();
        $this->assertEquals(100, count($result), 'Record count after delete.');
    }
    
    public function testImportRecordsCsvFormatWithJsonError()
    {
        $records = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import.csv');
   
        SystemFunctions::setJsonError();
        
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords($records, $format = 'csv');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::JSON_ERROR, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::clearJsonError();
    }
    
    
    public function testImportAndDeleteRecordsXmlFormat()
    {
        $records = '<?xml version="1.0" encoding="UTF-8" ?>'
            .'<records> <item>'
            .'<record_id><![CDATA[1101]]></record_id>'
            .'<first_name><![CDATA[Joe]]></first_name>'
            .'<last_name><![CDATA[Schmidt]]></last_name>'
            .'<address><![CDATA[72133 Joelle Grove Suite 055 Hayesburgh, AZ 84047-5437]]></address>'
            .'<telephone><![CDATA[(753) 406-4137]]></telephone>'
            .'<email><![CDATA[joe.schmidt@mailinator.com]]></email>'
            .'<dob><![CDATA[1944-01-17]]></dob>'
            .'<ethnicity><![CDATA[1]]></ethnicity>'
            .'<race><![CDATA[4]]></race>'
            .'<sex><![CDATA[1]]></sex>'
            .'<height><![CDATA[194]]></height>'
            .'<weight><![CDATA[88]]></weight>'
            .'<comments><![CDATA[]]></comments>'
            .'<demographics_complete><![CDATA[2]]></demographics_complete>'
            .'</item> </records>';
        
        $result = self::$basicDemographyProject->importRecords($records, $format = 'xml');
        $this->assertEquals(1, $result, 'Import result value.');
            
        $result = self::$basicDemographyProject->exportRecords();
        $this->assertEquals(101, count($result), 'Record count after import.');
            
        $recordIds = [1101];
        $result = self::$basicDemographyProject->deleteRecords($recordIds);
            
        $result = self::$basicDemographyProject->exportRecords();
        $this->assertEquals(100, count($result), 'Record count after delete.');
    }
    
    public function testImportRecordsWithNonArrayRecords()
    {
        $records = 1234;

        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords($records, $format = 'php');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportRecordsWithNonStringRecords()
    {
        $records = [1234, 'test'];
    
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords($records, $format = 'csv');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportRecordsPhpFormatWithJsonError()
    {
        $record = [
                'record_id'  => '1101',
                'first_name' => 'Joe',
                'last_name'  => 'Schmidt',
                'address'    => '72133 Joelle Grove Suite 055\nHayesburgh, AZ 84047-5437',
                'telephone'  => '(753) 406-4137',
                'email'      => 'joe.schmidt@mailinator.com',
                'dob'        => '1944-01-17',
                'ethnicity'  => 1,
                'race'       => 4,
                'sex'        => 1,
                'height'     => 197,
                'weight'     => 88,
                'comments'   => '',
                'demographics_complete' => 2
        ];
    
        $records = [$record];

        SystemFunctions::setJsonError();
        
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords($records, $format = 'php');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::JSON_ERROR, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::clearJsonError();
    }
    
    public function testImportRecordsWithInvalidOverwriteBehavior()
    {
        $records = 'record_id,first_name,last_name,address,telephone,email,dob,'
                .'ethnicity,race,sex,height,weight,comments,demographics_complete'."\n"
                .'1101,Joe,Schmidt,"72133 Joelle Grove Suite 055 Hayesburgh, AZ 84047-5437",'
                .'(753) 406-4137,joe.schmidt@mailinator.com,1945-07-15,0,4,1,191,88,,2';

        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords(
                $records,
                $format = 'csv',
                null,
                $overwriteBehavior = 'delete'
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testImportRecordsWithInvalidDateFormat()
    {
        $records = 'record_id,first_name,last_name,address,telephone,email,dob,'
                .'ethnicity,race,sex,height,weight,comments,demographics_complete'."\n"
                .'1101,Joe,Schmidt,"72133 Joelle Grove Suite 055 Hayesburgh, AZ 84047-5437",'
                .'(753) 406-4137,joe.schmidt@mailinator.com,1945-07-15,0,4,1,191,88,,2';
        
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords(
                $records,
                $format = 'csv',
                $type = null,
                $overwriteBehavior = null,
                $dateFormat = 'MMDY',    # invalid format
                $returnContent = null
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testImportRecordsWithInvalidReturnContent()
    {
        $records = 'record_id,first_name,last_name,address,telephone,email,dob,'
                .'ethnicity,race,sex,height,weight,comments,demographics_complete'."\n"
                .'1101,Joe,Schmidt,"72133 Joelle Grove Suite 055 Hayesburgh, AZ 84047-5437",'
                .'(753) 406-4137,joe.schmidt@mailinator.com,1945-07-15,0,4,1,191,88,,2';
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords(
                $records,
                $format = 'csv',
                $type = null,
                $overwriteBehavior = null,
                $dateFormat = null,
                $returnContent = 'total'     # invalid return content
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testImportRecordsWithInvalidForceAutoNumber()
    {
        $records = 'record_id,first_name,last_name,address,telephone,email,dob,'
            .'ethnicity,race,sex,height,weight,comments,demographics_complete'."\n"
            .'1101,Joe,Schmidt,"72133 Joelle Grove Suite 055 Hayesburgh, AZ 84047-5437",'
            .'(753) 406-4137,joe.schmidt@mailinator.com,1945-07-15,0,4,1,191,88,,2';
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords(
                $records,
                $format = 'csv',
                $type = null,
                $overwriteBehavior = null,
                $dateFormat = null,
                $returnContent = 'ids',
                $forceAutoNumber = 100   # invalid type
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }

    public function testImportRecordsWithInvalidAutoIdsReturnContent()
    {
        $records = 'record_id,first_name,last_name,address,telephone,email,dob,'
            .'ethnicity,race,sex,height,weight,comments,demographics_complete'."\n"
            .'1101,Joe,Schmidt,"72133 Joelle Grove Suite 055 Hayesburgh, AZ 84047-5437",'
            .'(753) 406-4137,joe.schmidt@mailinator.com,1945-07-15,0,4,1,191,88,,2';
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->importRecords(
                $records,
                $format = 'csv',
                $type = null,
                $overwriteBehavior = null,
                $dateFormat = null,
                $returnContent = 'auto_ids'  # invalid since forceAutoNumber defaults to false
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testDeleteRecordsWithArm()
    {
        $records = FileUtil::fileToString(__DIR__.'/../data/longitudinal-data-import.csv');
         
        $result = self::$longitudinalDataProject->importRecords(
            $records,
            $format = 'csv',
            null,
            null,
            $dateFormat = 'MDY'
        );
        
        $records = self::$longitudinalDataProject->exportRecordsAp(
            ['events' => ['enrollment_arm_1', 'enrollment_arm_2']]
        );
        
        $this->assertEquals(102, count($records), 'Record count check after import.');
        
        # delete the records that were just added that are in arm 1,
        # which should be only records with ID 1102
        $recordsDeleted = self::$longitudinalDataProject->deleteRecords([1101,1102], $arm = 1);
        
        # Note: as of May 10, 2017, this assertion fails (apparently) because of a REDCap API bug
        #$this->assertEquals(1, $recordsDeleted, 'Records deleted check after first delete.');
        
        $records = self::$longitudinalDataProject->exportRecordsAp(
            ['events' => ['enrollment_arm_1', 'enrollment_arm_2']]
        );
        $this->assertEquals(101, count($records), 'Record count after arm 1 delete');
        
        # delete remaining imported record
        $recordsDeleted = self::$longitudinalDataProject->deleteRecords([1101]);
        $this->assertEquals(1, $recordsDeleted, 'Records deleted check after first delete.');
    }
    
    public function testDeleteRecordsWithNonNumericStringArm()
    {
        $exceptionCaught = false;
        try {
            $recordsDeleted = self::$longitudinalDataProject->deleteRecords([1101,1102], $arm = 'A');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testDeleteRecordsWithNegativeIntArm()
    {
        $exceptionCaught = false;
        try {
            $recordsDeleted = self::$longitudinalDataProject->deleteRecords([1101,1102], $arm = -1);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
    
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testDeleteRecordsWithArmWithInvalidType()
    {
        $exceptionCaught = false;
        try {
            $recordsDeleted = self::$longitudinalDataProject->deleteRecords([1101,1102], $arm = true);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
    
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }

    public function testExportRecordsWithDateRange()
    {
        #clean up any records that did not get properly deleted from a prior test.
        $oldIds = [1200, 1201];
        $runWait = false;
        foreach ($oldIds as $id) {
            $exists = self::$basicDemographyProject->exportRecordsAp(['recordIds' => [$id]]);
            if (count($exists) > 0) {
                self::$basicDemographyProject->deleteRecords([$id]);
                $runWait = true;
            }
        }

        # If records were deleted, wait three minutes so that the test doesn't
        # pick up these log entries for the deletions as modified records
        if ($runWait) {
            sleep(self::DELETE_WAIT_TIME);
        };

        # Establish the date for dateRangeBegin
        $twoMinutesAgo = new \DateTime();
        $twoMinutesAgo->sub(new \DateInterval('PT2M'));

        # Create a test record to insert
        $records = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import2.csv');
        $result = self::$basicDemographyProject->importRecords(
            $records,
            $format = 'csv',
            $type = null,
            $overwriteBehavior = null,
            $dateFormat = null,
            $returnContent = 'ids'
        );

        #get the timestamp after the first record was inserted.
        $recordInsertedTs = new \DateTime();

        #adjust for timezone
        if (array_key_exists('timezone', self::$config)) {
            $tz = self::$config['timezone'];
        } else {
            $message = 'No timezone defined in configuration file "'.realpath(self::$configFile).'"';
            throw new \Exception($message);
        }

        if ($tz) {
            $twoMinutesAgo->setTimezone(new \DateTimeZone($tz));
            $recordInsertedTs->setTimezone(new \DateTimeZone($tz));
        }

        #test entering a begin date in the future so that no records should be returned
        $result1 = self::$basicDemographyProject->exportRecords(
            $format = 'php',
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = null,
            $rawOrLabel = 'raw',
            $rawOrLabelHeaders = 'raw',
            $exportCheckboxLabel = false,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $dateRangeBegin = '2100-01-01 00:00:00'
        );
        $this->assertEquals(0, count($result1), "Date Range check with wrong begin date.");

        #test entering an end date in the past so that no records should be returned
        $result2 = self::$basicDemographyProject->exportRecords(
            $format = 'php',
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = null,
            $rawOrLabel = 'raw',
            $rawOrLabelHeaders = 'raw',
            $exportCheckboxLabel = false,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $dateRangeBegin = null,
            $dateRangeEnd = '2000-01-01 00:00:00'
        );
        $this->assertEquals(0, count($result2), "Date Range check with wrong end date.");

        # wait a minute and a half and then add another record
        sleep(self::INSERT_WAIT_TIME);
        $record2 = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import3.csv');
        $result3 = self::$basicDemographyProject->importRecords(
            $record2,
            $format = 'csv',
            $type = null,
            $overwriteBehavior = null,
            $dateFormat = null,
            $returnContent = 'ids'
        );

        #retrieve only the first record that was imported
        $result4 = self::$basicDemographyProject->exportRecords(
            $format = 'php',
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = null,
            $rawOrLabel = 'raw',
            $rawOrLabelHeaders = 'raw',
            $exportCheckboxLabel = false,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $dateRangeBegin = $twoMinutesAgo->format('Y-m-d H:i:s'),
            $dateRangeEnd = $recordInsertedTs->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            1,
            count($result4),
            'Date Range check, first record, with dateRangeBegin='
                .$twoMinutesAgo->format('Y-m-d H:i:s')
                .' and dateRangeEnd='
                .$recordInsertedTs->format('Y-m-d H:i:s')
        );

        #retrieve both records that were imported
        #use tomorrow as the dateRangeEnd value to retrieve both records.
        $tomorrow = new \DateTime();
        $tomorrow->add(new \DateInterval('P1D'));
        if ($tz) {
            $tomorrow->setTimezone(new \DateTimeZone($tz));
        }

        $result5 = self::$basicDemographyProject->exportRecords(
            $format = 'php',
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = null,
            $rawOrLabel = 'raw',
            $rawOrLabelHeaders = 'raw',
            $exportCheckboxLabel = false,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $dateRangeBegin = $twoMinutesAgo->format('Y-m-d H:i:s'),
            $dateRangeEnd = $tomorrow->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            2,
            count($result5),
            'Date Range check, second record, with dateRangeBegin='
            .$twoMinutesAgo->format('Y-m-d H:i:s')
            .' and dateRangeEnd='
            .$tomorrow->format('Y-m-d H:i:s')
        );

        # Clean up the test by deleting the inserted test record
        self::$basicDemographyProject->deleteRecords([1200, 1201]);
    }

    public function testExportRecordsApWithDateRange()
    {
        # Clean up any records that did not get properly deleted from a prior test.
        $oldIds = [1202,1203];
        $runWait = false;
        foreach ($oldIds as $id) {
            $exists = self::$basicDemographyProject->exportRecordsAp(['recordIds' => [$id]]);
            if (count($exists) > 0) {
                self::$basicDemographyProject->deleteRecords([$id]);
                $runWait = true;
            };
        }

        # If records were deleted, wait three minutes so that the test doesn't
        # pick up these log entries for the deletions as modified records
        if ($runWait) {
            sleep(self::DELETE_WAIT_TIME);
        };

        #establish the date for dateRangeBegin
        $twoMinutesAgo = new \DateTime();
        $twoMinutesAgo->sub(new \DateInterval('PT2M'));

        #create a test record to insert
        $record = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import4.csv');
        
        $result = self::$basicDemographyProject->importRecords(
            $record,
            $format = 'csv',
            $type = null,
            $overwriteBehavior = null,
            $dateFormat = null,
            $returnContent = 'ids'
        );

        #get the timestamp after the first record was inserted.
        $recordInsertedTs = new \DateTime();

        #adjust for the timezone
        if (array_key_exists('timezone', self::$config)) {
            $tz = self::$config['timezone'];
        } else {
            $message = 'No timezone defined in configuration file "'.realpath(self::$configFile).'"';
            throw new \Exception($message);
        }

        if ($tz) {
            $twoMinutesAgo->setTimezone(new \DateTimeZone($tz));
            $recordInsertedTs->setTimezone(new \DateTimeZone($tz));
        }

        #test entering a begin date in the future so that no records should be returned
        $result1 = self::$basicDemographyProject->exportRecordsAp(['dateRangeBegin' => '2100-01-01 00:00:00']);
        $this->assertEquals(0, count($result1), "Export Records AP Date Range check with wrong begin date.");

        #test entering an end date in the past so that no records should be returned
        $result2 = self::$basicDemographyProject->exportRecordsAp(['dateRangeEnd' => '2000-01-01 00:00:00']);
        $this->assertEquals(0, count($result2), "Export Records AP Date Range check with wrong end date.");
        
        # wait a minute and a half and then add another record
        sleep(self::INSERT_WAIT_TIME);
        $record2 = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import5.csv');
        $result = self::$basicDemographyProject->importRecords(
            $record2,
            $format = 'csv',
            $type = null,
            $overwriteBehavior = null,
            $dateFormat = null,
            $returnContent = 'ids'
        );

        #retrieve only the first record that was imported
        $result3 = self::$basicDemographyProject->exportRecordsAp([
            'dateRangeBegin' => $twoMinutesAgo->format('Y-m-d H:i:s'),
            'dateRangeEnd' => $recordInsertedTs->format('Y-m-d H:i:s')
        ]);
        $this->assertEquals(
            1,
            count($result3),
            "Export Records AP Date Range, test for first record, dateRangeEnd = "
            .$twoMinutesAgo->format('Y-m-d H:i:s')
        );

        #retrieve both records that were imported
        #use tomorrow as the dateRangeEnd value to retrieve both records.
        $tomorrow = new \DateTime();
        $tomorrow->add(new \DateInterval('P1D'));
        if ($tz) {
            $tomorrow->setTimezone(new \DateTimeZone($tz));
        }

        $result = self::$basicDemographyProject->exportRecordsAp([
            'dateRangeBegin' => $twoMinutesAgo->format('Y-m-d H:i:s'),
            'dateRangeEnd' => $tomorrow->format('Y-m-d H:i:s')
        ]);
        $this->assertEquals(2, count($result), "Export Records AP Date Range, both records");

        #clean up the test by deleting the inserted test record
        self::$basicDemographyProject->deleteRecords([1202, 1203]);
    }

    public function testExportRecordsApWithBlankDateRange()
    {
        $result = self::$basicDemographyProject->exportRecordsAp(['dateRangeBegin' => '']);
    
        $this->assertEquals(count($result), 100, 'Number of records test.');
    
        $recordIds = array_column($result, 'record_id');
        $this->assertEquals(min($recordIds), 1001, 'Min record_id test.');
        $this->assertEquals(max($recordIds), 1100, 'Max record_id test.');
    
        $lastNameMap = array_flip(array_column($result, 'last_name'));
        $this->assertArrayHasKey('Braun', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Carter', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Hayes', $lastNameMap, 'Has last name test.');
    }
    
    public function testExportRecordsApWithNonStringDateRange()
    {
        $exceptionCaught = false;
        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['dateRangeBegin' => 123]);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
        }

        $this->assertTrue($exceptionCaught, 'Exception caught check');
    }
    
    public function testExportRecordsWithCsvDelimiter()
    {
        #export records with default delimiter (comma)
        $result = self::$basicDemographyProject->exportRecords(
            $format = 'csv',
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = "[last_name] = 'Thiel'",
            $rawOrLabel = 'raw',
            $rawOrLabelHeaders = 'raw',
            $exportCheckboxLabel = false,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $dateRangeBegin = null,
            $dateRangeEnd = null,
            $csvDelimiter = ','
        );
        $expected = "record_id,first_name,last_name,address,telephone,email";
        $expected .= ",dob,age,ethnicity,race,sex,height,weight,bmi,comments";
        $expected .= ",demographics_complete";
        $header = substr($result, 0, strlen($expected));
        $this->assertEquals($expected, $header, 'CSV delimiter check with comma');

        #export records with pipe delimiter
        $result = self::$basicDemographyProject->exportRecords(
            $format = 'csv',
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = "[last_name] = 'Thiel'",
            $rawOrLabel = 'raw',
            $rawOrLabelHeaders = 'raw',
            $exportCheckboxLabel = false,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $dateRangeBegin = null,
            $dateRangeEnd = null,
            $csvDelimiter = '|'
        );
        $expected = str_replace(',', chr(124), $expected);
        $header = substr($result, 0, strlen($expected));
        $this->assertEquals($expected, $header, 'CSV delimiter check with pipe');
    }

    public function testExportRecordsApWithCsvDelimiter()
    {
        #export records with default delimiter (comma)
        $result = self::$basicDemographyProject->exportRecordsAp([
            'format' => 'csv',
            'type' => 'flat',
            'csvDelimiter' => ','
        ]);
 
        $expected = "record_id,first_name,last_name,address,telephone,email";
        $expected .= ",dob,age,ethnicity,race,sex,height,weight,bmi,comments";
        $expected .= ",demographics_complete";
        $header = substr($result, 0, strlen($expected));
        $this->assertEquals($expected, $header, 'Export records AP CSV delimiter check with comma');

        #export records with pipe delimiter
        $result = self::$basicDemographyProject->exportRecordsAp([
            'format' => 'csv',
            'type' => 'flat',
            'csvDelimiter' => '|'
        ]);
        $expected = str_replace(',', chr(124), $expected);
        $header = substr($result, 0, strlen($expected));
        $this->assertEquals($expected, $header, 'Export records AP CSV delimiter check with pipe');
    }

    public function testExportRecordsWithDecimalCharacter()
    {
        #export records with dot decimal format
        $result = self::$basicDemographyProject->exportRecords(
            $format = 'php',
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = "[last_name] = 'Kerluke'",
            $rawOrLabel = 'raw',
            $rawOrLabelHeaders = 'raw',
            $exportCheckboxLabel = false,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $dateRangeBegin = null,
            $dateRangeEnd = null,
            $csvDelimiter = ',',
            $decimalCharacter = '.'
        );
        $expected = '26.8';
        $testResult = strval($result[0]['bmi']);
        $this->assertEquals($expected, $testResult, 'Decimal character check with dot/full stop');

        #export records with comma decimal format
        $result = self::$basicDemographyProject->exportRecords(
            $format = 'php',
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = "[last_name] = 'Kerluke'",
            $rawOrLabel = 'raw',
            $rawOrLabelHeaders = 'raw',
            $exportCheckboxLabel = false,
            $exportSurveyFields = false,
            $exportDataAccessGroups = false,
            $dateRangeBegin = null,
            $dateRangeEnd = null,
            $csvDelimiter = ',',
            $decimalCharacter = ','
        );
        $expected = '26,8';
        $testResult = strval($result[0]['bmi']);
        $this->assertEquals($expected, $testResult, 'Decimal character check with comma');
    }

    public function testExportRecordsApWithDecimalCharacter()
    {
        # export records with dot decimal format
        $result = self::$basicDemographyProject->exportRecordsAp(['decimalCharacter' => '.']);
        $expected = '28.7';
        $testResult = strval($result[1]['bmi']);
        $this->assertEquals($expected, $testResult, 'Export records AP decimal character check with dot/full stop');

        # export records with comma decimal format
        $result = self::$basicDemographyProject->exportRecordsAp(['decimalCharacter' => ',']);
        $expected = '28,7';
        $testResult = strval($result[1]['bmi']);
        $this->assertEquals($expected, $testResult, 'Export records AP decimal character check with comma');
    }

    public function testExportRecordsApWithInvalidDecimalCharacter()
    {
        $exceptionCaught = false;

        try {
            $result = self::$basicDemographyProject->exportRecordsAp(['decimalCharacter' => '%']);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->assertEquals($expectedCode, $exception->getCode());
        }

        $this->assertTrue($exceptionCaught, 'Exception caught check');
    }
}

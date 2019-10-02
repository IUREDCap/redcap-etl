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
 * PHPUnit integration tests batch processing.
 */
class BatchesTest extends TestCase
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
    
    public function testRecordIdBatches()
    {
        $expectedResult = self::$longitudinalDataProject->exportRecords($format = 'csv');
        
        # 100 records, so a batch size of 20 should return 5 batches
        $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(20);
        $this->assertEquals(5, count($recordIdBatches), '20 batch size test');
        
        $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(15);
        $this->assertEquals(7, count($recordIdBatches), '15 batch size test');
        $this->assertEquals(10, count($recordIdBatches[6]), '15 batch size last batch test');
    }
    
    
    public function testExportWithBatches()
    {
        $expectedResult = self::$longitudinalDataProject->exportRecords($format = 'csv');

        $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(10);
        
        $result = '';
        $isFirst = true;
        foreach ($recordIdBatches as $recordIdBatch) {
            $records = self::$longitudinalDataProject->exportRecordsAp(
                ['format' => 'csv', 'recordIds' => $recordIdBatch]
            );

            if ($isFirst) {
                $result .= $records;
                $isFirst = false;
            } else {
                # delete off the header line for all except
                # the first batch.
                $result .= substr($records, strpos($records, "\n") + 1);
            }
        }
        $this->assertEquals($expectedResult, $result, 'Batch result check.');
    }

    public function testExportWithBatchesWithFilterLogic()
    {
        $expectedResult = self::$longitudinalDataProject->exportRecordsAp(
            ['format' => 'csv', 'filterLogic' => '[age] >= 60']
        );
        
        $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(10, '[age] >= 60');
        
        $result = '';
        $isFirst = true;
        foreach ($recordIdBatches as $recordIdBatch) {
            # Need to repeat filterLogic, because otherwise all records for
            # people with age >= 60 will be returned, instead of all
            # records with age (defined and) >= 60 being returned
            $records = self::$longitudinalDataProject->exportRecordsAp(
                ['format' => 'csv', 'recordIds' => $recordIdBatch, 'filterLogic' => '[age] >= 60']
            );
            
            if ($isFirst) {
                $result .= $records;
                $isFirst = false;
            } else {
                # delete off the header line for all except
                # the first batch.
                $result .= substr($records, strpos($records, "\n") + 1);
            }
        }

        $this->assertEquals($expectedResult, $result, 'Batch result check.');
    }
    
    
    
    public function testNullBatches()
    {
        $caughtException = false;
        try {
            $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(null);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($caughtException, 'Exception caught check.');
    }
    
    
    public function testNonIntegerBatches()
    {
        $caughtException = false;
        try {
            $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches("two");
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($caughtException, 'Exception caught check.');
    }
    
    public function testZeroBatches()
    {
        $caughtException = false;
        try {
            $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(0);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($caughtException, 'Exception caught check.');
    }
}

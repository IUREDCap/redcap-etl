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
 * PHPUnit integration tests for arms.
 */
class ArmsTest extends TestCase
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
    
    public function testExportArms()
    {
        $result = self::$longitudinalDataProject->exportArms();
        
        $this->assertEquals(2, count($result), 'Number of arms test.');
        
        $this->assertEquals($result[0]['arm_num'], 1);
        $this->assertEquals($result[1]['arm_num'], 2);
        
        $this->assertEquals($result[0]['name'], 'Drug A');
        $this->assertEquals($result[1]['name'], 'Drug B');
    }
    
    public function testExportArmsWithNullArms()
    {
        $result = self::$longitudinalDataProject->exportArms($format = 'php', $arms = null);
    
        $this->assertEquals(count($result), 2, 'Number of arms test.');
    }
    
    public function testExportArmsNonArrayArms()
    {
        $arms = 'invalid';
        
        # Invalid non-array arm type
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportArms($format = 'php', $arms);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Non-array arm type returned INVALID_ARGUMENT.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Non-array arm type exception caught.');
    }
    
    public function testExportArmsNonNumericStringArm()
    {
    
        $arms = ['abc'];
        
        # Invalid non-array arm type
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportArms($format = 'php', $arms);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Non-numeric string arm returned INVALID_ARGUMENT.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Non-numeric string arm exception caught.');
    }
    
    public function testExportArmsNegativeArm()
    {
    
        $arms = [-1];
    
        # Invalid non-array arm type
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportArms($format = 'php', $arms);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Negative arm returned INVALID_ARGUMENT.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Negative arm exception caught.');
    }
    
    public function testExportArmsInvalidType()
    {
    
        $arms = [false];
    
        # Invalid non-array arm type
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportArms($format = 'php', $arms);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid arm type returned INVALID_ARGUMENT.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid arm type exception caught.');
    }
}

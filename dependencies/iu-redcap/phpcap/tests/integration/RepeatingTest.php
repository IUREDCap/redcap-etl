<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for repeating forms and events for the RedCapProject class.
 */
class RepeatingTest extends TestCase
{
    private static $config;
    private static $repeatableSurveyDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$repeatableSurveyDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['repeatable.survey.api.token']
        );
    }
    
    public function testExportRepeatingIntrumentsAndEvents()
    {
        $result = self::$repeatableSurveyDataProject->exportRepeatingInstrumentsAndEvents();
        $this->assertEquals(1, count($result), 'Number of results matched.');
        
        # Invalid format
        $exceptionCaught = false;
        try {
            $result = self::$repeatableSurveyDataProject->exportRepeatingInstrumentsAndEvents($format = 'txt');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid format argument.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid format exception caught.');

        # Invalid format type
        $exceptionCaught = false;
        try {
            $result = self::$repeatableSurveyDataProject->exportRepeatingInstrumentsAndEvents($format = [1,2,3]);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid format type argument.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid format type exception caught.');
    }
}

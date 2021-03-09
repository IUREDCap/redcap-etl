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
    private static $repeatingForms;
    private static $superToken;
    private static $redCap;

    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');

        if (array_key_exists('repeatable.survey.api.token', self::$config)) {
            if (self::$config['repeatable.survey.api.token']) {
                self::$repeatableSurveyDataProject = new RedCapProject(
                    self::$config['api.url'],
                    self::$config['repeatable.survey.api.token']
                );
            }
        }

        if (array_key_exists('repeating.forms.api.token', self::$config)) {
            if (self::$config['repeating.forms.api.token']) {
                self::$repeatingForms = new RedCapProject(
                    self::$config['api.url'],
                    self::$config['repeating.forms.api.token']
                );
            }
        }

        if (array_key_exists('super.token', self::$config)) {
            self::$superToken = self::$config['super.token'];
            if (self::$superToken) {
                self::$redCap = new RedCap(self::$config['api.url'], self::$superToken);
            } else {
                self::$superToken = null;
            }
        }
    }
    
    public function testExportRepeatingIntrumentsAndEvents()
    {
        if (self::$repeatableSurveyDataProject) {
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
    
    public function testImportRepeatingIntrumentsAndEventsWithApiToken()
    {
        if (self::$repeatingForms) {
            $records = FileUtil::fileToString(__DIR__.'/../data/repeatable-survey-forms-events.csv');
            $count = self::$repeatingForms->importRepeatingInstrumentsAndEvents(
                $records,
                $format = 'csv'
            );
            $this->assertEquals(
                1,
                $count,
                'Import Repeating Instruments and Events record count.'
            );
        
            $result = self::$repeatingForms->exportRepeatingInstrumentsAndEvents($format = 'csv');
            $expected = 'form_name,custom_form_label';
            $expected .= chr(10) . 'weight,[weight_date] [weight_time]' . chr(10);
            $this->assertEquals(
                $expected,
                $result,
                'Import Repeating Instruments and Events form now repeatable check.'
            );
        }
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for field names for the RedCapProject class.
 */
class SurveyTest extends TestCase
{
    private static $config;
    private static $apiUrl;
    private static $apiToken;
    private static $repeatableSurveyProject;
    private static $participantEmail;
    private static $participantIdentifier;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        
        self::$apiUrl = self::$config['api.url'];
        
        if (array_key_exists('repeatable.survey.api.token', self::$config)) {
            self::$apiToken              = self::$config['repeatable.survey.api.token'];
            self::$participantEmail      = self::$config['survey.participant.email'];
            self::$participantIdentifier = self::$config['survey.participant.identifier'];
        } else {
            self::$apiToken              = null;
            self::$participantEmail      = null;
            self::$participantIdentifier = null;
        }
        
        self::$repeatableSurveyProject = null;
        if (self::$apiToken != null && self::$apiToken !== '') {
            self::$repeatableSurveyProject = new RedCapProject(self::$apiUrl, self::$apiToken);
        }
    }
    
    public function testExportSurveyLink()
    {
        if (isset(self::$repeatableSurveyProject)) {
            $recordId = 1;
            $form = "weight";
            $surveyLink = self::$repeatableSurveyProject->exportSurveyLink($recordId, $form);
            
            $this->assertNotNull($surveyLink, 'Non-null survey link check.');
            
            $this->assertStringStartsWith('http', $surveyLink, 'Survey link starts with "http".');
        }
    }
    
    public function testExportSurveyLinkWithNullForm()
    {
        if (isset(self::$repeatableSurveyProject)) {
            $recordId = 1;
            $caughtException = false;
            try {
                $surveyLink = self::$repeatableSurveyProject->exportSurveyLink($recordId, null);
            } catch (PhpCapException $exception) {
                $caughtException = true;
                $code = $exception->getCode();
                $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
            }
            $this->assertTrue($caughtException, 'Exception caught check.');
        }
    }
    
    public function testExportSurveyParticipants()
    {
        if (isset(self::$repeatableSurveyProject)) {
            $form = 'weight';
            $surveyParticipants = self::$repeatableSurveyProject->exportSurveyParticipants($form);

            $emailFound = false;
            $identifierFound = false;
            foreach ($surveyParticipants as $participant) {
                if ($participant['email'] === self::$participantEmail) {
                    $emailFound = true;
                }
                if ($participant['identifier'] === self::$participantIdentifier) {
                    $identifierFound = true;
                }
                if ($emailFound === true && $identifierFound === true) {
                    break;
                }
            }
            
            $this->assertTrue($emailFound, 'Participant e-mail found.');
            $this->assertTrue($identifierFound, 'Participant identifier found.');
        }
    }
    
    
    public function testExportSurveyQueueLink()
    {
        if (isset(self::$repeatableSurveyProject)) {
            $recordId = 1;
            $form = "weight";
            $surveyQueueLink = self::$repeatableSurveyProject->exportSurveyQueueLink($recordId, $form);
            
            $this->assertNotNull($surveyQueueLink, 'Non-null survey queue link check.');
            
            $this->assertStringStartsWith('http', $surveyQueueLink, 'Survey queue link starts with "http".');
        }
    }
    
    
    public function testExportSurveyReturnCode()
    {
        if (isset(self::$repeatableSurveyProject)) {
            $recordId = 1;
            $form = "weight";
            $surveyReturnCode = self::$repeatableSurveyProject->exportSurveyReturnCode($recordId, $form);
            
            $this->assertNotNull($surveyReturnCode, 'Non-null survey return code check.');
            $this->assertTrue(ctype_alnum($surveyReturnCode), 'Alphanumeric survey return code check.');
        }
    }
}

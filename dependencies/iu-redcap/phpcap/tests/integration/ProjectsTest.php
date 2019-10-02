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
class ProjectsTest extends TestCase
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
    
    /**
     * Note: need to have an actual test that creates a project, otherwise the constructor
     * won't show up in code coverage
     */
    public function testCreateProject()
    {
        $basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        $this->assertNotNull($basicDemographyProject, "Basic demography project not null.");
        
        $longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
        $this->assertNotNull($longitudinalDataProject, "Longitudinal data project not null.");
    }
    

    public function testCreateProjectWithInvalidErrorHandler()
    {
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(
                $apiUrl = self::$config['api.url'],
                $apiToken = self::$config['basic.demography.api.token'],
                $sslVerify = false,
                $caCertificateFile = null,
                $errorHandler = 123
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid error handler check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid error handler exception caught.');
    }
    
    public function testCreateProjectWithInvalidConnection()
    {
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(
                $apiUrl = self::$config['api.url'],
                $apiToken = self::$config['basic.demography.api.token'],
                $sslVerify = false,
                $caCertificateFile = null,
                $errorHandler = null,
                $connection = 123
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid connection check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid connection exception caught.');
    }
    
    
    public function testGetApiInfo()
    {
        $apiUrl = self::$config['api.url'];
        $basicDemographyApiToken = self::$config['basic.demography.api.token'];
        
        $project = new RedCapProject($apiUrl, $basicDemographyApiToken);
        
        $apiToken = $project->getApiToken();
        
        $this->assertEquals($basicDemographyApiToken, $apiToken, 'API Token check.');
    }
}

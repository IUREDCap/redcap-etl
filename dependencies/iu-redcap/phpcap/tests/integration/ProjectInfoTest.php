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
 * PHPUnit tests for Project Info.
 */
class ProjectInfoTest extends TestCase
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
    }
  
    public function testExportProjectInfo()
    {
        $callInfo = true;
        $result = self::$basicDemographyProject->exportProjectInfo();
        
        $this->assertEquals($result['project_language'], 'English', 'Project info "project_language" test.');
    }
 
    public function testExportProjectInfoExternalModules()
    {
        $dateCalculateField = 'vanderbilt_datecalculatedfields';
        $result = self::$basicDemographyProject->exportProjectInfo();

        $this->assertStringContainsString(
            $dateCalculateField,
            $result['external_modules'],
            'Project info "external modules" test.'
        );
    }
}

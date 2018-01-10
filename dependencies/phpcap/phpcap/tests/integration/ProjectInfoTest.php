<?php

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
    
    public static function setUpBeforeClass()
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
}

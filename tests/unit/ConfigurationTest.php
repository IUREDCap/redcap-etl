<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Logger class.
 */
class ConfigurateTest extends TestCase
{
    public function setUp()
    {
    }
    
    public function testConfig()
    {
        $propertiesFile = __DIR__.'/../data/config-test.ini';
        $config = new Configuration('test-app', $propertiesFile);
        $this->assertNotNull($config, 'logger not null check');
    }
}

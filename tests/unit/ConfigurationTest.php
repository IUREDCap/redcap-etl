<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Logger class.
 */
class ConfigurationTest extends TestCase
{
    public function setUp()
    {
    }
    
    public function testConfig()
    {
        $propertiesFile = __DIR__.'/../data/config-test.ini';
        $logger = new Logger('test-app');

        $config = new Configuration($logger, $propertiesFile);
        $this->assertNotNull($config, 'logger not null check');
    }

    public function testNullPropertiesFile()
    {
        $propertiesFile = null;
        $logger = new Logger('test-app');

        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught');

        $expectedCode = EtlException::INPUT_ERROR;
        $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
    }

    public function testNonExistentPropertiesFile()
    {
        $propertiesFile = __DIR__.'/../data/non-existent-config-file.ini';
        $logger = new Logger('test-app');

        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught');

        $expectedCode = EtlException::INPUT_ERROR;
        $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
    }
}

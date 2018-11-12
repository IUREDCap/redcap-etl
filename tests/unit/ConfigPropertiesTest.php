<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Logger class.
 */
class ConfigPropertiesTest extends TestCase
{
    public function setUp()
    {
    }

    public function testIsValid()
    {
        $isValid = ConfigProperties::isValid('Not a valid property');
        $this->assertFalse($isValid, 'Invalid property test');

        $isValid = ConfigProperties::isValid(ConfigProperties::BATCH_SIZE);
        $this->assertTrue($isValid, 'Valid property test');
    }

    public function testIsValidConfigProject()
    {
        $validProperty = ConfigProperties::ALLOWED_SERVERS;
        $invalidProperty = 'nonsense string';

        $isValid = ConfigProperties::isValidInConfigProject($invalidProperty);
        $this->assertFalse($isValid, 'Inalid in file property test');

        $isValid = ConfigProperties::isValidInConfigProject($validProperty);
        $this->assertTrue($isValid, 'Valid in file property test');
    }
}

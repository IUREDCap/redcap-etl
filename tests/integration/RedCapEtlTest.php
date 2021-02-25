<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 *  This tests parts of the RedCapEtl class that are not
 *  covered by other tests.
 */
class RedCapEtlTest extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $logger;
    private static $properties;
    private static $originalTimezone;

    public static function setUpBeforeClass(): void
    {
        $app = basename(__FILE__, '.php');
        self::$logger = new Logger($app);
        if (date_default_timezone_get()) {
            self::$originalTimezone = date_default_timezone_get();
        } else {
            self::$originalTimezone = null;
        }
    }

    public function testConstructorAndRun()
    {
        $configFile = __DIR__.'/../config/basic-demography.ini';
        $rulesFile  = __DIR__.'/../config/basic-demography-rules.txt';

        $redCapEtl = new RedCapEtl(self::$logger, $configFile);
        $redCapEtl->run();

        $this->assertNotNull($redCapEtl, 'Not null redCapEtl check');

        $rulesText = $redCapEtl->getTransformationRulesText(0);
        $this->assertNotNull($rulesText, 'Rules text not null check');

        $expectedRulesText = file_get_contents($rulesFile);
        $this->assertEquals($expectedRulesText, $rulesText, 'Rules text match check');
    }


    /**
     * The basic-demography-3.ini file contains a designation
     * for the timezone and a missing api url and token.
     */
    public function testConstructRedCapObjectError()
    {
        $configFile = __DIR__.'/../config/basic-demography-3.ini';

        # Check to see if an error will be generated because of the missing
        # api token.
        $exceptionCaught = false;
        $expectedMessage = "No REDCap API URL was specified.";
        $expectedCode = EtlException::INPUT_ERROR;

        try {
            $redCapEtl = new RedCapEtl(self::$logger, $configFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'RedCapEtlTest, testConstructRedCapObjectError exception caught'
        );

        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'RedCapEtlTest, testConstructRedCapObjectError exception error code check'
        );

        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'RedCapEtlTest, testConstructRedCapObjectError exception error message check'
        );
    }

    public function testTimezone()
    {
        $baseDir = __DIR__.'/../config';
        $configFile = $baseDir.'/basic-demography.ini';
        $properties = TaskConfig::getPropertiesFromFile($configFile);
        $properties[ConfigProperties::TIMEZONE] = 'Arctic/Longyearbyen';

        $redCapEtl = new RedCapEtl(self::$logger, $properties, null, $baseDir);

        $expectedTimezone = 'Arctic/Longyearbyen';
        $currentTimezone = null;
        if (date_default_timezone_get()) {
            $currentTimezone = date_default_timezone_get();
        }
          
        $this->assertEquals(
            $expectedTimezone,
            $currentTimezone,
            'RedCapEtlTest, testConstructRedCapObjectError timezone change check'
        );
    }

    /**
     * This tests the exception condition of the autogenerateRules method
     * by using PHPUnit Reflection to create the condition by setting
     * the RedCapEtl->dataProject object to null.
     */
    /* NEEDS TO BE REWRITEN:
    public function testAutogenerateRulesException()
    {
        $configFile = __DIR__.'/../config/basic-demography.ini';
        $exceptionCaught = false;
        $expectedMessage = "No data project was found.";
        $expectedCode = EtlException::INPUT_ERROR;

        #create the redCapEtl object and establis the error condition
        # by setting the dataProject to null
        $redCapEtl = new RedCapEtl(self::$logger, $configFile);
        $reflection = new \ReflectionClass($redCapEtl);
        $property = $reflection->getProperty('dataProject');
        $property->setAccessible(true);
        $property->setValue($redCapEtl, null);

        #Check to see if the error is generated when invoking autoGenerateRules.
        try {
            $redCapEtl->autoGenerateRules();
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'RedCapEtlTest, testAutogenerateRulesException exception caught'
        );

        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'RedCapEtlTest, testAutogenerateRulesException error code check'
        );

        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'RedCapEtlTest, testAutogenerateRulesException error message check'
        );
    }
    */
}

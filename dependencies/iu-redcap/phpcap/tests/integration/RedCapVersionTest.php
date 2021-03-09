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
 * PHPUnit tests for REDCap version.
 */
class RedCapVersionTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    private static $testProject;
    private static $superToken;
    private static $redcap;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );

        if (array_key_exists('super.token', self::$config)) {
            self::$superToken = self::$config['super.token'];

            self::$redcap = new RedCap(self::$config['api.url'], self::$superToken);
        } else {
            self::$superToken = null;
            self::$redcap     = null;
        }
    }

    public function testExportRedcapVersion()
    {
        $result = self::$basicDemographyProject->exportRedcapVersion();
        $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9]+$/', $result, 'REDCap version format test.');
    }

    public function testExportRedcapVersionWithSuperToken()
    {
        if (self::$redcap) {
            $result = self::$redcap->exportRedcapVersion();
            $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9]+$/', $result, 'REDCap version super token format test.');
        }
    }
}

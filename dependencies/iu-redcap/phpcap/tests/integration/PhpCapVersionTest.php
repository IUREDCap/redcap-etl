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
class PhpCapVersionTest extends TestCase
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
    
    public function testGetPhpCapVersion()
    {
        $result = self::$basicDemographyProject->getPhpCapVersion();
        $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9]+$/', $result, 'PHPCap version format test.');
    }
}

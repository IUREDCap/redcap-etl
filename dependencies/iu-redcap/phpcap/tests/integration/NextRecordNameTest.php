<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for next record names for the RedCapProject class.
 */
class NextRecordNameTest extends TestCase
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
    
    public function testNextRecordName1()
    {
        $nextRecordName = self::$basicDemographyProject->generateNextRecordName();
        $this->assertEquals('1101', $nextRecordName, 'Export results check.');
    }
    
    
    public function testNextRecordName2()
    {
        $nextRecordName = self::$longitudinalDataProject->generateNextRecordName();
        $this->assertEquals('1101', $nextRecordName, 'Export results check.');
    }
}

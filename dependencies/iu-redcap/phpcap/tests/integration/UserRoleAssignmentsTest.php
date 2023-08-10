<?php
#-------------------------------------------------------
# Copyright (C) 2022 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for users for the RedCapProject class.
 */
class UserRoleAssignmentsTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass(): void
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
    
    public function testUserRoleAssignments()
    {
        # Export the current user role assignments
        $userRoleAssignments = self::$basicDemographyProject->exportUserRoleAssignments();
        $this->assertNotNull($userRoleAssignments, 'User role assignments non-null check.');

        # Import the user role assignments that were just exported
        $importResult = self::$basicDemographyProject->importUserRoleAssignments($userRoleAssignments);
        $this->assertEquals(1, $importResult, 'Import result check.');

        # Check to make sure that importing the exported user role assignments didn't change the
        # user role assignments
        $newUserRoleAssignments = self::$basicDemographyProject->exportUserRoleAssignments();
        $this->assertEquals($userRoleAssignments, $newUserRoleAssignments, 'User role assignments no change check');
    }
}

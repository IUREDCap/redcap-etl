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
class UserRolesTest extends TestCase
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
    
    public function testImportExportDeleteUserRoles()
    {
        $userRoles = [
            [
                'role_label' => 'Project Manager',
                'user_rights' => 0
            ]
        ];

        $importResult = self::$basicDemographyProject->importUserRoles($userRoles);

        $userRoles = self::$basicDemographyProject->exportUserRoles();
        $uniqueRoleNames = array_column($userRoles, 'unique_role_name');

        $this->assertNotNull($userRoles, 'Non-null users check.');
        $this->assertEquals(1, count($userRoles), 'User roles count check.');

        $rolesDeleted = self::$basicDemographyProject->deleteUserRoles($uniqueRoleNames);

        $userRoles = self::$basicDemographyProject->exportUserRoles();
    }

    public function testDeleteUserRolesErrors()
    {
        $uniqueRoleNames = 123;

        $exceptionCaught = false;
        try {
            $rolesDeleted = self::$basicDemographyProject->deleteUserRoles($uniqueRoleNames);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Non-array user roles error check.');

        $uniqueRoleNames = ['test', 'abc', 123];
        $exceptionCaught = false;
        try {
            $rolesDeleted = self::$basicDemographyProject->deleteUserRoles($uniqueRoleNames);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Non-string role in user roles array error check.');
    }
}

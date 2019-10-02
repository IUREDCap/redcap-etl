<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for users for the RedCapProject class.
 */
class UsersTest extends TestCase
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
    
    public function testExportUsers()
    {
        # This should return at least the owner of the project
        $users = self::$basicDemographyProject->exportUsers();

        $this->assertNotNull($users, 'Non-null users check.');
        
        $this->assertEquals('array', gettype($users), 'Users type check.');
        $this->assertGreaterThan(0, count($users), 'At least one user check.');
        $this->assertEquals('array', gettype($users), 'Users type check.');
        
        $user1 = $users[0];
        
        $this->assertEquals('array', gettype($user1), 'User 1 type check.');
        $this->assertTrue(array_key_exists('username', $user1), 'Key username check.');
    }
    
    public function testImportUsers()
    {
        $newUserData = array(
            [
                'username'      => 'phpcap_import_test_user',
                'design'        => 1,
                'data_export'   => 1,
                'record_create' => 1
            ]
        );
        
        $numberImported = self::$basicDemographyProject->importUsers($newUserData);
        $this->assertEquals(1, $numberImported, 'Number imported check.');

        $users = self::$basicDemographyProject->exportUsers();
        
        $newUser = null;
        foreach ($users as $user) {
            if ($user['username'] === $newUserData[0]['username']) {
                $newUser = $user;
                break;
            }
        }
        
        $this->assertNotNull($newUser, 'New user found in export check.');
        $this->assertEquals($newUser['design'], 1, 'Design check 1.');
        $this->assertEquals($newUser['data_export'], 1, 'Data export check 1.');
        $this->assertEquals($newUser['record_create'], 1, 'Record create check 1.');
        
        #------------------------------------------------------
        # Re-import with permissions set to zero, and check
        #------------------------------------------------------
        $newUserData[0]['design']      = 0;
        $newUserData[0]['data_export'] = 0;
        $newUserData[0]['record_create'] = 0;
        
        $numberImported = self::$basicDemographyProject->importUsers($newUserData);
        $this->assertEquals(1, $numberImported, 'Number imported check.');
        
        $users = self::$basicDemographyProject->exportUsers();
        
        $newUser = null;
        foreach ($users as $user) {
            if ($user['username'] === $newUserData[0]['username']) {
                $newUser = $user;
                break;
            }
        }
        
        $this->assertNotNull($newUser, 'New user found in export check.');
        $this->assertEquals($newUser['design'], 0, 'Design check 2.');
        $this->assertEquals($newUser['data_export'], 0, 'Data export check 2.');
        $this->assertEquals($newUser['record_create'], 0, 'Record create check 2.');
        
        # There's no way (as of June 2017) to delete the user that was added.
    }
}

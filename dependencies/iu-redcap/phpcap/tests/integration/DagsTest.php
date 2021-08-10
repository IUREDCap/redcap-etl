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
 * PHPUnit integration tests for DAGs.
 */
class DagsTest extends TestCase
{
    private static $config;
    private static $dagsProject;
    
    public static function setUpBeforeClass(): void
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$dagsProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['dags.api.token']
        );
        
        #clean up in case a prior test failed7yy
        $result = self::$dagsProject->exportDags($format='php');
        $dagNames = array_column($result, 'unique_group_name');
        if (in_array('group3', $dagNames)) {
            self::$dagsProject->deleteDags(array('group3'));
        }
        if (in_array('group4', $dagNames)) {
            self::$dagsProject->deleteDags(array('group4'));
        }
    }
    
    public function testExportDags()
    {
        $result = self::$dagsProject->exportDags($format='php');

        $this->assertEquals(2, count($result), 'Exports DAGs count check.');

        $dagNames = array_column($result, 'data_access_group_name');
        $this->assertContains('group1', $dagNames, 'Export DAGs name check.');
    }
      
    public function testImportAndDeleteDag()
    {
        # add the new DAG
        $newDag = [
            'data_access_group_name'  => 'group3',
            'unique_group_name' => ""
        ];
        
        $newDags = [$newDag];
        $result = self::$dagsProject->importDags($newDags, $format='php');
        $this->assertEquals(1, $result, 'Import DAG count check.');

        $result = self::$dagsProject->exportDags($format='php');
        $dagNames = array_column($result, 'data_access_group_name');
        $this->assertContains('group3', $dagNames, 'Import DAGs name check.');

        # delete the added DAG
        $deleteUniqueDags = array('group3');
        
        $result = self::$dagsProject->deleteDags($deleteUniqueDags);
        $this->assertEquals(1, $result, 'Delete DAG count check.');

        $result = self::$dagsProject->exportDags($format='php');
        $dagNames = array_column($result, 'data_access_group_name');
        $this->assertNotContains('group3', $dagNames, 'Delete DAGs name check.');
    }

    public function testExportUserDagAssignment()
    {
        $result = self::$dagsProject->exportUserDagAssignment($format='php');

        $this->assertGreaterThan(0, count($result), 'Exports DAG assignment count check.');

        $dagNames = array_column($result, 'redcap_data_access_group');
        $this->assertContains('group1', $dagNames, 'Export DAG assignment name check.');
    }

    public function testImportUserDagAssignment()
    {
        #This test assumes that the project was set up with a test user as specified in
        #the setup instructions.

        #get a user username
        $result = self::$dagsProject->exportUserDagAssignment($format='php');
        $username = array_column($result, 'username')[0];

        #create a new dag
        $newDag = [
            'data_access_group_name'  => 'group4',
            'unique_group_name' => ""
        ];
        $newDags = [$newDag];
        self::$dagsProject->importDags($newDags, $format='php');

        #map the user to the new dag
        $newDagAssignment = [
            'username'  => $username,
            'redcap_data_access_group'  => 'group4'
        ];
        $newDagAssignments = [$newDagAssignment];
        $result = self::$dagsProject->importUserDagAssignment($newDagAssignments, $format='php');

        $this->assertEquals(1, $result, 'Import User-DAG assignment count check.');

        $result = self::$dagsProject->exportUserDagAssignment($format='php');
        $key = array_search('group4', array_column($result, 'redcap_data_access_group'));
        $assignedUsername = $result[$key]['username'];
        $this->assertEquals($username, $assignedUsername, 'Import User-DAG assignment username check.');

        # delete the added DAG
        $deleteUniqueDags = array('group4');
        $result = self::$dagsProject->deleteDags($deleteUniqueDags);
    }
    
    public function testDagsArgumentNotSet()
    {
        $dagsArgument = null;
        $exceptionCaught = false;
        try {
            $result = self::$dagsProject->deleteDags($dagsArgument);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $code,
                'Exception code check.',
                'testDagsArgumentNotSet'
            );
        }
    
        $this->assertTrue($exceptionCaught, 'Exception caught.', 'testDagsArgumentNotSet');
    }
    
    public function testDagsArgumentNull()
    {
        $dagsArgument = array();
        $exceptionCaught = false;
        try {
            $result = self::$dagsProject->deleteDags($dagsArgument);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $code,
                'Exception code check.',
                'testDagsArgumentNull'
            );
        }
    
        $this->assertTrue($exceptionCaught, 'Exception caught.', 'testDagsArgumentNull');
    }
    
    public function testDagsArgumentNotArray()
    {
        $dagsArgument = 'hello';
        $exceptionCaught = false;
        try {
            $result = self::$dagsProject->deleteDags($dagsArgument);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $code,
                'Exception code check.',
                'testDagsArugmentNotArray'
            );
        }
    
        $this->assertTrue($exceptionCaught, 'Exception caught.', 'testDagsArgumentNotArray');
    }
    
    public function testDagsArgumentElementNotString()
    {
        $dagsArgument = array(true);
        $exceptionCaught = false;
        try {
            $result = self::$dagsProject->deleteDags($dagsArgument);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $code,
                'Exception code check.',
                'testDagsArugmentElementNotString'
            );
        }
    
        $this->assertTrue($exceptionCaught, 'Exception caught.', 'testDagsArgumentElementNotString');
    }
}

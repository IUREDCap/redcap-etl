<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating forms" systems tests.
 */
class RepeatingFormsSystemTest extends TestCase
{
    const CONFIG_FILE = '';

    const TEST_DATA_DIR   = __DIR__.'/../data/';     # directory with test data comparison files
    
    protected static $dbConnection;
    protected static $logger;


    public static function setUpBeforeClass()
    {
        if (file_exists(static::CONFIG_FILE)) {
            self::$logger = new Logger('repeating-forms-mysql-system-test');
        }
    }

    public function setUp()
    {
        if (!file_exists(static::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }


    public function runEtl($logger, $configFile)
    {
        try {
            $redCapEtl = new RedCapEtl($logger, $configFile);
            $redCapEtl->run();

            # Get the database connection.
            $firstTask = $redCapEtl->getTask(0);
            self::$dbConnection = $firstTask->getDbConnection();
        } catch (Exception $exception) {
            print "\nEXCEPTION in test ".__FILE__.": {$exception->getMessage()}\n";
            $logger->logException($exception);
            $logger->log('Processing failed.');
        }
    }

    public function testRepeatingForms()
    {
        $this->runEtl(static::$logger, static::CONFIG_FILE);

        $this->assertNotNull(self::$dbConnection, 'Database connection not null check');

        $this->registrationTableTest();
        $this->weightTableTest();
        $this->emergencyTableTest();
    }

    public function registrationTableTest()
    {
        $data = self::$dbConnection->getData('rf_registration', 'registration_id');
        $this->assertEquals(50, count($data), 'Row count check');

        # "rf_" is the table prefix specified in config
        $columnNames = self::$dbConnection->getTableColumnNames('rf_registration');
        $this->assertNotNull($columnNames);

        $expectedColumns = [
            'registration_id',
            'redcap_data_source',
            'record_id',
            'registration_date',
            'first_name',
            'last_name',
            'address',
            'phone',
            'email',
            'dob',
            'ethnicity',
            'race___0',
            'race___1',
            'race___2',
            'race___3',
            'race___4',
            'race___5',
            'sex',
            'physician_approval',
            'diabetic',
            'diabetes_type',
            'consent_form',
            'comments'
        ];

        $columns = self::$dbConnection->getTableColumnNames('rf_registration');
        $this->assertEquals($expectedColumns, $columns, 'Column name check');
    }

    public function weightTableTest()
    {
        $data = self::$dbConnection->getData('rf_weight', 'weight_id');
        $this->assertEquals(132, count($data), 'Row count check');

        $columns = self::$dbConnection->getTableColumnNames('rf_weight');
        $this->assertNotNull($columns);

        $expectedColumns = [
            'weight_id',
            'registration_id',
            'redcap_data_source',
            'record_id',
            'redcap_repeat_instrument',
            'redcap_repeat_instance',
            'weight_time',
            'weight_kg',
            'height_m',
            'bmi',
            'weekly_activity_level'
        ];
        $this->assertEquals($expectedColumns, $columns, 'Column name check');
    }

    public function emergencyTableTest()
    {
        $data = self::$dbConnection->getData('rf_emergency', 'emergency_id');
        $this->assertEquals(1, count($data), 'Row count check');

        $columns = self::$dbConnection->getTableColumnNames('rf_emergency');
        $this->assertNotNull($columns);

        $expectedColumns = [
            'emergency_id',
            'redcap_data_source',
            'record_id',
            'contact_name',
            'contact_address',
            'contact_email',
            'contact_phone'
        ];
        $this->assertEquals($expectedColumns, $columns, 'Column name check');
    }
}

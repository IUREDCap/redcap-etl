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
 * PHPUnit integration tests for Logging.
 */
class LoggingTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $dagsProject;
    private static $redCapVersion;
    private static $redCapMajorVersion;
    
    const DELETE_WAIT_TIME = 60;
    const INSERT_WAIT_TIME = 90;

    public static function setUpBeforeClass(): void
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );

        self::$dagsProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['dags.api.token']
        );
        
        #clean up any records that did not get properly deleted from a prior test.
        $oldIds = [1200, 1201];
        $runWait = false;
        foreach ($oldIds as $id) {
            $exists = self::$basicDemographyProject->exportRecordsAp(['recordIds' => [$id]]);
            if (count($exists) > 0) {
                self::$basicDemographyProject->deleteRecords([$id]);
                $runWait = true;
            }
        }
        
        # If records were deleted, wait three minutes so that the test doesn't
        # pick up these log entries for the deletions as modified records
        if ($runWait) {
            sleep(self::DELETE_WAIT_TIME);
        };

        self::$redCapVersion = self::$basicDemographyProject->exportRedCapVersion();
        self::$redCapMajorVersion = intval(explode(".", self::$redCapVersion)[0]);
    }
    
    public function testExportLoggingWithDateRange()
    {
        
        # Establish the begin date
        $twoMinutesAgo = new \DateTime();
        $twoMinutesAgo->sub(new \DateInterval('PT2M'));

        # Create a test record to insert
        $records = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import2.csv');
        $result = self::$basicDemographyProject->importRecords(
            $records,
            $format = 'csv',
            $type = null,
            $overwriteBehavior = null,
            $dateFormat = null,
            $returnContent = null
        );

        # Establish the end date
        $fiveMinutesFromNow = new \DateTime();
        $fiveMinutesFromNow->add(new \DateInterval('PT5M'));

        #adjust for timezone
        if (array_key_exists('timezone', self::$config)) {
            $tz = self::$config['timezone'];
        } else {
            $message = 'No timezone defined in configuration file "'.realpath(self::$configFile).'"';
            throw new \Exception($message);
        }

        if ($tz) {
            $twoMinutesAgo->setTimezone(new \DateTimeZone($tz));
            $fiveMinutesFromNow->setTimezone(new \DateTimeZone($tz));
        }
        
        $result = self::$basicDemographyProject->exportLogging(
            $format='php',
            $logType = null,
            $username = null,
            $recordId = null,
            $dag = null,
            $beginTime = $twoMinutesAgo->format('Y-m-d H:i:s'),
            $endTime = $fiveMinutesFromNow->format('Y-m-d H:i:s')
        );

        $this->assertGreaterThan(0, count($result), 'Export logging count check.');
        
        // It appears that from version 11 to 12 of REDCap, the messages generated changed a bit.
        $actions = array_column($result, 'action');
        if (self::$redCapMajorVersion <= 11) {
            $expected = 'Created Record (API) 1200';
        } else {
            $expected = 'Create record (API) 1200';
        }
        $this->assertContains($expected, $actions, 'Export logging begin date-action check.');

        #test entering a begin date in the future so that no records should be returned
        $result1 = self::$basicDemographyProject->exportLogging(
            $format='php',
            $logType = null,
            $username = null,
            $recordId = null,
            $dag = null,
            $beginTime = '2100-01-01 00:00:00',
            $endTime = null
        );
        $this->assertEquals(0, count($result1), "Export logging begin date with future begin date.");

        #test entering an end date in the past so that no records should be returned
        $result2 = self::$basicDemographyProject->exportLogging(
            $format='php',
            $logType = null,
            $username = null,
            $recordId = null,
            $dag = null,
            $beginTime = null,
            $dateRangeEnd = '1901-01-01 00:00:00'
        );
        $this->assertEquals(0, count($result2), "Export logging end date with old end date.");

        # Clean up the test by deleting the inserted test record
        self::$basicDemographyProject->deleteRecords([1200]);
    }
      
    public function testExportLoggingLogType()
    {
        $result = self::$basicDemographyProject->exportRecords($format = null);
        $exportLogType = 'export';
        $logs = self::$basicDemographyProject->exportLogging(
            $format='php',
            $logType = $exportLogType,
            $username = null,
            $recordId = null,
            $dag = null,
            $beginTime = null,
            $endTime = null
        );

        $expected = 'Data Export (API) ';
        $result = $logs[0]['action'];
        $this->assertEqualsIgnoringCase($expected, $result, "Export logging: log type check.");

        $badLogType = 'nonsense';
        $exceptionCaught = false;
        try {
            $logs = self::$basicDemographyProject->exportLogging(
                $format='php',
                $logType = $badLogType,
                $username = null,
                $recordId = null,
                $dag = null,
                $beginTime = null,
                $endTime = null
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid error handler check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid error handler exception caught.');
    }
    
    public function testExportLoggingUser()
    {
        # do something that creates a log entry (i.e., an export) and get the user for that log entry
        $result = self::$basicDemographyProject->exportRecords($format = null);
        $logType = 'export';
        $logs = self::$basicDemographyProject->exportLogging(
            $format='php',
            $logType = $logType,
            $username = null,
            $recordId = null,
            $dag = null,
            $beginTime = null,
            $endTime = null
        );

        # get the username and query the logs for that username
        $username = $logs[0]['username'];
        $result = self::$basicDemographyProject->exportLogging(
            $format='php',
            $logType = null,
            $username = $username,
            $recordId = null,
            $dag = null,
            $beginTime = null,
            $endTime = null
        );
        $users = array_unique(array_column($result, 'username'));
        $this->assertEquals(1, count($users), "Export logging: user count check.");
        $this->assertEquals($username, $users[0], "Export logging: username check.");

        $badUsername = true;
        try {
            $logs = self::$basicDemographyProject->exportLogging(
                $format='php',
                $logType = null,
                $username = $badUsername,
                $recordId = null,
                $dag = null,
                $beginTime = null,
                $endTime = null
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid error handler check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid error handler exception caught.');
    }

    public function testExportLoggingRecordId()
    {
        # Create a test record to insert
        $records = FileUtil::fileToString(__DIR__.'/../data/basic-demography-import2.csv');
        $result = self::$basicDemographyProject->importRecords(
            $records,
            $format = 'csv',
            $type = null,
            $overwriteBehavior = null,
            $dateFormat = null,
            $returnContent = null
        );
        
        sleep(self::INSERT_WAIT_TIME);

        # Establish the begin date
        $fiveMinutesAgo = new \DateTime();
        $fiveMinutesAgo->sub(new \DateInterval('PT5M'));

        #adjust for timezone
        if (array_key_exists('timezone', self::$config)) {
            $tz = self::$config['timezone'];
            $fiveMinutesAgo->setTimezone(new \DateTimeZone($tz));
        } else {
            $message = 'No timezone defined in configuration file "'.realpath(self::$configFile).'"';
            throw new \Exception($message);
        }

        # get the log records for that record id
        $result = self::$basicDemographyProject->exportLogging(
            $format='php',
            $logType = null,
            $username = null,
            $recordId = '1200',
            $dag = null,
            $beginTime = $fiveMinutesAgo->format('Y-m-d H:i:s'),
            $endTime = null
        );
        $this->assertGreaterThan(0, count($result), 'Export logging Record id count check.');

        # Clean up the test by deleting the inserted test record
        self::$basicDemographyProject->deleteRecords([1200]);
    }
    
    public function testExportLoggingDag()
    {
        #This test assumes that the project was set up with a test user as specified in
        #the setup instructions.

        #get a user and an assigned dag
        $result = self::$dagsProject->exportUserDagAssignment($format='php');
        foreach ($result as $key => $dag) {
            if ($dag['redcap_data_access_group']) {
                $originalUsername = $dag['username'];
                $originalDag = $dag['redcap_data_access_group'];
                break;
            }
        }

        if (!$originalDag) {
            $this->markTestSkipped('testExportLoggingDag: No dag assignments found.');
        }

        #map the user to the same dag to create a log entry
        $dagAssignment = [
            'username'  => $originalUsername,
            'redcap_data_access_group'  => $originalDag
        ];
        $dagAssignments = [$dagAssignment];
        self::$dagsProject->importUserDagAssignment($dagAssignments, $format='php');

        # Establish the begin date
        $sevenMinutesAgo = new \DateTime();
        $sevenMinutesAgo->sub(new \DateInterval('PT7M'));
        #adjust for timezone
        if (array_key_exists('timezone', self::$config)) {
            $tz = self::$config['timezone'];
            $sevenMinutesAgo->setTimezone(new \DateTimeZone($tz));
        } else {
            $message = 'No timezone defined in configuration file "'.realpath(self::$configFile).'"';
            throw new \Exception($message);
        }

        sleep(self::INSERT_WAIT_TIME);

        # run the test
        $dags = self::$dagsProject->exportLogging(
            $format='php',
            $logType = null,
            $username = null,
            $recordId = null,
            $dag = $originalDag,
            $beginTime = $sevenMinutesAgo->format('Y-m-d H:i:s'),
            $endTime = null
        );
        
        // It appears that from version 11 to 12 of REDCap, the messages generated changed a bit.
        if (self::$redCapMajorVersion <= 11) {
            $expected = 'Import User-DAG Assignments (API)';
        } else {
            $expected = 'Import User-DAG assignments (API)';
        }
        $details = array_unique(array_column($dags, 'details'));
        $this->assertContains($expected, $details, 'Export logging DAG check.');

        $badDag = true;
        try {
            $logs = self::$basicDemographyProject->exportLogging(
                $format='php',
                $logType = null,
                $username = null,
                $recordId = null,
                $dag = $badDag,
                $beginTime = null,
                $endTime = null
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Invalid error handler check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Invalid error handler exception caught.');
    }
}

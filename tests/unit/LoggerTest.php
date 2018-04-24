<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for Logger class.
 */
class LoggerTest extends TestCase
{
    private $project;

    public function setUp()
    {
        $apiUrl   = 'https://someplace.edu/api/';
        $apiToken = '11111111112222222222333333333344';

        $this->project = new TestProject($apiUrl, $apiToken);
        $this->project->setApp('LoggerTest');
    }
    
    public function testConstructor()
    {
        $logger = new Logger('test-app');
        $this->assertNotNull($logger, 'logger not null check');
    }

    public function testLog()
    {
        $logMessages = ['Test 1', 'Test 2', 'Test 3'];
        $logApps     = array_fill(0, count($logMessages), $this->project->getApp());

        $logger = new Logger($this->project->getApp());
        $logger->setLogProject($this->project);
        $logger->setPrintInfo(false);

        foreach ($logMessages as $logMessage) {
            $logger->logInfo($logMessage);
        }

        # Get the records from the project and check them
        $logRecords = $this->project->getAllRecords();
        $logRecordMessages = array_column($logRecords, 'message');
        $logRecordApps     = array_column($logRecords, 'app');

        $this->assertEquals($logMessages, $logRecordMessages, 'Log message check');

        $this->assertEquals($logApps, $logRecordApps, 'Log app check');
    }
    
    public function testGetApp()
    {
        $logger = new Logger($this->project->getApp());

        $app = $logger->getApp();
        $this->assertEquals($this->project->getApp(), $app, 'get app check');
    }
}

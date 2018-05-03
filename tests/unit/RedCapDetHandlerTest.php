<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the RedCapDetHandler class.
 */
class RedCapDetHandlerTest extends TestCase
{

    public function setUp()
    {
    }
    
    public function testConstructor()
    {
        global $_POST;
        global $_GET;

        $projectId      = 1234;
        $recordId       = 1;
        $allowedServers = '';
        $notifier       = null;

        $detHandler = new RedCapDetHandler($projectId, $allowedServers, null);

        $this->assertNotNull($detHandler, 'DET handler not null check');
        
        $_POST['record']     = $recordId;
        $_POST['project_id'] = $projectId;
        $_POST['instrument'] = RedCapEtl::DET_INSTRUMENT_NAME;

        $params = $detHandler->getDetParams();
        $this->assertEquals($projectId, $params[0], 'post project id check');
        $this->assertEquals($recordId, $params[1], 'post record check');
        $this->assertEquals($_POST['instrument'], $params[2], 'post instrument check');

        $_GET['record']     = $recordId;
        $_GET['project_id'] = $projectId;
        unset($_POST['record']);
        unset($_POST['project_id']);

        $getParams = $detHandler->getDetParams();
        $this->assertEquals($projectId, $getParams[0], 'project id check');
        $this->assertEquals($recordId, $getParams[1], 'record check');

        $idCheck = $detHandler->checkDetId($projectId);
        $this->assertTrue($idCheck);
    }
}

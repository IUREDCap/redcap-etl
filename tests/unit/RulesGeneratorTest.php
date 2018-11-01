<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCapApiConnectionInterface;

use IU\REDCapETL\TestProject;
use IU\REDCapETL\EtlRedCapProject;

/**
 * PHPUnit tests for the RulesGenerator class.
 */
class RulesGeneratorTest extends TestCase
{   

    private $dataProject;
    
    public function setUp()
    {
        
        $this->dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')->getMock();

        $projectInfo['project_id'] = 14;
        $projectInfo['project_title'] = 'ETL_Data';
        $projectInfo['creation_time'] = '2018-04-16 13:53:19';
        $projectInfo['production_time'] =
        $projectInfo['in_production'] = 0;
        $projectInfo['project_language'] = 'English';
        $projectInfo['purpose'] = 0;
        $projectInfo['purpose_other'] =
        $projectInfo['project_notes'] =
        $projectInfo['custom_record_label'] =
        $projectInfo['secondary_unique_field'] =
        $projectInfo['is_longitudinal'] = 0;
        $projectInfo['surveys_enabled'] = 0;
        $projectInfo['scheduling_enabled'] = 0;
        $projectInfo['record_autonumbering_enabled'] = 1;
        $projectInfo['randomization_enabled'] = 0;
        $projectInfo['ddp_enabled'] = 0;
        $projectInfo['project_irb_number'] =
        $projectInfo['project_grant_number'] =
        $projectInfo['project_pi_firstname'] =
        $projectInfo['project_pi_lastname'] =
        $projectInfo['display_today_now_button'] = 1;
        $projectInfo['has_repeating_instruments_or_events'] = 0;

        $this->dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->will($this->returnValue($projectInfo));



    }
    public function testGenerate()
    {

        $apiUrl   = 'https://redcap.somplace.edu/api/';
        $apiToken = '12345678901234567890123456789012';
        $connection = $this->getMockBuilder('IU\PHPCap\RedCapApiConnectionInterface')->getMock();
        
        

        // $connection->curlHandle = 'Resource id #25';
        // $connection->curlOptions[64] = '';
        // $connection->curlOptions[81] = 2;
        // $connection->curlOptions[13] = 1200;
        // $connection->curlOptions[78] = 20;
        // $connection->curlOptions[10002] = 'https://localhost/api/';
        // $connection->curlOptions[19913] = 1;
        // $connection->curlOptions[10023][0] = 'Accept: text/xml';
        // $connection->curlOptions[47] = 1;







        print_r($this->dataProject);
        $rulesGenerator = new RulesGenerator();
        $this->rulesText = $rulesGenerator->generate($this->dataProject);

        echo $rulesText;
        $this->assertFalse($this->rulesText, 'Invalid property test');
        $this->assertTrue($this->rulesText, 'Invalid property test');


    }
}
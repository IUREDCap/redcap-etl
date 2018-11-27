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
    public function testBasicGenerate()
    {
        $jsonFile  = __DIR__.'/../data/basic-demography.json';
        $xmlFile   = __DIR__.'/../data/basic-demography.xml';
        $rulesFile = __DIR__.'/../data/basic-demography-rules.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            ->setMethods(['exportProjectInfo', 'exportInstruments', 'exportMetadata', 'exportProjectXml'])
            ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->will($this->returnValue($projectInfo));

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->will($this->returnValue($instruments));

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->will($this->returnValue($metadata));

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->will($this->returnValue($projectXml));


        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }


    public function testLongitudinalGenerate()
    {
        $jsonFile  = __DIR__.'/../data/visits.json';
        $xmlFile   = __DIR__.'/../data/visits.xml';
        $rulesFile = __DIR__.'/../data/visits-rules.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $eventMappings = $projectData->getInstrumentEventMappings();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            ->setMethods(
                [
                    'exportProjectInfo',
                    'exportInstruments',
                    'exportMetadata',
                    'exportProjectXml',
                    'exportInstrumentEventMappings'
                ]
            )
            ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->will($this->returnValue($projectInfo));

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->will($this->returnValue($instruments));

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->will($this->returnValue($metadata));

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->will($this->returnValue($projectXml));
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->will($this->returnValue($eventMappings));

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }

    public function testGenerate()
    {
        $jsonFile  = __DIR__.'/../data/repeating-events.json';
        $xmlFile   = __DIR__.'/../data/repeating-events.xml';
        $rulesFile = __DIR__.'/../data/repeating-events-rules.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $eventMappings = $projectData->getInstrumentEventMappings();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            ->setMethods(
                [
                    'exportProjectInfo',
                    'exportInstruments',
                    'exportMetadata',
                    'exportProjectXml',
                    'exportInstrumentEventMappings'
                ]
            )
            ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->will($this->returnValue($projectInfo));

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->will($this->returnValue($instruments));

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->will($this->returnValue($metadata));

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->will($this->returnValue($projectXml));
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->will($this->returnValue($eventMappings));

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }

    public function testGenerateRepeatingEventAndInst()
    {
        $jsonFile  = __DIR__.'/../data/repeating-event-inst.json';
        $xmlFile   = __DIR__.'/../data/repeating-event-inst.xml';
        $rulesFile = __DIR__.'/../data/repeating-event-inst.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $eventMappings = $projectData->getInstrumentEventMappings();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            ->setMethods(
                [
                    'exportProjectInfo',
                    'exportInstruments',
                    'exportMetadata',
                    'exportProjectXml',
                    'exportInstrumentEventMappings'
                ]
            )
            ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->will($this->returnValue($projectInfo));

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->will($this->returnValue($instruments));

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->will($this->returnValue($metadata));

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->will($this->returnValue($projectXml));
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->will($this->returnValue($eventMappings));

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }    

    public function testGenerateNonRepeating()
    {
        $jsonFile  = __DIR__.'/../data/non-repeating.json';
        $xmlFile   = __DIR__.'/../data/non-repeating.xml';
        $rulesFile = __DIR__.'/../data/non-repeating.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $eventMappings = $projectData->getInstrumentEventMappings();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            ->setMethods(
                [
                    'exportProjectInfo',
                    'exportInstruments',
                    'exportMetadata',
                    'exportProjectXml',
                    'exportInstrumentEventMappings'
                ]
            )
            ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->will($this->returnValue($projectInfo));

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->will($this->returnValue($instruments));

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->will($this->returnValue($metadata));

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->will($this->returnValue($projectXml));
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->will($this->returnValue($eventMappings));

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }
}

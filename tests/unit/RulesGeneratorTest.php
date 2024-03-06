<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

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
        $jsonFile  = __DIR__.'/../data/projects/basic-demography.json';
        $xmlFile   = __DIR__.'/../data/projects/basic-demography.xml';
        $rulesFile = __DIR__.'/../data/projects/basic-demography-rules.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $expectedResult = $projectData->getRulesText();

        //$dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            //->setMethods(['exportProjectInfo', 'exportInstruments', 'exportMetadata', 'exportProjectXml'])
            //->getMock();

        $dataProject = $this->createMock(__NAMESPACE__.'\EtlRedCapProject');


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->willReturn($projectInfo);

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->willReturn($instruments);

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->willReturn($metadata);

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->willReturn($projectXml);


        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }


    public function testLongitudinalGenerate()
    {
        $jsonFile  = __DIR__.'/../data/projects/visits.json';
        $xmlFile   = __DIR__.'/../data/projects/visits.xml';
        $rulesFile = __DIR__.'/../data/projects/visits-rules.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $eventMappings = $projectData->getInstrumentEventMappings();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->createMock(__NAMESPACE__.'\EtlRedCapProject');
        #$dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
        #    ->setMethods(
        #        [
        #            'exportProjectInfo',
        #            'exportInstruments',
        #            'exportMetadata',
        #            'exportProjectXml',
        #            'exportInstrumentEventMappings'
        #        ]
        #    )
        #    ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->willReturn($projectInfo);

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->willReturn($instruments);

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->willReturn($metadata);

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->willReturn($projectXml);
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->willReturn($eventMappings);

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }

    public function testGenerate()
    {
        $jsonFile  = __DIR__.'/../data/projects/repeating-events.json';
        $xmlFile   = __DIR__.'/../data/projects/repeating-events.xml';
        $rulesFile = __DIR__.'/../data/projects/repeating-events-rules.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $eventMappings = $projectData->getInstrumentEventMappings();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->createMock(__NAMESPACE__.'\EtlRedCapProject');
        #$dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
        #    ->setMethods(
        #        [
        #            'exportProjectInfo',
        #            'exportInstruments',
        #            'exportMetadata',
        #            'exportProjectXml',
        #            'exportInstrumentEventMappings'
        #        ]
        #    )
        #    ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->willReturn($projectInfo);

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->willReturn($instruments);

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->willReturn($metadata);

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->willReturn($projectXml);
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->willReturn($eventMappings);

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }

    public function testGenerateRepeatingEventAndInst()
    {
        $jsonFile  = __DIR__.'/../data/projects/repeating-event-inst.json';
        $xmlFile   = __DIR__.'/../data/projects/repeating-event-inst.xml';
        $rulesFile = __DIR__.'/../data/projects/repeating-event-inst.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $eventMappings = $projectData->getInstrumentEventMappings();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->createMock(__NAMESPACE__.'\EtlRedCapProject');
        #$dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
        #    ->setMethods(
        #        [
        #            'exportProjectInfo',
        #            'exportInstruments',
        #            'exportMetadata',
        #            'exportProjectXml',
        #            'exportInstrumentEventMappings'
        #        ]
        #    )
        #    ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->willReturn($projectInfo);

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->willReturn($instruments);

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->willReturn($metadata);

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->willReturn($projectXml);
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->willReturn($eventMappings);

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }

    public function testGenerateNonRepeating()
    {
        $jsonFile  = __DIR__.'/../data/projects/non-repeating.json';
        $xmlFile   = __DIR__.'/../data/projects/non-repeating.xml';
        $rulesFile = __DIR__.'/../data/projects/non-repeating.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $projectInfo = $projectData->getProjectInfo();
        $instruments = $projectData->getInstruments();
        $metadata    = $projectData->getMetadata();
        $projectXml  = $projectData->getProjectXml();

        $eventMappings = $projectData->getInstrumentEventMappings();

        $expectedResult = $projectData->getRulesText();

        $dataProject = $this->createMock(__NAMESPACE__.'\EtlRedCapProject');
        #$dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
        #    ->setMethods(
        #        [
        #            'exportProjectInfo',
        #            'exportInstruments',
        #            'exportMetadata',
        #            'exportProjectXml',
        #            'exportInstrumentEventMappings'
        #        ]
        #    )
        #    ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->willReturn($projectInfo);

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->willReturn($instruments);

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->willReturn($metadata);

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->willReturn($projectXml);
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->willReturn($eventMappings);

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $this->assertSame($expectedResult, $rulesText);
    }
}

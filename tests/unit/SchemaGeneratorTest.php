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
class SchemaGeneratorTest extends TestCase
{
    public function testConstructSchema() 
    {
        $jsonFile  = __DIR__.'/../data/projects/basic-demography.json';
        $xmlFile   = __DIR__.'/../data/projects/basic-demography.xml';
        $rulesFile = __DIR__.'/../data/projects/basic-demography-rules.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $recordIdFieldName = $projectData->getRecordIdFieldName();
        $fieldNames = $projectData->getFieldNames();

        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
        ->setMethods(['getRecordIdFieldName', 'getFieldNames'])
        ->getMock();


        // getRecordIdFieldName() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('getRecordIdFieldName')
            ->will($this->returnValue($recordIdFieldName));

        // getFieldNames() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('getFieldNames')
        ->will($this->returnValue($fieldNames));

        $propertiesFile = __DIR__.'/../data/config-test.ini';
        $logger = new Logger('test-app');

        $config = new Configuration($logger, $propertiesFile);

        $schemaGenerator = new SchemaGenerator($dataProject, $configuration, $logger);
    }
    
}
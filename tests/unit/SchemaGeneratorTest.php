<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCapApiConnectionInterface;

use IU\REDCapETL\TestProject;
use IU\REDCapETL\EtlRedCapProject;
use IU\REDCapETL\Configuration;

/**
 * PHPUnit tests for the RulesGenerator class.
 */
class SchemaGeneratorTest extends TestCase
{
    public function testConstructSchema() 
    {
        $jsonFile  = __DIR__.'/../data/projects/basic-demography.json';
        $xmlFile   = __DIR__.'/../data/projects/basic-demography.xml';
        $rulesText = __DIR__.'/../data/projects/basic-demography-rules.txt';

        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesText);

        $recordIdFieldName = $projectData->getRecordIdFieldName();
        $fieldNames = $projectData->getFieldNames();
        $lookupChoices = $projectData->getLookupChoices();
        $expectedResult = $projectData->getSchema();

        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
        ->setMethods(['getRecordIdFieldName', 'getFieldNames', 'getLookupChoices'])
        ->getMock();


        // getRecordIdFieldName() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('getRecordIdFieldName')
            ->will($this->returnValue($recordIdFieldName));

        // getFieldNames() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('getFieldNames')
        ->will($this->returnValue($fieldNames));

        // getFieldNames() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('getLookupChoices')
        ->will($this->returnValue($lookupChoices));

        $propertiesFile = __DIR__.'/../data/config-schema.ini';
        $logger = new Logger('test-app');

        $config = new Configuration($logger, $propertiesFile);

        $schemaGenerator = new SchemaGenerator($dataProject, $config, $logger);

        $schema = $schemaGenerator->generateSchema($rulesText);

        $schemaString = $schema[0]->toString();

        echo "SchemaString: " . $schemaString;
        $expectedSchemaString = "Number of tables: 1
        Number of root tables: 1
        
        Root tables
            demographics [demographics_id]
            primary key: demographics_id  : demographics_id int
            foreign key:
            rows type: 0
            Rows Suffixes:
            Possible Suffixes:
            Fields:
                record_id  : record_id varchar(255)
                first_name text : first_name string
                last_name text : last_name string
                address notes : address string
                telephone text : telephone string
                email text : email string
                dob text : dob date
                ethnicity radio : ethnicity string
                race___0 checkbox : race___0 int
                race___1 checkbox : race___1 int
                race___2 checkbox : race___2 int
                race___3 checkbox : race___3 int
                race___4 checkbox : race___4 int
                race___5 checkbox : race___5 int
                sex radio : sex string
                height text : height string
                weight text : weight string
                bmi calc : bmi string
                comments notes : comments string
            Rows:
            Children:
            primary key value: 1
            uses lookup: 1
        
        
        Tables
            demographics [demographics_id]
            primary key: demographics_id  : demographics_id int
            foreign key:
            rows type: 0
            Rows Suffixes:
            Possible Suffixes:
            Fields:
                record_id  : record_id varchar(255)
                first_name text : first_name string
                last_name text : last_name string
                address notes : address string
                telephone text : telephone string
                email text : email string
                dob text : dob date
                ethnicity radio : ethnicity string
                race___0 checkbox : race___0 int
                race___1 checkbox : race___1 int
                race___2 checkbox : race___2 int
                race___3 checkbox : race___3 int
                race___4 checkbox : race___4 int
                race___5 checkbox : race___5 int
                sex radio : sex string
                height text : height string
                weight text : weight string
                bmi calc : bmi string
                comments notes : comments string
            Rows:
            Children:
            primary key value: 1
            uses lookup: 1
        
        ";

        $this->assertSame($expectedSchemaString, $schemaString);
    }
    
}
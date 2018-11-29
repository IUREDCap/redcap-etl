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

        $propertiesFile = __DIR__.'/../data/config-test.ini';
        $logger = new Logger('test-app');

        $config = new Configuration($logger, $propertiesFile);

        $schemaGenerator = new SchemaGenerator($dataProject, $config, $logger);

        $schema = $schemaGenerator->generateSchema($rulesText);

        $expectedResult = unserialize('a:2:{i:0;O:26:"IU\REDCapETL\Schema\Schema":3:{s:34:"IU\REDCapETL\Schema\Schematables";a:1:{i:0;O:25:"IU\REDCapETL\Schema\Table":14:{s:4:"name";s:12:"demographics";s:6:"parent";s:15:"demographics_id";s:7:"primary";O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:15:"demographics_id";s:10:"redcapType";s:0:"";s:4:"type";s:3:"int";s:4:"size";N;s:6:"dbName";s:15:"demographics_id";s:10:"usesLookup";b:0;}s:7:"foreign";s:0:"";s:11:"*children";a:0:{}s:8:"rowsType";a:1:{i:0;i:0;}s:12:"rowsSuffixes";a:0:{}s:43:"IU\REDCapETL\Schema\TablepossibleSuffixes";a:0:{}s:9:"*fields";a:19:{i:0;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:9:"record_id";s:10:"redcapType";s:0:"";s:4:"type";s:7:"varchar";s:4:"size";i:255;s:6:"dbName";s:9:"record_id";s:10:"usesLookup";b:0;}i:1;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:10:"first_name";s:10:"redcapType";s:4:"text";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:10:"first_name";s:10:"usesLookup";b:0;}i:2;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:9:"last_name";s:10:"redcapType";s:4:"text";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:9:"last_name";s:10:"usesLookup";b:0;}i:3;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:7:"address";s:10:"redcapType";s:5:"notes";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:7:"address";s:10:"usesLookup";b:0;}i:4;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:9:"telephone";s:10:"redcapType";s:4:"text";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:9:"telephone";s:10:"usesLookup";b:0;}i:5;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:5:"email";s:10:"redcapType";s:4:"text";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:5:"email";s:10:"usesLookup";b:0;}i:6;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:3:"dob";s:10:"redcapType";s:4:"text";s:4:"type";s:4:"date";s:4:"size";N;s:6:"dbName";s:3:"dob";s:10:"usesLookup";b:0;}i:7;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:9:"ethnicity";s:10:"redcapType";s:5:"radio";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:9:"ethnicity";s:10:"usesLookup";s:9:"ethnicity";}i:8;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:8:"race___0";s:10:"redcapType";s:8:"checkbox";s:4:"type";s:3:"int";s:4:"size";N;s:6:"dbName";s:8:"race___0";s:10:"usesLookup";s:4:"race";}i:9;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:8:"race___1";s:10:"redcapType";s:8:"checkbox";s:4:"type";s:3:"int";s:4:"size";N;s:6:"dbName";s:8:"race___1";s:10:"usesLookup";s:4:"race";}i:10;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:8:"race___2";s:10:"redcapType";s:8:"checkbox";s:4:"type";s:3:"int";s:4:"size";N;s:6:"dbName";s:8:"race___2";s:10:"usesLookup";s:4:"race";}i:11;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:8:"race___3";s:10:"redcapType";s:8:"checkbox";s:4:"type";s:3:"int";s:4:"size";N;s:6:"dbName";s:8:"race___3";s:10:"usesLookup";s:4:"race";}i:12;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:8:"race___4";s:10:"redcapType";s:8:"checkbox";s:4:"type";s:3:"int";s:4:"size";N;s:6:"dbName";s:8:"race___4";s:10:"usesLookup";s:4:"race";}i:13;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:8:"race___5";s:10:"redcapType";s:8:"checkbox";s:4:"type";s:3:"int";s:4:"size";N;s:6:"dbName";s:8:"race___5";s:10:"usesLookup";s:4:"race";}i:14;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:3:"sex";s:10:"redcapType";s:5:"radio";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:3:"sex";s:10:"usesLookup";s:3:"sex";}i:15;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:6:"height";s:10:"redcapType";s:4:"text";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:6:"height";s:10:"usesLookup";b:0;}i:16;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:6:"weight";s:10:"redcapType";s:4:"text";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:6:"weight";s:10:"usesLookup";b:0;}i:17;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:3:"bmi";s:10:"redcapType";s:4:"calc";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:3:"bmi";s:10:"usesLookup";b:0;}i:18;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:8:"comments";s:10:"redcapType";s:5:"notes";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:8:"comments";s:10:"usesLookup";b:0;}}s:7:"*rows";a:0:{}s:37:"IU\REDCapETL\Schema\TableprimaryKey";i:1;s:10:"usesLookup";b:1;s:44:"IU\REDCapETL\Schema\TablerecordIdFieldName";s:9:"record_id";s:34:"IU\REDCapETL\Schema\TablekeyType";O:38:"IU\REDCapETL\Schema\FieldTypeSpecifier":2:{s:44:"IU\REDCapETL\Schema\FieldTypeSpecifiertype";s:3:"int";s:44:"IU\REDCapETL\Schema\FieldTypeSpecifiersize";N;}}}s:38:"IU\REDCapETL\Schema\SchemarootTables";a:1:{i:0;r:4;}s:39:"IU\REDCapETL\Schema\SchemalookupTable";O:24:"IU\REDCapETL\LookupTable":17:{s:29:"IU\REDCapETL\LookupTablemap";a:1:{s:12:"demographics";a:3:{s:9:"ethnicity";a:3:{i:0;s:18:"Hispanic or Latino";i:1;s:22:"NOT Hispanic or Latino";i:2;s:22:"Unknown / Not Reported";}s:4:"race";a:6:{i:0;s:29:"American Indian/Alaska Native";i:1;s:5:"Asian";i:2;s:41:"Native Hawaiian or Other Pacific Islander";i:3;s:25:"Black or African American";i:4;s:5:"White";i:5;s:5:"Other";}s:3:"sex";a:2:{i:0;s:6:"Female";i:1;s:4:"Male";}}}s:39:"IU\REDCapETL\LookupTablelookupChoices";a:3:{s:9:"ethnicity";a:3:{i:0;s:18:"Hispanic or Latino";i:1;s:22:"NOT Hispanic or Latino";i:2;s:22:"Unknown / Not Reported";}s:4:"race";a:6:{i:0;s:29:"American Indian/Alaska Native";i:1;s:5:"Asian";i:2;s:41:"Native Hawaiian or Other Pacific Islander";i:3;s:25:"Black or African American";i:4;s:5:"White";i:5;s:5:"Other";}s:3:"sex";a:2:{i:0;s:6:"Female";i:1;s:4:"Male";}}s:39:"IU\REDCapETL\LookupTablelookupTableIn";a:3:{s:22:"demographics:ethnicity";b:1;s:17:"demographics:race";b:1;s:16:"demographics:sex";b:1;}s:4:"name";s:6:"Lookup";s:6:"parent";s:9:"lookup_id";s:7:"primary";O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:9:"lookup_id";s:10:"redcapType";s:0:"";s:4:"type";s:3:"int";s:4:"size";N;s:6:"dbName";s:9:"lookup_id";s:10:"usesLookup";b:0;}s:7:"foreign";s:0:"";s:11:"*children";a:0:{}s:8:"rowsType";a:1:{i:0;i:0;}s:12:"rowsSuffixes";a:0:{}s:43:"IU\REDCapETL\Schema\TablepossibleSuffixes";a:0:{}s:9:"*fields";a:4:{i:0;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:10:"table_name";s:10:"redcapType";s:0:"";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:10:"table_name";s:10:"usesLookup";b:0;}i:1;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:10:"field_name";s:10:"redcapType";s:0:"";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:10:"field_name";s:10:"usesLookup";b:0;}i:2;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:5:"value";s:10:"redcapType";s:0:"";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:5:"value";s:10:"usesLookup";b:0;}i:3;O:25:"IU\REDCapETL\Schema\Field":6:{s:4:"name";s:5:"label";s:10:"redcapType";s:0:"";s:4:"type";s:6:"string";s:4:"size";N;s:6:"dbName";s:5:"label";s:10:"usesLookup";b:0;}}s:7:"*rows";a:11:{i:0;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:9:"ethnicity";s:5:"value";s:1:"0";s:5:"label";s:18:"Hispanic or Latino";s:9:"lookup_id";i:1;}}i:1;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:9:"ethnicity";s:5:"value";s:1:"1";s:5:"label";s:22:"NOT Hispanic or Latino";s:9:"lookup_id";i:2;}}i:2;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:9:"ethnicity";s:5:"value";s:1:"2";s:5:"label";s:22:"Unknown / Not Reported";s:9:"lookup_id";i:3;}}i:3;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:4:"race";s:5:"value";s:1:"0";s:5:"label";s:29:"American Indian/Alaska Native";s:9:"lookup_id";i:4;}}i:4;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:4:"race";s:5:"value";s:1:"1";s:5:"label";s:5:"Asian";s:9:"lookup_id";i:5;}}i:5;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:4:"race";s:5:"value";s:1:"2";s:5:"label";s:41:"Native Hawaiian or Other Pacific Islander";s:9:"lookup_id";i:6;}}i:6;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:4:"race";s:5:"value";s:1:"3";s:5:"label";s:25:"Black or African American";s:9:"lookup_id";i:7;}}i:7;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:4:"race";s:5:"value";s:1:"4";s:5:"label";s:5:"White";s:9:"lookup_id";i:8;}}i:8;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:4:"race";s:5:"value";s:1:"5";s:5:"label";s:5:"Other";s:9:"lookup_id";i:9;}}i:9;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:3:"sex";s:5:"value";s:1:"0";s:5:"label";s:6:"Female";s:9:"lookup_id";i:10;}}i:10;O:23:"IU\REDCapETL\Schema\Row":2:{s:5:"table";r:163;s:4:"data";a:5:{s:10:"table_name";s:12:"demographics";s:10:"field_name";s:3:"sex";s:5:"value";s:1:"1";s:5:"label";s:4:"Male";s:9:"lookup_id";i:11;}}}s:37:"IU\REDCapETL\Schema\TableprimaryKey";i:12;s:10:"usesLookup";b:0;s:44:"IU\REDCapETL\Schema\TablerecordIdFieldName";N;s:34:"IU\REDCapETL\Schema\TablekeyType";r:158;}}i:1;a:2:{i:0;s:5:"valid";i:1;s:27:"Found 19 fields in REDCap.";}}Created table \'demographics\';');

        $this->assertSame($expectedResult, $schema);
    }
    
}
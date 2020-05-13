<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\EtlRedCapProject;
use IU\REDCapETL\Schema\Table;

class SchemaGeneratorGenerateSchemaTest extends TestCase
{
    private static $logger;

    public static function setupBeforeClass()
    {
        self::$logger = new Logger('schema_generator_generate_schema_test');
    }

    /**
     *  Test using basic demography test files in the config folder
     */
    public function testGenerateSchemaUsingBasicDemography()
    {
        # create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/basic-demography.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        $apiUrl = $configuration->getRedCapApiUrl();
        $apiToken = $configuration->getDataSourceApiToken();
        $sslVerify = false;
        $caCertificateFile = null;
        $errorHandler = null;
        $connection = null;

        $etlRedCapProject = new EtlRedCapProject(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
        );

        # Create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        # Generate the schema
        $rulesText = $configuration->getTransformationRules();
        list($schema, $parseResult) = $schemaGenerator->generateSchema($rulesText);

        # Test that the schema object was created
        $this->assertNotNull($schema, 'SchemaGenerator, generateSchema object not null');

        $expectedTableNames = ['Demography'];

        $tables = $schema->getTables();
        $actualTableNames = array();
        foreach ($tables as $table) {
            array_push($actualTableNames, $table->getName());
        }
        $this->assertEquals($expectedTableNames, $actualTableNames, 'Get tables test');

        # Put the returned schema into a string format for comparison purposes
        $output = print_r($schema, true);

        # Retrieve what the output text string should resemble
        $file = 'tests/data/schema_generator_output1.txt';
        $expected = file_get_contents($file);
 
        # Test that the generated output matches what is expected
        $this->assertEquals($expected, $output, 'SchemaGenerator, generateSchema output');
    }
   
    /**
     *  Test using a longitudinal project (repeating events)
     */
    public function testGenerateSchemaLongitudinal()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/repeating-events.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        $apiUrl = $configuration->getRedCapApiUrl();
        $apiToken = $configuration->getDataSourceApiToken();
        $sslVerify = false;
        $caCertificateFile = null;
        $errorHandler = null;
        $connection = null;
        $etlRedCapProject = new EtlRedCapProject(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
        );

        #create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        #Generate the schema
        $rulesText = $configuration->getTransformationRules();
        $result = $schemaGenerator->generateSchema($rulesText);

        #Put the returned schema into a string format for comparison purposes
        $output = print_r($result[0], true);

        #Retrieve what the output text string should resemble
        $file='tests/data/schema_generator_output4.txt';
        $expected = file_get_contents($file);

        #test that the generated output matches what is expected
        $this->assertEquals($expected, $output, 'SchemaGenerator, generateSchema longitudinal output');
    }

    /**
     *  Test using a classic project with repeating instruments
     */
    public function testGenerateSchemaLongitudinalProjectWithoutRepeatingEvents()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/visits.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        $apiUrl = $configuration->getRedCapApiUrl();
        $apiToken = $configuration->getDataSourceApiToken();
        $sslVerify = false;
        $caCertificateFile = null;
        $errorHandler = null;
        $connection = null;
        $etlRedCapProject = new EtlRedCapProject(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
        );

        #create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        #Generate the schema
        $rulesText = $configuration->getTransformationRules();
        list($schema, $parseResult) = $schemaGenerator->generateSchema($rulesText);
        
        $this->assertNotNull($schema, 'Schema not null');
        
        $this->assertEquals(2, count($schema->getRootTables()), 'Root tables check');
        $this->assertEquals(9, count($schema->getTables()), 'Get tables check');
        $this->assertEquals(9, count($schema->getTablesTopDown()), 'Get tables top-down check');
        
        $expectedTableNames = [
            'Demography', 'BMI', 'VisitInfo', 'VisitResults', 'Contact', 'Labs', 'Recipients', 'Sent', 'Followup'
        ];
        
        $tables = $schema->getTables();
        $actualTableNames = array();
        foreach ($tables as $table) {
            array_push($actualTableNames, $table->getName());
        }
        $this->assertEquals($expectedTableNames, $actualTableNames, 'Table check');
 
        $labsTable = $schema->getTable('Labs');
        $this->assertTrue($labsTable instanceof Table, 'Labs table class check');

        $expectedLabsFieldNames = ['record_id', 'redcap_suffix', 'lab'];
        $labsFields = $labsTable->getFields();
        $actualLabsFieldNames = array_column($labsFields, 'name');
        $this->assertEquals($expectedLabsFieldNames, $actualLabsFieldNames, 'Labs field names check');
    }

    /**
     *  Test using basic demography rules file that has an error in it, in this
     *  case, the 'FIELD' reserved word is misspelled.
     */
    public function testGenerateSchemaErrorInRule()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/basic-demography-bad-rule.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        $apiUrl = $configuration->getRedCapApiUrl();
        $apiToken = $configuration->getDataSourceApiToken();
        $sslVerify = false;
        $caCertificateFile = null;
        $errorHandler = null;
        $connection = null;
        $etlRedCapProject = new EtlRedCapProject(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
        );

        #clear out the log file
        $logFile = $configuration->getLogFile();
        $f = fopen($logFile, "w");
        fclose($f);

        #create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        #Generate the schema
        $rulesText = $configuration->getTransformationRules();
        $result = $schemaGenerator->generateSchema($rulesText);

        #Put the returned schema into a string format for comparison purposes
        $output = print_r($result[0], true);

        #Retrieve what the output text string should resemble (no comments field, since that
        #field has the misspelled 'FIELD' reserved word)
        $file='tests/data/schema_generator_output2.txt';
        $expected = file_get_contents($file);

        #test that the generated output matches what is expected
        $this->assertEquals($expected, $output, 'SchemaGenerator, generateSchema bad rule output');

        #test that the error message was written to the log file
        $logText = file_get_contents($logFile, false, null, 0, 500);
        $unrecognizedRuleMsg = 'Unrecognized rule type "IELD"';
        $unmappedFieldMsg= 'Unmapped fields: comments';
        $msgFound1 = (strpos($logText, $unrecognizedRuleMsg) ? true : false);
        $msgFound2 = (strpos($logText, $unmappedFieldMsg) ? true : false);
        $this->assertTrue(
            $msgFound1,
            'SchemaGenerator, generateSchema bad rule unrecognized rule msg'
        );
        $this->assertTrue(
            $msgFound2,
            'SchemaGenerator, generateSchema bad rule unmapped field msg'
        );
    }
 
    /**
     *  Test using basic demography rules file that has a wrong field name.
     */
    public function testGenerateSchemaErrorWithFieldName()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/basic-demography-bad-field-name.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        $apiUrl = $configuration->getRedCapApiUrl();
        $apiToken = $configuration->getDataSourceApiToken();
        $sslVerify = false;
        $caCertificateFile = null;
        $errorHandler = null;
        $connection = null;
        $etlRedCapProject = new EtlRedCapProject(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
        );

        #create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        #clear out the log file
        $logFile = $configuration->getLogFile();
        $f = fopen($logFile, "w");
        fclose($f);

        #Generate the schema
        $rulesText = $configuration->getTransformationRules();
        $result = $schemaGenerator->generateSchema($rulesText);

        #Put the returned schema into a string format for comparison purposes
        $output = print_r($result[0], true);

        #Retrieve what the output text string should resemble (no bmi field, since that
        #field name was misspelled as 'bbbmi')
        $file='tests/data/schema_generator_output3.txt';
        $expected = file_get_contents($file);

        #test that the generated output matches what is expected
        $this->assertEquals($expected, $output, 'SchemaGenerator, generateSchema bad field name output');

        #test that the error message was written to the log file
        $logText = file_get_contents($logFile, false, null, 0, 500);
        $fieldNotFoundMsg = "Field not found in REDCap: 'bbbmi'";
        $msgFound = (strpos($logText, $fieldNotFoundMsg) ? true : false);
        $this->assertTrue(
            $msgFound,
            'SchemaGenerator, generateSchema bad field name field not found msg'
        );
    }

    /**
     *  Test using visits rules file that has an additional field name not in the REDCap database.
     */
    public function testGenerateSchemaErrorWithSuffixField()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/visits-missing-suffix.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        $apiUrl = $configuration->getRedCapApiUrl();
        $apiToken = $configuration->getDataSourceApiToken();
        $sslVerify = false;
        $caCertificateFile = null;
        $errorHandler = null;
        $connection = null;
        $etlRedCapProject = new EtlRedCapProject(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
        );

        #create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        #clear out the log file
        $logFile = $configuration->getLogFile();
        $f = fopen($logFile, "w");
        fclose($f);

        #Generate the schema
        $rulesText = $configuration->getTransformationRules();
        $result = $schemaGenerator->generateSchema($rulesText);

        #test that the error message was written to the log file
        $logText = file_get_contents($logFile, false, null, 0, 500);
        $fieldNotFoundMsg = "Suffix field not found in REDCap: 'missingSuffixField'";
        $msgFound = (strpos($logText, $fieldNotFoundMsg) ? true : false);
        $this->assertTrue(
            $msgFound,
            'SchemaGenerator, generateSchema missing suffix field msg'
        );
    }

   /**
     *  Test using visits rules file that is empty
     */
    public function testGenerateSchemaErrorWithNoRules()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/visits-empty-rules.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        $apiUrl = $configuration->getRedCapApiUrl();
        $apiToken = $configuration->getDataSourceApiToken();
        $sslVerify = false;
        $caCertificateFile = null;
        $errorHandler = null;
        $connection = null;
        $etlRedCapProject = new EtlRedCapProject(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
        );

        #create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        #clear out the log file
        $logFile = $configuration->getLogFile();
        $f = fopen($logFile, "w");
        fclose($f);

        #Generate the schema
        $rulesText = $configuration->getTransformationRules();
        $result = $schemaGenerator->generateSchema($rulesText);

        #test that the error message was written to the log file
        $logText = file_get_contents($logFile, false, null, 0, 500);
        $fieldNotFoundMsg = "Found no transformation rules";
        $msgFound = (strpos($logText, $fieldNotFoundMsg) ? true : false);
        $this->assertTrue(
            $msgFound,
            'SchemaGenerator, generateSchema no rules found msg'
        );
    }

   /**
     * Test using basic demography rule file that has a field name that
     * is the sames as the primary key. THIS TEST REQUIRES A NEW
     * REDCAP ETL PROJECT. COMMENTING IT OUT UNTIL ONE IS CREATED.
     */
 /**   public function testGenerateSchemaErrorDuplicatePrimaryKeyName()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/basic-demography-duplicate-primary-key-name.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        $apiUrl = $configuration->getRedCapApiUrl();
        $apiToken = $configuration->getDataSourceApiToken();
        $sslVerify = false;
        $caCertificateFile = null;
        $errorHandler = null;
        $connection = null;
        $etlRedCapProject = new EtlRedCapProject(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
        );

        #create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        #clear out the log file
        $logFile = $configuration->getLogFile();
        $f = fopen($logFile, "w");
        fclose($f);

        #Generate the schema
        $rulesText = $configuration->getTransformationRules();
        $result = $schemaGenerator->generateSchema($rulesText);

        #test that the error message was written to the log file
        $logText = file_get_contents($logFile, FALSE, NULL, 0, 500);
        print $logText . PHP_EOL;
        $fieldNotFoundMsg = "Primary key field has same name as REDCap record id";
        $msgFound = (strpos($logText, $fieldNotFoundMsg) ? true : false);
        $this->assertTrue(
            $msgFound,
            'SchemaGenerator, generateSchema no rules found msg'
        );
    } */
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\EtlRedCapProject;
use IU\REDCapETL\Schema\Table;

class SchemaGenerationTest extends TestCase
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
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, $configFile);

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
            $actualTableNames[] = $table->name;
        }
        $this->assertEquals($expectedTableNames, $actualTableNames, 'Get tables test');
    }
   
    /**
     *  Test using a longitudinal project (repeating events)
     */
    public function testGenerateSchemaLongitudinal()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/repeating-events.ini';
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, $configFile);

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

        $this->assertNotNull($schema);

        #--------------------------------------------
        # Test table names
        #--------------------------------------------
        $tables = $schema->getTables();
        $tableNames = array();
        foreach ($tables as $table) {
            $tableName = $table->getName();
            $tableNames[] = $tableName;
        }

        $expectedTableNames = [
            're_enrollment', 're_baseline', 're_home_weight_visits', 're_home_cardiovascular_visits',
            're_visits', 're_baseline_and_visits', 're_baseline_and_home_visits',
            're_visits_and_home_visits', 're_all_visits'
        ];

        $this->assertEquals($expectedTableNames, $tableNames, 'Table names test');

        #------------------------------------------
        # Test enrollment field names
        #------------------------------------------
        $table = $schema->getTable('re_enrollment');
        $fields = $table->getFields();
        $enrollmentFieldNames = array_column($fields, 'name');

        $expectedEnrollmentFieldNames = [
            'redcap_data_source', 'record_id',    # fields added by REDCap-ETL
            'registration_date', 'first_name', 'last_name', 'birthdate', 'registration_age', 'gender',
            'race___0', 'race___1', 'race___2', 'race___3', 'race___4', 'race___5'
        ];

        $this->assertEquals($expectedEnrollmentFieldNames, $enrollmentFieldNames, 'Enrollment field names test');

 
        #-----------------------------------------
        # Test baseline field names
        #-----------------------------------------
        $table = $schema->getTable('re_baseline');
        $fields = $table->getFields();
        $baselineFieldNames = array_column($fields, 'name');

        $expectedBaselineFieldNames = [
            'redcap_data_source', 'record_id', 'redcap_event_name',    # fields added by REDCap-ETL
            'weight_time', 'weight_kg', 'height_m',
            'cardiovascular_date', 'hdl_mg_dl', 'ldl_mg_dl', 'triglycerides_mg_dl',
            'diastolic1', 'diastolic2', 'diastolic3',
            'systolic1', 'systolic2', 'systolic3'
        ];

        $this->assertEquals($expectedBaselineFieldNames, $baselineFieldNames, 'Enrollment field names test');
    }

    /**
     *  Test using a classic project with repeating instruments
     */
    public function testGenerateSchemaLongitudinalProjectWithoutRepeatingEvents()
    {
        #create an EtlRecCapProject object from which a schema will be generated
        $configFile = __DIR__.'/../config/visits.ini';
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, $configFile);

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

        $expectedLabsFieldNames = ['redcap_data_source', 'record_id', 'redcap_suffix', 'lab'];
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
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, $configFile);

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
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, $configFile);

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
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, $configFile);

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
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, $configFile);

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
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, $configFile);

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

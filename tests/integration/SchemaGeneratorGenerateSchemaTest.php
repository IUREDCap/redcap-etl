<?php
namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\EtlRedCapProject;

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
        ##########################################################################
        # Test using basic demography test files
        ##########################################################################

        #create an EtlRecCapProject object from which a schema will be generated
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

        #create a SchemaGenerator object
        $schemaGenerator = new SchemaGenerator($etlRedCapProject, $configuration, self::$logger);

        #Generate the schema 
        $rulesText = $configuration->getTransformationRules();
        $result = $schemaGenerator->generateSchema($rulesText);
        $this->assertNotNull($result, 'SchemaGenerator, generateSchema object not null');

        #Put the returned schema into a string format for comparison purposes
        $output = print_r($result[0], true);

        #Retrieve what the output text string should resemble
        $file='tests/data/schema_generator_output1.txt';
        $expected = file_get_contents($file);
 
        #test that the generated output matches what is expected
        $this->assertEquals($expected, $output, 'SchemaGenerator, generateSchema output'); 
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
     *  Test using a longitudinal project (repeating events)
     */
    public function testGenerateSchemaClassicProjectWithRepeatingEvents()
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
        $result = $schemaGenerator->generateSchema($rulesText);

        #Put the returned schema into a string format for comparison purposes
        $output = print_r($result[0], true);

        #Retrieve what the output text string should resemble
        $file='tests/data/schema_generator_output5.txt';
        $expected = file_get_contents($file);

        #test that the generated output matches what is expected
        $this->assertEquals($expected, $output, 'SchemaGenerator, generateSchema classic project, repeating events output');
    }
}

<?php
namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\EtlRedCapProject;

class SchemaGeneratorTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../data/config-test.ini';

    public function testConstructor()
    {
        $jsonFile  = __DIR__.'/../data/projects/basic-demography.json';
        $xmlFile   = __DIR__.'/../data/projects/basic-demography.xml';
        $rulesFile = __DIR__.'/../data/projects/basic-demography-rules.txt';
        $projectData = new ProjectData($jsonFile, $xmlFile, $rulesFile);

        $logger = new Logger('schema_generator_test');

        $configuration = new Configuration($logger, self::CONFIG_FILE);

        $schemaGenerator = new SchemaGenerator($projectData, $configuration, $logger);
        $this->assertNotNull($schemaGenerator, 'schemaGenerator object created check');
    }
}

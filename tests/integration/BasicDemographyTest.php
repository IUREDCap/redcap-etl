<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class DemographyTest extends TestCase
{
    private static $config;
    private static $csvDir;
    private static $properties;

    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');

        self::$properties = array();
        self::$properties['redcap_api_url'] = self::$config['api.url'];
        self::$properties['api_token'] = self::$config['basic.demography.api.token'];
        self::$properties['initial_email_address'] = self::$config['test.email'];

        self::$csvDir = self::$config['basic.demography.directory'];
        if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
            self::$csvDir .= DIRECTORY_SEPARATOR;
        }
    }

    public function testDemographyTable()
    {
        try {
            $app = basename(__FILE__, '.php');
            $logger = new Logger2($app);

            $redCapEtl = new RedCapEtl($logger, self::$properties);

            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            $logger->logInfo("Starting processing.");

            list($parse_status,$result) = $redCapEtl->parseMap();

            if (RedCapEtl::PARSE_ERROR === $parse_status) {
                $redCapEtl->log("Schema map not parsed. Processing stopped.");
            } else {
                $redCapEtl->loadTables();
                $redCapEtl->extractTransformLoad();
                $logger->logInfo("Processing complete.");
            }
        } catch (EtlException $exception) {
            $logger->logException($exception);
            $logger->logError('Processing failed.');
        }
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$csvDir . 'Demography.csv');
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/BasicDemography.csv'
        );
        $expectedCsv = $parser2->parse();

        $header = $csv[0];
        $this->assertEquals($header[1], 'record_id', 'Record id header test.');
        $this->assertEquals(101, count($csv), 'Demography row count check.');

        // $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }
}

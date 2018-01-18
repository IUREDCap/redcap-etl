<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class DemographyTest extends TestCase
{
    private static $csvDir;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/basic-demography.ini';

    public static function setUpBeforeClass()
    {
    }


    public function testDemographyTable()
    {
        try {
            $app = basename(__FILE__, '.php');
            self::$logger = new Logger2($app);

            $redCapEtl = new RedCapEtl(self::$logger, null, self::CONFIG_FILE);
            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            $config = $redCapEtl->getConfiguration();
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }

            self::$logger->logInfo("Starting processing.");

            list($parse_status,$result) = $redCapEtl->parseMap();

            if (RedCapEtl::PARSE_ERROR === $parse_status) {
                $redCapEtl->log("Schema map not parsed. Processing stopped.");
            } else {
                $redCapEtl->loadTables();
                $redCapEtl->extractTransformLoad();
                self::$logger->logInfo("Processing complete.");
            }
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->logError('Processing failed.');
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

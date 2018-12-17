<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class BasicDemography2Test extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/basic-demography-2.ini';
    const OUTPUT_FILE_NAME = 'demographics.csv';

    public static function setUpBeforeClass()
    {
    }


    public function testDemographyTable()
    {
        try {
            $app = basename(__FILE__, '.php');
            self::$logger = new Logger($app);

            $redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            $config = $redCapEtl->getConfiguration();
            $this->assertNotNull($config, 'redCapEtl configuration not null');

            self::$csvDir = str_ireplace('CSV:', '', $config->getDbConnection());
            if (substr(self::$csvDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                self::$csvDir .= DIRECTORY_SEPARATOR;
            }
            self::$csvFile = self::$csvDir . self::OUTPUT_FILE_NAME;

            # Try to delete the output file in case it exists from a previous run
            if (file_exists(self::$csvFile)) {
                unlink(self::$csvFile);
            }

            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
        $parser = \KzykHys\CsvParser\CsvParser::fromFile(self::$csvFile);
        $csv = $parser->parse();

        $parser2 = \KzykHys\CsvParser\CsvParser::fromFile(
            __DIR__.'/../data/BasicDemography2.csv'
        );
        $expectedCsv = $parser2->parse();

        $header = $csv[0];
        $this->assertEquals($header[1], 'record_id', 'Record id header test.');
        $this->assertEquals(101, count($csv), 'Demography row count check.');

        $this->assertEquals($expectedCsv, $csv, 'CSV file check.');
    }
}

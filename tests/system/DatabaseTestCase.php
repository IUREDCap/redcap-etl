<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\DbUnit\DataSet\CsvDataSet;

/**
 * Database test class that should be extended by PHPUnit tests that
 * load data to an SQL database.
 *
 * This abstract class allows us to code the database connection for our
 * tests in a single place, and to store the database connection parameters
 * in an XML file.
 *
 * Configuration information comes from the tests/config/visits.ini file.
 *
 * Also, because all of our data is coming from a REDCap project, and all
 * tables in our MySQL database will be dropped and recreated when the
 * ETL program is run, there's no need to set up any existing dataset in the
 * MySQL database, so we can just return an empty 'fixture' DataSet
 */
abstract class DatabaseTestCase extends TestCase
{
    use TestCaseTrait;

    const CONFIG_FILE = __DIR__.'/../config/visits.ini';
    const BIN_DIR     = __DIR__.'/../../bin';
    const ETL_COMMAND = 'redcap_etl.php';

    private $config = null;

    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only run the batch script once
    static private $have_run = null;

    // only instantiate
    // PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    // only instantiate PHPUnit_Extensions_Database_DataSet_IDataSet
    // once per test
    private $ds = null;

    final public function getConnection()
    {
        $logger = new Logger2('visits_test');

        $config = $this->getConfig();

        $configuration = new Configuration($logger, $config);

        list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getMySqlConnectionInfo();

        $dsn = 'mysql:dbname='.$dbName.';host='.$dbHost;

        if ($this->conn === null) {
            if (self::$pdo == null) {
                // Note global information comes from phpunit.xml file
                self::$pdo = new \PDO(
                    $dsn,
                    $dbUser,
                    $dbPassword
                );
            }

            $this->conn =
            $this->createDefaultDBConnection(
                self::$pdo,
                $dbName
            );
        }

        return $this->conn;
    }

    final public function getDataSet()
    {
        if ($this->ds === null) {
            $this->ds = new CsvDataSet();

            if (self::$have_run == null) {
                self::$have_run = true;

                $config = $this->getConfig();

                $testScript = getenv('REDCAP_ETL_DATA_TEST_SCRIPT');
                if ($testScript !== 'handler') {
                    $testScript = 'batch';
                }

                print("\n\nUsing $testScript script to run ETL\n");
                flush();

                if ($testScript === 'handler') {
                    # If the handler script is being used,
                    # post a request to its URL using cURL.

                    $url = $config['etl.handler.url'];

                    $configProjectId = $config['etl.config.projectid'];
                    $configRecord    = $config['etl.config.record'];

                    $curlHandle = curl_init($url);

                    $data = ['project_id' => $configProjectId, 'record' => $configRecord];

                    curl_setopt($curlHandle, CURLOPT_POST, 1);
                    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);

                    $response = curl_exec($curlHandle);

                    curl_close($curlHandle);

                    print "\n";
                    print "Handler POST response:\n";
                    print "----------------------\n";
                    print "$response\n";
                } else { // Run batch script
                    // Execute the Extract Transform Load program we're testing.
                    $etl_output = array();
                    $command = "cd ".self::BIN_DIR.";"
                        ." php ".self::ETL_COMMAND." -p ".self::CONFIG_FILE;

                    $etl_result = exec($command, $etl_output);
                    $expectedResult = 'Processing complete.';

                    print "ETL Result: $etl_result\n";
                    if ($etl_result !== 'Processing complete.') {
                        print "    ***WARNING: Expected ETL result is: $expectedResult\n";
                    }
                }
            }
        }

        return $this->ds;
    }

    private function getConfig()
    {
        if (!isset($this->config)) {
            $this->config = parse_ini_file(self::CONFIG_FILE);
        }
        return $this->config;
    }
}

<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class VisitsTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/visits.ini';
    const BIN_DIR     = __DIR__.'/../../bin';
    const ETL_COMMAND = 'redcap_etl.php';

    private static $dbh;

    public static function setUpBeforeClass()
    {
        $logger = new Logger('visits_test');

        $configuration = new Configuration($logger, self::CONFIG_FILE);

        list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getMySqlConnectionInfo();
        $dsn = 'mysql:dbname='.$dbName.';host='.$dbHost;
        self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
        VisitsTestUtility::dropTablesAndViews(self::$dbh);
    }


    public static function runBatchEtl()
    {
        $etlOutput = array();
        $command = "cd ".self::BIN_DIR.";"." php ".self::ETL_COMMAND." -c ".self::CONFIG_FILE;
        $etlResult = exec($command, $etlOutput);
    }

    public function testAll()
    {
        self::runBatchEtl();
        VisitsTestUtility::testAll($this, self::$dbh);
    }
}

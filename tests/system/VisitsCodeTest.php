<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "Visits" tests in code, so that code coverage
 * statistics will pick up these tests.
 */
class VisitsCodeTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/visits.ini';

    private static $dbh;
    private static $logger;

    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('visits_code_test');

        $configuration = new Configuration(self::$logger, self::CONFIG_FILE);

        list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getMySqlConnectionInfo();
        $dsn = 'mysql:dbname='.$dbName.';host='.$dbHost;
        try {
            self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
        } catch (Exception $exception) {
            print "ERROR - database connection error: ".$exception->getMessage()."\n";
        }
        VisitsTestUtility::dropTablesAndViews(self::$dbh);
    }


    public static function runEtl()
    {
        try {
            $redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }

    public function testAll()
    {
        self::runEtl();
        VisitsTestUtility::testAll($this, self::$dbh);
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "Visits" tests in code, so that code coverage
 * statistics will pick up these tests.
 */
class VisitsMysqlCodeTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/visits.ini';

    private static $dbh;
    private static $logger;

    public static function setUpBeforeClass()
    {
        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('visits_code_test');

            $configuration = new Configuration(self::$logger, self::CONFIG_FILE);

            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ];


            list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getMySqlConnectionInfo();
            $dsn = 'mysql:dbname='.$dbName.';host='.$dbHost;
            try {
                self::$dbh = new \PDO($dsn, $dbUser, $dbPassword, $options);
            } catch (Exception $exception) {
                print "ERROR - database connection error: ".$exception->getMessage()."\n";
            }
            VisitsTestUtility::dropTablesAndViews(self::$dbh);
        }
    }

    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
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

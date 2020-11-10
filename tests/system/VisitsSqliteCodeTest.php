<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "Visits" tests using SQLite in code, so that code coverage
 * statistics will pick up these tests.
 */
class VisitsSqliteCodeTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/visits-sqlite.ini';

    private static $dbh;
    private static $logger;

    public static function setUpBeforeClass()
    {
        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('visits_sqlite_code_test');

            $configuration = new TaskConfig();
            $configuration->set(self::$logger, self::CONFIG_FILE);

            $dbConnection = $configuration->getDbConnection();
            list($type, $db) = explode(':', $dbConnection, 2);

            $dsn = 'sqlite:'.realpath($db);

            try {
                $dbUser = null;
                $dbPassword = null;
                self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
                self::$dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
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

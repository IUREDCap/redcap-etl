<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Test of the REDCap-ETL script on the visits project. As a result, line
 * coverage statistics will not be collected for these tests, and a corresponsing test
 * that runs using methods calls was created to get line coverage.
 */
class VisitsMysqlTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/visits-mysql.ini';
    const BIN_DIR     = __DIR__.'/../../bin';
    const ETL_COMMAND = 'redcap_etl.php';

    private static $dbh;

    public static function setUpBeforeClass()
    {
        if (file_exists(self::CONFIG_FILE)) {
            $logger = new Logger('visits_test');

            $configuration = new TaskConfig();
            $configuration->set($logger, self::CONFIG_FILE);

            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ];

            list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getMySqlConnectionInfo();
            $dsn = 'mysql:dbname='.$dbName.';host='.$dbHost;
            self::$dbh = new \PDO($dsn, $dbUser, $dbPassword, $options);
            VisitsTestUtility::dropTablesAndViews(self::$dbh);
        }
    }


    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
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

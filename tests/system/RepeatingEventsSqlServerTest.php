<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating events" tests using SQL Server as the database.
 */
class RepeatingEventsSqlServerTest extends RepeatingEventsTests
{
    const WEIGHT_TIME_FIELD_DECLARATION = "FORMAT(weight_time, 'yyyy-MM-dd HH:mm') as 'weight_time'";
                
    const CONFIG_FILE = __DIR__.'/../config/repeating-events-sqlserver.ini';

    protected static $dbh;
    protected static $logger;

    public function setUp()
    {
        #These tests depend on the pdo_sqlsrv driver being installed.
        #If it isn't loaded in PHP, all tests will be skipped.
     
        if (!extension_loaded('pdo_sqlsrv')) {
            $this->markTestSkipped('The pdo_sqlsrv driver is not available.');
        }
    }

    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('repeating_events_system_test_sql_server');

        if (extension_loaded('pdo_sqlsrv')) {
            $configuration = new Configuration(self::$logger, self::CONFIG_FILE);
            $dbString = $configuration->getDbConnection();
            list($dbType, $dbHost, $dbUser, $dbPassword, $dbName) = explode(":", $dbString);
            $driver = 'sqlsrv';

            $dsn = "$driver:server=$dbHost ; Database=$dbName";

            try {
                self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
            } catch (Exception $exception) {
                print "ERROR - database connection error: ".$exception->getMessage()."\n";
            }

            self::dropTablesAndViews(self::$dbh);

            self::runEtl(self::$logger, self::CONFIG_FILE);
        }
    }
}

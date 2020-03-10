<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating events" tests using MySQL as the database.
 */
class RepeatingEventsSqliteTest extends RepeatingEventsTests
{
    const CONFIG_FILE = __DIR__.'/../config/repeating-events-sqlite.ini';

    protected static $dbh;
    protected static $logger;

    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('repeating_events_sqlite_system_test');

        $configuration = new Configuration(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($type, $db) = explode(':', $dbConnection, 2);

        $dsn = 'sqlite:'.realpath($db);

        try {
            self::$dbh = new \PDO($dsn, null, null);
            self::$dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $exception) {
            print "ERROR - database connection error for db {$dsn}: ".$exception->getMessage()."\n";
            exit(1);
        }

        self::dropTablesAndViews(self::$dbh);

        self::runEtl(self::$logger, self::CONFIG_FILE);
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\ConfigProperties;
use IU\REDCapETL\Database\DbConnectionFactory;
use IU\REDCapETL\Database\SqliteDbConnection;

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
        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('repeating_events_sqlite_system_test');

            $configuration = new TaskConfig();
            $configuration->set(self::$logger, self::CONFIG_FILE);

            $dbConnection = $configuration->getDbConnection();
            list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

            if ($dbType !== DbConnectionFactory::DBTYPE_SQLITE) {
                throw new \Exception('Incorrect database type "'.$dbType.'" SQLite test.');
            }

            self::$dbh = SqliteDbConnection::getPdoConnection($dbString);
        }
    }

    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }
}

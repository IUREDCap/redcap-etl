<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\ConfigProperties;
use IU\REDCapETL\Database\DbConnectionFactory;
use IU\REDCapETL\Database\PostgreSqlDbConnection;

/**
 * Runs the "repeating events" tests using MySQL as the database.
 */
class RepeatingEventsPostgreSqlTest extends RepeatingEventsTests
{
    const WEIGHT_TIME_FIELD_DECLARATION = "to_char(weight_time, 'YYYY-MM-DD HH24:MI') as \"weight_time\"";

    const CONFIG_FILE = __DIR__.'/../config/repeating-events-postgresql.ini';

    protected static $dbh;
    protected static $logger;

    public function setUp()
    {
        # These tests depend on the pdo_pgsql driver being installed.
        # If it isn't loaded, all tests will be skipped.
        if (!extension_loaded('pdo_pgsql')) {
            $this->markTestSkipped('The pdo_pgsql driver is not available.');
        } elseif (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }
    
    public static function setUpBeforeClass()
    {
        if (extension_loaded('pdo_pgsql') && file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('repeating_events_postgresql_system_test');

            $configuration = new TaskConfig();
            $configuration->set(self::$logger, self::CONFIG_FILE);

            $dbConnection = $configuration->getDbConnection();

            list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

            if ($dbType !== DbConnectionFactory::DBTYPE_POSTGRESQL) {
                throw new \Exception('Incorrect database type "'.$dbType.'" for PostgreSQL test.');
            }
        
            $ssl             = $configuration->getDbSsl();
            $sslVerify       = $configuration->getDbSslVerify();
            $caCertFile      = $configuration->getCaCertFile();
            self::$dbh = PostgreSqlDbConnection::getPdoConnection($dbString, $ssl, $sslVerify, $caCertFile);
        }
    }
}

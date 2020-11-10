<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\ConfigProperties;
use IU\REDCapETL\Database\DbConnectionFactory;
use IU\REDCapETL\Database\SqlServerDbConnection;

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
        # These tests depend on the pdo_sqlsrv driver being installed and
        # the configuration file existing.
        # If that is not the case, all tests will be skipped.
        if (!extension_loaded('pdo_sqlsrv')) {
            $this->markTestSkipped('The pdo_sqlsrv driver is not available.');
        } elseif (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }

    public static function setUpBeforeClass()
    {

        self::$logger = new Logger('repeating_events_system_test_sql_server');

        if (extension_loaded('pdo_sqlsrv') && file_exists(self::CONFIG_FILE)) {
            $configuration = new TaskConfig(self::$logger, self::CONFIG_FILE);

            $dbConnection = $configuration->getDbConnection();
            list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);
            if ($dbType !== DbConnectionFactory::DBTYPE_SQLSERVER) {
                throw new \Exception('Incorrect database type "'.$dbType.'" for SQL Server test.');
            }

            $ssl             = $configuration->getDbSsl();
            $sslVerify       = $configuration->getDbSslVerify();
            $caCertFile      = $configuration->getCaCertFile();
            self::$dbh = SqlServerDbConnection::getPdoConnection($dbString, $ssl, $sslVerify, $caCertFile);
        }
    }
}

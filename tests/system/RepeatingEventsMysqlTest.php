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
class RepeatingEventsMysqlTest extends RepeatingEventsTests
{
    const WEIGHT_TIME_FIELD_DECLARATION = "DATE_FORMAT(weight_time, '%Y-%m-%d %H:%i') as 'weight_time'";
        
    const CONFIG_FILE = __DIR__.'/../config/repeating-events-mysql.ini';

    const TEST_DATA_DIR   = __DIR__.'/../data/';     # directory with test data comparison files

    protected static $dbh;
    private static $logger;

    
    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('repeating_events_system_test');

        $configuration = new Configuration(self::$logger, self::CONFIG_FILE);

        list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getMySqlConnectionInfo();
        $dsn = 'mysql:dbname='.$dbName.';host='.$dbHost;
        try {
            self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
        } catch (Exception $exception) {
            print "ERROR - database connection error: ".$exception->getMessage()."\n";
        }

        self::dropTablesAndViews(self::$dbh);

        self::runEtl(self::$logger, self::CONFIG_FILE);
    }
}

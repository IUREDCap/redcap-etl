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
    const WEIGHT_TIME_FIELD_DECLARATION = "to_char(weight_time, 'YYYY-MM-DD HH24:MI') as \"weight_time\"";

    const CONFIG_FILE = __DIR__.'/../config/repeating-events-postgresql.ini';

    const TEST_DATA_DIR   = __DIR__.'/../data/';     # directory with test data comparison files

    protected static $dbh;
    private static $logger;

    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('repeating_events_postgresql_system_test');

        $configuration = new Configuration(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();

        $dbSchema = null;
        $dbPosrt  = null;
        $dbValues = explode(":", $dbConnection);

        if (count($dbValues) == 5) {
            list($dbType, $dbHost, $dbUser, $dbPassword, $dbName) = $dbValues;
        } elseif (count($dbValues) == 6) {
            list($dbType, $dbHost, $dbUser, $dbPassword, $dbName, $dbSchema) = $dbValues;
        } elseif (count($dbValues) == 7) {
            list($dbType, $dbHost, $dbUser, $dbPassword, $dbName, $dbSchema, $dbPort) = $dbValues;
        }

        print "DB SCHEMA: {$dbSchema}\n";

        $dsn = "pgsql:host={$dbHost};user={$dbUser};password={$dbPassword};dbname={$dbName}";
        if (!empty($dbPort)) {
            $dsn .= ";port={$dbPort}";
        }

        try {
            self::$dbh = new \PDO($dsn, null, null);
            self::$dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            if (!empty($dbSchema)) {
                $dbSchema = '"'.(str_replace('"', '', $dbSchema)).'"';
                $sql = 'SET search_path TO '.$dbSchema;
                self::$dbh->exec($sql);
            }
        } catch (\Exception $exception) {
            print "ERROR - database connection error for db {$dsn}: ".$exception->getMessage()."\n";
            exit(1);
        }

        self::dropTablesAndViews(self::$dbh);

        self::runEtl(self::$logger, self::CONFIG_FILE);
    }
}

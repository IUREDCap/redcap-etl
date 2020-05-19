<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\LookupTable;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\Table;

/**
 * Database connection class for SQLite databases that uses PDO (EXPERIMENTAL).
 */
class SqliteDbConnection extends PdoDbConnection
{
    const AUTO_INCREMENT_TYPE = 'INTEGER PRIMARY KEY';

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->errorString = '';

        $this->db = self::getPdoConnection($dbString);
    }

    public static function getPdoConnection($dbString)
    {
        #--------------------------------------------------------------
        # Get the database connection values
        #--------------------------------------------------------------
        $dbFile = $dbString;

        $driver  = 'sqlite';

        $dataSourceName = "{$driver}:{$dbFile}";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ];

        try {
            $pdoConnection = new \PDO($dataSourceName, null, null, $options);
        } catch (\Exception $exception) {
            $message = 'Database connection error for Sqlite database "'.$dataSourceName.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
        return $pdoConnection;
    }

    /**
     * UNSUPPORTED
     */
    public function addPrimaryKeyConstraint($table)
    {
        // Using ALTER TABLE to add a primary key is not supported by SQLite.
        // One approach that might  be used is to create a new table with
        // the primary key and then copy the values from the original table
        // and then drop the original table and rename the new one
    }

    /**
     * UNSUPPORTED
     */
    public function addForeignKeyConstraint($table)
    {
        // Using ALTER TABLE to add foreign keys is not supported by SQLite.
    }
}

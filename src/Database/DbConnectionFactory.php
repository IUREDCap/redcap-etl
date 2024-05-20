<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;

/**
 * Factory class for creating database connections.
 */
class DbConnectionFactory
{
    // Database types
    const DBTYPE_CSV        = 'CSV';
    const DBTYPE_MYSQL      = 'MySQL';
    const DBTYPE_POSTGRESQL = 'PostgreSQL';
    const DBTYPE_SQLITE     = 'SQLite';
    const DBTYPE_SQLSERVER  = 'SQLServer';
    
    public function __construct()
    {
    }

    /**
     * Creates a database connection based on the database type contained in the
     * specified connection string.
     *
     * @param string $connectionString the database connection string, which contains
     *     the database type and the connection details.
     *
     * @param boolean $ssl indicates if SSL should be used for the database connection.
     *
     * @param boolean $sslVerify indicates if SSL verification should be done for the database connection.
     *
     * @param string $caCertFile certificate authority certificate file; used for SSL verification (if set).
     *
     * @param string $tablePrefix the table name prefix to use for generated tables.
     *
     * @param string $labelViewSuffix suffix used for label views, i.e., views of tables
     *     that replace multiple choice value codes with their corresponding labels.
     */
    public function createDbConnection($connectionString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        list($dbType, $dbString) = $this->parseConnectionString($connectionString);

        switch ($dbType) {
            case DbConnectionFactory::DBTYPE_MYSQL:
                $dbcon = new MysqlDbConnection(
                    $dbString,
                    $ssl,
                    $sslVerify,
                    $caCertFile,
                    $tablePrefix,
                    $labelViewSuffix
                );
                break;

            case DbConnectionFactory::DBTYPE_POSTGRESQL:
                if (!extension_loaded('pgsql') || !extension_loaded('pdo_pgsql')) {
                    $message = 'Attempt to use a PostgreSQL database without required PHP extentions'
                        . ' "pgsql" and/or "pdo_pgsql". These PHP extensions will need to be installed'
                        . ' before a PostgreSQL database can be used with REDCap-ETL';
                    throw new \Exception($message);
                }

                $dbcon = new PostgreSqlDbConnection(
                    $dbString,
                    $ssl,
                    $sslVerify,
                    $caCertFile,
                    $tablePrefix,
                    $labelViewSuffix
                );
                break;

            case DbConnectionFactory::DBTYPE_SQLITE:
                if (!extension_loaded('sqlite3') || !extension_loaded('pdo_sqlite')) {
                    $message = 'Attempt to use a SQLite database without required PHP extentions'
                        . ' "sqlite3" and/or "pdo_sqlite". These PHP extensions will need to be installed'
                        . ' before a SQLite database can be used with REDCap-ETL';
                    throw new \Exception($message);
                }

                $dbcon = new SqliteDbConnection(
                    $dbString,
                    $ssl,
                    $sslVerify,
                    $caCertFile,
                    $tablePrefix,
                    $labelViewSuffix
                );
                break;

            case DbConnectionFactory::DBTYPE_CSV:
                $dbcon = new CsvDbConnection(
                    $dbString,
                    $ssl,
                    $sslVerify,
                    $caCertFile,
                    $tablePrefix,
                    $labelViewSuffix
                );
                break;

            case DbConnectionFactory::DBTYPE_SQLSERVER:
                if (!extension_loaded('sqlsrv') || !extension_loaded('pdo_sqlsrv')) {
                    $message = 'Attempt to use a SQL Server database without required PHP extentions'
                        . ' "sqlsrv" and/or "pdo_sqlsrv". These PHP extensions will need to be installed'
                        . ' before a SQL Server database can be used with REDCap-ETL';
                    throw new \Exception($message);
                }
                $dbcon = new SqlServerDbConnection(
                    $dbString,
                    $ssl,
                    $sslVerify,
                    $caCertFile,
                    $tablePrefix,
                    $labelViewSuffix
                );
                break;

            default:
                $message = 'Invalid database type: "'.$dbType.'". Valid types are: CSV, MySQL, SQLite, and sqlsrv.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        return($dbcon);
    }

    /**
     * Parses a connection string
     *
     * @param string $connectionString a connection string
     *     that has the format: <databaseType>:<databaseString>
     *
     * @return array an array with 2 string elements. The first
     *     is the database type, and the second is the database
     *     string (with database-specific connection information).
     */
    public static function parseConnectionString($connectionString)
    {
        if (empty($connectionString)) {
            throw new EtlException("Empty database connection string specified.", EtlException::INPUT_ERROR);
        }

        list($dbType, $dbString) = explode(':', $connectionString, 2);

        return array($dbType, $dbString);
    }

    
    /**
     * Creates  connection string from the specified database
     *     type and string.
     */
    public static function createConnectionString($dbType, $dbString)
    {
        return $dbType . ':' . $dbString;
    }
}

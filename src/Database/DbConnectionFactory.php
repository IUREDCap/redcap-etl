<?php

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;

/**
 * Factory class for creating database connections.
 */
class DbConnectionFactory
{
    // Database types
    const DBTYPE_CSV    = 'CSV';
    const DBTYPE_MYSQL  = 'MySQL';
    
    #const DBTYPE_SQLSRV = 'SQLServer';

    public function __construct()
    {
    }

    public function createDbConnection($connectionString, $tablePrefix, $labelViewSuffix)
    {
        list($dbType, $dbString) = $this->parseConnectionString($connectionString);

        switch ($dbType) {
            case DbConnectionFactory::DBTYPE_MYSQL:
                $dbcon = new MysqlDbConnection($dbString, $tablePrefix, $labelViewSuffix);
                break;

            #case DbConnectionFactory::DBTYPE_SQLSRV:
            #    $dbcon = new SqlServerDbConnection($dbString, $tablePrefix, $labelViewSuffix);
            #    break;

            case DbConnectionFactory::DBTYPE_CSV:
                $dbcon = new CsvDbConnection($dbString, $tablePrefix, $labelViewSuffix);
                break;

            default:
                $message = 'Invalid database type: "'.$dbType.'". Valid types are: CSV and MySQL.';
                throw new EtlException($mesage, EtlException::INPUT_ERROR);
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

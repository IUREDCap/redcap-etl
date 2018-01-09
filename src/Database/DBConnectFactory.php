<?php

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;

/**
 * DBConnectFactory - Creates storage-specific objects.
 * DBConnectFactory creates a DBConnect* object
 */
class DBConnectFactory
{
    // Database types
    const DBTYPE_MYSQL  = 'MySQL';
    const DBTYPE_SQLSRV = 'SQLServer';
    const DBTYPE_CSV    = 'CSV';


    public function __construct()
    {
        return(1);
    }

    public function createDbcon($connect_str, $tablePrefix, $labelViewSuffix)
    {
        list($db_type, $db_str) = $this->parseConnectStr($connect_str);

        switch ($db_type) {
            case DBConnectFactory::DBTYPE_MYSQL:
                $dbcon = new DBConnectMySQL($db_str, $tablePrefix, $labelViewSuffix);
                break;

            case DBConnectFactory::DBTYPE_SQLSRV:
                $dbcon = new DBConnectSQLSRV($db_str, $tablePrefix, $labelViewSuffix);
                break;

            case DBConnectFactory::DBTYPE_CSV:
                $dbcon = new DBConnectCSV($db_str, $tablePrefix, $labelViewSuffix);
                break;

            default:
                $dbcon = 'Did not understand DBTYPE ('.$db_type.')';
        }

        return($dbcon);
    }

    /**
     * A $connect_str is of the form $db_type:$db_str
     */
    public function parseConnectStr($connect_str)
    {
        list($db_type, $db_str) = explode(':', $connect_str, 2);

        return array($db_type, $db_str);
    }
}

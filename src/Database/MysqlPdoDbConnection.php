<?php

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\LookupTable;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\Table;

/**
 * Database connection class for MySQL databases that uses PDO (EXPERIMENTAL).
 * This class is considered as experimental, and is not currently used.
 * There is one known problem with this class, which is that verification of the server's
 * SSL certificate does not work.
 */
class MysqlPdoDbConnection extends PdoDbConnection
{
    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->errorString = '';

        #--------------------------------------------------------------
        # Get the database connection values
        #--------------------------------------------------------------
        $dbValues = DbConnection::parseConnectionString($dbString);
        $port = null;
        if (count($dbValues) == 4) {
            list($host,$username,$password,$database) = DbConnection::parseConnectionString($dbString);
        } elseif (count($dbValues) == 5) {
            list($host,$username,$password,$database,$port) = DbConnection::parseConnectionString($dbString);
            $port = intval($port);
        } else {
            $message = 'The database connection is not correctly formatted: ';
            if (count($dbValues) < 4) {
                $message = 'not enough values.';
            } else {
                $message = 'too many values.';
            }
            $code = EtlException::DATABASE_ERROR;
            throw new \Exception($message, $code);
        }

        if (empty($port)) {
            $port = null;
        }
        
        $driver  = 'mysql';
        $charset = 'utf8mb4';

        $port = 3306;

        $dataSourceName = "{$driver}:host={$host};dbname={$database};charset={$charset}";
        if (isset($port)) {
            $dataSourceName .= ";port={$port}";
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ];

        if ($ssl) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $caCertFile;
        }

        try {
            $this->db = new \PDO($dataSourceName, $username, $password, $options);
        } catch (\Exception $exception) {
            $message = 'Database connection error for database "'.$database.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
    }
}

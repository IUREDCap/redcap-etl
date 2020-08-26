<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;
#use IU\REDCapETL\LookupTable;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\Table;

/**
 * Database connection class for SQL Server databases.
 */
class PostgreSqlDbConnection extends PdoDbConnection
{
    const AUTO_INCREMENT_TYPE = 'SERIAL PRIMARY KEY';
    const DATETIME_TYPE       = 'timestamptz';

    private $id;

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->errorString = '';
        
        $this->db = self::getPdoConnection($dbString, $ssl, $sslVerify, $caCertFile);

        #--------------------------------------
        # Set ID
        #--------------------------------------
        $dbValues = DbConnection::parseConnectionString($dbString);

        if (count($dbValues) == 4) {
            list($host,$username,$password,$database) = $dbValues;
            $idValues = array(DbConnectionFactory::DBTYPE_POSTGRESQL, $host, $database);
        } elseif (count($dbValues) == 5) {
            list($host,$username,$password,$database,$schema) = $dbValues;
            $idValues = array(DbConnectionFactory::DBTYPE_POSTGRESQL, $host, $database, $schema);
        } elseif (count($dbValues) == 6) {
            list($host,$username,$password,$database,$schema, $port) = $dbValues;
            $idValues = array(DbConnectionFactory::DBTYPE_POSTGRESQL, $host, $database, $schema, $port);
        }

        $this->id = DbConnection::createConnectionString($idValues);
    }
 
    public static function getPdoConnection($dbString, $ssl, $sslVerify, $caCertFile)
    {
        $pdoConnection = null;
        
        #--------------------------------------------------------------
        # Get the database connection values
        #--------------------------------------------------------------
        $driver  = 'pgsql';

        $dbValues = DbConnection::parseConnectionString($dbString);

        $schema = null;
        $port   = null;

        if (count($dbValues) == 4) {
            list($host,$username,$password,$database) = $dbValues;
            $idValues = array(DbConnectionFactory::DBTYPE_POSTGRESQL, $host, $database);
        } elseif (count($dbValues) == 5) {
            list($host,$username,$password,$database,$schema) = $dbValues;
            $idValues = array(DbConnectionFactory::DBTYPE_POSTGRESQL, $host, $database, $schema);
        } elseif (count($dbValues) == 6) {
            list($host,$username,$password,$database,$schema, $port) = $dbValues;
            $idValues = array(DbConnectionFactory::DBTYPE_POSTGRESQL, $host, $database, $schema, $port);
            $port = intval($port);
        } else {
            $message = 'The database connection is not correctly formatted: ';
            if (count($dbValues) < 4) {
                $message .= 'not enough values.';
            } else {
                $message .= 'too many values.';
            }
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        if (empty($port)) {
            $port = null;
        } else {
            $host .= ",$port";
        }
        
        $dataSourceName = "{$driver}:host={$host};dbname={$database}";
        if ($ssl) {
            $dataSourceName .= ";sslmode=require";
            if ($sslVerify) {
                # set the attribute to verify the certificate
                $dataSourceName .= ";sslrootcert={$caCertFile}";
            } else {
                ;
            }
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION #,
            # \PDO::SQLSRV_ATTR_ENCODING => \PDO::SQLSRV_ENCODING_UTF8
        ];

        try {
            $pdoConnection = new \PDO($dataSourceName, $username, $password, $options);

            # Set schema:
            if (!empty($schema)) {
                $sql = 'SET search_path TO '.self::escapeNameStatically($schema);
                $pdoConnection->exec($sql);
            }
        } catch (\Exception $exception) {
            $message = 'Database connection error on host "'.$host.'"'
                .' for PostgeSQL database "'.$database.'"';
            if (!empty($schema)) {
                $message .= ' and schema "'.$schema.'"';
            }
            $message .= ' for user "'.$username.'"';
            $message .= ': '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
        
        return $pdoConnection;
    }

    public function getId()
    {
        return $this->id;
    }
    
    protected function escapeName($name)
    {
        $name = self::escapeNameStatically($name);
        return $name;
    }
    
    protected static function escapeNameStatically($name)
    {
        $name = '"'.(str_replace('"', '', $name)).'"';
        return $name;
    }
}

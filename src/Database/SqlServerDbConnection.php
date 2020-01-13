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
class SqlServerDbConnection extends PdoDbConnection
{
    const AUTO_INCREMENT_TYPE = 'INT NOT NULL IDENTITY(0,1) PRIMARY KEY';

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->errorString = '';

        #--------------------------------------------------------------
        # Get the database connection values
        #--------------------------------------------------------------
        $driver  = 'sqlsrv';

        $dbValues = DbConnection::parseConnectionString($dbString);

        $port = null;
        if (count($dbValues) == 4) {
            list($host,$username,$password,$database) = $dbValues;
        } elseif (count($dbValues) == 5) {
            list($host,$username,$password,$database,$port) = $dbValues;
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
            #$port = 1433; not using the default port for SQL Server; allowing it to be null
        } else {
            $host .= ",$port";
            #print "host has been changed to $host" . PHP_EOL;
        }
        
        $dataSourceName = "{$driver}:server={$host};Database={$database}";
        if ($ssl) {
            $dataSourceName .= ";Encrypt=1";
            if ($sslVerify) {
                #set the attribute to verify the certificate, i.e., TrustServerCertificate is false.
                $dataSourceName .= ";TrustServerCertificate=false";
            } else {
                #set the attribute to so that the cert is not verified,
                #i.e., TrustServerCertificate is true.
                $dataSourceName .= ";TrustServerCertificate=true";
            }
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::SQLSRV_ATTR_ENCODING => \PDO::SQLSRV_ENCODING_UTF8
        ];

        try {
            $this->db = new \PDO($dataSourceName, $username, $password, $options);
        } catch (\Exception $exception) {
            $message = 'Database connection error for database "'.$database.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
    }
 

    protected function getCreateTableIfNotExistsQueryPrefix($tableName)
    {
        $query = 'IF NOT EXISTS (SELECT [name] FROM sys.tables ';
        $query .= "WHERE [name] = " . $this->db->quote($tableName) . ') ';
        $query .= 'CREATE TABLE ' . $this->escapeName($tableName) . ' (';
        return $query;
    }


    protected function escapeName($name)
    {
        $name = str_replace('[', '', $name);
        $name = str_replace(']', '', $name);
        $name = '['.$name.']';
        return $name;
    }
}

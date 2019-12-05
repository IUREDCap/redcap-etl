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
    private static $auto_increment_type = 'INT NOT NULL IDENTITY(0,1) PRIMARY KEY';

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
        
        $driver  = 'sqlsrv';
        $dataSourceName = "{$driver}:server={$host};Database={$database}";
        if ($ssl) {
            $dataSourceName .= ";Encrypt=1";
            if ($sslVerify) {
                $dataSourceName .= ";TrustServerCertificate=false";
            } else {
                $dataSourceName .= ";TrustServerCertificate=true";
            }
        }    
        #print "dataSourceName: $dataSourceName" . PHP_EOL;

        /*getAttributes generates an error, probably issue with unixodbc. find out why.
        $pdo = new \PDO($dataSourceName, $username, $password);
        $checkAttributes = array("ERRMODE", "CONNECTION_STATUS", "SQLSRV_ATTR_ENCODING");
        foreach ($checkAttributes as $attribute) {
            print "for PDO::ATTR_$attribute: ";
            print $pdo->getAttribute(constant("PDO::ATTR_$attribute")) . PHP_EOL;
        }*/

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
 
    /**
     * Drops the specified table from the database.
     *
     * @param Table $table the table object corresponding to the table in
     *     the database that will be deleted.
     */
    public function dropTable($table, $ifExists = false)
    {
        // Define query
        if ($ifExists) {
            $query = "DROP TABLE IF EXISTS ". $this->escapeName($table->name);
        } else {
            $query = "DROP TABLE ". $this->escapeName($table->name);
        }
        
        // Execute query
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'Database error in query "'.$query.'" : '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        return(1);
    }

    /**
     * Creates the specified table.
     *
     * @param Table $table the table to be created.
     * @param boolean $ifNotExists indicates if the table should only be created if it
     *     doesn't already exist.
     */
    public function createTable($table, $ifNotExists = false)
    {
        // Start query
        if ($ifNotExists) {
            $query = 'IF NOT EXISTS (SELECT [name] FROM sys.tables ';
            $query .= "WHERE [name] = '" . $table->name . "'" .') ';
            $query .= 'CREATE TABLE ' . $table->name . ' (';
        } else {
            $query = 'CREATE TABLE ' . $table->name .' (';
        }

        // foreach field
        $fieldDefs = array();
        foreach ($table->getAllFields() as $field) {
            // Begin field_def
            $fieldDef = $field->dbName . ' ';

            // Add field type to field definition
            switch ($field->type) {
                case FieldType::DATE:
                    $fieldDef .= 'DATE';
                    break;
                    
                case FieldType::DATETIME:
                    $fieldDef .= 'DATETIME';
                    if (isset($field->size)) {
                        $size = intval($field->size);
                        if ($size > 0 || $size <= 6) {
                            $fieldDef .= '('.$size.')';
                        }
                    }
                    break;

                case FieldType::INT:
                case FieldType::CHECKBOX:
                    $fieldDef .= 'INT';
                    break;

                case FieldType::AUTO_INCREMENT:
                    $fieldDef .= self::$auto_increment_type;
                    break;
                    
                case FieldType::FLOAT:
                    $fieldDef .= 'FLOAT';
                    break;
    
                case FieldType::CHAR:
                    $fieldDef .= 'CHAR('.($field->size).')';
                    break;

                case FieldType::VARCHAR:
                    $fieldDef .= 'VARCHAR('.($field->size).')';
                    break;

                case FieldType::STRING:
                default:
                      $fieldDef .= 'TEXT';
                    break;
            } // switch

            // Add field_def to array of field_defs
            array_push($fieldDefs, $fieldDef);
        }

        // Add field definitions to query
        $query .= join(', ', $fieldDefs);

        // End query
        $query .= ')';

        #print "in SqlServerDbConnection create table , query is $query" . PHP_EOL;

        // Execute query
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'Database error in query "'.$query.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
        return(1);
    }

    /**
     * Inserts a single row into the datatabase.
     *
     * @parm Row $row the row of data to insert into the table (the row
     *     has a reference to the table that it should be inserted into).
     *
     * @return integer if the table has an auto-increment ID, then the value
     *     of the ID for the record that was inserted.
     */
    public function insertRow($row)
    {
        $table = $row->table;

        #--------------------------------------------------
        # Remove auto-increment fields
        #--------------------------------------------------
        $fields = $table->getAllNonAutoIncrementFields();

        $queryValues = array();
        $rowValues = $this->getRowValues($row, $fields);
        $queryValues[] = '('.implode(",", $rowValues).')';
    
        $query = $this->createInsertStatement($table->name, $fields, $queryValues);
        #print "\nin SqlServerDbConnection, insertRow, QUERY: $query\n";

    
        try {
            $rc = $this->db->exec($query);
            $insertId = $this->db->lastInsertId();
        } catch (\Exception $exception) {
            $message = 'Database error while trying to insert a single row into table "'
                .$table->name.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        return $insertId;
    }

    /**
     * Creates an insert statement.
     *
     * @param array $fields array of Field objects for the table which values are being inserted into.
     */
    protected function createInsertStatement($tableName, $fields, $values)
    {
        $dbFieldNames = array_column($fields, 'dbName');
        $insertStart = 'INSERT INTO ' .  $tableName . ' (' . implode(",", $dbFieldNames) .') VALUES ';
        $insert = $insertStart.implode(",", $values);
        #print "\nin SqlServerDbConnection, createInsertStatement, INSERT: $insert\n";
        return $insert;
    }
}

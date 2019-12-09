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
    private static $autoIncrementType = 'INT NOT NULL IDENTITY(0,1) PRIMARY KEY';

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->errorString = '';

        #--------------------------------------------------------------
        # Get the database connection values
        #--------------------------------------------------------------
        $driver  = 'sqlsrv';

        #strip out the driver if it has been included
        if (strtolower(substr($dbString, 0, 7)) == $driver . ':') {
            $dbString = substr($dbString, -1*(trim(strlen($dbString)) - 7));
        }

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
        
        $dataSourceName = "{$driver}:server={$host};Database={$database}";
        if ($ssl) {
            $dataSourceName .= ";Encrypt=1";
            if ($sslVerify) {
                $dataSourceName .= ";TrustServerCertificate=false";
            } else {
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
 
    /**
     * Drops the specified table from the database.
     *
     * @param Table $table the table object corresponding to the table in
     *     the database that will be deleted.
     */
    protected function dropTable($table, $ifExists = false)
    {
        // Define query
        if ($ifExists) {
            $query = "DROP TABLE IF EXISTS ". $table->name;
        } else {
            $query = "DROP TABLE ". $table->name;
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
                    break;

                case FieldType::CHECKBOX:
                case FieldType::INT:
                    $fieldDef .= 'INT';
                    break;

                case FieldType::AUTO_INCREMENT:
                    $fieldDef .= self::$autoIncrementType;
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

    public function replaceLookupView($table, $lookup)
    {
        $selects = array();

        // foreach field
        foreach ($table->getAllFields() as $field) {
            // If the field does not use lookup table
            if ($field->usesLookup === false) {
                array_push($selects, $field->dbName);
            } else {
                // $field->usesLookup holds name of lookup field, if not false
                // name of lookup field is root of field name for checkbox
                $fname = $field->usesLookup;

                // If the field uses the lookup table and is a checkbox field
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->dbName)) {
                // For checkbox fields, the join needs to be done based on
                // the category embedded in the name of the checkbox field

                // Separate root from category
                    list($rootName, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->dbName);

                    $label = $this->db->quote(
                        $lookup->getLabel($table->name, $fname, $cat)
                    );
                    $select = 'CASE ' . $field->dbName . ' WHEN 1 THEN '
                        . $label
                        . " ELSE '0'"
                        . ' END as ' . $field->dbName;
                } // The field uses the lookup table and is not a checkbox field
                else {
                    $select = 'CASE ' . $field->dbName;
                    $map = $lookup->getValueLabelMap($table->name, $fname);
                    foreach ($map as $value => $label) {
                        $select .= ' WHEN '.($this->db->quote($value))
                            .' THEN '.($this->db->quote($label));
                    }
                    $select .= ' END as ' . $field->dbName;
                }
                array_push($selects, $select);
            }
        }

        #------------------------------------------------
        # Drop the view if it exists
        #------------------------------------------------
        $query = 'DROP VIEW IF EXISTS ' . $table->name.$this->labelViewSuffix;

        # Execute query
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'SQL Server error in query "'.$query.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        #------------------------------------------------------------
        # Create the view
        #------------------------------------------------------------
        $query = 'CREATE VIEW ' . $table->name.$this->labelViewSuffix . ' AS ';

        $select = 'SELECT ' . implode(', ', $selects);
        $from = 'FROM ' . $table->name;

        $query .= $select.' '.$from;

        #print("IN REPLACE LABEL VIEW, QUERY: $query\n");
        // Execute query
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'SQL Server error in query "'.$query.'": '.$exception->getMessage();
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
                
        #if the table is one the log tables, then convert the string date into
        #a datetime value that SQL Server can process.
        if ($table->name === 'etl_event_log') {
            #check to see if the string is a date.
            if (is_string($rowValues[1])
                 && (substr($rowValues[1], 1, 2) === '20')
                 && (substr($rowValues[1], 5, 1) === '-')
                ) {
                    #Since later code implodes on a comma and commas are used in the convert syntax,
                    #use '!!!!' to get past the implode, then replace the '!!!!' with commas
                    $rowValues[1] = 'CONVERT(datetime!!!!' . substr($rowValues[1], 0, 24) . "'!!!!21)";
            }
        } elseif ($table->name === 'etl_log') {
            #check to see if the string is a date.
            if (is_string($rowValues[0])
                 && (substr($rowValues[0], 1, 2) === '20')
                 && (substr($rowValues[0], 5, 1) === '-')
                ) {
                    #Since later code implodes on a comma and commas are used in the convert syntax,
                    #use '!!!!' to get past the implode, then replace the '!!!!' with commas
                    $rowValues[0] = 'CONVERT(datetime!!!!' . substr($rowValues[0], 0, 24) . "'!!!!21)";
            }
        }

        $queryValues[] = '('.implode(",", $rowValues).')';
        
        if ($table->name === 'etl_log' || $table->name === 'etl_event_log') {
            $queryValues[0] = str_replace('!!!!', ',', $queryValues[0]);
        }

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
     * Inserts all rows in the specified (in-memory) table into the database. The rows
     * are inserted using a single SQL INSERT command.
     *
     * @param Table $table the table object containing the rows to be inserted in the database.
     */
    protected function insertRows($table)
    {
        $rows = $table->getRows();
        $result = true;

        if (is_array($rows) && count($rows) > 0) {
            #--------------------------------------------------
            # Get non-auto-increment fields
            #--------------------------------------------------
            $fields = $table->getAllNonAutoIncrementFields();

            $queryValues = array();
            foreach ($rows as $row) {
                $rowValues = $this->getRowValues($row, $fields);
                $queryValues[] = '('.implode(",", $rowValues).')';
            }
    
            $query = $this->createInsertStatement($table->name, $fields, $queryValues);
            #print "\n in SQL Server insert Rows, $query\n";
    
            try {
                $rc = $this->db->exec($query);
            } catch (\Exception $exception) {
                $message = 'SQL Server error while trying to insert values into table "'
                    .$table->name.'": '.$exception->getMessage();
                $code = EtlException::DATABASE_ERROR;
                throw new EtlException($message, $code);
            }
        }
    
        return($result);
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

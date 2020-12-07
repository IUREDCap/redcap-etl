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
 * Abstract PDO Database connection class.
 */
abstract class PdoDbConnection extends DbConnection
{
    protected $db;

    const AUTO_INCREMENT_TYPE = 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
    const DATETIME_TYPE       = 'DATETIME';

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);
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
            $query = "DROP TABLE IF EXISTS ". $this->escapeName($table->getName());
        } else {
            $query = "DROP TABLE ". $this->escapeName($table->getName());
        }
        
        // Execute query
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'Database error in query "'.$query.'" : '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
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
            $query = $this->getCreateTableIfNotExistsQueryPrefix($table->getName());
            #$query = 'CREATE TABLE IF NOT EXISTS '.$this->escapeName($table->getName()).' (';
        } else {
            $query = 'CREATE TABLE '.$this->escapeName($table->getName()).' (';
        }

        // foreach field
        $fieldDefs = array();
        foreach ($table->getAllFields() as $field) {
            # Add field name to field definition
            $fieldDef = $this->escapeName($field->dbName).' ';

            # Add field type to field definition
            switch ($field->type) {
                case FieldType::DATE:
                    $fieldDef .= 'DATE';
                    break;
                    
                case FieldType::DATETIME:
                    $fieldDef .= static::DATETIME_TYPE;
                    # Note: neither SQLite or SQL Server support a size specification
                    # for DATETIME
                    break;

                case FieldType::CHECKBOX:
                case FieldType::INT:
                    $fieldDef .= 'INT';
                    break;

                case FieldType::AUTO_INCREMENT:
                    $fieldDef .= static::AUTO_INCREMENT_TYPE;
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

            if ($field->name === $table->primary->dbName && $field->type !== FieldType::AUTO_INCREMENT) {
                $fieldDef .= ' NOT NULL';
            }

            // Add field_def to array of field_defs
            array_push($fieldDefs, $fieldDef);
        }

        // Add field definitions to query
        $query .= join(', ', $fieldDefs);

        // End query
        $query .= ')';

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

    protected function getCreateTableIfNotExistsQueryPrefix($tableName)
    {
        $query = 'CREATE TABLE IF NOT EXISTS '.$this->escapeName($tableName).' (';
        return $query;
    }

    public function addPrimaryKeyConstraint($table)
    {
        $query = 'ALTER TABLE ' . $this->escapeName($table->getName())
            . ' ADD PRIMARY KEY('.$table->primary->dbName.')';

        try {
            $result = $this->db->query($query);
        } catch (\Exception $exception) {
            $message = 'Database error in query "'.$query.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
    }

    public function addForeignKeyConstraint($table)
    {
        if ($table->isChildTable()) {
            $query = 'ALTER TABLE ' . $this->escapeName($table->getName())
                . ' ADD FOREIGN KEY('.$this->escapeName($table->foreign->dbName).')'
                . ' REFERENCES '.$this->escapeName($table->parent->getName())
                .'('.$this->escapeName($table->parent->primary->dbName).')';

            try {
                $result = $this->db->query($query);
            } catch (\Exception $exception) {
                $message = 'Database error in query "'.$query.'": '.$exception->getMessage();
                $code = EtlException::DATABASE_ERROR;
                throw new EtlException($message, $code);
            }
        }
    }


    public function dropLabelView($table, $ifExists = false)
    {
        $view = ($table->getName()).($this->labelViewSuffix);

        // Define query
        if ($ifExists) {
            $query = "DROP VIEW IF EXISTS ". $this->escapeName($view);
        } else {
            $query = "DROP VIEW ". $this->escapeName($view);
        }
        
        // Execute query
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'Database error in query "'.$query.'" : '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
    }


    /**
     * Creates (or replaces) the lookup view for the specified table.
     */
    public function replaceLookupView($table, $lookup)
    {
        $selects = array();

        // foreach field
        foreach ($table->getAllFields() as $field) {
            // If the field does not use lookup table
            if ($field->usesLookup() === false) {
                array_push($selects, $this->escapeName($field->dbName));
            } else {
                // $field->usesLookup holds name of lookup field, if not false
                // name of lookup field is root of field name for checkbox
                $fname = $field->usesLookup();

                // If the field uses the lookup table and is a checkbox field
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->dbName)) {
                    // For checkbox fields, the join needs to be done based on
                    // the category embedded in the name of the checkbox field

                    // Separate root from choice value
                    list($rootName, $choiceValue) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->dbName);

                    $label = $this->db->quote(
                        $lookup->getLabel($table->getName(), $fname, $choiceValue)
                    );
                    $select = 'CASE '.$this->escapeName($field->dbName).' WHEN 1 THEN '
                        . $label
                        . " ELSE '0'"
                        . ' END as '.$this->escapeName($field->dbName);
                } // The field uses the lookup table and is not a checkbox field
                else {
                    $select = 'CASE '.$this->escapeName($field->dbName);
                    $map = $lookup->getValueLabelMap($table->getName(), $fname);
                    foreach ($map as $value => $label) {
                        $select .= ' WHEN '.($this->db->quote($value))
                            .' THEN '.($this->db->quote($label));
                    }
                    $select .= ' END as '.$this->escapeName($field->dbName);
                }
                array_push($selects, $select);
            }
        }

        #------------------------------------------------
        # Drop the view if it exists
        #------------------------------------------------
        $query = 'DROP VIEW IF EXISTS '.$this->escapeName($table->getName().$this->labelViewSuffix);

        # Execute query
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'Error in database query "'.$query.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        #------------------------------------------------------------
        # Create the view
        #------------------------------------------------------------
        $query = 'CREATE VIEW '.$this->escapeName($table->getName().$this->labelViewSuffix).' AS ';

        $select = 'SELECT '. implode(', ', $selects);
        $from = 'FROM '.$this->escapeName($table->getName());

        $query .= $select.' '.$from;

        // Execute query
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'Error in database query "'.$query.'": '.$exception->getMessage();
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
    
        $query = $this->createInsertStatement($table->getName(), $fields, $queryValues);
        #print "\nQUERY: $query\n";
    
        try {
            $rc = $this->db->exec($query);
            $insertId = $this->db->lastInsertId();
        } catch (\Exception $exception) {
            $message = 'Database error while trying to insert a single row into table "'
                .$table->getName().'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        return (int) $insertId;
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
    
            $query = $this->createInsertStatement($table->getName(), $fields, $queryValues);
            #print "\n\nQUERY:\n----------------------------\n$query\n\n";
    
            try {
                $rc = $this->db->exec($query);
            } catch (\Exception $exception) {
                $message = 'Database error while trying to insert values into table "'
                    .$this->escapeName($table->getName()).'": '.$exception->getMessage();
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
        # Escape the database field names
        $dbFieldNames = array_column($fields, 'dbName');
        for ($i = 0; $i < count($dbFieldNames); $i++) {
            $dbFieldNames[$i] = $this->escapeName($dbFieldNames[$i]);
        }

        $insertStart = 'INSERT INTO '.$this->escapeName($tableName).' ('. implode(",", $dbFieldNames) .') VALUES ';

        $insert = $insertStart.implode(",", $values);
        return $insert;
    }


    protected function getRowValues($row, $fields)
    {
        #print "\nFIELDS:\n";
        #print "--------------------------------------------------------------------:\n";
        #print_r($fields);

        $rowData = $row->getData();
        $rowValues = array();
        foreach ($fields as $field) {
            $fieldName  = $field->name;
            $fieldType  = $field->type;
            $redcapType = $field->redcapType;

            switch ($fieldType) {
                case FieldType::INT:
                    #print "REDCAP TYPE FOR {$fieldName}: {$redcapType}\n";
                    if (empty($rowData[$fieldName]) && $rowData[$fieldName] !== 0) {
                        if (strcasecmp($redcapType, 'checkbox') === 0) {
                            $rowValues[] = 0;
                        } else {
                            $rowValues[] = 'null';
                        }
                    } else {
                        $rowValues[] = (int) $rowData[$fieldName];
                    }
                    break;
                case FieldType::CHECKBOX:
                    if (empty($rowData[$fieldName])) {
                        $rowValues[] = 0;
                    } else {
                        $rowValues[] = (int) $rowData[$fieldName];
                    }
                    break;
                case FieldType::FLOAT:
                    if (empty($rowData[$fieldName]) && $rowData[$fieldName] !== 0.0) {
                        $rowValues[] = 'null';
                    } else {
                        $rowValues[] = (float) $rowData[$fieldName];
                    }
                    break;
                case FieldType::STRING:
                case FieldType::CHAR:
                case FieldType::VARCHAR:
                    $rowValues[] = $this->db->quote($rowData[$fieldName]);
                    break;
                case FieldType::DATE:
                case FieldType::DATETIME:
                    if (empty($rowData[$fieldName])) {
                        $rowValues[] = "null";
                    } else {
                        $rowValues[] = $this->db->quote($rowData[$fieldName]);
                    }
                    break;
                default:
                    $message = 'Unrecognized database field type for Database: "'.$fieldType.'".';
                    $code = EtlException::DATABASE_ERROR;
                    throw new EtlException($message, $code);
                    break;
            }
        }
        return $rowValues;
    }


    public function processQueryFile($queryFile)
    {
        if (file_exists($queryFile)) {
            $queries = file_get_contents($queryFile);
            if ($queries === false) {
                $error = 'processQueryFile: Could not access query file "'.$queryFile.'": '
                    .error_get_last()['message'];
                $code = EtlException::DATABASE_ERROR;
                throw new EtlException($error, $code);
            } else {
                $this->processQueries($queries);
            }
        } else {
            $error = "Could not access query file $queryFile: "
                 .error_get_last()['message'];
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($error, $code);
        }
    }
    
    public function processQueries($queries)
    {
        try {
            $querySet = DbConnection::parseSqlQueries($queries);
            foreach ($querySet as $query) {
                $result = $this->db->query($query);
            }
        } catch (\Exception $exception) {
            $error = "SQL query failed: ".$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($error, $code);
        }
    }

    /**
     * Escapes a name for use as a table or column name in a query.
     */
    protected function escapeName($name)
    {
        $name = '`'.str_replace("`", "``", $name).'`';
        return $name;
    }
}

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
 * Database connection class for MySQL databases.
 */
class MysqlDbConnection extends DbConnection
{
    private $mysqli;

    private $id;

    private $databaseName;

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->errorString = '';

        #--------------------------------------------------------------
        # Get the database connection values
        #--------------------------------------------------------------
        $dbValues = DbConnection::parseConnectionString($dbString);
        $idValues = array();
        $port = null;
        if (count($dbValues) == 4) {
            list($host,$username,$password,$database) = $dbValues;
            $this->databaseName = $database;
            $idValues = array(DbConnectionFactory::DBTYPE_MYSQL, $host, $database);
        } elseif (count($dbValues) == 5) {
            list($host,$username,$password,$database,$port) = $dbValues;
            $this->databaseName = $database;
            $idValues = array(DbConnectionFactory::DBTYPE_MYSQL, $host, $database, $port);
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

        $this->id = DbConnection::createConnectionString($idValues);

        // Get MySQL connection
        // NOTE: Could add error checking
        // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // Setting the above causes the program to stop for any uncaugt errors
        
        if (empty($port)) {
            $port = null;
        }
        
        $flags = 0;
        if ($ssl) {
            $flags = MYSQLI_CLIENT_SSL;
        }
        
        $this->mysqli = mysqli_init();
        if (!isset($this->mysqli)) {
            $message = "Call to mysqli_init failed.";
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        } elseif ($sslVerify && !empty($caCertFile)) {
            $result = $this->mysqli->ssl_set(null, null, $caCertFile, null, null);
            if (!$result) {
                $message = "Call to mysqli::ssl_set failed.";
                $code = EtlException::DATABASE_ERROR;
                throw new EtlException($message, $code);
            }

            $this->mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        }

        try {
            @$result = $this->mysqli->real_connect($host, $username, $password, $database, $port, null, $flags);
            if (!$result) {
                $message = 'MySQL error ['.$this->mysqli->connect_errno.']: '.$this->mysqli->connect_error;
                $code = EtlException::DATABASE_ERROR;
                throw new EtlException($message, $code);
            }
        } catch (\Exception $exception) {
            $message = 'Unable to connect to MySQL database "'.$database.'" at host "'.$host.'"';
            if (!empty($port)) {
                $message .= ' on port '.$port;
            }

            $message .= ' as user "'.$username.'"';

            if ($ssl) {
                $message .= ' using SSL';
                if ($sslVerify) {
                    $message .= ' with verification';
                }
                $message .= '. Possible causes:';
                $message .= ' your database connection information may be incorrect';
                if ($ssl) {
                    $message .= ', your database may not support SSL';
                    if ($sslVerify) {
                        $message .= ' or the database SSL certificate could not be verified';
                    }
                }
                $message .= ', a firewall may be restricting access to your database';
            }
            $message .= '.';

            $message .= ' '. $exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
    }


    public function getId()
    {
        return $this->id;
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
        $result = $this->mysqli->query($query);
        if ($result === false) {
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
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
            $query = 'CREATE TABLE IF NOT EXISTS '.$this->escapeName($table->getName()).' (';
        } else {
            $query = 'CREATE TABLE '.$this->escapeName($table->getName()).' (';
        }

        // foreach field
        $fieldDefs = array();
        foreach ($table->getAllFields() as $field) {
            // Begin field_def
            $fieldDef = $this->escapeName($field->dbName).' ';

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

                case FieldType::CHECKBOX:
                case FieldType::INT:
                    $fieldDef .= 'INT';
                    break;

                case FieldType::AUTO_INCREMENT:
                    $fieldDef .= 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
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

        # Add field definitions to query
        $query .= join(', ', $fieldDefs);

        # End query
        $query .= ')';

        # print "\nQUERY: {$query}\n";

        # Execute query
        $result = $this->mysqli->query($query);
        if ($result === false) {
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        return(1);
    }

    /**
     * Adds a primary key to the table (note: MySQL ignores the constraint name
     * as of when this method was implemented).
     */
    public function addPrimaryKeyConstraint($table)
    {
        $query = 'ALTER TABLE ' . $this->escapeName($table->getName())
            . ' ADD PRIMARY KEY ('.$this->escapeName($table->primary->dbName).')';

        $result = $this->mysqli->query($query);

        if ($result === false) {
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
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

            $result = $this->mysqli->query($query);

            if ($result === false) {
                $message = 'MySQL error in query "'.$query.'"'
                    .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
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
        $result = $this->mysqli->query($query);
        if ($result === false) {
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
    }


    /**
     * Creates (or replaces) the lookup view for the specified table.
     *
     * @param Table $table the table for which the label view is being created.
     * @param LookupTable $lookup the lookup table.
     */
    public function replaceLookupView($table, $lookup)
    {
        if ($table->getNeedsLabelView()) {
            $selects = array();

            // foreach field
            foreach ($table->getAllFields() as $field) {
                if ($field->isLabel()) {
                    ; // don't add to view
                } elseif ($field->usesLookup() === false) {
                    // If the field does not use lookup table
                    array_push($selects, $this->escapeName($field->dbName));
                } else {
                    // $field->usesLookup holds name of lookup field, if not false
                    // name of lookup field is root of field name for checkbox
                    $fname = $field->usesLookup();

                    // If the field uses the lookup table and is a checkbox field
                    if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->dbName)) {
                    // For checkbox fields, the join needs to be done based on
                    // the category embedded in the name of the checkbox field

                    // Separate root from category
                        list($rootName, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->dbName);

                        $label = $this->mysqli->real_escape_string(
                            $lookup->getLabel($table->getName(), $fname, $cat)
                        );
                        $select = 'CASE '.$this->escapeName($field->dbName).' WHEN 1 THEN '
                            . "'".$label."'"
                            . ' ELSE 0'
                            . ' END as '.$this->escapeName($field->dbName);
                    } else {
                        # The field uses the lookup table and is not a checkbox field
                        $select = 'CASE '.$this->escapeName($field->dbName);
                        $map = $lookup->getValueLabelMap($table->getName(), $fname);
                        foreach ($map as $value => $label) {
                            $select .= ' WHEN '."'".($this->mysqli->real_escape_string($value))."'"
                                .' THEN '."'".($this->mysqli->real_escape_string($label))."'";
                        }
                        $select .= ' END as '.$this->escapeName($field->dbName);
                    }
                    array_push($selects, $select);
                }
            }

            $query = 'CREATE OR REPLACE VIEW '.$this->escapeName($table->getName().$this->labelViewSuffix).' AS ';

            $select = 'SELECT '. implode(', ', $selects);
            $from = 'FROM '.$this->escapeName($table->getName());

            $query .= $select.' '.$from;

            ###print("QUERY: $query\n");

            // Execute query
            $result = $this->mysqli->query($query);
            if ($result === false) {
                $message = 'MySQL error in query "'.$query.'"'
                    .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
                $code = EtlException::DATABASE_ERROR;
                throw new EtlException($message, $code);
            }
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
        # print "\nQUERY: $query\n";
    
        $rc = $this->mysqli->query($query);
    
        #---------------------------------------------------
        # If there's an error executing the statement
        #---------------------------------------------------
        if ($rc === false) {
            $this->errorString = $this->mysqli->error;
            # Note: do not print out the specific query here, because it will
            #     be logged, and could contain PHI
            $message = 'MySQL error while trying to insert a single row into table "'
                .$table->getName().'": '
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        } else {
            $insertId = $this->mysqli->insert_id;
        }

        return $insertId;
    }

    /**
     * Inserts all rows in the specified (in-memory) table into the database. The rows
     * are inserted using a single SQL INSERT command.
     *
     * @param Table $table the table object containing the rows to be inserted in the database.
     */
    protected function insertRows($table, $batchSize = null)
    {
        $rows = $table->getRows();

        $result = true;

        if (is_array($rows) && count($rows) > 0) {
            #--------------------------------------------------
            # Get non-auto-increment fields
            #--------------------------------------------------
            $fields = $table->getAllNonAutoIncrementFields();

            $rowBatches = array();
            if (isset($batchSize) && is_int($batchSize) && $batchSize > 0) {
                $rowBatches = array_chunk($rows, $batchSize, true);
            } else {
                $rowBatches[] = $rows;
            }

            foreach ($rowBatches as $rows) {
                $queryValues = array();

                foreach ($rows as $row) {
                    $rowValues = $this->getRowValues($row, $fields);
                    $queryValues[] = '('.implode(",", $rowValues).')';
                }
    
                $query = $this->createInsertStatement($table->getName(), $fields, $queryValues);
                # print "QUERY: {$query}\n";
    
                $rc = $this->mysqli->query($query);
    
                #---------------------------------------------------
                # If there's an error executing the statement
                #---------------------------------------------------
                if ($rc === false) {
                    $this->errorString = $this->mysqli->error;
                    $result = false;

                    # Note: do not print out the specific query here, because it will
                    #     be logged, and could contain PHI
                    $message = 'MySQL error while trying to insert values into table "'
                        .$this->escapeName($table->getName()).'": '
                        .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
                    $code = EtlException::DATABASE_ERROR;
                    throw new EtlException($message, $code);
                }
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
        $rowData = $row->getData();
        $rowValues = array();
        foreach ($fields as $field) {
            $fieldDbName = $field->dbName;
            $fieldType   = $field->type;
            $redcapType  = $field->redcapType;

            switch ($fieldType) {
                case FieldType::INT:
                    #print "REDCAP TYPE FOR {$fieldDbName}: {$redcapType}\n";
                    if (empty($rowData[$fieldDbName])
                            && $rowData[$fieldDbName] !== 0
                            && $rowData[$fieldDbName] !== '0') {
                        if (strcasecmp($redcapType, 'checkbox') === 0) {
                            $rowValues[] = 0;
                        } else {
                            $rowValues[] = 'null';
                        }
                    } else {
                        $rowValues[] = (int) $rowData[$fieldDbName];
                    }
                    break;
                case FieldType::CHECKBOX:
                    if (empty($rowData[$fieldDbName])) {
                        $rowValues[] = 0;
                    } else {
                        $rowValues[] = (int) $rowData[$fieldDbName];
                    }
                    break;
                case FieldType::FLOAT:
                    if (isset($rowData[$fieldDbName]) && is_numeric($rowData[$fieldDbName])) {
                        $rowValues[] = (float) $rowData[$fieldDbName];
                    } else {
                        $rowValues[] = 'null';
                    }
                    break;
                case FieldType::STRING:
                case FieldType::CHAR:
                case FieldType::VARCHAR:
                    $rowValues[] = "'".$this->mysqli->real_escape_string($rowData[$fieldDbName])."'";
                    break;
                case FieldType::DATE:
                case FieldType::DATETIME:
                    if (empty($rowData[$fieldDbName])) {
                        $rowValues[] = "null";
                    } else {
                        $rowValues[] = "'".$this->mysqli->real_escape_string($rowData[$fieldDbName])."'";
                    }
                    break;
                default:
                    $message = 'Unrecognized database field type for MySQL: "'
                        . print_r($fieldType, true) . '" for database field "' . $fieldDbName . '".';
                    $code = EtlException::DATABASE_ERROR;
                    throw new EtlException($message, $code);
                    break;
            }
        }
        return $rowValues;
    }

    public function getTableColumnNames($tableName)
    {
        $columnNames = array();

        $query = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? '
            .' ORDER BY ORDINAL_POSITION';
        $statement = $this->mysqli->prepare($query);
        $statement->bind_param('ss', $this->databaseName, $tableName);

        $statement->execute();
        $result = $statement->get_result();

        $rows = $result->fetch_all(MYSQLI_NUM);
        foreach ($rows as $row) {
            $columnNames[] = $row[0];
        }

        return $columnNames;
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
            $error = "Could not access query file $queryFile: ";
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($error, $code);
        }
    }
    
    public function processQueries($queries)
    {
        #---------------------------------------------------
        # Note: use of multi_query has advantage that it
        # does the parsing; there could be cases where
        # a semi-colons is in a string, or commented out
        # that would make parsing difficult
        #---------------------------------------------------
        $queryNumber = 1;
        $result = $this->mysqli->multi_query($queries);
        if ($result === false) {
            $mysqlError = $this->mysqli->error;
            $error = "SQL query {$queryNumber} failed: {$mysqlError}.\n";
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($error, $code);
        } else {
            while ($this->mysqli->more_results()) {
                $result = $this->mysqli->next_result();
                if ($result === false) {
                    $mysqlError = $this->mysqli->error;
                    $error = "SQL query {$queryNumber} failed: {$mysqlError}.\n";
                    $code = EtlException::DATABASE_ERROR;
                    throw new EtlException($error, $code);
                } else {
                    $result = $this->mysqli->store_result();
                    if ($result instanceof mysqli_result) {
                        # This appears to be a select query, so free the result
                        # to avoid the following error:
                        #     MySQL error [2014]: Commands out of sync;
                        #     you can't run this command now
                        $result->free();
                    }
                }
                $queryNumber++;
            }
        }
    }


    /**
     * Note: MySQL with return all values with type string.
     */
    public function getData($tableName, $orderByField = null)
    {
        $data = array();
        $query = 'SELECT * FROM '.$this->escapeName($tableName);

        if (!empty($orderByField)) {
            $query .= ' ORDER BY '.$this->escapeName($orderByField);
        }

        $result = $this->mysqli->query($query);
        if ($result) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
        }
        return $data;
    }


    /**
     * Escapes a name for use as a table or column name in a query.
     */
    private function escapeName($name)
    {
        $name = '`'.str_replace("`", "``", $name).'`';
        return $name;
    }
}

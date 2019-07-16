<?php

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

    private $insertRowStatements;
    private $insertRowBindTypes;

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

        // Get MySQL connection
        // NOTE: Could add error checking
        // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // Setting the above causes the program to stop for any uncaugt errors
        
        if (empty($port)) {
            $port = null;
        }
        
        $flags = null;
        if ($ssl) {
            $flags = MYSQLI_CLIENT_SSL;
        }
        
        $this->mysqli = mysqli_init();
        if ($sslVerify && !empty($caCertFile)) {
            $this->mysqli->ssl_set(null, null, $caCertFile, null, null);
            $this->mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        }
        $this->mysqli->real_connect($host, $username, $password, $database, $port, null, $flags);


        if ($this->mysqli->connect_errno) {
            $message = 'MySQL error ['.$this->mysqli->connect_errno.']: '.$this->mysqli->connect_error;
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        $this->insertRowStatements = array();
        $this->insertRowBindTypes = array();
    }

    protected function existsTable($table)
    {

        // Note: exists_table currently assumes that a table always exists,
        //       as there is no practical problem with attempting to drop
        //       a non-existent table

        return(true);
    }


    /**
     * Drops the specified table from the database.
     *
     * @param Table $table the table object corresponding to the table in
     *     the database that will be deleted.
     */
    protected function dropTable($table)
    {
        // Define query
        $query = "DROP TABLE IF EXISTS ". $this->escapeName($table->name);

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
            $query = 'CREATE TABLE IF NOT EXISTS '.$this->escapeName($table->name).' (';
        } else {
            $query = 'CREATE TABLE '.$this->escapeName($table->name).' (';
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

        // Add field definitions to query
        $query .= join(', ', $fieldDefs);

        // End query
        $query .= ')';

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
     * Creates (or replaces) the lookup view for the specified table.
     */
    public function replaceLookupView($table, $lookup)
    {
        $selects = array();

        // foreach field
        foreach ($table->getAllFields() as $field) {
            // If the field does not use lookup table
            if ($field->usesLookup === false) {
                array_push($selects, $this->escapeName($field->dbName));
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

                    $label = $this->mysqli->real_escape_string(
                        $lookup->getLabel($table->name, $fname, $cat)
                    );
                    $select = 'CASE '.$this->escapeName($field->dbName).' WHEN 1 THEN '
                        . "'".$label."'"
                        . ' ELSE 0'
                        . ' END as '.$this->escapeName($field->dbName);
                } // The field uses the lookup table and is not a checkbox field
                else {
                    $select = 'CASE '.$this->escapeName($field->dbName);
                    $map = $lookup->getValueLabelMap($table->name, $fname);
                    foreach ($map as $value => $label) {
                        $select .= ' WHEN '."'".($this->mysqli->real_escape_string($value))."'"
                            .' THEN '."'".($this->mysqli->real_escape_string($label))."'";
                    }
                    $select .= ' END as '.$this->escapeName($field->dbName);
                }
                array_push($selects, $select);
            }
        }

        $query = 'CREATE OR REPLACE VIEW '.$this->escapeName($table->name.$this->labelViewSuffix).' AS ';

        $select = 'SELECT '. implode(', ', $selects);
        $from = 'FROM '.$this->escapeName($table->name);

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


        return(1);
    }


    protected function existsRow($row)
    {

        // NOTE: For now, existsRow will assume that the row does not
        //       exist and always return false. If the code ever needs
        //       to maintain existing rows, this will need to be implemented

        return(false);
    }

    protected function updateRow($row)
    {

        // NOTE: For now, updateRow is just a stub that returns true. It
        //       is not expected to be reached. If the code ever needs to
        //       maintain existing rows, this will need to be implemented.

        return(1);
    }

    /**
     * Insert the specified row into the database.
     *
     * @parm Row $row the row of data to insert into the table (the row
     *     has a reference to the table that it should be inserted into).
     *
     * @return integer if the table has an auto-increment ID, then the value
     *     of the ID for the record that was inserted.
     */
    public function insertRow($row)
    {

        // How to handle unknow # of vars in bind_param. See answer by abd.agha
        // http://stackoverflow.com/questions/1913899/mysqli-binding-params-using-call-user-func-array

        // Get parameterized query
        //     If the query doesn't already exist, it will be created
        list($stmt,$bindTypes) = $this->getInsertRowStmt($row->table);

        // Start bind parameters list with bind_types and escaped table name
        $params = array($bindTypes);

        #---------------------------------------------------
        # Add field values, in order, to bind parameters,
        # but omit auto-increments fields.
        #---------------------------------------------------
        foreach ($row->table->getAllFields() as $field) {
            if ($field->type != FieldType::AUTO_INCREMENT) {
                // Replace empty string with null
                $toBind = $row->data[$field->name];
                
                if ($toBind === '') {
                    $toBind = null;
                }

                array_push($params, $toBind);
            }
        }

        // Get references to each parameter -- necessary because
        // call_user_func_array wants references
        $paramRefs = array();
        foreach ($params as $key => $value) {
            $paramRefs[$key] = &$params[$key];
        }

        // Bind references to prepared query
        call_user_func_array(array($stmt, 'bind_param'), $paramRefs);

        // Execute query
        $rc = $stmt->execute();

        $insertId = 0;
        
        // If there's an error executing the statement
        if ($rc === false) {
            $this->errorString = $stmt->error;
            $message = 'MySQL error '.' ['.$stmt->errno.']: '.$stmt->error;
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        } else {
            $insertId = $this->mysqli->insert_id;
        }
    
        return($insertId);
    }


    private function getInsertRowStmt($table)
    {

        // If we've already created this query, return it
        if (isset($this->insertRowStatements[$table->name])) {
            return(array($this->insertRowStatements[$table->name],
               $this->insertRowBindTypes[$table->name]));
        } // Otherwise, create and save the query and its associated bind types
        else {
            // Create field_names, positions array, and params arrays
            $fieldNames = array();
            $bindPositions = array();
            $bindTypes = '';
            foreach ($table->getAllFields() as $field) {
                if ($field->type == FieldType::AUTO_INCREMENT) {
                    continue;
                }
                
                array_push($fieldNames, $field->dbName);
                array_push($bindPositions, '?');

                switch ($field->type) {
                    case FieldType::CHAR:
                    case FieldType::VARCHAR:
                    case FieldType::STRING:
                    case FieldType::DATE:
                    case FieldType::DATETIME:
                        $bindTypes .= 's';
                        break;

                    case FieldType::FLOAT:
                          $bindTypes .= 'd';
                        break;

                    case FieldType::INT:
                    default:
                          $bindTypes .= 'i';
                        break;
                }
            }

            // Start $sql
            $query = 'INSERT INTO '.$table->name;

            // Add field names to $sql
            $query .= ' ('. implode(",", $fieldNames) .') ';

            // Add values positions to $sql
            $query .= 'VALUES';

            $query .= ' ('.implode(",", $bindPositions) .')';

            // Prepare query
            $stmt = $this->mysqli->prepare($query);

            // Check prepared query
            if (!$stmt) {
                $message = 'MySQL error preparing query "'.$query.'"'
                    .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
                $code = EtlException::DATABASE_ERROR;
                throw new EtlException($message, $code);
            }

            $this->insertRowStatements[$table->name] = $stmt;
            $this->insertRowBindTypes[$table->name] = $bindTypes;
    
            return(array($stmt,$bindTypes));
        } // else
    }


    private function getBindType($field)
    {
        switch ($field->type) {
            case FieldType::CHAR:
            case FieldType::VARCHAR:
            case FieldType::STRING:
            case FieldType::DATE:
            case FieldType::DATETIME:
                $bindType = 's';
                break;

            case FieldType::FLOAT:
                $bindType = 'd';
                break;

            case FieldType::INT:
            default:
                $bindType = 'i';
                break;
        }

        return $bindType;
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
            $fields = $table->getAllFields();
            for ($i = 0; $i < count($fields); $i++) {
                $field = $fields[$i];
                if ($field->type === FieldType::AUTO_INCREMENT) {
                    unset($fields[$i]);
                }
            }

            $redcapFieldNames = array_column($fields, 'name');
            $redcapTypes = array_column($fields, 'redcapType');
            $dbFieldNames = array_column($fields, 'dbName');
            $fieldTypes = array_column($fields, 'type');

            # Escape the database field names
            for ($i = 0; $i < count($dbFieldNames); $i++) {
                $dbFieldNames[$i] = $this->escapeName($dbFieldNames[$i]);
            }

            $queryStart = 'INSERT INTO '.$this->escapeName($table->name).' ('. implode(",", $dbFieldNames) .') VALUES ';

            $queryValues = array();
            foreach ($rows as $row) {
                $rowData = $row->getData();
                $rowValues = array();
                for ($i = 0; $i < count($redcapFieldNames); $i++) {
                    $fieldName = $redcapFieldNames[$i];
                    $fieldType = $fieldTypes[$i];
                    $redcapType = $redcapTypes[$i];

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
                            $rowValues[] = "'".$this->mysqli->real_escape_string($rowData[$fieldName])."'";
                            break;
                        case FieldType::DATE:
                        case FieldType::DATETIME:
                            if (empty($rowData[$fieldName])) {
                                $rowValues[] = "null";
                            } else {
                                $rowValues[] = "'".$this->mysqli->real_escape_string($rowData[$fieldName])."'";
                            }
                            break;
                        default:
                            $message = 'Unrecognized database field type for MySQL: "'.$fieldType.'".';
                            $code = EtlException::DATABASE_ERROR;
                            throw new EtlException($message, $code);
                            break;
                    }
                }
                $queryValues[] = '('.implode(",", $rowValues).')';
            }
    
            $query = $queryStart.implode(",", $queryValues);
            # print "\n$query\n";
    
            $rc = $this->mysqli->query($query);
    
            #---------------------------------------------------
            # If there's an error executing the statement
            #---------------------------------------------------
            if ($rc === false) {
                $this->errorString = $this->mysqli->error;
                $result = false;
                $message = 'MySQL error while trying to insert values into table "'
                    .$this->escapeName($table->name).'": '
                    .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
                $code = EtlException::DATABASE_ERROR;
                throw new EtlException($message, $code);
            }
        }
    
        return($result);
    }
    
    public function processQueryFile($queryFile)
    {
        $queries = file_get_contents($queryFile);
        if ($queries === false) {
            $error = 'Could not access query file "'.$queryFile.'": '
                .error_get_last();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($error, $code);
        } else {
            $this->processQueries($queries);
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
            $error = "Query {$queryNumber} failed: {$mysqlError}.\n";
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($error, $code);
        } else {
            while ($this->mysqli->more_results()) {
                $result = $this->mysqli->next_result();
                if ($result === false) {
                    $mysqlError = $this->mysqli->error;
                    $error = "Query {$queryNumber} failed: {$mysqlError}.\n";
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
     * Escapes a name for use as a table or column name in a query.
     */
    private function escapeName($name)
    {
        $name = '`'.str_replace("`", "``", $name).'`';
        return $name;
    }
}

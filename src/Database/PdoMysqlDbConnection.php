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
 * There are 2 known problems with this class. Verification of the server's
 * SSL certificate does not work. And, while post-processing of SQL queries
 * appears to basically work, if there are errors in the post-processing SQL,
 * the queries (some or all of them) will fail without notice.
 */
class PdoMysqlDbConnection extends DbConnection
{
    private $db;

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
            #throw new Exception($message, $code);
            throw new EtlException($message, $code);
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

        #$this->mysqli = mysqli_init();
        #if ($sslVerify && !empty($caCertFile)) {
            #$this->mysqli->ssl_set(null, null, $caCertFile, null, null);
            #$this->mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        #}
        #$this->mysqli->real_connect($host, $username, $password, $database, $port, null, $flags);

        #if ($this->mysqli->connect_errno) {
        #    $message = 'MySQL error ['.$this->mysqli->connect_errno.']: '.$this->mysqli->connect_error;
        #    $code = EtlException::DATABASE_ERROR;
        #    throw new EtlException($message, $code);
        #}
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

                    $label = $this->db->quote(
                        $lookup->getLabel($table->name, $fname, $cat)
                    );
                    $select = 'CASE '.$this->escapeName($field->dbName).' WHEN 1 THEN '
                        . $label
                        . ' ELSE 0'
                        . ' END as '.$this->escapeName($field->dbName);
                } // The field uses the lookup table and is not a checkbox field
                else {
                    $select = 'CASE '.$this->escapeName($field->dbName);
                    $map = $lookup->getValueLabelMap($table->name, $fname);
                    foreach ($map as $value => $label) {
                        $select .= ' WHEN '.($this->db->quote($value))
                            .' THEN '.($this->db->quote($label));
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
        try {
            $result = $this->db->exec($query);
        } catch (\Exception $exception) {
            $message = 'MySQL error in query "'.$query.'": '.$exception->getMessage();
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
        ##print "\nQUERY: $query\n";
    
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
            # print "\n$query\n";
    
            try {
                $rc = $this->db->exec($query);
            } catch (\Exception $exception) {
                $message = 'MySQL error while trying to insert values into table "'
                    .$this->escapeName($table->name).'": '.$exception->getMessage();
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
                    $message = 'Unrecognized database field type for MySQL: "'.$fieldType.'".';
                    $code = EtlException::DATABASE_ERROR;
                    throw new EtlException($message, $code);
                    break;
            }
        }
        return $rowValues;
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
        try {
            $result = $this->db->query($queries);
        } catch (\Exception $exception) {
            $error = "Post-processing query failed: ".$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($error, $code);
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

<?php

namespace IU\REDCapETL\Database;

// DBConnection provides classes for interacting with a data store,
// such as a relational database or files.
//
// Classes:
//    DBConnect - Parent class. Storage-system specific classes
//                inherit from DBConnection. Should not be instantiated.
//    DBConnectMySQL - Interacts w/ MySQL database
//    DBConnectSQLSRV - Interacts w/ SQL Server database
//    DBConnectCSV - Interacts w/ CSV files
//    DBConnectFactory - Creates storage-specific objects.
//
//=========================================================================


//-------------------------------------------------------------------------
//
// DBConnectSQLSRV extends DBConnect and knows how to read/write to a
// SQL Server database.
//
//-------------------------------------------------------------------------
//

class DBConnectSQLSRV extends DBConnect
{

    private $pdo;

    private $insert_row_stmts;
    private $insert_row_bind_types;

    public function __construct($db_str, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($db_str, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->err_str = '';

        // Get parameters from db_str
        list($host,$database,$username,$password) = explode(':', $db_str, 4);
        $dsn = "odbc:Driver={ODBC Driver 13 for SQL Server};".
        "Server=$host;Database=$database";

        // Get PDO/ODBC connection
        $opt = array(
         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES   => false,
         );
        $this->pdo = new PDO($dsn, $username, $password, $opt);

        $this->insertRowStmts = array();

        return(1);
    }

    protected function existsTable($table)
    {

        // Note: existsTable currently assumes that a table always exists,
        //       as there is no practical problem with attempting to drop
        //       a non-existent table

        return(true);
    }

    protected function dropTable($table)
    {

        // Define query
        //$query = "DROP TABLE IF EXISTS ". $table->name;

        $query = "IF EXISTS ".
        "(SELECT * from INFORMATION_SCHEMA.TABLES ".
        "WHERE TABLE_NAME = '". $table->name .
        "' and TABLE_SCHEMA = 'dbo') ".
        "DROP TABLE dbo.". $table->name;

        // Execute query
        $this->pdo->query($query);  // NOTE: Add error checking?

        return(1);
    }

    protected function createTable($table)
    {

        // Start query
        $query = 'CREATE TABLE '.$table->name.' (';

        // foreach field
        $field_defs = array();
        foreach ($table->getAllFields() as $field) {
            // Begin field_def
            $field_def = $field->name.' ';

            // Add field type to field definition
            switch ($field->type) {
                case FieldType::DATE:
                    $field_def .= 'DATE';
                    break;

                case FieldType::INT:
                    $field_def .= 'INT';
                    break;

                case FieldType::FLOAT:
                    $field_def .= 'FLOAT';
                    break;
    
                case FieldType::STRING:
                default:
                      $field_def .= 'TEXT';
                    break;
            } // switch

            // Add field_def to array of field_defs
            array_push($field_defs, $field_def);
        }

        // Add field definitions to query
        $query .= join(', ', $field_defs);

        // End query
        $query .= ')';

        // Execute query
        $this->pdo->query($query); // NOTE: add error checking?

        return(1);
    }

    public function replaceLookupView($table, $lookup)
    {

        // ADA DEBUG -- Making this a stub for now
        return(1);

        $selects = array();

        // foreach field
        foreach ($table->getAllFields() as $field) {
            // If the field does not use lookup table
            if (false === $field->uses_lookup) {
                array_push($selects, 't.'.$field->name);
            } else {
                // $field->uses_lookup holds name of lookup field, if not false
                $fname = $field->uses_lookup;

                // If the field uses the lookup table and is a checkbox field
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->name)) {
                // For checkbox fields, the join needs to be done based on
                // the category embedded in the name of the checkbox field

                // Separate root from category
                    list($root_name,$cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->name);

                    $agg = "GROUP_CONCAT(if(l.field_name='".$fname."' ".
                         "and l.category=".$cat.", label, NULL)) ";

                    $select = 'CASE WHEN t.'.$field->name.' = 1 THEN '.$agg.
                        ' ELSE 0'.
                    ' END as '.$field->name;
                } // The field uses the lookup table and is not a checkbox field
                else {
                    $select = "GROUP_CONCAT(if(l.field_name='".$fname."' ".
                    "and l.category=t.".$field->name.", label, NULL)) ".
                    "as ".$field->name;
                }

                array_push($selects, $select);
            }
        }

        $query = 'CREATE OR REPLACE VIEW '.$table->name.$this->labelViewSuffix.' AS ';

        $select = 'SELECT '. implode(', ', $selects);
        $from = 'FROM '.$this->tablePrefix.RedCalEtl::LOOKUP_TABLE_NAME.' l, '.$table->name.' t';
        $where = "WHERE l.table_name like '".$table->name."'";
        $group_by = 'GROUP BY t.'.$table->primary->name;

        $query .= $select.' '.$from.' '.$where.' '.$group_by;

        // Execute query
        if (! $this->mysqli->query($query)) {
            // ADA DEBUG
            // Is this the best way to handle errors?
            error_log("sql: ".$query."\n", 0);
            error_log("sql errors for lookup view on table '".$table->name.
            "': ".$this->mysqli->error."\n", 0);
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

    protected function insertRow($row)
    {

        // How to prepare/bind/execute PDO https://phpdelusions.net/pdo#methods

        // How to handle unknow # of vars in bind_param. See answer by abd.agha
        // http://stackoverflow.com/questions/1913899/mysqli-binding-params-using-call-user-func-array

        // Get parameterized query
        //     If the query doesn't already exist, it will be created
        list($stmt,$bind_types) = $this->getInsertRowStmt($row->table);

        // Bind each parameter
        $fields = $row->table->getAllFields();
        for ($i=0; $i < count($fields); $i++) {
            $field = $fields[$i];

            // Replace empty string with null
            $value = $row->data[$field->name];
            $to_bind = ('' !== $value) ? $value : null;

            // Bind param
            if (false ===
            $stmt->bindValue(':'.strtolower($field->name), $to_bind, $bind_types[$field->name])
            ) {
              // ADA DEBUG
                print implode($this->pdo->errorInfo()."\n", 0);
            }
        }

        // Execute query
        // See examples from http://php.net/manual/en/pdostatement.execute.php
        $rc = $stmt->execute();   //NOTE add error checking?

        // If there's an error executing the statement
        if (false === $rc) {
            // ADA DEBUG
            // NOT SURE WHAT TO USE FOR AN ERROR STRING HERE
            $this->err_str = implode($this->pdo->errorInfo() . "\n", 0);
            return(false);
        }

        // Close the cursor
        $stmt->closeCursor();
    
        return(1);
    }

    private function getInsertRowStmt($table)
    {

        // How to prepare/bind/execute PDO https://phpdelusions.net/pdo#methods

        // If we've already created this query, return it
        if (isset($this->insert_row_stmts[$table->name])) {
            return(array($this->insert_row_stmts[$table->name],
               $this->insert_row_bind_types[$table->name]));
        } // Otherwise, create and save the query and its associated bind types
        else {
            // Create field_names, positions array, and params arrays
            $field_names = array();
            $bind_positions = array();
            $bind_types = array();
            foreach ($table->getAllFields() as $field) {
                array_push($field_names, $field->name);

                array_push($bind_positions, ':'.strtolower($field->name));

                switch ($field->type) {
                    case FieldType::INT:
                        $bind_types[$field->name] = PDO::PARAM_INT;
                        break;

                    case FieldType::STRING:
                    case FieldType::DATE:
                    case FieldType::FLOAT:
                    default:
                          $bind_types[$field->name] = PDO::PARAM_STR;
                        break;
                }
            }

            // Start $sql
            $query = 'INSERT INTO '.$table->name;

            // Add field names to $sql
            $query .= ' ('. implode(",", $field_names) .') ';

            // Add values positions to $sql
            $query .= 'VALUES (' . implode(",", $bind_positions) .')';
      
            // Prepare query
            $stmt = $this->pdo->prepare($query);  //NOTE add error checking?

            // Check that stmt was created
            if (!$stmt) {
                 error_log("query :".$query."\n", 0);
                 error_log("Statement failed: ".
                 implode("\n", $this->pdo->errorInfo() . "\n", 0));
            }

            $this->insert_row_stmts[$table->name] = $stmt;
            $this->insert_row_bind_types[$table->name] = $bind_types;
    
            return(array($stmt,$bind_types));
        } // else
    } // get_insert_row_stmt
}

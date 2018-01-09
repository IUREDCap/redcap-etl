<?php

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;

/**
 * For Sqlite - UNFINISHED
 */
class DBConnectSqlite extends DBConnect
{
    private $mysqli;

    private $insert_row_stmts;
    private $insert_row_bind_types;

    public function __construct($db_str, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($db_str, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->err_str = '';

        // Get parameters from db_str
        list($host,$username,$password,$database) = explode(':', $db_str);
        ###list($host,$database,$username,$password) = explode(':',$db_str,4);

        // Get MySQL connection
        // NOTE: Could add error checking
        // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // Setting the above causes the program to stop for any uncaugt errors
        $this->mysqli = new \mysqli($host, $username, $password, $database);

        $this->insert_row_stmts = array();
        $this->insert_row_bind_types = array();

        return(1);
    }

    protected function existsTable($table)
    {

        // Note: exists_table currently assumes that a table always exists,
        //       as there is no practical problem with attempting to drop
        //       a non-existent table

        return(true);
    }


    protected function dropTable($table)
    {
        // Define query
        $query = "DROP TABLE IF EXISTS ". $table->name;

        // Execute query
        $this->mysqli->query($query); // NOTE: add error checking?

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
        $this->mysqli->query($query); // NOTE: add error checking?

        return(1);
    }

    public function replaceLookupView($table, $lookup)
    {

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
                    list($root_name, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->name);

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

        $query = 'CREATE OR REPLACE VIEW '.$table->name.$labelViewSiffux.' AS ';

        $select = 'SELECT '. implode(', ', $selects);
        $from = 'FROM '.$this->tablePrefix.RedCapEtl::LOOKUP_TABLE_NAME.' l, '.$table->name.' t';
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

        // How to handle unknow # of vars in bind_param. See answer by abd.agha
        // http://stackoverflow.com/questions/1913899/mysqli-binding-params-using-call-user-func-array

        // Get parameterized query
        //     If the query doesn't already exist, it will be created
        list($stmt,$bind_types) = $this->getInsertRowStmt($row->table);

        // Start bind parameters list with bind_types and escaped table name
        $params = array($bind_types);

        // Add escaped field values, in order, to bind parameters
        foreach ($row->table->getAllFields() as $field) {
            // Replace empty string with null
            $escaped = $this->mysqli->real_escape_string($row->data[$field->name]);
            $to_bind = ('' !== $escaped) ? $escaped : null;

            array_push($params, $to_bind);
        }

        // Get references to each parameter -- necessary because
        // call_user_func_array wants references
        $param_refs = array();
        foreach ($params as $key => $value) {
            $param_refs[$key] = &$params[$key];
        }

        // Bind references to prepared query
        call_user_func_array(array($stmt, 'bind_param'), $param_refs);
    
        // Execute query
        $rc = $stmt->execute();   //NOTE add error checking?

        // If there's an error executing the statement
        if (false === $rc) {
            $this->err_str = $stmt->error;
            return(false);
        }
    
        return(1);
    }

    private function getInsertRowStmt($table)
    {

        // If we've already created this query, return it
        if (isset($this->insertRowStmts[$table->name])) {
            return(array($this->insertRowStmts[$table->name],
               $this->insertRowBindTypes[$table->name]));
        } // Otherwise, create and save the query and its associated bind types
        else {
            // Create field_names, positions array, and params arrays
            $field_names = array();
            $bind_positions = array();
            $bind_types = '';
            foreach ($table->getAllFields() as $field) {
                array_push($field_names, $field->name);
                array_push($bind_positions, '?');

                switch ($field->type) {
                    case FieldType::STRING:
                    case FieldType::DATE:
                        $bind_types .= 's';
                        break;

                    case FieldType::FLOAT:
                          $bind_types .= 'd';
                        break;

                    case FieldType::INT:
                    default:
                          $bind_types .= 'i';
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
            $stmt = $this->mysqli->prepare($query);  //NOTE add error checking?

            // ADA DEBUG
            if (!$stmt) {
                 error_log("query :".$query."\n", 0);
                 error_log("Statement failed: ". $this->mysqli->error . "\n", 0);
            }

            $this->insert_row_stmts[$table->name] = $stmt;
            $this->insert_row_bind_types[$table->name] = $bind_types;
    
            return(array($stmt,$bind_types));
        } // else
    }
}

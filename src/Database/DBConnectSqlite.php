<?php

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;

/**
 * For Sqlite - UNFINISHED
 */
class DBConnectSqlite extends DBConnect
{
    private $mysqli;

    private $insertRowStatements;
    private $insertRowBindTypes;

    public function __construct($dbString, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->errorString = '';

        // Get parameters from dbString
        list($host,$username,$password,$database) = explode(':', $dbString);
        ###list($host,$database,$username,$password) = explode(':',$dbString,4);

        // Get MySQL connection
        // NOTE: Could add error checking
        // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // Setting the above causes the program to stop for any uncaugt errors
        $this->mysqli = new \mysqli($host, $username, $password, $database);

        $this->insertRowStatements = array();
        $this->insertRowBindTypes = array();

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
        $fieldDefs = array();
        foreach ($table->getAllFields() as $field) {
            // Begin fieldDef
            $fieldDef = $field->name.' ';

            // Add field type to field definition
            switch ($field->type) {
                case FieldType::DATE:
                    $fieldDef .= 'DATE';
                    break;

                case FieldType::INT:
                    $fieldDef .= 'INT';
                    break;

                case FieldType::FLOAT:
                    $fieldDef .= 'FLOAT';
                    break;
    
                case FieldType::STRING:
                default:
                      $fieldDef .= 'TEXT';
                    break;
            } // switch

            // Add fieldDef to array of fieldDefs
            array_push($fieldDefs, $fieldDef);
        }

        // Add field definitions to query
        $query .= join(', ', $fieldDefs);

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
            if (false === $field->usesLookup) {
                array_push($selects, 't.'.$field->name);
            } else {
                // $field->usesLookup holds name of lookup field, if not false
                $fname = $field->usesLookup;

                // If the field uses the lookup table and is a checkbox field
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->name)) {
                // For checkbox fields, the join needs to be done based on
                // the category embedded in the name of the checkbox field

                // Separate root from category
                    list($rootName, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->name);

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
        $groupBy = 'GROUP BY t.'.$table->primary->name;

        $query .= $select.' '.$from.' '.$where.' '.$groupBy;

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
        list($stmt,$bindTypes) = $this->getInsertRowStmt($row->table);

        // Start bind parameters list with bind_types and escaped table name
        $params = array($bindTypes);

        // Add escaped field values, in order, to bind parameters
        foreach ($row->table->getAllFields() as $field) {
            // Replace empty string with null
            $escaped = $this->mysqli->real_escape_string($row->data[$field->name]);
            $toBind = ('' !== $escaped) ? $escaped : null;

            array_push($params, $toBind);
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
        $rc = $stmt->execute();   //NOTE add error checking?

        // If there's an error executing the statement
        if (false === $rc) {
            $this->errorString = $stmt->error;
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
            $fieldNames = array();
            $bindPositions = array();
            $bindTypes = '';
            foreach ($table->getAllFields() as $field) {
                array_push($fieldNames, $field->name);
                array_push($bindPositions, '?');

                switch ($field->type) {
                    case FieldType::STRING:
                    case FieldType::DATE:
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
            $query .= 'VALUES (' . implode(",", $bindPositions) .')';
      
            // Prepare query
            $stmt = $this->mysqli->prepare($query);  //NOTE add error checking?

            // ADA DEBUG
            if (!$stmt) {
                 error_log("query :".$query."\n", 0);
                 error_log("Statement failed: ". $this->mysqli->error . "\n", 0);
            }

            $this->insertRowStatements[$table->name] = $stmt;
            $this->insertRowBindTypes[$table->name] = $bindTypes;
    
            return(array($stmt,$bindTypes));
        } // else
    }
}

<?php

namespace IU\REDCapETL\Schema;

use IU\REDCapETL\RedCapEtl;

/**
 * Schema is used for holding information about dynamically created
 * tables/fields, as well as rows of data assigned to those tables.
 *
 * These tables and rows know about a datastore (such as a relational
 * database or flat file) where their data can be persisted
 *
 */
class Schema
{

    private $tables = array();
    private $root_tables = array();

    public function __construct()
    {
        return true;
    }

    public function addTable($table)
    {

        // Add table to list of all tables
        array_push($this->tables, $table);

        // If it is a root table, add to list of root tables
        if (RedCapEtl::ROOT == $table->rows_type) {
            array_push($this->root_tables, $table);
        }
    }

    /**
     * Get all the tables in the schema.
     *
     * @return array an array of Table objects for all the
     *    tables in the schema
     */
    public function getTables()
    {
        return($this->tables);
    }

    public function getRootTables()
    {
        return($this->root_tables);
    }

    public function getTable($table_name)
    {
        foreach ($this->tables as $table) {
            if (0 == strcmp($table->name, $table_name)) {
                return($table);
            }
        }

        // If the table is not found, return the table_name
        return($table_name);
    }


    public function toString($indent = 0)
    {
        $in = str_repeat(' ', $indent);
        $string = '';
        $string .= "${in}Number of tables: ".count($this->tables)."\n";
        $string .= "${in}Number of root tables: ".count($this->root_tables)."\n";
        $string .= "\n${in}Root tables\n";
        foreach ($this->root_tables as $table) {
            $string .= $table->toString($indent + 4)."\n";
        }
        $string .= "\nTables\n";
        foreach ($this->tables as $table) {
            $string .= $table->toString($indent + 4)."\n";
        }

        return $string;
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

/**
 * A Schema object is used for holding information about the tables where
 * extracted and transformated data are stored that
 * are described in transformation rules for an ETL configuration.
 *
 * Schema objects also contain information about several system generated
 * tables, including: logging tables, a lookup table that maps multiple
 * choice values to labels, and REDCap project info and metadata tables.
 */
class Schema
{
    /** @var array array of Table objects for all data tables in the schema, including root tables,
     *    but excluding system generated tables, such as the lookup table */
    private $tables;

    /** @var array array of table objects for only the root tables in the schema */
    private $rootTables;
    
    /** @var LookupTable table for mapping multiple choice fields from (table name, field name, value) to label */
    private $lookupTable;

    /** @var array map from table name (string) to LookupTable object
     *    for storing lookup tabes for merged schema. */
    private $lookupTableMap;
    
    /** @var EtlLogTable parent log table that has one entry per ETL task; this table is NOT deleted after each run */
    private $dbLogTable;

    /** @var array map from table name (string) to EtlLogTable that contains
     *     all DB log tables for a merged schema */
    private $dbLogTableMap;

    /** @var EtlEventLogTable child log table that has one entry per ETL task event;
     *    this table is NOT deleted after each run */
    private $dbEventLogTable;

    /** @var array map from table name (string) to EtlEventLogTable that contains
     *    all DB event log tables for a merged schema */
    private $dbEventLogTableMap;

    /** @var ProjectInfoTable table with REDCap project information */
    private $projectInfoTable;

    /** @var MetadataTable table with REDCap metadata information */
    private $metadataTable;


    public function __construct()
    {
        $this->tables     = array();
        $this->rootTables = array();

        $this->lookupTable = null;

        $this->lookupTableMap = array();
 
        $this->dbLogTable      = null;
        $this->dbEventLogTable = null;

        $this->dbLogTableMap      = array();
        $this->dbEventLogTableMap = array();

        $this->projectInfoTable = null;
        $this->metadataTable = null;
    }

    /**
     * Merges the specified schema into this one.
     *
     * @param Schema $schema the schema to merge.
     * @param string $dbId the database ID For the database for which the schemas are being merged.
     *     This is needed only for informational purposes in error message, and not to merge the schemas.
     * @param string $taskName the name of the task for the merged schema.
     *     This is needed only for informational purposes in error message, and not to merge the schemas.
     */
    public function merge($schema, $dbId = null, $taskName = null)
    {
        $mergedSchema = new Schema();

        $mergedRootTables = array();

        # Create a table map that combines both schemas' tables that maps
        # from table name to a 2-element array, where the first element
        # is the table for this object, and the second for the $schema
        # argument.
        $tableMap = array();
        foreach ($this->tables as $table) {
            $tableMap[$table->getName()] = [$table, null];
        }

        foreach ($schema->tables as $table) {
            if (array_key_exists($table->getName(), $tableMap)) {
                $tables = $tableMap[$table->getName()];
                $tables[1] = $table;
                $tableMap[$table->getName()] = $tables;
            } else {
                $tableMap[$table->getName()] = [null, $table];
            }
        }

        $mergedTables = array();
        foreach ($tableMap as $name => $tables) {
            if (empty($tables[0])) {
                # table not in $this, table in $schema
                $mergedTables[] = $tables[1];
                if (in_array($tables[1], $schema->getRootTables())) {
                    $mergedRootTables[] = $tables[1];
                }
            } elseif (empty($fields[1])) {
                # table in $this, not in $schema
                $mergedTables[] = $tables[0];
                if (in_array($tables[0], $this->getRootTables())) {
                    $mergedRootTables[] = $tables[0];
                }
            } else {
                # table in both $this and $schema
                $mergedTable = ($tables[0])->merge($tables[1]);
                $mergedTables[] = $mergedTable;
                if (in_array($tables[0], $this->getRootTables())) {
                    $mergedRootTables[] = $mergedTable;
                }
            }
        }

        $mergedSchema->tables     = $mergedTables;
        $mergedSchema->rootTables = $mergedRootTables;

        #------------------------------------------------------------------------
        # Merge the lookup tables that provide a map for mutliple choice fields
        # from (table name, field name, value) to label
        #------------------------------------------------------------------------
        #$dataRows = $this->lookupTable->mergeDataRows($schema->lookupTable);

        #--------------------------------------
        # Merge lookup table(s)
        #--------------------------------------
        #$mergedSchema->setLookupTable($this->lookupTable);
        #$lookupName = $schema->lookupTable->getName();
        #if (array_key_exists($lookupName, $mergedSchema->lookupTableMap)) {
        #    # If the new lookup table already exists in the lookup table map, then
        #    # merge the new lookup table with the schemae.
        #    $mergedSchema->lookupTableMap[$lookupName] = $this->lookupTable->merge($schema->lookupTable);
        #} else {
        #    # Else, this is a new lookup table.
        #    $this->lookupTableMap[$lookupName] = $schema->lookupTable;
        #}

        $mergedSchema->lookupTable = $this->lookupTable->merge($schema->lookupTable);

        #------------------------------------------------------------------
        # Merge the REDCap project info and metadata tables
        #------------------------------------------------------------------
        $mergedSchema->projectInfoTable = $this->projectInfoTable->merge($schema->projectInfoTable);
        $mergedSchema->metadataTable    = $this->metadataTable->merge($schema->metadataTable);

        #-----------------------------------------------
        # Merge the logging tables
        #-----------------------------------------------
        $mergedSchema->dbLogTableMap      = array_merge($this->dbLogTableMap, $schema->dbLogTableMap);
        $mergedSchema->dbEventLogTableMap = array_merge($this->dbEventLogTableMap, $schema->dbEventLogTableMap);

        return $mergedSchema;
    }


    /**
     * Adds the specified table to the schema.
     *
     * @param Table $table the table to add to the schema.
     */
    public function addTable($table)
    {
        # Add table to list of all data tables
        array_push($this->tables, $table);

        # If it is a root table, add to list of root tables
        if (in_array(RowsType::ROOT, $table->rowsType)) {
            array_push($this->rootTables, $table);
        }
    }

    /**
     * Get the tables in top-down, depth-first order, i.e., parents before children
     *
     * @return array list of Table objects for the tables in the schema.
     */
    public function getTablesTopDown()
    {
        $tables = array();
        foreach ($this->rootTables as $table) {
            array_push($tables, $table);
            $descendants = $table->getDescendantsDepthFirst();
            $tables = array_merge($tables, $descendants);
        }
        return $tables;
    }

    /**
     * Returns tables always listing children before parents. This is useful
     * for table deletion so that foreign key constraints won't be violated.
     */
    public function getTablesBottomUp()
    {
        $tables = $this->getTablesTopDown();
        return array_reverse($tables);
    }


    /**
     * Gets all the tables in the schema.
     *
     * @return array an array of Table objects for all the
     *    tables in the schema.
     */
    public function getTables()
    {
        return($this->tables);
    }


    /**
     * Gets the names of all the data tables in the Schema
     */
    public function getTableNames()
    {
        $tableNames = array();
        if (isset($this->tables)) {
            foreach ($this->tables as $table) {
                $tableNames[] = $table->getName();
            }
        }
        return $this->tableNames;
    }

    /**
     * Gets all the root (i.e., non-child) tables in the schema.
     *
     * @return array an array of Table objects for all the
     *     root tables in the schema.
     */
    public function getRootTables()
    {
        return($this->rootTables);
    }

    /**
     * Gets the table with the specified table name.
     *
     * @param string $tableName the name of the table to return.
     * @return mixed if the table is found, the Table object for it is
     *     returned, otherwise the table name that was specified is
     *     returned.
     */
    public function getTable($tableName)
    {
        foreach ($this->tables as $table) {
            if (0 == strcmp($table->name, $tableName)) {
                return($table);
            }
        }

        // If the table is not found, return the tableName
        return($tableName);
    }


    /**
     * Gets the lookup table.
     *
     * @return LookupTable table that maps multiple choice coded values to labels.
     */
    public function getLookupTable()
    {
        return $this->lookupTable;
    }

    /**
     * Sets the lookup table for the database.
     *
     * @param Table $lookupTable the lookup table for the database.
     */
    public function setLookupTable($lookupTable)
    {
        $this->lookupTableMap[$lookupTable->getName()] = $lookupTable;
        if (count($this->lookupTableMap) === 1) {
            # If this is the first lookup table, make the primary lookup table
            # point to it
            $this->lookupTable = & $this->lookupTableMap[$lookupTable->getName()];
        }
    }


    public function getDbLogTable()
    {
        return $this->dbLogTable;
    }

    public function setDbLogTable($dbLogTable)
    {
        $this->dbLogTableMap[$dbLogTable->getName()] = $dbLogTable;
        $this->dbLogTable = $dbLogTable;
    }


    public function getDbEventLogTable()
    {
        return $this->dbEventLogTable;
    }

    public function setDbEventLogTable($dbEventLogTable)
    {
        $this->dbEventLogTableMap[$dbEventLogTable->getName()] = $dbEventLogTable;
        $this->dbEventLogTable = $dbEventLogTable;
    }

    public function getProjectInfoTable()
    {
        return $this->projectInfoTable;
    }

    public function setProjectInfoTable($projectInfoTable)
    {
        $this->projectInfoTable = $projectInfoTable;
    }

    public function getMetadataTable()
    {
        return $this->metadataTable;
    }

    public function setMetadataTable($metadataTable)
    {
        $this->metadataTable = $metadataTable;
    }

    /**
     * Returns a string representation of the schema.
     *
     * @return string a human-readable string representation of the
     *     schema.
     */
    public function toString($indent = 0)
    {
        $in = str_repeat(' ', $indent);
        $string = '';
        $string .= "${in}Number of tables: ".count($this->tables)."\n";
        $string .= "${in}Number of root tables: ".count($this->rootTables)."\n";

        $string .= "\nLookup Table\n";
        if (isset($this->lookupTable)) {
            $string .= $this->lookupTable->toString($indent + 4)."\n";
        }

        $string .= "\n";
        $string .= "\n${in}Root tables\n";
        foreach ($this->rootTables as $table) {
            $string .= $table->toString($indent + 4)."\n";
        }
        $string .= "\nTables\n";
        foreach ($this->tables as $table) {
            $string .= $table->toString($indent + 4)."\n";
        }

        return $string;
    }
}

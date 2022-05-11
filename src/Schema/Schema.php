<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use IU\REDCapETL\EtlException;

/**
 * A Schema object is used for holding information about the tables where
 * extracted and transformated data are loaded that
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
    
    /** @var string suffix string that is appended to the end of the label view names.
     *    These views are created for tables that have multiple choice values,
     *    and have the labels for the multiple choice fields instead of the values. */
    private $labelViewSuffix;

    /** @var string filter logic applied to data extracted from REDCap */
    private $extractFilterLogic;

    public function __construct()
    {
        $this->tables     = array();
        $this->rootTables = array();

        $this->lookupTable = null;

        $this->dbLogTable      = null;
        $this->dbEventLogTable = null;

        $this->dbLogTableMap      = array();
        $this->dbEventLogTableMap = array();

        $this->projectInfoTable = null;
        $this->metadataTable = null;

        $this->extractFilterLogic = null;
    }

    /**
     * Merges the specified schema into this one.
     *
     * @param Schema $schema the schema to merge.
     * @param string $task the task of the schema that is being merged (if any) - used to get info for logging.
     */
    public function merge($schema, $task = null)
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
            } elseif (empty($tables[1])) {
                # table in $this, not in $schema
                $mergedTables[] = $tables[0];
                if (in_array($tables[0], $this->getRootTables())) {
                    $mergedRootTables[] = $tables[0];
                }
            } else {
                # table in both $this and $schema
                $mergeData = false;
                $mergedTable = ($tables[0])->merge($tables[1], $mergeData, $task);
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
        $mergeData = false;
        $mergedSchema->lookupTable = $this->lookupTable->merge($schema->lookupTable, $mergeData, $task);

        #------------------------------------------------------------------
        # Merge the REDCap project info and metadata tables
        #------------------------------------------------------------------
        $mergeData = true;
        $mergedSchema->projectInfoTable =
            $this->projectInfoTable->merge($schema->projectInfoTable, $mergeData, $task);
        $mergedSchema->metadataTable =
            $this->metadataTable->merge($schema->metadataTable, $mergeData, $task);

        #-----------------------------------------------
        # Merge the logging tables
        #-----------------------------------------------
        $mergedSchema->dbLogTableMap      = array_merge($this->dbLogTableMap, $schema->dbLogTableMap);
        $mergedSchema->dbEventLogTableMap = array_merge($this->dbEventLogTableMap, $schema->dbEventLogTableMap);

        #----------------------------------------------
        # Merge the label view suffix
        #----------------------------------------------
        if ($this->labelViewSuffix !== $schema->labelViewSuffix) {
            $message = 'Cannot merge database schemas; label view suffixes in task configurations are different: "'
                .$this->getlabelViewSuffix().'"'.' and "'.$schema->getLabelViewSuffix().'".';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        } else {
            $mergedSchema->labelViewSuffix = $this->labelViewSuffix;
        }
        
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
            if (0 == strcmp($table->getName(), $tableName)) {
                return($table);
            }
        }

        // If the table is not found, return the tableName
        return($tableName);
    }

    public function hasTable($tableName)
    {
        $hasTable = false;
        foreach ($this->tables as $table) {
            if (strcmp($table->getName(), $tableName) === 0) {
                $hasTable = true;
                break;
            }
        }
        return $hasTable;
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
        $this->lookupTable = $lookupTable;
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

    public function getExtractFilterLogic()
    {
        return $this->extractFilterLogic;
    }

    public function setExtractFilterLogic($extractFilterLogic)
    {
        $this->extractFilterLogic = $extractFilterLogic;
    }

    public function getDataTableNames()
    {
        $tableNames = array();

        foreach ($this->tables as $table) {
            $tableNames[] = $table->getName();
        }
        sort($tableNames);
        return $tableNames();
    }

    public function getNonLoggingSystemTableNames()
    {
        $tableNames = array();
        $tableNaems[] = $this->projectInfoTable->getName();
        $tableNames[] = $this->metadataTable->getName();

        if (!empty($this->lookupTable)) {
            $tableNames[] = $this->lookupTable->getName();
        }
        return $tableNames;
    }
    
    public function getLabelViewSuffix()
    {
        return $this->labelViewSuffix;
    }
    
    public function setLabelViewSuffix($labelViewSuffix)
    {
        $this->labelViewSuffix = $labelViewSuffix;
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

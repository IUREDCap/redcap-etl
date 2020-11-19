<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCap;
use IU\RedCapEtl\Database\CsvDbConnection;
use IU\REDCapETL\Database\DbConnectionFactory;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Schema;
use IU\REDCapETL\Schema\Table;

/**
 * REDCap-ETL workflow that contains 1 or more ETL tasks.
 */
class Workflow
{
    /** @var array array of task objects that represent the tasks of the workflow. */
    private $tasks;

    /** @var array map from database ID (string) to array of task objects that use that database. */
    private $dbTasks;

    /** @var array map from database ID to database schema, which merges the schemas for all
     *             the tasks that load data to the database.
     */
    private $dbSchemas;
    
    /** @var array map from database ID to database connection. */
    private $dbConnections;
    
    private $logger;

    /**
     * Constructor.
     *
     */
    public function __construct($workflowConfig, $logger, $redcapProjectClass = null)
    {
        $this->dbSchemas     = array();
        $this->dbConnections = array();
        $this->dbTasks       = array();
        
        $this->tasks = array();
        
        $this->logger = $logger;
        
        #---------------------------------------------
        # Create tasks and database information
        #---------------------------------------------
        $taskId = 1;
        foreach ($workflowConfig->getTaskConfigs() as $taskConfigName => $taskConfig) {
            # Create task for this task configuration
            $task = new Task($taskId);
            $task->initialize($this->logger, $taskConfigName, $taskConfig, $redcapProjectClass);
        
            $this->tasks[] = $task;

            # Get string that serves as database identifier
            $dbId = $task->getDbId();

            # Create a merged schema and get connection information for the current database,
            # unless the task is an SQL-only task
            if ($task->isSqlOnlyTask()) {
                ; // SQL only - it has no schema to merge
            } elseif (array_key_exists($dbId, $this->dbSchemas)) {
                $this->dbSchemas[$dbId] = $this->dbSchemas[$dbId]->merge(
                    $task->getSchema(),
                    $dbId,
                    $task->getName()
                );
                $this->dbTasks[$dbId]   = array_merge($this->dbTasks[$dbId], [$task]);
            } else {
                $this->dbSchemas[$dbId]     = $task->getSchema();
                $this->dbConnections[$dbId] = $task->getDbConnection();
                $this->dbTasks[$dbId]       = [$task];
            }
            
            $taskId++;
        }
        
        #---------------------------------------------
        # Set the DB (merged) schemas for the tasks
        #---------------------------------------------
        foreach ($this->tasks as $task) {
            $dbId = $task->getDbId();
            if (array_key_exists($dbId, $this->dbSchemas)) {
                # There wil be no schema for a database if it has only SQL-only tasks
                $dbSchema = $this->dbSchemas[$dbId];
                $task->setDbSchema($dbSchema);    // FIX!!!!,
            }
            // only want subset of merged schema that has
            // original tables in task OR REMOVE??????????? !!!!!!!!!!!!
        }
    }

    public function dropAllLoadTables()
    {
        foreach ($this->dbSchemas as $dbId => $schema) {
            $dbConnection = $this->dbConnections[$dbId];
            $this->dropLoadTables($dbConnection, $schema);
        }
    }


    public function createAllLoadTables()
    {
        # For each of the load databases, create the load tables
        $i = 1;
        foreach ($this->dbSchemas as $dbId => $schema) {
            $dbConnection = $this->dbConnections[$dbId];
            $this->createLoadTables($dbConnection, $schema);
            $dbTasks = $this->dbTasks[$dbId];
            
            # reset task DB connections after the Lookup table information has been
            # added from code above
            foreach ($dbTasks as $task) {
                $task->setDbConnection($dbConnection);
            }
            
            #--------------------------------------------------------
            # Create Project Info and Metadata tables for the
            # current database
            #--------------------------------------------------------
            $projectInfoTable = $schema->getProjectInfoTable();
            $metadataTable    = $schema->getMetadataTable();

            $dbConnection->replaceTable($projectInfoTable);
            $dbConnection->replaceTable($metadataTable);
            
            $dbConnection->storeRows($projectInfoTable);
            $dbConnection->storeRows($metadataTable);
        }
    }
        
    /**
     * Drop all the tables in the specified schema in the specified database.
     *
     * @parameter DbConnection $dbConnection the database connection to use.
     * @parameter Schema $schema the schema from which to drop the tables.
     */
    public function dropLoadTables($dbConnection, $schema)
    {
        #--------------------------------------------------
        # Drop the project info and metadata tables
        #--------------------------------------------------
        $projectInfoTable = $schema->getProjectInfoTable();
        $metadataTable    = $schema->getMetadataTable();
        $dbConnection->dropTable($projectInfoTable, $ifExists = true);
        $dbConnection->dropTable($metadataTable, $ifExists = true);
        
        #-------------------------------------------------------------
        # Get the tables in top-down order, so that each parent table
        # will always come before its child tables
        #-------------------------------------------------------------
        $tables = $schema->getTablesTopDown();

        #---------------------------------------------------------------------
        # Drop tables in the reverse order (bottom-up), so that child tables
        # will always be dropped before their parent table. And drop
        # the label view (if any) for a table before dropping the table
        #---------------------------------------------------------------------
        foreach (array_reverse($tables) as $table) {
            if ($table->usesLookup === true) {
                $ifExists = true;
                $dbConnection->dropLabelView($table, $ifExists);
            }

            $ifExists = true;
            $dbConnection->dropTable($table, $ifExists);
        }
    }



    public function createLoadTables(& $dbConnection, $schema)
    {
        #-------------------------------------------------------------
        # Get the tables in top-down order, so that each parent table
        # will always come before its child tables
        #-------------------------------------------------------------
        $tables = $schema->getTablesTopDown();

        #------------------------------------------------------
        # Create the tables in the order they were defined
        #------------------------------------------------------
        foreach ($tables as $table) {
            $ifNotExists = true;   // same table could be created by 2 different task configurations
            $dbConnection->createTable($table, $ifNotExists);

            $msg = "Created table '".$table->name."'";

            #--------------------------------------------------------------------------
            # If this table uses the Lookup table (i.e., has multiple-choice values),
            # Create a view of the table that has multiple-choice labels instead of
            # multiple-choice values.
            #--------------------------------------------------------------------------
            if ($table->usesLookup === true) {
                $dbConnection->replaceLookupView($table, $schema->getLookupTable());
                $msg .= '; Lookup table created';
            }

            $this->logger->log($msg);
        }

        #-----------------------------------------------------------------------------------
        # If configured, create the lookup table that maps multiple choice values to labels
        #-----------------------------------------------------------------------------------
        # FIX!!!!!!! - still need to make sure that lookup tables are generated for
        # all configurations if any have it set???
        $lookupTable = $schema->getLookupTable();
        if (isset($lookupTable)) {
            $dbConnection->replaceTable($lookupTable);
            $this->loadTableRows($dbConnection, $lookupTable);
        }
    }
    
    protected function loadTableRows($dbConnection, $table, $deleteRowsAfterLoad = true)
    {
        foreach ($table->getRows() as $row) {
            $rc = $dbConnection->storeRow($row);
            if (false === $rc) {
                $this->log("Error storing row in '".$table->name."': ".$this->dbcon->errorString);
            }
        }

        if ($deleteRowsAfterLoad) {
            // Empty the rows for this table
            $table->emptyRows();
        }
    }
        

    /**
     * Indicates if the specified database needs to have primary keys generated for its tables.
     */
    public function needsDatabasePrimaryKeys($dbId)
    {
        $needsPrimaryKeys = false;

        if (!empty($dbId) && array_key_exists($dbId, $this->dbTasks)) {
            foreach ($this->dbTasks[$dbId] as $task) {
                if ($task->getTaskConfig()->getDbPrimaryKeys()) {
                    $needsPrimaryKeys = true;
                }
            }
        }

        return $needsPrimaryKeys;
    }

    /**
     * Indicates if the specified database needs to have foreign keys generated for its tables.
     */
    public function needsDatabaseForeignKeys($dbId)
    {
        $needsForeignKeys = false;

        if (!empty($dbId) && array_key_exists($dbId, $this->dbTasks) && array_key_exists($dbId, $this->dbSchemas)) {
            foreach ($this->dbTasks[$dbId] as $task) {
                if ($task->getTaskConfig()->getDbForeignKeys()) {
                    $needsForeignKeys = true;
                }
            }
        }

        return $needsForeignKeys;
    }

    /**
    /**
     * Creates primary and foreign keys for the database tables if they
     * have been specified (note: unsupported for CSV and SQLite).
     */
    public function createDatabaseKeys($dbId)
    {
        #---------------------------------------------------------------------------------
        # If database keys are needed then generate the keys.
        #
        # Note: foreign keys can only be configured if primary keys are alsoconfigured.
        #---------------------------------------------------------------------------------
        if ($this->needsDatabasePrimaryKeys($dbId)) {
            $schema = $this->dbSchemas[$dbId];

            #-------------------------------------------------------------
            # Get the tables in top-down order, so that each parent table
            # will always come before its child tables
            #-------------------------------------------------------------
            $tables = $schema->getTablesTopDown();

            $dbConnection = $this->getDbConnection($dbId);

            #------------------------------------------------------
            # Create tables
            #------------------------------------------------------
            foreach ($tables as $table) {
                $dbConnection->addPrimaryKeyConstraint($table);
            }

            if ($this->needsDatabaseForeignKeys($dbId)) {
                foreach ($tables as $table) {
                    $dbConnection->addForeignKeyConstraint($table);
                }
            }
        }
    }


    public function getDbIds()
    {
        $dbIds = array_keys($this->dbSchemas);
        return $dbIds;
    }

    public function getDbSchemas()
    {
        return $this->dbSchemas;
    }
    
    public function getDbSchema($dbId)
    {
        $schema = null;
        if (array_key_exists($dbId, $this->dbSchemas)) {
            $schema = $this->dbSchemas[$dbId];
        }
        return $schema;
    }
    
    public function getDbConnection($dbId)
    {
        $dbConnection = null;
        if (array_key_exists($dbId, $this->dbConnections)) {
            $dbConnection = $this->dbConnections[$dbId];
        }
        return $dbConnection;
    }
    
    public function getTask($index)
    {
        $task = $this->tasks[$index];
        return $task;
    }
    
    public function getTasks()
    {
        return $this->tasks;
    }
    
    public function getTaskConfig($index)
    {
        $task = $this->tasks[$index];
        return $task->getTaskConfig();
    }
    
    public function getDataProject($index)
    {
        $task = $this->tasks[$index];
        return $task->getDataProject();
    }
}
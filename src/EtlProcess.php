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
 * REDCap-ETL task representing a single configuration that contains a single data source (REDCap project)
 * and destination (database).
 */
class EtlProcess
{
    /*@var array array of task objects that represent the tasks of the workflow. */
    private $tasks;
    
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
    public function __construct($workflow, $logger, $redcapProjectClass)
    {
        $this->dbSchemas     = array();
        $this->dbConnections = array();
        
        $this->tasks = array();
        
        $this->logger = $logger;
        
        #---------------------------------------------
        # Create tasks and database information
        #---------------------------------------------
        $i = 0;
        foreach ($workflow->getConfigurations() as $configName => $configuration) {
            # Create task for this configuration
            $etlTask = new EtlTask();
            $etlTask->initialize($this->logger, $configName, $configuration, $redcapProjectClass);
        
            $this->tasks[] = $etlTask;

            # Get string that serves as database identifier
            $dbId = $etlTask->getDbId();

            # Set schema and connection information for the current database
            if (array_key_exists($dbId, $this->dbSchemas)) {
                $this->dbSchemas[$dbId] = $this->dbSchemas[$dbId]->merge($etlTask->getSchema());
            } else {
                $this->dbSchemas[$dbId]     = $etlTask->getSchema();
                $this->dbConnections[$dbId] = $etlTask->getDbConnection();
            }
            
            $i++;
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
        foreach ($this->dbSchemas as $dbId => $schema) {
            $dbConnection = $this->dbConnections[$dbId];
            $this->createLoadTables($dbConnection, $schema);
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



    public function createLoadTables($dbConnection, $schema)
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
            $ifNotExists = true;   // same table could be created by 2 different configurations
            $dbConnection->createTable($table, $ifNotExists);
            // $this->dbcon->addPrimaryKeyConstraint($table);

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

        # FIX!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        #-----------------------------------------------------------------------------------
        # If configured, create the lookup table that maps multiple choice values to labels
        #-----------------------------------------------------------------------------------
        #if ($this->configuration->getCreateLookupTable()) {
        #    $lookupTable = $schema->getLookupTable();
        #    $dbConnection->replaceTable($lookupTable);
        #    $this->loadTableRows($lookupTable);
        #}
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
    
    public function getConfiguration($index)
    {
        $task = $this->tasks[$index];
        return $task->getConfiguration();
    }
    
    public function getDataProject($index)
    {
        $task = $this->tasks[$index];
        return $task->getDataProject();
    }
}

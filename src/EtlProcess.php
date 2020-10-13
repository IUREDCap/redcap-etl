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
    /** @var array array of task objects that represent the tasks of the workflow. */
    private $tasks;

    /** @var array map from database ID (string) to array of task objects. */
    private $dbTasks;

    /** @var array map from database ID to database schema, which merges the schemas for all
     *             the tasks that load data to the database.
     */
    private $dbSchemas;
    
    /** @var array map from database ID to database connection. */
    private $dbConnections;
    
    private $logger;

    /** @var array map from synthetic REDCap source ID to REDCap project Information */
    private $projectInfos;

    /** @var array map from synthetic REDCap source ID to REDCap metadata information */
    private $metadata;

    /** @var array map from database ID to a ProjectInfoTable table with REDCap project information */
    private $projectInfoTables;

    /**
     * Constructor.
     *
     */
    public function __construct($workflow, $logger, $redcapProjectClass)
    {
        $this->dbSchemas     = array();
        $this->dbConnections = array();
        $this->dbTasks       = array();
        
        $this->tasks = array();

        $this->projectInfos = array();
        
        $this->logger = $logger;
        
        #---------------------------------------------
        # Create tasks and database information
        #---------------------------------------------
        $taskId = 1;
        foreach ($workflow->getConfigurations() as $configName => $configuration) {
            # Create task for this configuration
            $etlTask = new EtlTask($taskId);
            $etlTask->initialize($this->logger, $configName, $configuration, $redcapProjectClass);
        
            $this->tasks[] = $etlTask;

            # Get string that serves as database identifier
            $dbId = $etlTask->getDbId();

            # Set schema and connection information for the current database
            if (array_key_exists($dbId, $this->dbSchemas)) {
                $this->dbSchemas[$dbId] = $this->dbSchemas[$dbId]->merge($etlTask->getSchema());
                $this->dbTasks[$dbId]   = array_merge($this->dbTasks[$dbId], [$etlTask]);
            } else {
                $this->dbSchemas[$dbId]     = $etlTask->getSchema();
                $this->dbConnections[$dbId] = $etlTask->getDbConnection();
                $this->dbTasks[$dbId]       = [$etlTask];
            }
            
            $taskId++;
        }
        
        #---------------------------------------------
        # Set the DB (merged) schemas for the tasks
        #---------------------------------------------
        foreach ($this->tasks as $task) {
            $dbId = $task->getDbId();
            $dbSchema = $this->dbSchemas[$dbId];
            $task->setDbSchema($dbSchema);
        }


        $dataSource = 1;
        foreach ($this->tasks as $task) {
            $apiUrl      = $task->getRedCapApiUrl();
            $projectInfo = $task->getRedCapProjectInfo();
            $projectInfo['api_url'] = $apiUrl;

            $metadata    = $task->getRedCapMetadata();

            $this->projectInfos[$dataSource] = $projectInfo;
            $this->metadata[$dataSource]     = $metadata;

            $dataSource++;
        }

        #---------------------------------------------
        # Create the project info tables
        #---------------------------------------------
        $dbIds = $this->getDbIds();
        foreach ($dbIds as $dbId) {
            $this->projectInfoTables[$dbId] = new ProjectInfoTable();
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
        foreach ($this->dbSchemas as $dbId => $schema) {
            $dbConnection = $this->dbConnections[$dbId];
            $this->createLoadTables($dbConnection, $schema);
            $dbTasks = $this->dbTasks[$dbId];
            
            # reset task DB connections after the Lookup table information has been
            # added from code above
            foreach ($dbTasks as $task) {
                $task->setDbConnection($dbConnection);
            }
            
            # Create Project Info table with data for all tasks
            # that use the current database
            $projectInfoTable = $this->projectInfoTables[$dbId];
            $dbConnection->replaceTable($projectInfoTable);
            
            foreach ($dbTasks as $task) {
                $apiUrl      = $task->getRedCapApiUrl();
                $projectInfo = $task->getRedCapProjectInfo();
                $projectInfo['api_url'] = $apiUrl;
                $row = $projectInfoTable->createDataRow($task->getId(), $apiUrl, $projectInfo);
                $dbConnection->insertRow($row);

                $metadata    = $task->getRedCapMetadata();
            }

            

            # Create REDCap project info and metadata tables for each database
            /*
            $projectInfoTable = $this->projectInfoTables[$dbId];
            $dbConnection->replaceTable($projectInfoTable);

            foreach ($this->projectInfos as $projectInfo) {
                #print "\n\nPROJECT INFO:\n";
                #print_r($projectInfo);
                #print "\n\n";
            }
            */
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
        $dbId = $dbConnection->getId();
        $dbConnection->dropTable($this->projectInfoTables[$dbId], $ifExists = true);

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

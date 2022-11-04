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

    /** @var string the name of the workflow. */
    private $name;

    /** @var string generated unique ID, which can be used to tell which tasks belong to the same workflow. */
    private $id;

    /** @var float time in seconds to extract the data form REDCap. */
    private $extractTime;

    /** @var float time in seconds to transform the extracted data. */
    private $transformTime;

    /** @var float time in seconds to load the transformed data into the database. */
    private $loadTime;

    /** @var float time in seconds for pre-processing, which includes inital logging, dropping existing load
                   tables (if any), creating the load tables, and running any user-defined pre-processing SQL. */
    private $preProcessingTime;

    /** @var float time in seconds for creating primary and foreign keys (if configured), and running any
                   user-defined post-processing SQL. */
    private $postProcessingTime;

    /** @var float time for overhead that doesn't fit into other categories, including some logging, and
                   batch processing overhead. */
    private $overheadTime;

    /** @var float total time in seconds for the entire ETL process. */
    private $totalTime;

    private $workflowConfig;

    /* Max batch size for metadata row insert; over 1,000 causes an error for SQL Server */
    const METADATA_BATCH_SIZE = 1000;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->workflowConfig = null;

        $this->dbSchemas     = array();
        $this->dbConnections = array();
        $this->dbTasks       = array();
        
        $this->tasks = array();

        $this->extractTime    = 0.0;
        $this->transformTime  = 0.0;
        $this->loadTime       = 0.0;

        $this->preProcessingTime  = 0.0;
        $this->postProcessingTime = 0.0;

        $this->overheadTime = 0.0;

        $this->totalTime = 0.0;
    }

    public function set($workflowConfig, &$logger, $redcapProjectClass = null)
    {
        $this->workflowConfig = $workflowConfig;

        $this->logger = $logger;

        $this->name = $workflowConfig->getWorkflowName();
        $this->id = uniqid('', true);

        #---------------------------------------------
        # Create tasks and database information
        #---------------------------------------------
        $taskId = 1;
        foreach ($workflowConfig->getTaskConfigs() as $taskConfig) {
            # Create task for this task configuration
            $task = new Task($taskId);
            $logger = $taskConfig->getLogger();
            $task->initialize($logger, $taskConfig, $redcapProjectClass);

            $this->tasks[] = $task;

            # Get string that serves as database identifier
            $dbId = $task->getDbId();

            # Create a merged schema and get connection information for the current database,
            # unless the task is an SQL-only task
            if ($task->isSqlOnlyTask()) {
                ; // SQL only - it has no schema to merge
            } elseif (array_key_exists($dbId, $this->dbSchemas)) {
                $this->dbSchemas[$dbId] = $this->dbSchemas[$dbId]->merge($task->getSchema(), $task);
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
            }
        }
    }


    public function run(&$logger = null)
    {
        $startTime = microtime(true);
        $preProcessingStartTime = $startTime;
        $numberOfRecordIds = 0;

        #-----------------------------------------
        # For each task, log header information
        #-----------------------------------------
        foreach ($this->getTasks() as $task) {
            #-------------------------------------------------
            # Set memory limit for the task
            #-------------------------------------------------
            $getValue = ini_get('memory_limit');
            print "Retrieved memory limit = {$getValue}\n";
            $memoryLimit = $task->getTaskConfig()->getMemoryLimit();
            if (!empty($memoryLimit)) {
                $ival = ini_set('memory_limit', $memoryLimit);
                print("ini_set return value: {$ival}\n");
                print("MEMORY LIMIT SET TO: {$memoryLimit}\n");
                $getValue = ini_get('memory_limit');
                print "Retrieved memory limit = {$getValue}\n";
                foreach (range(1,1000000) as $i) {
                    $arr[] = $i;
                }
                # print_r($arr);
                print "MEMORY USAGE: " . memory_get_usage() . "\n";
            }
            ini_set('memory_limit', '2K');

            $taskName = $task->getName();
            
            $logger = $task->getLogger();

            #----------------------------------------------------------------
            # Log version and job information
            #----------------------------------------------------------------
            $logger->log('REDCap-ETL version '.Version::RELEASE_NUMBER);

            if ($task->isSqlOnlyTask()) {
                $logger->log('SQL-only task');
            } else {
                $logger->log('REDCap version '.$task->getDataProject()->exportRedCapVersion());
                $task->logJobInfo();
            }
            $workflowName = $this->getName();
            $workflowId   = $this->getId();
            if (!empty($workflowName)) {
                $logger->log("Workflow: {$workflowName}");
                $logger->log("Workflow ID: {$workflowId}");
            }

            $logger->log('Number of load databases: '.count($this->getDbIds()));
            $i = 1;
            foreach (array_keys($this->getDbIds()) as $dbId) {
                $logger->log("Load database {$i}: {$dbId}");
                $i++;
            }

            $logger->log("Starting processing.");
        }

        $getValue = ini_get('memory_limit');
        print "-----------------------------------------\n";
        print "Retrieved memory limit = {$getValue}\n";
        print "-----------------------------------------\n";

        #-----------------------------------------------------------------------------------
        # For each task, run pre-processing SQL.
        #
        # This needs to be run before the tables are dropped so that user-created views
        # based on the ETL generated tables can be dropped before the tables are dropped.
        #-----------------------------------------------------------------------------------
        foreach ($this->getTasks() as $task) {
            $task->runPreProcessingSql();
        }

        #----------------------------------------------
        # Drop old load tables if they exist,
        # and then create the load tables
        #----------------------------------------------
        $this->dropAllLoadTables();
        $this->createAllLoadTables();

        $preProcessingEndTime = microtime(true);
        $this->preProcessingTime = $preProcessingEndTime - $preProcessingStartTime;

        #---------------------------------------------------------------------------------
        # For each ETL task (i.e., non-SQL-only tasks) run ETL (Extract Transform Load)
        #---------------------------------------------------------------------------------
        foreach ($this->getTasks() as $task) {
            $memoryLimit = $task->getTaskConfig()->getMemoryLimit();
            if (!empty($memoryLimit)) {
                ini_set('memory_limit', $memoryLimit);
            }

            # ETL
            if (!$task->isSqlOnlyTask()) {
                $numberOfRecordIds += $task->extractTransformLoad();

                $this->extractTime   += $task->getExtractTime();
                $this->transformTime += $task->getTransformTime();
                $this->loadTime      += $task->getLoadTime();
            }
        }

        $postProcessingStartTime = microtime(true);
        #-------------------------------------------------------------------------
        # Generate primary and foreign keys for the databases, if configured
        #-------------------------------------------------------------------------
        foreach ($this->getDbIds() as $dbId) {
            $this->createDatabaseKeys($dbId);
        }

        $logWorkflowEmailSummary = false;
        if ($this->workflowConfig->isWorkflow() && $this->workflowConfig->getLogger()->canLogEmailSummary()) {
            $logWorkflowEmailSummary = true;
        }

        #------------------------------------------------------------------
        # For each task, run post-processing SQL and log as complete
        #------------------------------------------------------------------
        foreach ($this->getTasks() as $task) {
            $logger = $task->getLogger();
            $task->runPostProcessingSql();
                
            $logger->log(RedCapEtl::PROCESSING_COMPLETE);

            if (!$logWorkflowEmailSummary) {
                $logger->logEmailSummary();
            }
        }

        if ($logWorkflowEmailSummary) {
            $this->workflowConfig->getLogger()->logEmailSummary();
        }

        $endTime = microtime(true);
        $this->postProcessingTime = $endTime - $postProcessingStartTime;
        $this->totalTime = $endTime - $startTime;

        $this->overheadTime = $this->totalTime
            - $this->extractTime
            - $this->transformTime
            - $this->loadTime
            - $this->preProcessingTime
            - $this->postProcessingTime;

        return $numberOfRecordIds;
    }




    public function dropAllLoadTables()
    {
        foreach ($this->dbSchemas as $dbId => $schema) {
            $dbConnection = $this->dbConnections[$dbId];
            $this->dropLoadTables($dbConnection, $schema);
        }
    }


    /**
     * Creates all the tables for all databases in the workflow.
     */
    public function createAllLoadTables()
    {
        # For each of the load databases, create the load tables
        $i = 1;
        foreach ($this->dbSchemas as $dbId => $schema) {
            $this->createLoadTables($dbId, $schema);
            $dbTasks = $this->dbTasks[$dbId];
            
            #-----------------------------------------------------------------------
            # Reset task DB connections after the Lookup table information has been
            # added from code above. This is a kludge needed for CSV databases,
            # because they can't create views, so they need to store the lookup
            # table information between method calls.
            #-----------------------------------------------------------------------
            $dbConnection = $this->dbConnections[$dbId];
            foreach ($dbTasks as $task) {
                $task->setDbConnection($dbConnection);
            }
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

        #--------------------------------------------------
        # Drop the lookup table, if any
        #--------------------------------------------------
        $lookupTable = $schema->getLookupTable();
        $dbConnection->dropTable($lookupTable, $ifExists = true);

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



    /**
     * Creates the tables for the specified database.
     *
     * @parameter string $dbId the database identifier for the database for which the tables
     *     are to be created.
     *
     * @parameter Schema $schema the database schema for the specified database.
     */
    public function createLoadTables($dbId, $schema)
    {
        $dbConnection = $this->dbConnections[$dbId];

        $ifNotExists = true;

        #--------------------------------------------------------
        # Create Project Info and Metadata tables for the
        # current database
        #--------------------------------------------------------
        $projectInfoTable = $schema->getProjectInfoTable();
        $metadataTable    = $schema->getMetadataTable();

        $dbConnection->createTable($projectInfoTable, $ifNotExists);
        $dbConnection->createTable($metadataTable, $ifNotExists);
            
        $dbConnection->storeRows($projectInfoTable);
        $dbConnection->storeRows($metadataTable, self::METADATA_BATCH_SIZE);

        #------------------------------------------------------------------------------------
        # If configured, create the lookup table that maps multiple choice values to labels,
        # and load the data rows for this table.
        #------------------------------------------------------------------------------------
        if ($this->needsLookupTable($dbId)) {
            $lookupTable = $schema->getLookupTable();
            $dbConnection->createTable($lookupTable, $ifNotExists);
            $dbConnection->storeRows($lookupTable);
        }

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

            $msg = "Created table '".$table->getName()."'";

            #--------------------------------------------------------------------------
            # If this table uses the Lookup table (i.e., has multiple-choice values),
            # Create a view of the table that has multiple-choice labels instead of
            # multiple-choice values.
            #--------------------------------------------------------------------------
            if ($table->usesLookup === true) {
                $dbConnection->replaceLookupView($table, $schema->getLookupTable());
                $msg .= '; Lookup table created';
            }

            #------------------------------------------------------------
            # Log table creation for each task that contains the table
            #------------------------------------------------------------
            foreach ($this->getTasks() as $task) {
                if ($task->getSchema()->hasTable($table->getName())) {
                    $logger = $task->getLogger();
                    $logger->log($msg);
                }
            }
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
     * Indicates if the specified database needs to have label views. If any task has this flag set,
     * label views will be generated.
     */
    public function needsLookupTable($dbId)
    {
        $needsLookupTable = false;

        if (!empty($dbId) && array_key_exists($dbId, $this->dbSchemas)) {
            $needsLookupTable = $this->dbSchemas[$dbId]->getLabelViews();
        }

        return $needsLookupTable;
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

    public function getTaskByName($name)
    {
        $taskWithName = null;
        foreach ($this->tasks as $task) {
            if ($name === $task->getName()) {
                $taskWithName = $task;
                break;
            }
        }
        return $taskWithName;
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

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPreProcessingTime()
    {
        return $this->preProcessingTime;
    }

    public function getPostProcessingTime()
    {
        return $this->postProcessingTime;
    }

    public function getExtractTime()
    {
        return $this->extractTime;
    }

    public function getTransformTime()
    {
        return $this->transformTime;
    }

    public function getLoadTime()
    {
        return $this->loadTime;
    }

    public function getOverheadTime()
    {
        return $this->overheadTime;
    }

    public function getTotalTime()
    {
        return $this->totalTime;
    }

    public function getWorkflowConfig()
    {
        return $this->workflowConfig;
    }

    /**
     * Indicates if this workflow represents a standalone task, i.e., it
     * contains a single task that was configured as a standalone configuration,
     * outside of a workflow.
     */
    public function isStandaloneTask()
    {
        $isStandAloneTask = false;
        if (count($this->getTasks()) === 1) {
            $task = $this->getTask(0);
            if (empty($task->getName())) {
                $isStandAloneTask = true;
            }
        }
        return $isStandAloneTask;
    }
}

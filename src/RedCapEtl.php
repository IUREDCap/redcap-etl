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
 * REDCap-ETL (Extract, Transform, Load) class.
 */
class RedCapEtl
{
    const CHECKBOX_SEPARATOR         = '___';

    // For creating output fields to represent events, suffixes and
    // repeating instruments
    const COLUMN_EVENT             = 'redcap_event_name';
    const COLUMN_SUFFIXES          = 'redcap_suffix';
    const COLUMN_REPEATING_INSTRUMENT   = 'redcap_repeat_instrument';
    const COLUMN_REPEATING_INSTANCE     = 'redcap_repeat_instance';
    const COLUMN_SURVEY_IDENTIFIER      = 'redcap_survey_identifier';

    const COLUMN_DAG = 'redcap_data_access_group';

    # Text logged when the ETL process completes successfully.
    # This can be used to programmatically check the log for
    # when the process completes.
    const PROCESSING_COMPLETE = 'Processing complete.';

    protected $projectInfoTable;
    
    protected $logger;

    protected $rowsLoadedForTable = array();
  
    private $errorHandler;

    private $app;

    private $recordIdFieldName;   // The field name for the record ID
                                  // for the data project in REDCap

    private $workflow;       // parsed (possibly multiple) configuration

    /** @var array map where the keys represent root tables that have
     *     fields that have multiple rows of data per record ID.
     *     Root tables are intended for fields that have a 1:1 mapping
     *     with the record ID.
     */
    private $rootTablesWithMultiValues;

    private $redcapProjectClass;

    /** @var EtlProcess */
    private $etlProcess;
    
    /** @var array map from configuration name to ETL process.
     */
    private $etlTasks;

    private $etlTaskMap; // Map from database to $etlTask (merge schemas based on this);

    /** @var array map of database ID's (strings) to merged schemas for configurations for that database */
    private $dbToSchemaMap;

    /** @var array map of database ID to database connection. */
    private $dbIdToConnectionMap;

    /**
     * Constructor.
     *
     * @param Logger $logger logger for information and errors
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     * @param string $redcapProjectClass fully qualified class name for class
     *     to use as the RedcapProject class. By default the EtlRedCapProject
     *     class is used.
     */
    public function __construct(
        & $logger,
        $properties,
        $redcapProjectClass = null
    ) {
        $this->app = $logger->getApp();

        $this->workflow = new Workflow($logger, $properties, $redcapProjectClass);
        
        $this->etlProcess = new EtlProcess($this->workflow, $logger, $redcapProjectClass);
        
        $this->logger = $logger;
        $this->redcapProjectClass = $redcapProjectClass;

/*
        $this->etlTasks  = array();
        $this->etlTaskMap = array();

        $this->dbToSchemaMap       = array();
        $this->dbIdToConnectionMap = array();

        #---------------------------------------------
        # Set up the ETL processes
        #---------------------------------------------
        foreach ($this->workflow->getConfigurations() as $configName => $configuration) {
            $etlTask = new EtlTask();
            $etlTask->initialize($this->logger, $configName, $configuration, $this->redcapProjectClass);
            $this->etlTasks[$configName] = $etlTask;

            $dbId = $etlTask->getDbId();

            //$this->etlTasks[] = $etlTask;

            # Set database (ID) to Schema map, and database ID to connection map
            if (array_key_exists($dbId, $this->dbToSchemaMap)) {
                $this->dbToSchemaMap[$dbId] = $this->dbToSchemaMap[$dbId]->merge($etlTask->getSchema());
            } else {
                $this->dbToSchemaMap[$dbId] = $etlTask->getSchema();
                $this->dbIdToConnectionMap[$dbId] = $etlTask->getDbConnection();
            }
        }
*/
    }


    /**
     * Creates the project info table that has information on the project(s) used in
     * the ETL configuration(s). Only one of these tables is created for each ETL
     * run, regardless of how many individual configurations are used.
     */
    public function createProjectInfoTable()
    {
        #-----------------------------------------------------------
        # Create the project info table
        #-----------------------------------------------------------
        #$this->projectInfoTable = new ProjectInfoTable($this->configuration->getTablePrefix() /* , $name */);
        #$this->dbcon->replaceTable($this->projectInfoTable);
        #$this->loadTableRows($this->projectInfoTable);
    }


    /**
     * Loads the rows that have been stored in the schema into the target database
     * and deletes the rows after they have been loaded.
     */
    protected function loadRows()
    {
        #--------------------------------------------------------------
        # foreach table object, store it's rows in the database and
        # then remove them from the table object
        #--------------------------------------------------------------
        foreach ($this->schema->getTables() as $table) {
            $this->loadTableRows($table);
        }

        return true;
    }

    /**
     * Loads an in-memory table's rows into the database.
     *
     * @param Table $table the table containing the (in-memory) rows
     *    to be loaded into the database.
     * @param boolean $deleteRowsAfterLoad indicates if the rows in the in-memory
     *     table should be deleted after they are loaded into the database.
     */
    protected function loadTableRows($table, $deleteRowsAfterLoad = true)
    {
        foreach ($table->getRows() as $row) {
            $rc = $this->dbcon->storeRow($row);
            if (false === $rc) {
                $this->log("Error storing row in '".$table->name."': ".$this->dbcon->errorString);
            }
        }

        // Add to summary how many rows created for this table
        if (array_key_exists($table->name, $this->rowsLoadedForTable)) {
            $this->rowsLoadedForTable[$table->name] += $table->getNumRows();
        } else {
            $this->rowsLoadedForTable[$table->name] = $table->getNumRows();
        }

        if ($deleteRowsAfterLoad) {
            // Empty the rows for this table
            $table->emptyRows();
        }
    }


    /**
     * Load all rows in the specified table using a method that will use
     * a single insert statement.
     *
     * @param Table $table table object for which rows are loaded.
     * @param boolean $deleteRowsAfterLoad if true, the rows in the table object are
     *     deleted after they are loaded into the database.
     */
    protected function loadTableRowsEfficiently($table, $deleteRowsAfterLoad = true)
    {
        $rc = $this->dbcon->storeRows($table);

        // Add to summary how many rows created for this table
        if (array_key_exists($table->name, $this->rowsLoadedForTable)) {
            $this->rowsLoadedForTable[$table->name] += $table->getNumRows();
        } else {
            $this->rowsLoadedForTable[$table->name] = $table->getNumRows();
        }

        if ($deleteRowsAfterLoad) {
            // Empty the rows for this table
            $table->emptyRows();
        }
    }

    /**
     * Reports rows written to the database
     */
    protected function reportRows()
    {
        // foreach table
        foreach ($this->rowsLoadedForTable as $tableName => $rows) {
            $msg = "Rows loaded for table '".$tableName."': ".$rows;
            $this->log($msg);
        }

        return true;
    }



    /**
     * Runs the entire ETL process.
     *
     * @return int the number of record IDs found (and hopefully processed).
     */
    public function run()
    {
        $numberOfRecordIds = 0;

        # Create the project info table that stores information
        # on all the REDCap projects that are accessed
        ##### $this->createProjectInfoTable();

        #----------------------------------------------
        # Drop old load tables if they exist,
        # and then create the load tables
        #----------------------------------------------
        foreach ($this->etlProcess->getDbSchemas() as $dbId => $schema) {
            $dbConnection = $this->etlProcess->getDbConnection($dbId);
            $this->dropLoadTables($dbConnection, $schema);
            $this->createLoadTables($dbConnection, $schema);
        }

        #-----------------------------------------
        # Run ETL for each ETL task
        #-----------------------------------------
        foreach ($this->etlProcess->getTasks() as $etlTask) {
            $taskName = $etlTask->getName();
            
            $logger = $etlTask->getLogger();

            $logger->log('REDCap-ETL version '.Version::RELEASE_NUMBER);
            $logger->log('REDCap version '.$etlTask->getDataProject()->exportRedCapVersion());
            $etlTask->logJobInfo();
            $logger->log('Number of load databases: '.count($this->etlProcess->getDbIds()));
            $i = 1;
            foreach (array_keys($this->etlProcess->getDbIds()) as $dbId) {
                $logger->log("Load database {$i}: {$dbId}");
                $i++;
            }
            $logger->log("Starting processing.");

            $etlTask->runPreProcessingSql();

            ## Create the database tables where the extracted and
            ## transformed data from REDCap will be loaded
            #$etlTask->createLoadTables();

            #----------------------------------------------------------------------
            # Project Info table (eventually will have possible multiple projects)
            #----------------------------------------------------------------------
            $etlTask->createProjectInfoTable();

            # ETL
            $numberOfRecordIds += $etlTask->extractTransformLoad();

            $etlTask->createDatabaseKeys(); // create primary and foreign keys, if configured

            $etlTask->runPostProcessingSql();
                
            $logger->log(self::PROCESSING_COMPLETE);
            $logger->logEmailSummary();
        }

        return $numberOfRecordIds;
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


    public function getLogger()
    {
        return $this->logger;
    }
    
    public function log($message)
    {
        $this->logger->log($message);
    }

    public function logToFile($message)
    {
        $this->logger->logToFile($message);
    }

    public function getEtlTasks()
    {
        return $this->etlTasks;
    }

    public function getEtlTask($index)
    {
        $proc = null;
        $i = 0;
        foreach ($this->etlTasks as $configName => $etlTask) {
            if ($i === $index) {
                $proc = $etlTask;
                break;
            }
            $i++;
        }
        return $proc;
    }
    
    public function getEtlProcess()
    {
        return $this->etlProcess;
    }
    
    public function getConfiguration($index)
    {
        $configuration = $this->etlProcess->getConfiguration($index);
        return $configuration;
    }
    
    public function getDataProject($index)
    {
        $dataProject = $this->etlProcess->getDataProject($index);
        return $dataProject;
    }
}

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

        #----------------------------------------------
        # Drop old load tables if they exist,
        # and then create the load tables
        #----------------------------------------------
        $this->etlProcess->dropAllLoadTables();
        $this->etlProcess->createAllLoadTables();

        #-----------------------------------------
        # Run ETL for each ETL task
        #-----------------------------------------
        foreach ($this->etlProcess->getTasks() as $etlTask) {
            $taskName = $etlTask->getName();
            
            $logger = $etlTask->getLogger();

            #----------------------------------------------------------------
            # Log version and job information
            #----------------------------------------------------------------
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

            #----------------------------------------------------------------------
            # Project Info table (eventually will have possible multiple projects)
            #----------------------------------------------------------------------
            // $etlTask->createProjectInfoTable();

            # ETL
            $numberOfRecordIds += $etlTask->extractTransformLoad();

// FIX!!!!!!!!!! Try to create when table created - if at end,
// need to wait for all tasks for a given database to finish:

            //$etlTask->createDatabaseKeys(); // create primary and foreign keys, if configured

            $etlTask->runPostProcessingSql();
                
            $logger->log(self::PROCESSING_COMPLETE);
            $logger->logEmailSummary();
        }

        return $numberOfRecordIds;
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

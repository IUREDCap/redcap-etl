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

    const COLUMN_DATA_SOURCE = 'redcap_data_source';

    # Text logged when the ETL process completes successfully.
    # This can be used to programmatically check the log for
    # when the process completes.
    const PROCESSING_COMPLETE = 'Processing complete.';

    protected $logger;

    protected $rowsLoadedForTable = array();
  
    private $errorHandler;

    private $app;

    private $recordIdFieldName;   // The field name for the record ID
                                  // for the data project in REDCap

    /** @var WorkflowConfig workflow configuration */
    private $workflowConfig;

    /** @var array map where the keys represent root tables that have
     *     fields that have multiple rows of data per record ID.
     *     Root tables are intended for fields that have a 1:1 mapping
     *     with the record ID.
     */
    private $rootTablesWithMultiValues;

    private $redcapProjectClass;

    /** @var EtlWorkflow */
    private $etlWorkflow;

    /**
     * Constructor.
     *
     * @param Logger $logger logger for information and errors
     *
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     *
     * @param string $redcapProjectClass fully qualified class name for class
     *     to use as the RedcapProject class. By default the EtlRedCapProject
     *     class is used.
     *
     * @param string $baseDir used as the base directory for file properties
     *     that are specified as relative paths. This generally only needs to
     *     be set if the properties are passed as an array (instead of a file).
     */
    public function __construct(
        & $logger,
        $properties,
        $redcapProjectClass = null,
        $baseDir = null
    ) {
        $this->app = $logger->getApp();

        $this->workflowConfig = new WorkflowConfig();
        $this->workflowConfig->set($logger, $properties, $baseDir);

        $this->etlWorkflow = new EtlWorkflow($this->workflowConfig, $logger, $redcapProjectClass);
        
        $this->logger = $logger;
        $this->redcapProjectClass = $redcapProjectClass;
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
        $this->etlWorkflow->dropAllLoadTables();
        $this->etlWorkflow->createAllLoadTables();

        #-----------------------------------------
        # Run ETL for each ETL task
        #-----------------------------------------
        foreach ($this->etlWorkflow->getTasks() as $etlTask) {
            $taskName = $etlTask->getName();
            
            $logger = $etlTask->getLogger();

            #----------------------------------------------------------------
            # Log version and job information
            #----------------------------------------------------------------
            $logger->log('REDCap-ETL version '.Version::RELEASE_NUMBER);

            if ($etlTask->isSqlOnlyTask()) {
                $logger->log('SQL-only task');
            } else {
                $logger->log('REDCap version '.$etlTask->getDataProject()->exportRedCapVersion());
                $etlTask->logJobInfo();
            }
            $logger->log('Number of load databases: '.count($this->etlWorkflow->getDbIds()));
            $i = 1;
            foreach (array_keys($this->etlWorkflow->getDbIds()) as $dbId) {
                $logger->log("Load database {$i}: {$dbId}");
                $i++;
            }

            $logger->log("Starting processing.");

            $etlTask->runPreProcessingSql();

            # ETL
            if (!$etlTask->isSqlOnlyTask()) {
                $numberOfRecordIds += $etlTask->extractTransformLoad();
            }

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
        return $this->etlWorkflow->getTasks();
    }

    public function getEtlTask($index)
    {
        return $this->etlWorkflow->getTask($index);
    }
    
    public function getEtlWorkflow()
    {
        return $this->etlWorkflow;
    }
    
    public function getTaskConfig($index)
    {
        $taskConfig = $this->etlWorkflow->getTaskConfig($index);
        return $taskConfig;
    }
    
    public function getDataProject($index)
    {
        $dataProject = $this->etlWorkflow->getDataProject($index);
        return $dataProject;
    }
}

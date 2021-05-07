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

    /** @var array map where the keys represent root tables that have
     *     fields that have multiple rows of data per record ID.
     *     Root tables are intended for fields that have a 1:1 mapping
     *     with the record ID.
     */
    private $rootTablesWithMultiValues;

    private $redcapProjectClass;

    /** @var Workflow */
    private $workflow;

    /** @var WorkflowConfig the workflow configuration. */
    private $workflowConfig;

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
        &$logger,
        $properties,
        $redcapProjectClass = null,
        $baseDir = null
    ) {
        $this->app = $logger->getApp();

        $this->workflowConfig = new WorkflowConfig();
        $this->workflowConfig->set($logger, $properties, $baseDir);

        $this->workflow = new Workflow();
        $this->workflow->set($this->workflowConfig, $logger, $redcapProjectClass);
        
        $this->logger = $logger;
        $this->redcapProjectClass = $redcapProjectClass;

        KeyValueDb::initialize();
    }



    /**
     * Runs the entire ETL process.
     *
     * @return int the number of record IDs found (and hopefully processed).
     */
    public function run()
    {
        $numberOfRecordIds = $this->workflow->run();
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

    public function getTasks()
    {
        return $this->workflow->getTasks();
    }

    public function getTask($index)
    {
        return $this->workflow->getTask($index);
    }

    public function getTransformationRulesText($index)
    {
        return $this->getTask($index)->getTransformationRulesText();
    }
    
    public function getWorkflow()
    {
        return $this->workflow;
    }
    
    public function getWorkflowConfig()
    {
        return $this->workflowConfig;
    }

    public function getTaskConfig($index)
    {
        $taskConfig = $this->workflow->getTaskConfig($index);
        return $taskConfig;
    }
    
    public function getDataProject($index)
    {
        $dataProject = $this->workflow->getDataProject($index);
        return $dataProject;
    }
}

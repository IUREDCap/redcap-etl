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
    private $tasks;
    
    /** @var array map from database ID to database schema (a merged schema for all tasks that
     * load data to the database.
     */
    private $dbSchemas;
    
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

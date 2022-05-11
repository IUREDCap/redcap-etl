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
 * REDCap-ETL task defined by a single task configuration that contains a single data source (REDCap project)
 * and destination (database).
 */
class Task
{
    private $name;
    
    /** @var int the task ID, which should be unique within a workflow; used as redcap_data_source value */
    private $id;
    
    /** @var EtlRedCapProject the project that has the data to extract */
    protected $dataProject;

    protected $logger;

    /** @var Schema schema based on tasks configuration */
    protected $schema;
    
    protected $rowsLoadedForTable = array();
  
    /** @var DbConnection the database connection object for this task */
    protected $dbcon;

    private $errorHandler;

    private $app;

    /** @var TaskConfig the task configuration for this task. */
    private $taskConfig;

    /** @var Workflow the workflow for this task, */
    private $workflow;

    /** @var array map where the keys represent root tables that have
     *     fields that have multiple rows of data per record ID.
     *     Root tables are intended for fields that have a 1:1 mapping
     *     with the record ID.
     */
    private $rootTablesWithMultiValues;

    private $transformationRulesText;

    /** @var float time in seconds to extract the data form REDCap. */
    private $extractTime;

    /** @var float time in seconds to transform the extracted data. */
    private $transformTime;

    /** @var float time in seconds to load the transformed data into the database. */
    private $loadTime;

    /**
     * Constructor.
     *
     */
    public function __construct($id)
    {
        $this->id   = $id;
        $this->name = '';
        $this->transformationRulesText = '';
        $this->schema = null;

        $this->extractTime   = 0.0;
        $this->transformTime = 0.0;
        $this->loadTime      = 0.0;
    }

    /**
     * Initializes the ETL task.
     *
     * @param Logger $logger logger for information and errors
     *
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     *
     * @param TaskConfig the task configuration object for this task.
     *
     * @param string $redcapProjectClass fully qualified class name for class
     *     to use as the RedcapProject class. By default the EtlRedCapProject
     *     class is used.
     *
     * @param Workflow the workflow this task belongs to.
     */
    public function initialize($logger, $taskConfig, $redcapProjectClass = null, $workflow = null)
    {
        $this->app = $logger->getApp();
        $this->logger = $taskConfig->getLogger();
        $this->name = $taskConfig->getTaskName();
        # $this->logger->setTaskConfig($taskConfig);    // Handled in TaskConfig

        $this->rootTablesWithMultiValues = array();

        $this->taskConfig = $taskConfig;

        $this->workflow = $workflow;

        #---------------------------------------------------------
        # Set time limit
        #---------------------------------------------------------
        $timeLimit = $this->taskConfig->getTimeLimit();
        if (isset($timeLimit) && trim($timeLimit) !== '') {
            set_time_limit($timeLimit);
        } else {
            set_time_limit(0);   # no time limit
        }

        #---------------------------------------------------------
        # Set timezone if one was specified
        #---------------------------------------------------------
        $timezone = $this->taskConfig->getTimezone();
        if (isset($timezone) && trim($timezone) !== '') {
            date_default_timezone_set($timezone);
        }

        #----------------------------------------------------------
        # If this is NOT an SQL-only task, set up REDCap access
        #----------------------------------------------------------
        if (!$this->isSqlOnlyTask()) {
            $this->setUpRedCapAccess();
        }

        #---------------------------------------------------
        # Create a database connection for the database
        # where the transformed REDCap data will be stored
        #---------------------------------------------------
        $dbconfactory = new DbConnectionFactory();
        $this->dbcon = $dbconfactory->createDbConnection(
            $this->taskConfig->getDbConnection(),
            $this->taskConfig->getDbSsl(),
            $this->taskConfig->getDbSslVerify(),
            $this->taskConfig->getCaCertFile(),
            $this->taskConfig->getTablePrefix(),
            $this->taskConfig->getLabelViewSuffix()
        );

        #-------------------------------------------------
        # Set up database logging
        #-------------------------------------------------
        $dbLogTable      = null;
        $dbEventLogTable = null;
        # if ($this->taskConfig->getDbLogging()) {
        if ($this->taskConfig->getDbLogging() && !($this->dbcon instanceof CsvDbConnection)) {
            $this->logger->setDbConnection($this->dbcon);
            $this->logger->setDbLogging(true);
            
            # Add information to logger that is used by database logging
            
        
            #----------------------------------------
            # (Main) database log table
            #----------------------------------------
            $name = $this->taskConfig->getDbLogTable();
            $dbLogTable = new EtlLogTable($name);
            $this->logger->setDbLogTable($dbLogTable);
            
            $this->dbcon->createTable($dbLogTable, true);
            
            $this->logger->logToDatabase();
            
            #------------------------------
            # Database event log table
            #------------------------------
            $name = $this->taskConfig->getDbEventLogTable();
            $dbEventLogTable = new EtlEventLogTable($name);
            $this->logger->setDbEventLogTable($dbEventLogTable);
            
            $this->dbcon->createTable($dbEventLogTable, true);
        }

        #-------------------------------------------------------------------
        # If this is NOT an SQL-only task, process the transformation rules
        #-------------------------------------------------------------------
        if (!$this->isSqlOnlyTask()) {
            $this->schema = $this->processTransformationRules();
        } else {
            $this->schema = new Schema();
        }
        
        # if ($this->taskConfig->getDbLogging()) {
        if ($this->taskConfig->getDbLogging() && !($this->dbcon instanceof CsvDbConnection)) {
            $this->schema->setDbLogTable($dbLogTable);
            $this->schema->setDbEventLogTable($dbEventLogTable);
        }

        #print "\n\n-----------------------------------------------------SCHEMA:=======\n";
        #print $this->schema->toString();
    }


    protected function setUpRedCapAccess()
    {
        #-----------------------------------------------------------
        # Create RedCap object to use for getting REDCap projects
        #-----------------------------------------------------------
        $apiUrl = $this->taskConfig->getRedCapApiUrl();
        $superToken = null; // There is no need to create projects, so this is not needed
        $sslVerify  = $this->taskConfig->getSslVerify();
        $caCertFile = $this->taskConfig->getCaCertFile();

        if (empty($redcapProjectClass)) {
            $redcapProjectClass = EtlRedCapProject::class;
        }

        # Callback function for use in the RedCap class so
        # that project objects retrieved will have class
        # EtlRedCapProject, which has extensions for REDCapETL.
        $callback = function (
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        ) use ($redcapProjectClass) {
            return new $redcapProjectClass(
                $apiUrl,
                $apiToken,
                $sslVerify,
                $caCertificateFile,
                $errorHandler,
                $connection
            );
        };
        
        try {
            $redCap = new RedCap($apiUrl, $superToken, $sslVerify, $caCertFile);
            $redCap->setProjectConstructorCallback($callback);
        } catch (PhpCapException $exception) {
            $message = 'Unable to set up access to REDCap';
            throw new EtlException($message, EtlException::PHPCAP_ERROR, $exception);
        }


        #----------------------------------------------------------------
        # Get the project that has the actual data
        #----------------------------------------------------------------
        $dataToken = $this->taskConfig->getDataSourceApiToken();
        try {
            $this->dataProject = $redCap->getProject($dataToken);
        } catch (PhpCapException $exception) {
            $message = 'Could not access REDCap project';
            throw new EtlException($message, EtlException::PHPCAP_ERROR, $exception);
        }
    }
    

    /**
     * @return boolean true if this task has SQL only (no ETL), and false otherwise.
     */
    public function isSqlOnlyTask()
    {
        $isSqlOnly = false;

        $apiUrl   = $this->taskConfig->getRedCapApiUrl();
        $apiToken = $this->taskConfig->getDataSourceApiToken();

        $dbConnection      = $this->taskConfig->getDbConnection();
        $preProcessingSql  = $this->taskConfig->getPreProcessingSql();
        $postProcessingSql = $this->taskConfig->getPostProcessingSql();

        if (empty($apiUrl) || empty($apiToken)) {
            if (!empty($dbConnection)) {
                if (!empty($preProcessingSql) || !empty($postProcessingSql)) {
                    $isSqlOnly = true;
                }
            }
        }

        return $isSqlOnly;
    }

    /**
     * Gets the task ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the database ID string. This method depends on database connections
     * being specified consistently in task configurations. For example, for a given
     * database that uses the default port number, either all references to
     * that database should not include a port number, or all should include
     * the default port number.
     *
     * @return string a database ID that is used to check for database equivalence.
     */
    public function getDbId()
    {
        return $this->dbcon->getId();
    }


    /**
     * Parses the transformation rules, and creates a Schema object that describes
     * the schema of the database where the extracted data will be loaded.
     *
     * @return Schema the database schema generated from the transformation rules.
     */
    public function processTransformationRules()
    {
        $rulesText = '';

        #-----------------------------------------------------------------------------
        # If auto-generated rules were specified, generate the rules,
        # otherwise, get the from the task configuration
        #-----------------------------------------------------------------------------
        if ($this->taskConfig->getTransformRulesSource() === TaskConfig::TRANSFORM_RULES_DEFAULT) {
            $rulesText = $this->autoGenerateRules();
        } else {
            $rulesText = $this->taskConfig->getTransformationRules();
        }

        $tablePrefix = $this->taskConfig->getTablePrefix();
        $schemaGenerator = new SchemaGenerator($this->dataProject, $this->taskConfig, $this->logger, $this->id);

        list($schema, $parseResult) = $schemaGenerator->generateSchema($rulesText);

        $this->transformationRulesText = $rulesText;

        # print "\n\n----------------------------------------------------------------------------\n";
        # print "TASK {$this->getName()}\n";
        # print "\n".($schema->toString())."\n";

        return $schema;
    }

    /**
     * Automatically generates the data transformation rules.
     *
     * @param boolean $addFormCompleteFields indicates if a form complete field should be added to each table.
     *
     * @param boolean $addDagFields indicates if a DAG (Data Access Group) field should be added to each table.
     *
     * @param boolean $addFileFields indicates if file fields should be added.
     *
     * @return string the rules text
     */
    public function autoGenerateRules(
        $addFormCompleteFields = false,
        $addDagFields = false,
        $addFileFields = false,
        $addSurveyFields = false,
        $removeNotesFields = false,
        $removeIdentifierFields = false,
        $combineNonRepeatingFields = false,
        $nonRepeatingFieldsTable = ''
    ) {
        if (!isset($this->dataProject)) {
            $message = 'No data project was found.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        $taskConfig = $this->taskConfig;

        $addFormCompleteFields     = $addFormCompleteFields     || $taskConfig->getAutogenIncludeCompleteFields();
        $addDagFields              = $addDagFields              || $taskConfig->getAutogenIncludeDagFields();
        $addFileFields             = $addFileFields             || $taskConfig->getAutogenIncludeFileFields();
        $addSurveyFields           = $addSurveyFields           || $taskConfig->getAutogenIncludeSurveyFields();
        $removeNotesFields         = $removeNotesFields         || $taskConfig->getAutogenRemoveNotesFields();
        $removeIdentifierFields    = $removeIdentifierFields    || $taskConfig->getAutogenRemoveIdentifierFields();
        $combineNonRepeatingFields = $combineNonRepeatingFields
            || $taskConfig->getAutogenCombineNonRepeatingFields();

        if ($combineNonRepeatingFields) {
            if (empty($nonRepeatingFieldsTable)) {
                $nonRepeatingFieldsTable = $taskConfig->getAutogenNonRepeatingFieldsTable();
            }
        } else {
            $nonRepeatingFieldsTable = '';
        }

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate(
            $this->dataProject,
            $addFormCompleteFields,
            $addDagFields,
            $addFileFields,
            $addSurveyFields,
            $removeNotesFields,
            $removeIdentifierFields,
            $combineNonRepeatingFields,
            $nonRepeatingFieldsTable
        );
        return $rulesText;
    }



    /**
     * Reads all records from the RedCapEtl project, transforms them
     * into Rows, and loads the rows into the target database.
     *
     * Reads records out of REDCap in batches in order to reduce the likelihood
     * of causing memory issues on the Application server or Database server.
     *
     * These three steps are joined together at this level so that
     * the data from REDCap can be worked on in batches.
     *
     * @return int the number of record IDs processed.
     */
    public function extractTransformLoad()
    {
        $startEtlTime = microtime(true);

        #--------------------------------------------------
        # Extract the record ID batches
        #--------------------------------------------------
        $extractFilterLogic = $this->schema->getExtractFilterLogic();

        $startExtractTime = microtime(true);
        $recordIdBatches = $this->dataProject->getRecordIdBatches(
            (int) $this->taskConfig->getBatchSize(),
            $extractFilterLogic
        );
        $endExtractTime = microtime(true);
        $this->extractTime += $endExtractTime - $startExtractTime;

        # Count and log the number of record IDs found
        $recordIdCount = 0;
        foreach ($recordIdBatches as $recordIdBatch) {
            $recordIdCount += count($recordIdBatch);
        }
        $this->logger->log("Number of record_ids found: ". $recordIdCount);

        #--------------------------------------------------------------
        # Foreach record_id, get all REDCap records for that record_id.
        # There will be one record for each event for each record_id
        #--------------------------------------------------------------
        $recordEventsCount = 0;
                   
        #-------------------------------------------------------
        # For each batch of data, extract, transform, and load
        #-------------------------------------------------------
        foreach ($recordIdBatches as $recordIdBatch) {
            #---------------------------------
            # Extract the data from REDCap
            #---------------------------------
            $startExtractTime = microtime(true);
            $recordBatch = $this->dataProject->getRecordBatch($recordIdBatch);
            $endExtractTime = microtime(true);
            $this->extractTime += $endExtractTime - $startExtractTime;

            if ($this->taskConfig->getExtractedRecordCountCheck()) {
                if (count($recordBatch) < count($recordIdBatch)) {
                    $message = "Attempted to retrieve ".count($recordIdBatch)." records, but only "
                        .count($recordBatch)." were actually retrieved."
                        ." This error can be caused by a very large batch size. If you are using a large"
                        ." batch size (1,000 or greater), try reducing it to 500 or less."
                        ." This error could also be caused by records being deleted while the ETL process"
                        ." is running."
                        ." You can turn this error check off by setting the "
                        .ConfigProperties::EXTRACTED_RECORD_COUNT_CHECK." to false.";
                    $code =  EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                } elseif (count($recordBatch) > count($recordIdBatch)) {
                    $message = "Attempted to retrieve ".count($recordIdBatch)." records, but "
                        .count($recordBatch)." were actually retrieved."
                        ." This error could be caused by records being added while the"
                        ." ETL process is running."
                        ." You can turn this error check off by setting the "
                        .ConfigProperties::EXTRACTED_RECORD_COUNT_CHECK." to false.";
                    $code =  EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                }
            }

            #print "\n\n";
            #print $this->schema->toString();
            #print "\n\n";

            foreach ($recordBatch as $recordId => $records) {
                $recordEventsCount += count($records);

                #-----------------------------------
                # Transform the data
                #-----------------------------------
                $startTransformTime = microtime(true);
                # For each root table, because processing will be done
                # recursively to the child tables
                foreach ($this->schema->getRootTables() as $rootTable) {
                    // Transform the records for this record_id into rows
                    $this->transform($rootTable, $records, '', '');
                }
                $endTransformTime = microtime(true);
                $this->transformTime += $endTransformTime - $startTransformTime;
            }

            #print("\n\n===============================================================\n");
            #print("\n\nSCHEMA\n{$this->schema->toString()}\n\n");
            #print("\n\n===============================================================\n");
            
            #-------------------------------------
            # Load the data into the database
            #-------------------------------------
            $startLoadTime = microtime(true);
            foreach ($this->schema->getTables() as $table) {
                # Single row storage (stores one row at a time):
                # foreach row, load it
                $this->loadTableRowsEfficiently($table);
            }
            $endLoadTime = microtime(true);
            $this->loadTime += $endLoadTime - $startLoadTime;
        }

        $endEtlTime = microtime(true);
        $this->logger->logToFile('Extract time:   '.$this->extractTime.' seconds');
        $this->logger->logToFile('Transform time: '.$this->transformTime.' seconds');
        $this->logger->logToFile('Load time:      '.$this->loadTime.' seconds');
        $this->logger->logToFile('ETL total time: '.($endEtlTime - $startEtlTime).' seconds');

        $this->reportRows();

        $this->logger->log("Number of record events transformed: ". $recordEventsCount);
    
        return $recordIdCount;
    }


    /**
     * Transform the values from REDCap in the specified records into
     * values in the specified table and its child tables objects.
     * The rows are added to the Table objects as data rows, and NOT
     * stored in the database at this point.
     *
     * @param Table $table the table (and its child tables) in which the
     *        records values are being stored.
     *
     * @param array $records array of records for a single record_id. If there
     *     is more than one record, they represent multiple
     *     events or repeating instruments (forms).
     *
     * @param string $foreignKey if set, represents the value to use as the
     *     foreign key for any records created.
     *
     * @param string $suffix If set, represents the suffix used for the parent table.
     */
    protected function transform($table, $records, $foreignKey, $suffix)
    {
        $tableName = $table->getName();
        $calcFieldIgnorePattern = $this->taskConfig->getCalcFieldIgnorePattern();
        $ignoreEmptyIncompleteForms = $this->taskConfig->getIgnoreEmptyIncompleteForms();

        foreach ($table->rowsType as $rowType) {
            // Look at row_event for this table
            switch ($rowType) {
                #-------------------------------------------------------------------
                # ROOT Table - this case should only occur for non-recursive calls,
                # since a root table can't be a child of another table
                #-------------------------------------------------------------------
                case RowsType::ROOT:
                    #------------------------------------------------------------------------
                    # Root tables are for fields that have a 1:1 mapping with the record ID,
                    # so stop processing once a record for the record ID being processed is
                    # found that has at least some data for the root table.
                    # For the child tables, which, in general, have a m:1 relationship
                    # with the record ID, process all records for this record ID.
                    #------------------------------------------------------------------------
                    $rootRecordFound = false;
                    foreach ($records as $record) {
                        # Add the REDCap data source to the record
                        $record[RedCapEtl::COLUMN_DATA_SOURCE] = $this->id;
                        $primaryKey = $table->createRow(
                            $record,
                            $foreignKey,
                            $suffix,
                            $rowType,
                            $calcFieldIgnorePattern,
                            $ignoreEmptyIncompleteForms,
                            $this->getDbId()
                        );

                        # If row creation succeeded:
                        if ($primaryKey) {
                            if (!$rootRecordFound) {
                                $rootRecordFound = true;
                                foreach ($table->getChildren() as $childTable) {
                                    $this->transform($childTable, $records, $primaryKey, $suffix);
                                }
                                if ($table->isRecordIdTable()) {
                                    # If this is a record ID table stop processing; don't store multiple rows
                                    break;
                                }
                            } else {
                                # A record with values for the root table was already found,
                                # so there are multiple values per record ID for at least one
                                # field in the root table.
                                if (!array_key_exists($tableName, $this->rootTablesWithMultiValues)) {
                                    # Only print warning message once for each table
                                    $message = 'WARNING: ROOT table "'.$tableName.'" has fields'
                                        .' that have multiple values per record ID in REDCap.'
                                        .' ROOT tables are intended for fields that only have'
                                        .' one value per record ID.';
                                    $this->logger->log($message);
                                    $this->rootTablesWithMultiValues[$tableName] = true;
                                }
                            }
                        }
                    }
                    break;

                // If events or repeatable forms or repeating events
                case RowsType::BY_EVENTS:
                case RowsType::BY_REPEATING_INSTRUMENTS:
                case RowsType::BY_REPEATING_EVENTS:
                    // Foreach Record (i.e., foreach event or repeatable form or repeatable event)
                    foreach ($records as $record) {
                        # Add the REDCap data source to the record
                        $record[RedCapEtl::COLUMN_DATA_SOURCE] = $this->id;
                        $primaryKey = $table->createRow(
                            $record,
                            $foreignKey,
                            $suffix,
                            $rowType,
                            $calcFieldIgnorePattern,
                            $ignoreEmptyIncompleteForms,
                            $this->getDbId()
                        );

                        if ($primaryKey) {
                            foreach ($table->getChildren() as $childTable) {
                                $this->transform($childTable, array($record), $primaryKey, $suffix);
                            }
                        }
                    }
                    break;

                // If suffix
                case RowsType::BY_SUFFIXES:
                    // Foreach Suffix
                    foreach ($table->rowsSuffixes as $newSuffix) {
                        # Add the REDCap data source to the record
                        $records[0][RedCapEtl::COLUMN_DATA_SOURCE] = $this->id;
                        $primaryKey = $table->createRow(
                            $records[0],
                            $foreignKey,
                            $suffix.$newSuffix,
                            $rowType,
                            $calcFieldIgnorePattern,
                            $ignoreEmptyIncompleteForms,
                            $this->getDbId()
                        );

                        if ($primaryKey) {
                            foreach ($table->getChildren() as $childTable) {
                                $this->transform($childTable, $records, $primaryKey, $suffix.$newSuffix);
                            }
                        }
                    }
                    break;

                // If events and suffix
                case RowsType::BY_EVENTS_SUFFIXES:
                    // Foreach Record (i.e., foreach event)
                    foreach ($records as $record) {
                        // Foreach Suffix
                        foreach ($table->rowsSuffixes as $newSuffix) {
                            # Add the REDCap data source to the record
                            $record[RedCapEtl::COLUMN_DATA_SOURCE] = $this->id;
                            $primaryKey = $table->createRow(
                                $record,
                                $foreignKey,
                                $suffix.$newSuffix,
                                $rowType,
                                $calcFieldIgnorePattern,
                                $ignoreEmptyIncompleteForms,
                                $this->getDbId()
                            );

                            if ($primaryKey) {
                                foreach ($table->getChildren() as $childTable) {
                                    $this->transform($childTable, array($record), $primaryKey, $suffix.$newSuffix);
                                }
                            }
                        }
                    }
                    break;
            }
        }
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
                $this->log("Error storing row in '".$table->getName()."': ".$this->dbcon->errorString);
            }
        }

        // Add to summary how many rows created for this table
        if (array_key_exists($table->getName(), $this->rowsLoadedForTable)) {
            $this->rowsLoadedForTable[$table->getName()] += $table->getNumRows();
        } else {
            $this->rowsLoadedForTable[$table->getName()] = $table->getNumRows();
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
        if (array_key_exists($table->getName(), $this->rowsLoadedForTable)) {
            $this->rowsLoadedForTable[$table->getName()] += $table->getNumRows();
        } else {
            $this->rowsLoadedForTable[$table->getName()] = $table->getNumRows();
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
            $this->logger->log($msg);
        }

        return true;
    }


    public function runPreProcessingSql()
    {
        try {
            $sql = $this->taskConfig->getPreProcessingSql();
            if (!empty($sql)) {
                $this->dbcon->processQueries($sql);
            }

            $sqlFile = $this->taskConfig->getPreProcessingSqlFile();
            if (!empty($sqlFile)) {
                $this->dbcon->processQueryFile($sqlFile);
            }
        } catch (\Exception $exception) {
            $message = 'Pre-processing SQL error: '.$exception->getMessage();
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
    }

    public function runPostProcessingSql()
    {
        try {
            $sql = $this->taskConfig->getPostProcessingSql();
            if (!empty($sql)) {
                $this->dbcon->processQueries($sql);
            }

            $sqlFile = $this->taskConfig->getPostProcessingSqlFile();
            if (!empty($sqlFile)) {
                $this->dbcon->processQueryFile($sqlFile);
            }
        } catch (\Exception $exception) {
            $message = 'Post-processing SQL error: '.$exception->getMessage();
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
    }

    /**
     * Logs job info from taskConfig, if any
     */
    public function logJobInfo()
    {
        if (!empty($this->taskConfig)) {
            $redcapApiUrl = $this->taskConfig->getRedCapApiUrl();
            $this->logger->log("REDCap API URL: ".$redcapApiUrl);

            $projectInfo = $this->dataProject->exportProjectInfo();
            if (!empty($projectInfo)) {
                $this->logger->log("Project ID: ".$projectInfo['project_id']);
                $this->logger->log("Project title: ".$projectInfo['project_title']);
            }

            $configName  = $this->taskConfig->getConfigName();
            $configFile  = $this->taskConfig->getPropertiesFile();
            if (!empty($configName)) {
                $this->logger->log("TaskConfig: ".$configName);
            } elseif (!empty($configFile)) {
                $this->logger->log("TaskConfig: ".$configFile);
            }

            $cronJob = $this->taskConfig->getCronJob();
            if (!empty($cronJob)) {
                if (strcasecmp($cronJob, 'true') === 0) {
                    $this->logger->log('Job type: scheduled');
                } else {
                    $this->logger->log('Job type: on demand');
                }
            }
        }
    }

    public function getRedCapApiUrl()
    {
        $apiUrl = $this->taskConfig->getRedCapApiUrl();
        return $apiUrl;
    }

    public function getRedCapProjectInfo()
    {
        $projectInfo = $this->dataProject->exportProjectInfo();
        return $projectInfo;
    }

    public function getRedCapMetadata()
    {
        $metadata = $this->dataProject->exportMetadata();
        return $metadata;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLogger()
    {
        return $this->logger;
    }
    
    public function getTaskConfig()
    {
        return $this->taskConfig;
    }
    
    public function getDataProject()
    {
        return $this->dataProject;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getDbConnection()
    {
        return $this->dbcon;
    }
    
    public function setDbConnection($dbConnection)
    {
        $this->dbcon = $dbConnection;
    }
    
    public function getWorkflow()
    {
        return $this->workflow;
    }

    public function getTransformationRulesText()
    {
        return $this->transformationRulesText;
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
}

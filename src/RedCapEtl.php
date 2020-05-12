<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCap;
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

    /** @var EtlRedCapProject the project that has the data to extract */
    protected $dataProject;
    
    protected $logger;

    protected $schema;

    protected $rowsLoadedForTable = array();
  
    protected $dbcon;

    private $logFile;

    private $errorHandler;

    private $app;

    private $recordIdFieldName;   // The field name for the record ID
                                  // for the data project in REDCap

    private $configuration;

    /** @var array map where the keys represent root tables that have
     *     fields that have multiple rows of data per record ID.
     *     Root tables are intended for fields that have a 1:1 mapping
     *     with the record ID.
     */
    private $rootTablesWithMultiValues;


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

        $this->rootTablesWithMultiValues = array();

        $this->configuration = new Configuration(
            $logger,
            $properties
        );

        $this->logger = $logger;
        $this->logger->setConfiguration($this->configuration);
        
        #---------------------------------------------------------
        # Set time limit
        #---------------------------------------------------------
        $timeLimit = $this->configuration->getTimeLimit();
        if (isset($timeLimit) && trim($timeLimit) !== '') {
            set_time_limit($timeLimit);
        } else {
            set_time_limit(0);   # no time limit
        }

        #---------------------------------------------------------
        # Set timezone if one was specified
        #---------------------------------------------------------
        $timezone = $this->configuration->getTimezone();
        if (isset($timezone) && trim($timezone) !== '') {
            date_default_timezone_set($timezone);
        }


        #-----------------------------------------------------------
        # Create RedCap object to use for getting REDCap projects
        #-----------------------------------------------------------
        $apiUrl = $this->configuration->getRedCapApiUrl();
        $superToken = null; // There is no need to create projects, so this is not needed
        $sslVerify  = $this->configuration->getSslVerify();
        $caCertFile = $this->configuration->getCaCertFile();

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
            $message = 'Unable to set up RedCap object.';
            throw new EtlException($message, EtlException::PHPCAP_ERROR, $exception);
        }


        #----------------------------------------------------------------
        # Get the project that has the actual data
        #----------------------------------------------------------------
        $dataToken = $this->configuration->getDataSourceApiToken();
        try {
            $this->dataProject = $redCap->getProject($dataToken);
        } catch (PhpCapException $exception) {
            $message = 'Could not get data project.';
            throw new EtlException($message, EtlException::PHPCAP_ERROR, $exception);
        }

        #-----------------------------------------
        # Initialize the schema
        #-----------------------------------------
        $this->schema = new Schema();
                

        #---------------------------------------------------
        # Create a database connection for the database
        # where the transformed REDCap data will be stored
        #---------------------------------------------------
        $dbconfactory = new DbConnectionFactory();
        $this->dbcon = $dbconfactory->createDbConnection(
            $this->configuration->getDbConnection(),
            $this->configuration->getDbSsl(),
            $this->configuration->getDbSslVerify(),
            $this->configuration->getCaCertFile(),
            $this->configuration->getTablePrefix(),
            $this->configuration->getLabelViewSuffix()
        );

        #-------------------------------------------------
        # Set up database logging
        #-------------------------------------------------
        if ($this->configuration->getDbLogging()) {
            $this->logger->setDbConnection($this->dbcon);
            $this->logger->setDbLogging(true);
            
            # Add information to logger that is used by database logging
            
        
            #----------------------------------------
            # (Main) database log table
            #----------------------------------------
            $name = $this->configuration->getDbLogTable();
            $dbLogTable = new EtlLogTable($name);
            $this->logger->setDbLogTable($dbLogTable);
            
            $this->dbcon->createTable($dbLogTable, true);
                
            $this->schema->setDbLogTable($dbLogTable);
            
            $this->logger->logToDatabase();
            
            #------------------------------
            # Database event log table
            #------------------------------
            $name = $this->configuration->getDbEventLogTable();
            $dbEventLogTable = new EtlEventLogTable($name);
            $this->logger->setDbEventLogTable($dbEventLogTable);
            
            $this->dbcon->createTable($dbEventLogTable, true);
            
            $this->schema->setDbEventLogTable($dbEventLogTable);
        }
    }


    /**
     * Parses the transformation rules, and creates a Schema object that describes
     * the schema of the database where the extracted data will be loaded.
     *
     * @return array The first element is the parse result code, and the second
     *     is a string that contains any info, warning and error messages that
     *     were generated by the parsing.
     */
    public function processTransformationRules()
    {
        $rulesText = '';

        #-----------------------------------------------------------------------------
        # If auto-generated rules were specified, generate the rules,
        # otherwise, get the from the configuration
        #-----------------------------------------------------------------------------
        if ($this->configuration->getTransformRulesSource() === Configuration::TRANSFORM_RULES_DEFAULT) {
            $rulesText = $this->autoGenerateRules();
        } else {
            $rulesText = $this->configuration->getTransformationRules();
        }
        
        $tablePrefix = $this->configuration->getTablePrefix();
        $schemaGenerator = new SchemaGenerator($this->dataProject, $this->configuration, $this->logger);

        list($schema, $parseResult) = $schemaGenerator->generateSchema($rulesText);

        ###print "\n".($schema->toString())."\n";

        $this->schema      = $schema;

        return $parseResult;
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
    public function autoGenerateRules($addFormCompleteFields = false, $addDagFields = false, $addFileFields = false)
    {
        if (!isset($this->dataProject)) {
            $message = 'No data project was found.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate(
            $this->dataProject,
            $addFormCompleteFields,
            $addDagFields,
            $addFileFields
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

        $extractTime   = 0.0;
        $transformTime = 0.0;
        $loadTime      = 0.0;

        #--------------------------------------------------
        # Extract the record ID batches
        #--------------------------------------------------
        $startExtractTime = microtime(true);
        $recordIdBatches = $this->dataProject->getRecordIdBatches(
            (int) $this->configuration->getBatchSize()
        );
        $endExtractTime = microtime(true);
        $extractTime += $endExtractTime - $startExtractTime;

        # Count and log the number of record IDs found
        $recordIdCount = 0;
        foreach ($recordIdBatches as $recordIdBatch) {
            $recordIdCount += count($recordIdBatch);
        }
        $this->log("Number of record_ids found: ". $recordIdCount);

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
            $extractTime += $endExtractTime - $startExtractTime;

            if ($this->configuration->getExtractedRecordCountCheck()) {
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
                $transformTime += $endTransformTime - $startTransformTime;
            }

            #print("\n\n===============================================================\n");
            #print("\n\nSCHEMA MAP\n{$this->schema->toString()}\n\n");
            #print("\n\n===============================================================\n");
            
            #-------------------------------------
            # Load the data into the database
            #-------------------------------------
            $startLoadTime = microtime(true);
            foreach ($this->schema->getTables() as $table) {
                # Single row storage (stores one row at a time):
                # foreach row, load it
                ### $this->loadTableRows($table);
                $this->loadTableRowsEfficiently($table);
            }
            #####$this->loadRows();
            $endLoadTime = microtime(true);
            $loadTime += $endLoadTime - $startLoadTime;
        }

        $endEtlTime = microtime(true);
        $this->logToFile('Extract time:   '.$extractTime.' seconds');
        $this->logToFile('Transform time: '.$transformTime.' seconds');
        $this->logToFile('Load time:      '.$loadTime.' seconds');
        $this->logToFile('ETL total time: '.($endEtlTime - $startEtlTime).' seconds');

        $this->reportRows();

        $this->log("Number of record events transformed: ". $recordEventsCount);
    
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
        $calcFieldIgnorePattern = $this->configuration->getCalcFieldIgnorePattern();

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
                        $primaryKey =
                            $table->createRow($record, $foreignKey, $suffix, $rowType, $calcFieldIgnorePattern);

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
                        $primaryKey =
                            $table->createRow($record, $foreignKey, $suffix, $rowType, $calcFieldIgnorePattern);

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
                        $primaryKey = $table->createRow(
                            $records[0],
                            $foreignKey,
                            $suffix.$newSuffix,
                            $rowType,
                            $calcFieldIgnorePattern
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
                            $primaryKey = $table->createRow(
                                $record,
                                $foreignKey,
                                $suffix.$newSuffix,
                                $rowType,
                                $calcFieldIgnorePattern
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
     * Creates the database tables where the data will be loaded, and
     * creates views for the tables that have multiple choice
     * fields that have labels, instead of values, for those fields.
     * If there are existing
     * tables, then those tables are dropped first.
     */
    protected function createLoadTables()
    {
        // foreach table, replace it with an empty table
        $tables = $this->schema->getTables();

        #---------------------------------------------------------------------
        # Drop tables in the reverse order of how they were defined, and drop
        # the label view (if any) for a table before dropping the table
        #---------------------------------------------------------------------
        foreach (array_reverse($tables) as $table) {
            if ($table->usesLookup === true) {
                $ifExists = true;
                $this->dbcon->dropLabelView($table, $ifExists);
            }

            $ifExists = true;
            $this->dbcon->dropTable($table, $ifExists);
        }

        #------------------------------------------------------
        # Create the tables in the order they were defined
        #------------------------------------------------------
        foreach ($tables as $table) {
            $ifExists = false;
            $this->dbcon->createTable($table, $ifExists);

            $msg = "Created table '".$table->name."'";

            // If this table uses the Lookup table, create a view
            if ($table->usesLookup === true) {
                $this->dbcon->replaceLookupView($table, $this->schema->getLookupTable());
                $msg .= '; Lookup table created';
            }

            $this->log($msg);
        }

        if ($this->configuration->getCreateLookupTable()) {
            $lookupTable = $this->schema->getLookupTable();
            $this->dbcon->replaceTable($lookupTable);
            $this->loadTableRows($lookupTable);
        }
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
        $this->log('REDCap-ETL version '.Version::RELEASE_NUMBER);
        $this->log('REDCap version '.$this->dataProject->exportRedCapVersion());
        $this->logJobInfo();
        $this->log("Starting processing.");

        list($parseStatus, $result) = $this->processTransformationRules();

        if ($parseStatus === SchemaGenerator::PARSE_ERROR) {
            $message = "Transformation rules not parsed. Processing stopped.";
            throw new EtlException($message, EtlException::INPUT_ERROR);
        } else {
            $this->runPreProcessingSql();

            $this->createLoadTables();
            $numberOfRecordIds = $this->extractTransformLoad();

            $this->runPostProcessingSql();
                
            $this->log(self::PROCESSING_COMPLETE);
            $this->logger->logEmailSummary();
        }

        return $numberOfRecordIds;
    }

    protected function runPreProcessingSql()
    {
        try {
            $sql = $this->configuration->getPreProcessingSql();
            if (!empty($sql)) {
                $this->dbcon->processQueries($sql);
            }

            $sqlFile = $this->configuration->getPreProcessingSqlFile();
            if (!empty($sqlFile)) {
                $this->dbcon->processQueryFile($sqlFile);
            }
        } catch (\Exception $exception) {
            $message = 'Pre-processing SQL error: '.$exception->getMessage();
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
    }

    protected function runPostProcessingSql()
    {
        try {
            $sql = $this->configuration->getPostProcessingSql();
            if (!empty($sql)) {
                $this->dbcon->processQueries($sql);
            }

            $sqlFile = $this->configuration->getPostProcessingSqlFile();
            if (!empty($sqlFile)) {
                $this->dbcon->processQueryFile($sqlFile);
            }
        } catch (\Exception $exception) {
            $message = 'Post-processing SQL error: '.$exception->getMessage();
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
    }


    /**
     * Logs job info from configuration, if any
     */
    public function logJobInfo()
    {
        if (!empty($this->configuration)) {
            $redcapApiUrl = $this->configuration->getRedCapApiUrl();
            $this->log("REDCap API URL: ".$redcapApiUrl);
            
            $projectInfo = $this->dataProject->exportProjectInfo();
            if (!empty($projectInfo)) {
                $this->log("Project ID: ".$projectInfo['project_id']);
                $this->log("Project title: ".$projectInfo['project_title']);
            }
            
            $configName  = $this->configuration->getConfigName();
            $configFile  = $this->configuration->getPropertiesFile();
            if (!empty($configName)) {
                $this->log("Configuration: ".$configName);
            } elseif (!empty($configFile)) {
                $this->log("Configuration: ".$configFile);
            }
                        
            $cronJob = $this->configuration->getCronJob();
            if (!empty($cronJob)) {
                if (strcasecmp($cronJob, 'true') === 0) {
                    $this->log('Job type: scheduled');
                } else {
                    $this->log('Job type: on demand');
                }
            }
        }
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
        $this->logger->logToFile($message, $this->logFile);
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }
    
    public function getDataProject()
    {
        return $this->dataProject;
    }
}

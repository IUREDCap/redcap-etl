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
     */
    public function autoGenerateRules($addFormCompleteField = false)
    {
        if (!isset($this->dataProject)) {
            $message = 'No data project was found.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($this->dataProject, $addFormCompleteField);
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

            $dataExportRight = $this->configuration->getDataExportFilter();
            $this->filterRecordBatch($recordBatch, $dataExportRight);

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
     * Filters out data that is not allowed by the specified data export right.
     * A data export right of "3" means that all fields labeled as identifiers
     * will be set to blank. A data export right of "2" means that all
     * non-de-identified data values will be set to blank, which includes
     * free-form text fields (notes), date and time fields, and fields
     * labeled as identifiers.
     */
    protected function filterRecordBatch(& $recordBatch, $dataExportRight)
    {
        if ($dataExportRight == 2 || $dataExportRight == 3) {
            #---------------------------------------------------------
            # Create a field map from field name to field information
            #---------------------------------------------------------
            $fields     = $this->dataProject->getMetadata();
            $primaryKey = $this->dataProject->getPrimaryKey();

            $fieldMap = array();
            foreach ($fields as $field) {
                $fieldMap[$field['field_name']] = $field;
            }

            $fieldNames = $this->dataProject->exportFieldNames();
            foreach ($fieldNames as $fieldName) {
                #if (original 'original_field_name', 'export_field_name')
                $originalName = $fieldName['original_field_name'];
                $exportName   = $fieldName['export_field_name'];

                # If the export field name is not in the field map, but the original field name is,
                # then create a new entry in the field map for the export field name with the same
                # value as the original field name.
                if (!array_key_exists($exportName, $fieldMap) && array_key_exists($originalName, $fieldMap)) {
                    $fieldMap[$exportName] = $fieldMap[$originalName];
                }
            }

            #----------------------------------------------------------------
            # Blank out fields according to the specified data export right
            #----------------------------------------------------------------
            foreach ($recordBatch as & $recordGroup) {
                foreach ($recordGroup as & $record) {
                    foreach ($record as $fieldName => $fieldValue) {
                        if (array_key_exists($fieldName, $fieldMap)) {
                            $field = $fieldMap[$fieldName];

                            $identifier     = $field['identifier'];
                            $validationType = trim($field['text_validation_type_or_show_slider_number']);
                            $fieldType      = $field['field_type'];

                            # blank out fields labeled as identifier
                            if ($identifier) {
                                $record[$fieldName] = '';
                            }

                            # If filtering out de-identified data (as defined for REDCap data export)
                            if ($dataExportRight == 2) {
                                if (strcasecmp($fieldName, $primaryKey) !== 0
                                    && $fieldType ===  'text'
                                    && empty($validationType)) {
                                    # text field without a validation type that is NOT the record ID field
                                    $record[$fieldName] = '';
                                } elseif ($fieldType == 'notes') {
                                    $record[$fieldName] = '';
                                } elseif ($fieldType == 'text' && substr($validationType, 0, 4) == 'date') {
                                    # text field with validation type that starts with 'date',
                                    # i.e., a date or datetime field
                                    $record[$fieldName] = '';
                                }
                            }
                        }
                    }
                } // foreach record group as record
            } // foreach record batch as record group
        }
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
        foreach ($table->rowsType as $rowType) {
            // Look at row_event for this table
            switch ($rowType) {
                // If root
                case RowsType::ROOT:
                    $this->createRowAndRecurse($table, $records, $foreignKey, $suffix, $rowType);
                    break;

                // If events
                case RowsType::BY_EVENTS:
                    // Foreach Record (i.e., foreach event)
                    foreach ($records as $record) {
                        $this->createRowAndRecurse($table, array($record), $foreignKey, $suffix, $rowType);
                    }
                    break;

                // If repeatable forms
                case RowsType::BY_REPEATING_INSTRUMENTS:
                    // Foreach Record (i.e., foreach repeatable form)
                    foreach ($records as $record) {
                        $this->createRowAndRecurse($table, array($record), $foreignKey, $suffix, $rowType);
                    }
                    break;

                // If repeatable events
                case RowsType::BY_REPEATING_EVENTS:
                    // Foreach Record (i.e., foreach repeatable event)
                    foreach ($records as $record) {
                        $this->createRowAndRecurse($table, array($record), $foreignKey, $suffix, $rowType);
                    }
                    break;

                // If suffix
                case RowsType::BY_SUFFIXES:
                    // Foreach Suffix
                    foreach ($table->rowsSuffixes as $newSuffix) {
                        $this->createRowAndRecurse($table, $records, $foreignKey, $suffix.$newSuffix, $rowType);
                    }
                    break;

                // If events and suffix
                case RowsType::BY_EVENTS_SUFFIXES:
                    // Foreach Record (i.e., foreach event)
                    foreach ($records as $record) {
                        // Foreach Suffix
                        foreach ($table->rowsSuffixes as $newSuffix) {
                            $this->createRowAndRecurse(
                                $table,
                                array($record),
                                $foreignKey,
                                $suffix.$newSuffix,
                                $rowType
                            );
                        }
                    }
                    break;
            }
        }
    }


    /**
     * See 'transform' function for explanation of variables.
     */
    protected function createRowAndRecurse($table, $records, $foreignKey, $suffix, $rowType)
    {
        // Create Row using 1st Record
        $calcFieldIgnorePattern = $this->configuration->getCalcFieldIgnorePattern();
        $primaryKey = $table->createRow($records[0], $foreignKey, $suffix, $rowType, $calcFieldIgnorePattern);

        // If primary key generated, recurse for child tables
        if ($primaryKey) {
            // Foreach child table
            foreach ($table->getChildren() as $childTable) {
                $this->transform($childTable, $records, $primaryKey, $suffix);
            }
        }
    }


    /** WORK IN PROGRESS
     *
     * Need to split of drop and create for workflows
     */
    public function dropLoadTables()
    {
        $tables = $this->schema->getTables();
        foreach ($tables as $table) {
            if ($table->usesLookup === true) {
                $ifExists = true;
                $this->dbcon->dropLabelView($table, $ifExists);
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
        foreach ($tables as $table) {
            if ($table->usesLookup === true) {
                $ifExists = true;
                $this->dbcon->dropLabelView($table, $ifExists);
            }

            $this->dbcon->replaceTable($table);

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
            $this->createLoadTables();
            $numberOfRecordIds = $this->extractTransformLoad();
                
            #----------------------------------------
            # Post-processing SQL
            #----------------------------------------
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

            $this->log(self::PROCESSING_COMPLETE);
            $this->logger->logEmailSummary();
        }

        return $numberOfRecordIds;
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

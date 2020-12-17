<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\PHPCap\RedCap;
use IU\PHPCap\PhpCapException;

use IU\REDCapETL\Database\DbConnection;
use IU\REDCapETL\Database\DbConnectionFactory;

use IU\REDCapETL\Schema\FieldTypeSpecifier;

/**
 * Class used to store ETL configuration information from
 * a configuration file or proprties arrary.
 */
class TaskConfig
{
    # Transform rules source values
    const TRANSFORM_RULES_TEXT    = '1';
    const TRANSFORM_RULES_FILE    = '2';
    const TRANSFORM_RULES_DEFAULT = '3';     // Auto-generate default rules

    # Default values
    const DEFAULT_AUTOGEN_INCLUDE_COMPLETE_FIELDS      = false;
    const DEFAULT_AUTOGEN_INCLUDE_DAG_FIELDS           = false;
    const DEFAULT_AUTOGEN_INCLUDE_FILE_FIELDS          = false;
    const DEFAULT_AUTOGEN_INCLUDE_SURVEY_FIELDS        = false;
    const DEFAULT_AUTOGEN_REMOVE_NOTES_FIELDS          = false;
    const DEFAULT_AUTOGEN_REMOVE_IDENTIFIER_FIELDS     = false;
    const DEFAULT_AUTOGEN_COMBINE_NON_REPEATING_FIELDS = false;
    const DEFAULT_AUTOGEN_NON_REPEATING_FIELDS_TABLE   = '';

    const DEFAULT_BATCH_SIZE          = 100;
    const DEFAULT_CREATE_LOOKUP_TABLE = false;

    const DEFAULT_DB_SSL             = true;
    const DEFAULT_DB_SSL_VERIFY      = false;

    const DEFAULT_DB_PRIMARY_KEYS    = true;
    const DEFAULT_DB_FOREIGN_KEYS    = true;

    const DEFAULT_DB_LOGGING         = true;
    const DEFAULT_DB_LOG_TABLE       = 'etl_log';
    const DEFAULT_DB_EVENT_LOG_TABLE = 'etl_event_log';

    const DEFAULT_EMAIL_ERRORS   = false;
    const DEFAULT_EMAIL_SUMMARY  = false;
    const DEFAULT_EMAIL_SUBJECT  = 'REDCap-ETL Error';

    const DEFAULT_GENERATED_INSTANCE_TYPE  = 'int';
    const DEFAULT_GENERATED_KEY_TYPE       = 'int';
    const DEFAULT_GENERATED_LABEL_TYPE     = 'varchar(255)';
    const DEFAULT_GENERATED_NAME_TYPE      = 'varchar(255)';
    const DEFAULT_GENERATED_RECORD_ID_TYPE = 'varchar(255)';
    const DEFAULT_GENERATED_SUFFIX_TYPE    = 'varchar(255)';

    const DEFAULT_IGNORE_EMPTY_INCOMPLETE_FORMS = false;

    const DEFAULT_LABEL_VIEW_SUFFIX = '_label_view';
    
    const DEFAULT_PRINT_LOGGING = true;
    
    const DEFAULT_TABLE_PREFIX      = '';   # i.e., No table prefix
    const DEFAULT_TIME_LIMIT        = 0;    # zero => no time limit

    private $logger;

    private $app;

    private $autogenIncludeCompleteFields;
    private $autogenIncludeDagFields;
    private $autogenIncludeFileFields;
    private $autogenIncludeSurveyFields;
    private $autogenRemoveNotesFields;
    private $autogenRemoveIdentifierFields;
    private $autogenCombineNonRepeatingFields;
    private $autogenNonRepeatingFieldsTable;

    private $batchSize;

    private $caCertFile;
    private $calcFieldIgnorePattern;
    
    private $configName;
    private $configOwner;
    
    private $createLookupTable;

    private $cronJob;
        
    private $dataSourceApiToken;
    private $dbConnection;
    private $dbSsl;
    private $dbSslVerify;

    private $dbPrimaryKeys;
    private $dbForeignKeys;

    private $dbLogging;
    private $dbLogTable;
    private $dbEventLogTable;

    private $extractedRecordCountCheck;

    private $generatedInstanceType;
    private $generatedKeyType;
    private $generatedLabelType;
    private $generatedNameType;
    private $generatedRecordIdType;
    private $generatedSuffixType;

    private $ignoreEmptyIncompleteForms;
    
    private $labelViewSuffix;
    private $lookupTableName;

    private $preProcessingSql;
    private $preProcessingSqlFile;
    private $postProcessingSql;
    private $postProcessingSqlFile;
    
    private $projectId;
    private $printLogging;

    private $redcapApiUrl;

    private $redcapMetadataTable;
    private $redcapProjectInfoTable;

    private $sslVerify;
    
    private $tablePrefix;
    private $timeLimit;
    private $timezone;

    private $transformationRules;
    private $transformRulesSource;

    private $properties;
    private $propertiesFile;

    private $emailErrors;
    private $emailSummary;
    private $emailFromAddres;
    private $emailSubject;
    private $emailToList;
    
    /** @var string the base directory used for relative paths specified
     *     in property values */
    private $baseDir;

    /** @var string the name of the task (only tasks within workflows will have names). */
    private $taskName;

    /**
     */
    public function __construct()
    {
        $this->logger = null;
        $this->app    = '';
        $this->propertiesFile = null;

        $this->taskName = '';

        #---------------------------------------------------------
        # Set default config properties values
        #---------------------------------------------------------
        $this->redcapApiUrl       = null;
        $this->dataSourceApiToken = null;

        $this->dbSsl           = self::DEFAULT_DB_SSL;

        $this->printLogging    = self::DEFAULT_PRINT_LOGGING;
        $this->logFile         = null;

        $this->dbLogging       = self::DEFAULT_DB_LOGGING;
        $this->dbLogTable      = self::DEFAULT_DB_LOG_TABLE;
        $this->dbEventLogTable = self::DEFAULT_DB_EVENT_LOG_TABLE;

        $this->emailErrors      = self::DEFAULT_EMAIL_ERRORS;
        $this->emailSummary     = self::DEFAULT_EMAIL_SUMMARY;
        $this->emailFromAddress = null;
        $this->emailToList      = null;
        $this->emailSubject     = self::DEFAULT_EMAIL_SUBJECT;

        $this->cronJob          = ''; # By default, make this blank

        $this->redcapMetadataTable    = MetadataTable::DEFAULT_NAME;
        $this->redcapProjectInfoTable = ProjectInfoTable::DEFAULT_NAME;

        $this->sslVerify        = true;

        $this->extractedRecordCountCheck = true;

        $this->transformRulesSource = null;
    }

    /**
     * Sets a TaskConfig object from either an array of properties
     * or a configuration file, * and updates the logger based on the
     * configuration information found.
     *
     * @param Logger $logger logger for information and errors
     *
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     *     If a properties file name string is used, then it is assumed
     *     to be a JSON file if the file name ends with .json, and a
     *     .ini file otherwise.
     *
     * @param string $baseDir the base directory to use for references to files
     *     in the properties. For example, if the base directory was specified as
     *     "/home/etluser/" and the post_sql_processing_file property was specified
     *     as "post.sql", then the file "/home/etluser/post.sql" would be used
     *     for the post-processing SQL commands.
     */
    public function set(& $logger, $properties, $taskName = '', $baseDir = null)
    {
        $this->logger = $logger;
        $this->app = $this->logger->getApp();
        $this->taskName = $taskName;

        #-----------------------------------------------------------------------------------------
        # Process the properties, which could be specified as an array or a file name (string)
        #-----------------------------------------------------------------------------------------
        if (empty($properties)) {
            # No properties specified
            $message = 'No properties or properties file was specified.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        } elseif (is_array($properties)) {
            # Properties specified as an array
            $this->properties = $properties;
        } elseif (is_string($properties)) {
            # Properties specified in a file
            $this->propertiesFile = trim($properties);
            $this->properties = self::getPropertiesFromFile($this->propertiesFile);
        }
        
        #-----------------------------------------------------
        # Set the base directory, wich is used for properties
        # that contain relative paths
        #-----------------------------------------------------
        if (isset($baseDir)) {
            $this->baseDir = $baseDir;
        } elseif (!empty($this->propertiesFile)) {
            $this->baseDir = dirname($this->propertiesFile);
        } else {
            $this->baseDir = realpath(__DIR__);
        }


        #-------------------------------------------
        # Print logging
        #-------------------------------------------
        if (array_key_exists(ConfigProperties::PRINT_LOGGING, $this->properties)) {
            $printLogging = $this->properties[ConfigProperties::PRINT_LOGGING];
            if ($printLogging === true || strcasecmp($printLogging, 'true') === 0 || $printLogging === '1') {
                $this->printLogging = true;
            } elseif ($printLogging === false || strcasecmp($printLogging, 'false') === 0 || $printLogging === '0') {
                $this->printLogging = false;
            }
        }
        $this->logger->setPrintLogging($this->printLogging);
        
        #-----------------------------------------------------------------------------
        # Get the log file and set it in the logger, so that messages
        # will start to log to the file
        #-----------------------------------------------------------------------------
        if (array_key_exists(ConfigProperties::LOG_FILE, $this->properties)) {
            $this->logFile = $this->properties[ConfigProperties::LOG_FILE];
        }
        
        if (!empty($this->logFile)) {
            $this->logFile = $this->processFile($this->logFile, $fileShouldExist = false);
            $this->logger->setLogFile($this->logFile);
        }
 
        #-------------------------------------------------------------------
        # Database logging
        #-------------------------------------------------------------------
        if (array_key_exists(ConfigProperties::DB_LOGGING, $this->properties)) {
            $dbLogging = $this->properties[ConfigProperties::DB_LOGGING];
            if ($dbLogging === true || strcasecmp($dbLogging, 'true') === 0 || $dbLogging === '1') {
                $this->dbLogging = true;
            } elseif ($dbLogging === false || strcasecmp($dbLogging, 'false') === 0 || $dbLogging === '0') {
                $this->dbLogging = false;
            }
        }
        
        if (array_key_exists(ConfigProperties::DB_LOG_TABLE, $this->properties)) {
            $dbLogTable = trim($this->properties[ConfigProperties::DB_LOG_TABLE]);
            if (!empty($dbLogTable)) {
                $this->dbLogTable = $dbLogTable;
            }
        }

        if (array_key_exists(ConfigProperties::DB_EVENT_LOG_TABLE, $this->properties)) {
            $dbEventLogTable = trim($this->properties[ConfigProperties::DB_EVENT_LOG_TABLE]);
            if (!empty($dbEventLogTable)) {
                $this->dbEventLogTable = $dbEventLogTable;
            }
        }

        #-----------------------------------------------------------
        # Email logging
        #-----------------------------------------------------------
        if (array_key_exists(ConfigProperties::EMAIL_ERRORS, $this->properties)) {
            $emailErrors = $this->properties[ConfigProperties::EMAIL_ERRORS];
            if ($emailErrors === true || strcasecmp($emailErrors, 'true') === 0 || $emailErrors === '1') {
                $this->emailErrors = true;
            } elseif ($emailErrors === false || strcasecmp($emailErrors, 'false') === 0 || $emailErrors === '0') {
                $this->emailErrors = false;
            }
        }
        $this->logger->setEmailErrors($this->emailErrors);

        # E-mail summary notification
        if (array_key_exists(ConfigProperties::EMAIL_SUMMARY, $this->properties)) {
            $send = $this->properties[ConfigProperties::EMAIL_SUMMARY];
            if ($send === true || strcasecmp($send, 'true') === 0 || $send === '1') {
                $this->emailSummary  = true;
            }
        }
        $this->logger->setEmailSummary($this->emailSummary);
        
        # E-mail from address
        if (array_key_exists(ConfigProperties::EMAIL_FROM_ADDRESS, $this->properties)) {
            $this->emailFromAddress = trim($this->properties[ConfigProperties::EMAIL_FROM_ADDRESS]);
        }

        # E-mail to list
        if (array_key_exists(ConfigProperties::EMAIL_TO_LIST, $this->properties)) {
            $this->emailToList = trim($this->properties[ConfigProperties::EMAIL_TO_LIST]);
        }
        
        # E-mail subject
        if (array_key_exists(ConfigProperties::EMAIL_SUBJECT, $this->properties)) {
            $this->emailSubject = $this->properties[ConfigProperties::EMAIL_SUBJECT];
        }

        # Check and set email logging information
        if (empty($this->emailFromAddress)) {
            if ($this->emailErrors || $this->emailSummary) {
                $message = 'E-mailing of errors and/or summary specified without an e-mail from address.'
                    ." errors: ".$this->emailErrors.", summary: ".$this->emailSummary;
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        } elseif (empty($this->emailToList)) {
            if ($this->emailErrors || $this->emailSummary) {
                $message = 'E-mailing of errors and/or summary specified without an e-mail to address.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        } else {
            # Both an e-mail from address and to address were specified
            $this->logger->setLogEmail(
                $this->emailFromAddress,
                $this->emailToList,
                $this->emailSubject
            );
        }

        #-------------------------------------------------------------------------
        # Check for illegal workflow name property withing a task configuration
        #-------------------------------------------------------------------------
        if (array_key_exists(ConfigProperties::WORKFLOW_NAME, $this->properties)) {
            $message = 'The "'.ConfigProperties::WORKFLOW_NAME.'" property cannot be used in a task configuration.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }

        #------------------------------------------------
        # Get the REDCap API URL
        #------------------------------------------------
        if (array_key_exists(ConfigProperties::REDCAP_API_URL, $this->properties)) {
            $this->redcapApiUrl = $this->properties[ConfigProperties::REDCAP_API_URL];
        }
        
        #---------------------------------------------------
        # Get the REDCap Metadata and Project Info tables
        #---------------------------------------------------
        if (array_key_exists(ConfigProperties::REDCAP_METADATA_TABLE, $this->properties)) {
            if (empty($this->properties[ConfigProperties::REDCAP_METADATA_TABLE])) {
                $this->redcapMetadataTable = MetadataTable::DEFAULT_NAME;
            } else {
                $this->redcapMetadataTable = $this->properties[ConfigProperties::REDCAP_METADATA_TABLE];
            }
        }

        if (array_key_exists(ConfigProperties::REDCAP_PROJECT_INFO_TABLE, $this->properties)) {
            if (empty($this->properties[ConfigProperties::REDCAP_PROJECT_INFO_TABLE])) {
                $this->redcapProjectInfoTable = ProjectInfoTable::DEFAULT_NAME;
            } else {
                $this->redcapProjectInfoTable = $this->properties[ConfigProperties::REDCAP_PROJECT_INFO_TABLE];
            }
        }
        
        #--------------------------------------------------------
        # Get configuration information used for file logging
        #--------------------------------------------------------
        if (array_key_exists(ConfigProperties::PROJECT_ID, $this->properties)) {
            $this->projectId = $this->properties[ConfigProperties::PROJECT_ID];
        }
        
        if (array_key_exists(ConfigProperties::CONFIG_OWNER, $this->properties)) {
            $this->configOwner = $this->properties[ConfigProperties::CONFIG_OWNER];
        }
        
        if (array_key_exists(ConfigProperties::CONFIG_NAME, $this->properties)) {
            $this->configName = $this->properties[ConfigProperties::CONFIG_NAME];
        }
        
        if (array_key_exists(ConfigProperties::CRON_JOB, $this->properties)) {
            $cronJob = $this->properties[ConfigProperties::CRON_JOB];
            
            if ((is_bool($cronJob) && $cronJob) || strcasecmp($cronJob, 'true') === 0 || $cronJob === '1') {
                $this->cronJob = 'true';
            } elseif ((is_bool($cronJob) && !$cronJob) || strcasecmp($cronJob, 'false') === 0 || $cronJob === '0'
                    || trim($cronJob) === '') {
                $this->cronJob = 'false';
            }
        }
        
        #--------------------------------------------------
        # Check for invalid configuration file property
        #--------------------------------------------------
        if (array_key_exists(ConfigProperties::TASK_CONFIG_FILE, $this->properties)) {
            $message = 'Invalid property '.ConfigProperties::TASK_CONFIG_FILE
                .' specified in configuration; this property can only be used in workflows.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
        
        #---------------------------------------------------------------
        # Get SSL verify flag
        #
        # Indicates if verification should be done for the SSL
        # connection to REDCap. Setting this to false is not secure.
        #---------------------------------------------------------------
        if (array_key_exists(ConfigProperties::SSL_VERIFY, $this->properties)) {
            $sslVerify = $this->properties[ConfigProperties::SSL_VERIFY];
            if (strcasecmp($sslVerify, 'false') === 0 || $sslVerify === '0' || $sslVerify === 0) {
                $this->sslVerify = false;
            } elseif (!isset($sslVerify) || $sslVerify === '' || $sslVerify === '1'
                || strcasecmp($sslVerify, 'true') === 0
                || $sslVerify === 1 || $sslVerify === true) {
                $this->sslVerify = true;
            } else {
                $message = 'Unrecognized value "'.$sslVerify.'" for '.ConfigProperties::SSL_VERIFY
                    .' property; a true or false value should be specified.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        }


        #---------------------------------------------------------------
        # Get extracted record count check flag
        #
        # Indicates if the count of extracted records should be checked
        # against the number of record IDs passed to REDCap to see if
        # they match.
        #---------------------------------------------------------------
        if (array_key_exists(ConfigProperties::EXTRACTED_RECORD_COUNT_CHECK, $this->properties)) {
            $countCheck = $this->properties[ConfigProperties::EXTRACTED_RECORD_COUNT_CHECK];

            if (strcasecmp($countCheck, 'false') === 0 || $countCheck === '0' || $countCheck === '') {
                $this->extractedRecordCountCheck = false;
            } elseif (!isset($countCheck) || $countCheck === true
                || strcasecmp($countCheck, 'true') === 0
                || $countCheck === 1 || $countCheck === '1') {
                $this->extractedRecordCountCheck = true;
            } else {
                $message = 'Unrecognized value "'.$countCheck.'" for '
                    .ConfigProperties::EXTRACTED_RECORD_COUNT_CHECK
                    .' property; a true or false value should be specified.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        }

        #---------------------------------------------------------
        # Get CA (Certificate Authority) Certificate File
        #
        # The CA (Certificate Authority) certificate file used
        # for veriying the REDCap site's SSL certificate (i.e.,
        # for verifying that the REDCap site that is connected
        # to is the one specified).
        #---------------------------------------------------------
        if (array_key_exists(ConfigProperties::CA_CERT_FILE, $this->properties)) {
            $this->caCertFile = null;
            $caCertFile = $this->properties[ConfigProperties::CA_CERT_FILE];
            if (isset($caCertFile)) {
                $caCertFile = trim($caCertFile);
                if ($caCertFile !== '') {
                    $this->caCertFile = $this->processFile($caCertFile);
                }
            }
        }

        #---------------------------------------------
        # Get the pre-processing SQL (if any)
        #---------------------------------------------
        $this->preProcessingSql = null;
        if (array_key_exists(ConfigProperties::PRE_PROCESSING_SQL, $this->properties)) {
            $sql = $this->properties[ConfigProperties::PRE_PROCESSING_SQL];
            if (!empty($sql)) {
                $this->preProcessingSql = $sql;
            }
        }

        #---------------------------------------------
        # Get the pre-processing SQL file (if any)
        #---------------------------------------------
        $this->preProcessingSqlFile = null;
        if (array_key_exists(ConfigProperties::PRE_PROCESSING_SQL_FILE, $this->properties)) {
            $file = $this->properties[ConfigProperties::PRE_PROCESSING_SQL_FILE];
            if (!empty($file)) {
                $this->preProcessingSqlFile = $this->processFile($file);
            }
        }

        #---------------------------------------------
        # Get the post-processing SQL (if any)
        #---------------------------------------------
        $this->postProcessingSql = null;
        if (array_key_exists(ConfigProperties::POST_PROCESSING_SQL, $this->properties)) {
            $sql = $this->properties[ConfigProperties::POST_PROCESSING_SQL];
            if (!empty($sql)) {
                $this->postProcessingSql = $sql;
            }
        }

        #---------------------------------------------
        # Get the post-processing SQL file (if any)
        #---------------------------------------------
        $this->postProcessingSqlFile = null;
        if (array_key_exists(ConfigProperties::POST_PROCESSING_SQL_FILE, $this->properties)) {
            $file = $this->properties[ConfigProperties::POST_PROCESSING_SQL_FILE];
            if (!empty($file)) {
                $this->postProcessingSqlFile = $this->processFile($file);
            }
        }

        #-----------------------------------------------------------------
        # Initialize generated field types and then
        # process any generated field type properties
        #-----------------------------------------------------------------
        $this->generatedInstanceType = FieldTypeSpecifier::create(self::DEFAULT_GENERATED_INSTANCE_TYPE);
        $this->generatedKeyType      = FieldTypeSpecifier::create(self::DEFAULT_GENERATED_KEY_TYPE);
        $this->generatedLabelType    = FieldTypeSpecifier::create(self::DEFAULT_GENERATED_LABEL_TYPE);
        $this->generatedNameType     = FieldTypeSpecifier::create(self::DEFAULT_GENERATED_NAME_TYPE);
        $this->generatedRecordIdType = FieldTypeSpecifier::create(self::DEFAULT_GENERATED_RECORD_ID_TYPE);
        $this->generatedSuffixType   = FieldTypeSpecifier::create(self::DEFAULT_GENERATED_SUFFIX_TYPE);

        #if (array_key_exists(ConfigProperties::GENERATED_INSTANCE_TYPE, $this->properties)) {
        #    $this->generatedInstanceType = FieldTypeSpecifier::create(
        #        $this->properties[ConfigProperties::GENERATED_INSTANCE_TYPE]
        #    );
        #}

        #if (array_key_exists(ConfigProperties::GENERATED_KEY_TYPE, $this->properties)) {
        #    $this->generatedKeyType = FieldTypeSpecifier::create(
        #        $this->properties[ConfigProperties::GENERATED_KEY_TYPE]
        #    );
        #}

        if (array_key_exists(ConfigProperties::GENERATED_LABEL_TYPE, $this->properties)) {
            $this->generatedLabelType = FieldTypeSpecifier::create(
                $this->properties[ConfigProperties::GENERATED_LABEL_TYPE]
            );
        }

        if (array_key_exists(ConfigProperties::GENERATED_NAME_TYPE, $this->properties)) {
            $this->generatedNameType = FieldTypeSpecifier::create(
                $this->properties[ConfigProperties::GENERATED_NAME_TYPE]
            );
        }

        if (array_key_exists(ConfigProperties::GENERATED_RECORD_ID_TYPE, $this->properties)) {
            $this->generatedRecordIdType = FieldTypeSpecifier::create(
                $this->properties[ConfigProperties::GENERATED_RECORD_ID_TYPE]
            );
        }

        if (array_key_exists(ConfigProperties::GENERATED_SUFFIX_TYPE, $this->properties)) {
            $this->generatedSuffixType = FieldTypeSpecifier::create(
                $this->properties[ConfigProperties::GENERATED_SUFFIX_TYPE]
            );
        }


        #-------------------------------------------------------------
        # Lookup table properties
        #-------------------------------------------------------------
        $this->createLookupTable = self::DEFAULT_CREATE_LOOKUP_TABLE;
        if (array_key_exists(ConfigProperties::CREATE_LOOKUP_TABLE, $this->properties)) {
            $this->createLookupTable = $this->properties[ConfigProperties::CREATE_LOOKUP_TABLE];
        }

        $this->lookupTableName = LookupTable::DEFAULT_NAME;
        if (array_key_exists(ConfigProperties::LOOKUP_TABLE_NAME, $this->properties)) {
            if (!empty($this->properties[ConfigProperties::LOOKUP_TABLE_NAME])) {
                $this->lookupTableName = $this->properties[ConfigProperties::LOOKUP_TABLE_NAME];
            }
        }

        #-------------------------------------------------------------
        # Calc field ignore pattern
        #-------------------------------------------------------------
        $this->calcFieldIgnorePattern = '';
        if (array_key_exists(ConfigProperties::CALC_FIELD_IGNORE_PATTERN, $this->properties)) {
            $this->calcFieldIgnorePattern = $this->properties[ConfigProperties::CALC_FIELD_IGNORE_PATTERN];
        }
        
        #--------------------------------------------------------
        # Ignore empty incomplete forms
        #--------------------------------------------------------
        $this->ignoreEmptyIncompleteForms = self::DEFAULT_IGNORE_EMPTY_INCOMPLETE_FORMS;
        if (array_key_exists(ConfigProperties::IGNORE_EMPTY_INCOMPLETE_FORMS, $this->properties)) {
            $ignore = $this->properties[ConfigProperties::IGNORE_EMPTY_INCOMPLETE_FORMS];
            if ($ignore === true || strcasecmp($ignore, 'true') === 0 || $ignore === '1') {
                $this->ignoreEmptyIncompleteForms = true;
            } elseif ($ignore === false || strcasecmp($ignore, 'false') === 0 || $ignore === '0') {
                $this->ignoreEmptyIncompleteForms = false;
            }
        }

        #----------------------------------------------------------------
        # Get the data source project API token
        #----------------------------------------------------------------
        $this->dataSourceApiToken = '';
        if (array_key_exists(ConfigProperties::DATA_SOURCE_API_TOKEN, $this->properties)) {
            $this->dataSourceApiToken = $this->properties[ConfigProperties::DATA_SOURCE_API_TOKEN];
        }

        #----------------------------------------------------------
        # Set the time limit; if none is provided, use the default
        #----------------------------------------------------------
        if (array_key_exists(ConfigProperties::TIME_LIMIT, $this->properties)) {
            $this->timeLimit = $this->properties[ConfigProperties::TIME_LIMIT];
        } else {
            $this->timeLimit = self::DEFAULT_TIME_LIMIT;
        }

        #-----------------------------------------------
        # Get the timezone, if any
        #-----------------------------------------------
        if (array_key_exists(ConfigProperties::TIMEZONE, $this->properties)) {
            $this->timezone = $this->properties[ConfigProperties::TIMEZONE];
        }


        #-----------------------------------------------------------------------
        # Determine the batch size to use (how many records to process at once)
        # Batch size is expected to be a positive integer. The TaskConfig
        # project should enforce that, but not the configuration file.
        #-----------------------------------------------------------------------
        $this->batchSize = self::DEFAULT_BATCH_SIZE;
        if (array_key_exists(ConfigProperties::BATCH_SIZE, $this->properties)) {
            $batchSize = $this->properties[ConfigProperties::BATCH_SIZE];

            $message = "Invalid ".ConfigProperties::BATCH_SIZE." property."
                . " This property must be an integer greater than 0.";

            if (is_int($batchSize)) {
                if ($batchSize < 1) {
                    throw new EtlException($message, EtlException::INPUT_ERROR);
                }
            } elseif (is_string($batchSize)) {
                if (!empty($batchSize) && intval($batchSize) < 1) {
                    throw new EtlException($message, EtlException::INPUT_ERROR);
                }
            } else {
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
            $this->batchSize = $batchSize;
        }

        $this->processTransformationRules($this->properties);

        #----------------------------------------------------------------
        # Get the table prefix (if any)
        #----------------------------------------------------------------
        $this->tablePrefix = self::DEFAULT_TABLE_PREFIX;
        if (array_key_exists(ConfigProperties::TABLE_PREFIX, $this->properties)) {
            $tablePrefix = $this->properties[ConfigProperties::TABLE_PREFIX];

            if (!empty($tablePrefix)) {
                # If the prefix contains something other than letters, numbers or underscore
                if (preg_match("/[^a-zA-Z0-9_]+/", $tablePrefix) === 1) {
                    $message = "Invalid ".ConfigProperties::TABLE_PREFIX." property."
                        . " This property may only contain letters, numbers, and underscores.";
                    throw new EtlException($message, EtlException::INPUT_ERROR);
                }
                $this->tablePrefix = $tablePrefix;
            }
        }


        #----------------------------------------------------------------
        # Get the label view suffix (if any)
        #----------------------------------------------------------------
        $this->labelViewSuffix = self::DEFAULT_LABEL_VIEW_SUFFIX;
        if (array_key_exists(ConfigProperties::LABEL_VIEW_SUFFIX, $this->properties)) {
            $labelViewSuffix = $this->properties[ConfigProperties::LABEL_VIEW_SUFFIX];

            if (!empty($labelViewSuffix)) {
                # If the suffix contains something other than letters, numbers or underscore
                if (preg_match("/[^a-zA-Z0-9_]+/", $labelViewSuffix) === 1) {
                    $message = "Invalid ".ConfigProperties::LABEL_VIEW_SUFFIX." property."
                        . " This property may only contain letters, numbers, and underscores.";
                     throw new EtlException($message, EtlException::INPUT_ERROR);
                }
                $this->labelViewSuffix = $labelViewSuffix;
            }
        }


        #---------------------------------------------------
        # Create a database connection for the database
        # where the transformed REDCap data will be stored
        #---------------------------------------------------
        if (array_key_exists(ConfigProperties::DB_CONNECTION, $this->properties)) {
            $this->dbConnection = trim($this->properties[ConfigProperties::DB_CONNECTION]);

            if (empty($this->dbConnection)) {
                $message = 'No database connection was specified in the configuration.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }

            list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($this->dbConnection);
            if ($dbType === DbConnectionFactory::DBTYPE_CSV || $dbType === DbConnectionFactory::DBTYPE_SQLITE) {
                $dbString = $this->processDirectory($dbString);
                $this->dbConnection = DbConnectionFactory::createConnectionString($dbType, $dbString);
            }
        } else {
            $message = 'No database connection was specified in the configuration.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        #-----------------------------------------
        # Process the database SSL flag
        #-----------------------------------------
        if (array_key_exists(ConfigProperties::DB_SSL, $this->properties)) {
            $ssl = $this->properties[ConfigProperties::DB_SSL];
            if ($ssl === false|| strcasecmp($ssl, 'false') === 0 || $ssl === '0') {
                $this->dbSsl  = false;
            }
        }

        #-----------------------------------------
        # Process the database SSL verify flag
        #-----------------------------------------
        $this->dbSslVerify = self::DEFAULT_DB_SSL_VERIFY;
        if (array_key_exists(ConfigProperties::DB_SSL_VERIFY, $this->properties)) {
            $verify = $this->properties[ConfigProperties::DB_SSL_VERIFY];
            if ($verify === true || strcasecmp($verify, 'true') === 0 || $verify === '1') {
                if (empty($this->caCertFile)) {
                    $message = 'Property "'.ConfigProperties::DB_SSL_VERIFY.'" was set, but no value was provided for "'
                        .ConfigProperties::CA_CERT_FILE.'" (the certificate authority certificate file).';
                    throw new EtlException($message, EtlException::INPUT_ERROR);
                }
                $this->dbSslVerify  = true;
            }
        }

        #-----------------------------------------
        # Process the database primary keys flag
        #-----------------------------------------
        $this->dbPrimaryKeys = self::DEFAULT_DB_PRIMARY_KEYS;
        if (array_key_exists(ConfigProperties::DB_PRIMARY_KEYS, $this->properties)) {
            $primaryKeys = $this->properties[ConfigProperties::DB_PRIMARY_KEYS];
            if ($primaryKeys === false|| strcasecmp($primaryKeys, 'false') === 0
                || $primaryKeys === '0' || $primaryKeys === 0) {
                $this->dbPrimaryKeys  = false;
            }
        }

        #-----------------------------------------
        # Process the database foreign keys flag
        #-----------------------------------------
        $this->dbForeignKeys = self::DEFAULT_DB_FOREIGN_KEYS;
        if (array_key_exists(ConfigProperties::DB_FOREIGN_KEYS, $this->properties)) {
            $foreignKeys = $this->properties[ConfigProperties::DB_FOREIGN_KEYS];
            if ($foreignKeys === false|| strcasecmp($foreignKeys, 'false') === 0
                || $foreignKeys === '0' || $foreignKeys == 0) {
                $this->dbForeignKeys  = false;
            }
        }

        if ($this->dbForeignKeys && !$this->dbPrimaryKeys) {
            $message = 'The configuration was set to generate foreign keys in the database, but not primary keys.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        $this->hasValidSetOfProperties();
    }


    /**
     * Indicates if the task configuration has a valid set of (non-emtpy) properties.
     * A valid set of properties consists of either a database connection and
     * at least one SQL property (an SQL task), or a REDCap API URL,
     * API token, transformation rules, and a database connection (an ETL task).
     */
    public function hasValidSetOfProperties()
    {
        $hasValidSet = false;

        $hasDbConnection  = false;
        $hasSql           = false;   // has pre or post-processing SQL
        $hasApiUrl        = false;
        $hasApiToken      = false;

        $hasTransformationRules = false;

        if (!empty($this->dbConnection)) {
            $hasDbConnection = true;
        }

        if (!empty($this->preProcessingSql) || !empty($this->preProcessingSqlFile)
            || !empty($this->postProcessingSql) || !empty($this->postProcessingSqlFile)) {
            $hasSql = true;
        }

        if (!empty($this->redcapApiUrl)) {
            $hasApiUrl = true;
        }

        if (!empty($this->dataSourceApiToken)) {
            $hasApiToken = true;
        }

        if (!empty($this->transformRulesSource)) {
            $hasTransformationRules = true;
        }

        if ($hasDbConnection && $hasSql) {
            # SQL task
            $hasValidSet = true;
        } else {
            # Check for valid ETL task
            if (!$hasApiUrl) {
                $message = 'No REDCap API URL was specified.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            } elseif (!$hasApiToken) {
                $message = 'No API token was found.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            } elseif (!$hasTransformationRules) {
                $message = 'No transformation rules specified.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            } else {
                $hasValidSet = true;
            }
        }

        return $hasValidSet;
    }



    /**
     * Gets properties from a configuration file.
     *
     * @return array that maps property names to property values.
     */
    public static function getPropertiesFromFile($configurationFile)
    {
        $properties = array();
             
        if (!isset($configurationFile) || !is_string($configurationFile) || empty(trim($configurationFile))) {
            # No properties specified
            $message = 'No configuration file was specified.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        } else {
            if (preg_match('/\.json$/i', $configurationFile) === 1) {
                #-----------------------------------------------------------------
                # JSON configuration file
                #-----------------------------------------------------------------
                $configurationFileContents = file_get_contents($configurationFile);
                if ($configurationFileContents === false) {
                    $message = 'The JSON configuration file "'.$configurationFile.'" could not be read.';
                    $code    = EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                }

                $properties = json_decode($configurationFileContents, true);

                if (array_key_exists(ConfigProperties::TRANSFORM_RULES_TEXT, $properties)) {
                    $rulesText = $properties[ConfigProperties::TRANSFORM_RULES_TEXT];
                    if (is_array($rulesText)) {
                        $rulesText = implode("\n", $rulesText);
                        $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = $rulesText;
                    }
                }

                if (array_key_exists(ConfigProperties::PRE_PROCESSING_SQL, $properties)) {
                    $sql = $properties[ConfigProperties::PRE_PROCESSING_SQL];
                    if (is_array($sql)) {
                        $sql = implode("\n", $sql);
                        $properties[ConfigProperties::PRE_PROCESSING_SQL] = $sql;
                    }
                }

                if (array_key_exists(ConfigProperties::POST_PROCESSING_SQL, $properties)) {
                    $sql = $properties[ConfigProperties::POST_PROCESSING_SQL];
                    if (is_array($sql)) {
                        $sql = implode("\n", $sql);
                        $properties[ConfigProperties::POST_PROCESSING_SQL] = $sql;
                    }
                }
            } else {
                #-------------------------------------------------------------
                # .ini configuration file
                #-------------------------------------------------------------
                # suppress errors for this, because it should be
                # handled by the check for $properties being false
                @ $properties = parse_ini_file($configurationFile);
                if ($properties === false) {
                    $error = error_get_last();
                    $parseError = '';
                    if (isset($error) && is_array($error) && array_key_exists('message', $error)) {
                        $parseError = preg_replace('/\s+$/', '', $error['message']);
                    }
                    $message = 'The configuration file "'.$configurationFile.'" could not be read: '.$parseError.'.';
                    $code    = EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                }
            }
        }
        
        //$baseDir = dirname(realpath($configurationFile));

        return $properties;
    }
    
    /**
     * Overrides properties in $properties with those defined in $propertyOverrides.
     *
     * @return array the overridden properties.
     */
    public static function overrideProperties($properties, $propertyOverrides)
    {
        if (!empty($propertyOverrides) && is_array($propertyOverrides)) {
            foreach ($propertyOverrides as $propertyName => $propertyValue) {
                $properties[$propertyName] = $propertyValue;
            }
        }
        return $properties;
    }

    /**
     * Gets version of properties where any relative paths specified for
     * file properties are modified to absolute paths.
     */
    public static function makeFilePropertiesAbsolute($properties, $baseDir)
    {
        foreach ($properties as $name => $value) {
            if (ConfigProperties::isFileProperty($name) && !empty($value)) {
                if ($name === ConfigProperties::LOG_FILE) {
                    $properties[$name] = self::processFileProperty($value, $baseDir, false);
                } else {
                    $properties[$name] = self::processFileProperty($value, $baseDir);
                }
            } elseif ($name === ConfigProperties::DB_CONNECTION) {
                list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($value);
                if ($dbType === DbConnectionFactory::DBTYPE_CSV) {
                    $dbString = self::processDirectorySpecification($dbString, $baseDir);
                    $properties[$name] = DbConnectionFactory::createConnectionString($dbType, $dbString);
                }
            }
        }
        return $properties;
    }


    /**
     * Processes the transformation rules.
     *
     * @param array $properties The current properties.
     */
    private function processTransformationRules($properties)
    {
        if (array_key_exists(ConfigProperties::TRANSFORM_RULES_SOURCE, $properties)) {
            $this->transformRulesSource = $properties[ConfigProperties::TRANSFORM_RULES_SOURCE];

            if (empty($this->transformRulesSource)) {
                ; // Could be OK, if this is an SQL task - that needs to be checked later
            } elseif ($this->transformRulesSource == self::TRANSFORM_RULES_TEXT) {
                if (array_key_exists(ConfigProperties::TRANSFORM_RULES_TEXT, $properties)) {
                    $this->transformationRules = $properties[ConfigProperties::TRANSFORM_RULES_TEXT];
                    if ($this->transformationRules == '') {
                        $error = 'No transformation rules were entered.';
                        throw new EtlException($error, EtlException::FILE_ERROR);
                    }
                } else {
                    $error = 'No transformation rules text was defined.';
                    throw new EtlException($error, EtlException::INPUT_ERROR);
                }
            } elseif ($this->transformRulesSource == self::TRANSFORM_RULES_FILE) {
                $file = $properties[ConfigProperties::TRANSFORM_RULES_FILE];
                $file = $this->processFile($file);
                $this->transformationRules = file_get_contents($file);
            } elseif ($this->transformRulesSource == self::TRANSFORM_RULES_DEFAULT) {
                # The actual rules are not part of the configuration and will need
                # to be generate later after the data project has been set up.
                $this->transformationRules == '';
                $this->getAutogenProperties($properties);
            } else {
                $message = 'Unrecognized transformation rules source: '.$this->transformRulesSource;
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        }
    }


    public function processFile($file, $fileShouldExist = true)
    {
        $baseDir = $this->baseDir;
        return self::processFileProperty($file, $baseDir, $fileShouldExist);
    }

    /**
     * Processes a file and returns the absolute pathname for the file.
     * Relative file paths in the configuration file are considered
     * to be relative to the directory of the configuration file.
     *
     * @param string $file The file property value, which should be a relative or absolute path
     *     to a file for file.
     *
     * @param boolean $fileShouldExist if true, the file should already
     *    exists, so an exception will be thrown if it does nore.
     *
     * @return string absolute path for file to use.
     */
    public static function processFileProperty($file, $baseDir, $fileShouldExist = true)
    {
        if ($file == null) {
            $file = '';
        } else {
            $file = trim($file);
        }

        if (!FileUtil::isAbsolutePath($file)) {
            $file = $baseDir . '/' . $file;
        }
        
        $dirName  = dirname($file);
        $realDir  = realpath($dirName);
        $fileName = basename($file);
        $realFile = $realDir.'/'.$fileName;


        if ($fileShouldExist) {
            $realFile = realpath($realFile);
            if ($realFile === false) {
                $message = 'File "'.$file.'" not found.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        } else {
            if ($realDir === false) {
                $message = 'Directory for file "'.$file.'" not found.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        }

        return $realFile;
    }

    /**
     * Processes the specified directory path and returns its canonicalized
     * absolute path name.
     *
     * @param string $path the path to process.
     *
     * @return string the canonicalized absolute path name for the specified
     *     path.
     */
    public function processDirectory($path)
    {
        return self::processDirectorySpecification($path, $this->baseDir);
    }

    public static function processDirectorySpecification($path, $baseDir)
    {
        if ($path == null) {
            $message = 'Null path specified as argument to '.__METHOD__;
            throw new EtlException($message, EtlException::INPUT_ERROR);
        } elseif (!is_string($path)) {
            $message = 'Non-string path specified as argument to '.__METHOD__;
            throw new EtlException($message, EtlException::INPUT_ERROR);
        } else {
            $path = trim($path);
        }

        if (FileUtil::isAbsolutePath($path)) {
            $realDir  = realpath($path);
        } else { // Relative path
            $realDir = realpath(realpath($baseDir).'/'.$path);
        }

        if ($realDir === false) {
            $message = 'Directory "'.$path.'" not found.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        return $realDir;
    }



    /**
     * Indicated if the specified e-mail address is valid.
     *
     * @param string $email The e-mail address to check.
     *
     * @return boolean returns true of the specified e-mail address
     *     is valid, and false otherwise.
     */
    public function isValidEmail($email)
    {
        $isValid = false;
        if (preg_match('/^[^@]+@[^@]+$/', $email) === 1) {
            $isValid = true;
        }
        return $isValid;
    }

    /**
     * Gets the MySQL connection information.
     *
     * @return array string array with (host, user, password, dbname),
     *               or null if there is no MySQL connection.
     */
    public function getMySqlConnectionInfo()
    {
        $connectionInfo = null;
        list($dbType, $dbInfo) = explode(':', $this->dbConnection, 2);

        if (strcasecmp($dbType, DbConnectionFactory::DBTYPE_MYSQL) === 0) {
            $connectionInfo = DbConnection::parseConnectionString($dbInfo);
        }

        return $connectionInfo;
    }

    /**
     * Gets the SqlServer connection information.
     *
     * @return array string array with (host, user, password, dbname),
     *               or null if there is no MySQL connection.
     */
    public function getSqlServerConnectionInfo()
    {
        $connectionInfo = null;
        list($dbType, $dbInfo) = explode(':', $this->dbConnection, 2);

        if (strcasecmp($dbType, DbConnectionFactory::DBTYPE_SQLSERVER) === 0) {
            $connectionInfo = DbConnection::parseConnectionString($dbInfo);
        }

        return $connectionInfo;
    }

    public function getPropertiesFile()
    {
        return $this->propertiesFile;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * Gets the specified property.
     */
    public function getProperty($name)
    {
        return $this->properties[$name];
    }

    public function getPropertyInfo($property)
    {
        $info = '';
        if (ConfigProperties::isValid($property)) {
            if (array_key_exists($property, $this->properties)) {
                $info = $this->properties[$property];

                if (empty($this->propertiesFile)) {
                    $info .= ' - defined in array argument';
                } else {
                    $info .= ' - defined in file: '.$this->propertiesFile;
                }
            } else {
                $info = 'undefined';
            }
        } else {
            $info = 'invalid property';
        }

        return $info;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getApp()
    {
        return $this->app;
    }

    public function getBatchSize()
    {
        return $this->batchSize;
    }

    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    public function getCaCertFile()
    {
        return $this->caCertFile;
    }

    public function getCalcFieldIgnorePattern()
    {
        return $this->calcFieldIgnorePattern;
    }
    
    public function getConfigName()
    {
        return $this->configName;
    }
    
    public function getConfigOwner()
    {
        return $this->configOwner;
    }
        
    public function getCreateLookupTable()
    {
        return $this->createLookupTable;
    }

    public function getCronJob()
    {
        return $this->cronJob;
    }
    
    public function getDataSourceApiToken()
    {
        return $this->dataSourceApiToken;
    }

    public function getDbConnection()
    {
        return $this->dbConnection;
    }

    public function setDbConnection($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function getDbSsl()
    {
        return $this->dbSsl;
    }

    public function getDbSslVerify()
    {
        return $this->dbSslVerify;
    }

    public function getDbPrimaryKeys()
    {
        return $this->dbPrimaryKeys;
    }

    public function getDbForeignKeys()
    {
        return $this->dbForeignKeys;
    }

    public function getDbLogging()
    {
        return $this->dbLogging;
    }
    
    public function getDbLogTable()
    {
        return $this->dbLogTable;
    }
    
    public function getDbEventLogTable()
    {
        return $this->dbEventLogTable;
    }
    
    public function getEmailErrors()
    {
        return $this->emailErrors;
    }
    
    public function getEmailSummary()
    {
        return $this->emailSummary;
    }
    
    public function getEmailFromAddress()
    {
        return $this->emailFromAddress;
    }

    public function getEmailSubject()
    {
        return $this->emailSubject;
    }

    public function getEmailToList()
    {
        return $this->emailToList;
    }

    public function getExtractedRecordCountCheck()
    {
        return $this->extractedRecordCountCheck;
    }

    public function getGeneratedInstanceType()
    {
        return $this->generatedInstanceType;
    }

    public function getGeneratedKeyType()
    {
        return $this->generatedKeyType;
    }

    public function getGeneratedLabelType()
    {
        return $this->generatedLabelType;
    }

    public function getGeneratedNameType()
    {
        return $this->generatedNameType;
    }

    public function getGeneratedRecordIdType()
    {
        return $this->generatedRecordIdType;
    }

    public function getGeneratedSuffixType()
    {
        return $this->generatedSuffixType;
    }

    public function getLabelViewSuffix()
    {
        return $this->labelViewSuffix;
    }

    public function getIgnoreEmptyIncompleteForms()
    {
        return $this->ignoreEmptyIncompleteForms;
    }
    
    public function getLogFile()
    {
        return $this->logFile;
    }

    public function getLookupTableName()
    {
        return $this->lookupTableName;
    }

    public function getPreProcessingSql()
    {
        return $this->preProcessingSql;
    }

    public function getPreProcessingSqlFile()
    {
        return $this->preProcessingSqlFile;
    }

    public function getPostProcessingSql()
    {
        return $this->postProcessingSql;
    }

    public function getPostProcessingSqlFile()
    {
        return $this->postProcessingSqlFile;
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    public function getPrintLogging()
    {
         return $this->printLogging;
    }
    
    public function getRedCapApiUrl()
    {
        return $this->redcapApiUrl;
    }

    public function getRedCapMetadataTable()
    {
        return $this->redcapMetadataTable;
    }

    public function getRedCapProjectInfoTable()
    {
        return $this->redcapProjectInfoTable;
    }

    public function getSslVerify()
    {
        return $this->sslVerify;
    }

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function getTimeLimit()
    {
        return $this->timeLimit;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getTransformationRules()
    {
        return $this->transformationRules;
    }

    public function setTransformationRules($rules)
    {
        $this->transformationRules = $rules;
    }

    public function getTransformRulesSource()
    {
        return $this->transformRulesSource;
    }

    public function getAutogenIncludeCompleteFields()
    {
        return $this->autogenIncludeCompleteFields;
    }

    public function getAutogenIncludeDagFields()
    {
        return $this->autogenIncludeDagFields;
    }

    public function getAutogenIncludeFileFields()
    {
        return $this->autogenIncludeFileFields;
    }

    public function getAutogenIncludeSurveyFields()
    {
        return $this->autogenIncludeSurveyFields;
    }

    public function getAutogenRemoveNotesFields()
    {
        return $this->autogenRemoveNotesFields;
    }

    public function getAutogenRemoveIdentifierFields()
    {
        return $this->autogenRemoveIdentifierFields;
    }

    public function getAutogenCombineNonRepeatingFields()
    {
        return $this->autogenCombineNonRepeatingFields;
    }

    public function getAutogenNonRepeatingFieldsTable()
    {
        return $this->autogenNonRepeatingFieldsTable;
    }

    public function getTaskName()
    {
        return $this->taskName;
    }


    private function getAutogenProperties($properties)
    {
        $this->autogenIncludeCompleteFields = self::DEFAULT_AUTOGEN_INCLUDE_COMPLETE_FIELDS;
        if (array_key_exists(ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS, $this->properties)) {
            $includeCompleteFields = $this->properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS];

            if ($includeCompleteFields === true || strcasecmp($includeCompleteFields, 'true') === 0
                || $includeCompleteFields === '1') {
                    $includeCompleteFields = true;
            } elseif ($includeCompleteFields === false || strcasecmp($includeCompleteFields, 'false') === 0
                || $includeCompleteFields === '0') {
                    $includeCompleteFields = false;
            }
            $this->autogenIncludeCompleteFields = $includeCompleteFields;
        }


        $this->autogenIncludeDagFields = self::DEFAULT_AUTOGEN_INCLUDE_DAG_FIELDS;
        if (array_key_exists(ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS, $this->properties)) {
            $includeDagFields = $this->properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS];

            if ($includeDagFields === true || strcasecmp($includeDagFields, 'true') === 0
                || $includeDagFields === '1') {
                    $includeDagFields = true;
            } elseif ($includeDagFields === false || strcasecmp($includeDagFields, 'false') === 0
                || $includeDagFields === '0') {
                    $includeDagFields = false;
            }
            $this->autogenIncludeDagFields = $includeDagFields;
        }


        $this->autogenIncludeFileFields = self::DEFAULT_AUTOGEN_INCLUDE_FILE_FIELDS;
        if (array_key_exists(ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS, $this->properties)) {
            $includeFileFields = $this->properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS];

            if ($includeFileFields === true || strcasecmp($includeFileFields, 'true') === 0
                || $includeFileFields === '1') {
                    $includeFileFields = true;
            } elseif ($includeFileFields === false || strcasecmp($includeFileFields, 'false') === 0
                || $includeFileFields === '0') {
                    $includeFileFields = false;
            }
            $this->autogenIncludeFileFields = $includeFileFields;
        }

        $this->autogenIncludeSurveyFields = self::DEFAULT_AUTOGEN_INCLUDE_SURVEY_FIELDS;
        if (array_key_exists(ConfigProperties::AUTOGEN_INCLUDE_SURVEY_FIELDS, $this->properties)) {
            $includeSurveyFields = $this->properties[ConfigProperties::AUTOGEN_INCLUDE_SURVEY_FIELDS];

            if ($includeSurveyFields === true || strcasecmp($includeSurveyFields, 'true') === 0
                || $includeSurveyFields === '1') {
                    $includeSurveyFields = true;
            } elseif ($includeSurveyFields === false || strcasecmp($includeSurveyFields, 'false') === 0
                || $includeSurveyFields === '0') {
                    $includeSurveyFields = false;
            }
            $this->autogenIncludeSurveyFields = $includeSurveyFields;
        }


        $this->autogenRemoveNotesFields = self::DEFAULT_AUTOGEN_REMOVE_NOTES_FIELDS;
        if (array_key_exists(ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS, $this->properties)) {
            $removeNotesFields = $this->properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS];

            if ($removeNotesFields === true || strcasecmp($removeNotesFields, 'true') === 0
                || $removeNotesFields === '1') {
                    $removeNotesFields = true;
            } elseif ($removeNotesFields === false || strcasecmp($removeNotesFields, 'false') === 0
                || $removeNotesFields === '0') {
                    $removeNotesFields = false;
            }
            $this->autogenRemoveNotesFields = $removeNotesFields;
        }


        $this->autogenRemoveIdentifierFields = self::DEFAULT_AUTOGEN_REMOVE_IDENTIFIER_FIELDS;
        if (array_key_exists(ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS, $this->properties)) {
            $removeIdentifierFields = $this->properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS];

            if ($removeIdentifierFields === true || strcasecmp($removeIdentifierFields, 'true') === 0
                || $removeIdentifierFields === '1') {
                    $removeIdentifierFields = true;
            } elseif ($removeIdentifierFields === false || strcasecmp($removeIdentifierFields, 'false') === 0
                || $removeIdentifierFields === '0') {
                    $removeIdentifierFields = false;
            }
            $this->autogenRemoveIdentifierFields = $removeIdentifierFields;
        }


        $this->autogenCombineNonRepeatingFields = self::DEFAULT_AUTOGEN_COMBINE_NON_REPEATING_FIELDS;
        if (array_key_exists(ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS, $this->properties)) {
            $combineNonRepeatingFields = $this->properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS];

            if ($combineNonRepeatingFields === true || strcasecmp($combineNonRepeatingFields, 'true') === 0
                || $combineNonRepeatingFields === '1') {
                    $combineNonRepeatingFields = true;
            } elseif ($combineNonRepeatingFields === false || strcasecmp($combineNonRepeatingFields, 'false') === 0
                || $combineNonRepeatingFields === '0') {
                    $combineNonRepeatingFields = false;
            }
            $this->autogenCombineNonRepeatingFields = $combineNonRepeatingFields;
        }

        $message = null;
        $this->autogenNonRepeatingFieldsTable = self::DEFAULT_AUTOGEN_NON_REPEATING_FIELDS_TABLE;

        if ($this->autogenCombineNonRepeatingFields) {
            if (array_key_exists(ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE, $this->properties)) {
                $nonRepeatingFieldsTable = $this->properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE];

                if (empty($nonRepeatingFieldsTable)) {
                    $message = "Invalid ".ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE." property."
                        . " This property must have a value if the AUTOGEN_COMBINE_NON_REPEATING_FIELDS property"
                        . " is set to true.";
                } elseif (preg_match("/[^a-zA-Z0-9_]+/", $nonRepeatingFieldsTable) === 1) {
                    $message = "Invalid ".ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE." property."
                        . " This property may only contain letters, numbers, and underscores.";
                }

                $this->autogenNonRepeatingFieldsTable = $nonRepeatingFieldsTable;
            } else {
                $message = "Invalid ".ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE." property."
                        . " This property is required if the AUTOGEN_COMBINE_NON_REPEATING_FIELDS property"
                        . " is set to true.";
            }

            if ($message) {
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        }
    }
}

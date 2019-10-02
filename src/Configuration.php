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
 * the configuration file and the optional configuration
 * project if defined.
 */
class Configuration
{
    # Transform rules source values
    const TRANSFORM_RULES_TEXT    = '1';
    const TRANSFORM_RULES_FILE    = '2';
    const TRANSFORM_RULES_DEFAULT = '3';

    # Default values
    const DEFAULT_BATCH_SIZE          = 100;
    const DEFAULT_CREATE_LOOKUP_TABLE = false;

    const DEFAULT_DB_SSL             = true;
    const DEFAULT_DB_SSL_VERIFY      = false;

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

    const DEFAULT_LABEL_VIEW_SUFFIX = '_label_view';
    const DEFAULT_LOOKUP_TABLE_NAME = 'Lookup';
    
    const DEFAULT_PRINT_LOGGING = true;
    
    const DEFAULT_TABLE_PREFIX      = '';   # i.e., No table prefix
    const DEFAULT_TIME_LIMIT        = 0;    # zero => no time limit

    private $logger;

    private $app;
    private $batchSize;

    private $caCertFile;
    private $calcFieldIgnorePattern;
    
    private $configName;
    private $configOwner;
    
    private $createLookupTable;

    private $cronJob;
        
    private $dataExportFilter;

    private $dataSourceApiToken;
    private $dbConnection;
    private $dbSsl;
    private $dbSslVerify;

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

    private $labelViewSuffix;
    private $lookupTableName;

    private $postProcessingSql;
    private $postProcessingSqlFile;
    private $projectId;
    private $printLogging;
    private $redcapApiUrl;

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

    /**
     * Creates a Configuration object from either an array or properties
     * or a configuration file, * and updates the logger based on the
     * configuration information found.
     *
     * @param Logger $logger logger for information and errors
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     */
    public function __construct(& $logger, $properties)
    {
        $this->logger = $logger;
        $this->app = $this->logger->getApp();
        $this->propertiesFile = null;

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

            if (preg_match('/\.json$/i', $this->propertiesFile) === 1) {
                #-----------------------------------------------------------------
                # JSON configuration file
                #-----------------------------------------------------------------
                $propertiesFileContents = file_get_contents($this->propertiesFile);
                if ($propertiesFileContents === false) {
                    $message = 'The JSON properties file "'.$this->propertiesFile.'" could not be read.';
                    $code    = EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                }

                $this->properties = json_decode($propertiesFileContents, true);

                if (array_key_exists(ConfigProperties::TRANSFORM_RULES_TEXT, $this->properties)) {
                    $rulesText = $this->properties[ConfigProperties::TRANSFORM_RULES_TEXT];
                    if (is_array($rulesText)) {
                        $rulesText = implode("\n", $rulesText);
                        $this->properties[ConfigProperties::TRANSFORM_RULES_TEXT] = $rulesText;
                    }
                }

                if (array_key_exists(ConfigProperties::POST_PROCESSING_SQL, $this->properties)) {
                    $sql = $this->properties[ConfigProperties::POST_PROCESSING_SQL];
                    if (is_array($sql)) {
                        $sql = implode("\n", $sql);
                        $this->properties[ConfigProperties::POST_PROCESSING_SQL] = $sql;
                    }
                }
            } else {
                #-------------------------------------------------------------
                # .ini configuration file
                #-------------------------------------------------------------

                # suppress errors for this, because it should be
                # handled by the check for $properties being false
                @ $this->properties = parse_ini_file($this->propertiesFile);
                if ($this->properties === false) {
                    $error = error_get_last();
                    $parseError = '';
                    if (isset($error) && is_array($error) && array_key_exists('message', $error)) {
                        $parseError = ': '.preg_replace('/\s+$/', '', $error['message']);
                    }
                    $message = 'The properties file "'.$this->propertiesFile.'" could not be read'.$parseError.'.';
                    $code    = EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                }
            }
        }

        #-------------------------------------------
        # Print logging
        #-------------------------------------------
        $this->printLogging = self::DEFAULT_PRINT_LOGGING;
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
        $this->logFile = null;
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
        $this->dbLogging = self::DEFAULT_DB_LOGGING;
        if (array_key_exists(ConfigProperties::DB_LOGGING, $this->properties)) {
            $dbLogging = $this->properties[ConfigProperties::DB_LOGGING];
            if ($dbLogging === true || strcasecmp($dbLogging, 'true') === 0 || $dbLogging === '1') {
                $this->dbLogging = true;
            } elseif ($dbLogging === false || strcasecmp($dbLogging, 'false') === 0 || $dbLogging === '0') {
                $this->dbLogging = false;
            }
        }
        
        $this->dbLogTable = self::DEFAULT_DB_LOG_TABLE;
        if (array_key_exists(ConfigProperties::DB_LOG_TABLE, $this->properties)) {
            $dbLogTable = trim($this->properties[ConfigProperties::DB_LOG_TABLE]);
            if (!empty($dbLogTable)) {
                $this->dbLogTable = $dbLogTable;
            }
        }

        $this->dbEventLogTable = self::DEFAULT_DB_EVENT_LOG_TABLE;
        if (array_key_exists(ConfigProperties::DB_EVENT_LOG_TABLE, $this->properties)) {
            $dbEventLogTable = trim($this->properties[ConfigProperties::DB_EVENT_LOG_TABLE]);
            if (!empty($dbEventLogTable)) {
                $this->dbEventLogTable = $dbEventLogTable;
            }
        }

        #-----------------------------------------------------------
        # Email logging
        #-----------------------------------------------------------
        $this->emailErrors = self::DEFAULT_EMAIL_ERRORS;
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
        $this->emailSummary = self::DEFAULT_EMAIL_SUMMARY;
        if (array_key_exists(ConfigProperties::EMAIL_SUMMARY, $this->properties)) {
            $send = $this->properties[ConfigProperties::EMAIL_SUMMARY];
            if ($send === true || strcasecmp($send, 'true') === 0 || $send === '1') {
                $this->emailSummary  = true;
            }
        }
        $this->logger->setEmailSummary($this->emailSummary);
        
        # E-mail from address
        $this->emailFromAddress = null;
        if (array_key_exists(ConfigProperties::EMAIL_FROM_ADDRESS, $this->properties)) {
            $this->emailFromAddress = trim($this->properties[ConfigProperties::EMAIL_FROM_ADDRESS]);
        }

        # E-mail to list
        $this->emailToList = null;
        if (array_key_exists(ConfigProperties::EMAIL_TO_LIST, $this->properties)) {
            $this->emailToList = trim($this->properties[ConfigProperties::EMAIL_TO_LIST]);
        }
        
        # E-mail subject
        $this->emailSubject = self::DEFAULT_EMAIL_SUBJECT;
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

        #------------------------------------------------
        # Get the REDCap API URL
        #------------------------------------------------
        if (array_key_exists(ConfigProperties::REDCAP_API_URL, $this->properties)) {
            $this->redcapApiUrl = $this->properties[ConfigProperties::REDCAP_API_URL];
        } else {
            $message = 'No "'.ConfigProperties::REDCAP_API_URL.'" property was defined.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
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
        
        $this->cronJob = ''; # By default, make this blank
        if (array_key_exists(ConfigProperties::CRON_JOB, $this->properties)) {
            $cronJob = $this->properties[ConfigProperties::CRON_JOB];
            
            if ((is_bool($cronJob) && $cronJob) || strcasecmp($cronJob, 'true') === 0 || $cronJob === '1') {
                $this->cronJob = 'true';
            } elseif ((is_bool($cronJob) && !$cronJob) || strcasecmp($cronJob, 'false') === 0 || $cronJob === '0'
                    || trim($cronJob) === '') {
                $this->cronJob = 'false';
            }
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
            } elseif (!isset($sslVerify) || $sslVerify === ''
                    || strcasecmp($sslVerify, 'true') === 0 || $sslVerify === '1' || $sslVerify === 1) {
                $this->sslVerify = true;
            } else {
                $message = 'Unrecognized value "'.$sslVerify.'" for '
                    .ConfigProperties::SSL_VERIFY
                    .' property; a true or false value should be specified.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        } else {
            $this->sslVerify = true;
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
            } elseif (!isset($countCheck)
                    || strcasecmp($countCheck, 'true') === 0 || $countCheck === '1') {
                $this->extractedRecordCountCheck = true;
            } else {
                $message = 'Unrecognized value "'.$countCheck.'" for '
                    .ConfigProperties::EXTRACTED_RECORD_COUNT_CHECK
                    .' property; a true or false value should be specified.';
                throw new EtlException($message, EtlException::INPUT_ERROR);
            }
        } else {
            $this->extractedRecordCountCheck = true;
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
                    $this->caCertFile = $caCertFile;
                }
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
                $this->postProcessingSqlFile = $this->processFile($file, $fileShouldExist = false);
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

        if (array_key_exists(ConfigProperties::GENERATED_INSTANCE_TYPE, $this->properties)) {
            $this->generatedInstanceType = FieldTypeSpecifier::create(
                $this->properties[ConfigProperties::GENERATED_INSTANCE_TYPE]
            );
        }

        if (array_key_exists(ConfigProperties::GENERATED_KEY_TYPE, $this->properties)) {
            $this->generatedKeyType = FieldTypeSpecifier::create(
                $this->properties[ConfigProperties::GENERATED_KEY_TYPE]
            );
        }

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

        $this->lookupTableName = self::DEFAULT_LOOKUP_TABLE_NAME;
        if (array_key_exists(ConfigProperties::LOOKUP_TABLE_NAME, $this->properties)) {
            $this->lookupTableName = $this->properties[ConfigProperties::LOOKUP_TABLE_NAME];
        }

        #-------------------------------------------------------------
        # Calc field ignore pattern
        #-------------------------------------------------------------
        $this->calcFieldIgnorePattern = '';
        if (array_key_exists(ConfigProperties::CALC_FIELD_IGNORE_PATTERN, $this->properties)) {
            $this->calcFieldIgnorePattern = $this->properties[ConfigProperties::CALC_FIELD_IGNORE_PATTERN];
        }


        #----------------------------------------------------------------
        # Get the data source project API token
        #----------------------------------------------------------------
        $this->dataSourceApiToken = '';
        if (array_key_exists(ConfigProperties::DATA_SOURCE_API_TOKEN, $this->properties)) {
            $this->dataSourceApiToken = $this->properties[ConfigProperties::DATA_SOURCE_API_TOKEN];
        } else {
            $message = 'No data source API token was found.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
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
        # Batch size is expected to be a positive integer. The Configuration
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
        $this->dbSsl = self::DEFAULT_DB_SSL;
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

        #-------------------------------------------------
        # Process the data export filter property
        #-------------------------------------------------
        $this->dataExportFilter = null;
        if (array_key_exists(ConfigProperties::DATA_EXPORT_FILTER, $this->properties)) {
            $this->dataExportFilter = trim($this->properties[ConfigProperties::DATA_EXPORT_FILTER]);
        }

        return true;
    }


    /**
     * Processes the transformation rules.
     *
     * @param array $properties The current properties.
     */
    private function processTransformationRules($properties)
    {
        $this->transformRulesSource = $properties[ConfigProperties::TRANSFORM_RULES_SOURCE];

        if ($this->transformRulesSource === self::TRANSFORM_RULES_TEXT) {
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
        } elseif ($this->transformRulesSource === self::TRANSFORM_RULES_FILE) {
            $file = $properties[ConfigProperties::TRANSFORM_RULES_FILE];
            $file = $this->processFile($file);
            $this->transformationRules = file_get_contents($file);
        } elseif ($this->transformRulesSource === self::TRANSFORM_RULES_DEFAULT) {
            # The actual rules are not part of the configuration and will need
            # to be generate later after the data project has been set up.
            $this->transformationRules == '';
        } else {
            $message = 'Unrecognized transformation rules source: '.$this->transformRulesSource;
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
    }


    /**
     * Processes a file and returns the absolute pathname for the file.
     * Relative file paths in the configuration file are considered
     * to be relative to the directory of the configuration file.
     *
     * @param string $file Relative or absolute path for file to be
     *     processed.
     * @param boolean $fileShouldExist if true, the file should already
     *    exists, so an exception will be thrown if it does nore.
     *
     * @return string absolute path for file to use.
     */
    public function processFile($file, $fileShouldExist = true)
    {
        if ($file == null) {
            $file = '';
        } else {
            $file = trim($file);
        }

        if ($this->isAbsolutePath($file)) {
            if ($fileShouldExist) {
                $realFile = realpath($file);
            } else {
                $dirName  = dirname($file);
                $realDir  = realpath($dirName);
                $fileName = basename($file);
                $realFile = $realDir.'/'.$fileName;
            }
        } else { // Relative path
            # take path relative to properties file, if it exists, or
            # relative to the directory of this file if it does not
            if (empty($this->propertiesFile)) {
                $baseDir = realpath(__DIR__);
            } else {
                $baseDir = dirname(realpath($this->propertiesFile));
            }

            if ($fileShouldExist) {
                $realFile = realpath($baseDir.'/'.$file);
            } else {
                # File may not exist (e.g., a log file)
                $dirName  = dirname($file);
                $fileName = basename($file);
                $realDir  = realpath($baseDir.'/'.$dirName);
                $realFile = $realDir.'/'.$fileName;
            }
        }

        if ($fileShouldExist) {
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
        if ($path == null) {
            $message = 'Null path specified as argument to '.__METHOD__;
            throw new EtlException($message, EtlException::INPUT_ERROR);
        } elseif (!is_string($path)) {
            $message = 'Non-string path specified as argument to '.__METHOD__;
            throw new EtlException($message, EtlException::INPUT_ERROR);
        } else {
            $path = trim($path);
        }

        if ($this->isAbsolutePath($path)) {
            $realDir  = realpath($path);
        } else { // Relative path
            if (empty($this->propertiesFile)) {
                $baseDir = dirname(realpath(__DIR__));
            } else {
                $baseDir = dirname(realpath($this->propertiesFile));
            }
            $realDir = realpath($baseDir.'/'.$path);
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

        if (strcasecmp($dbType, 'MySQL') === 0) {
            $connectionInfo = DbConnection::parseConnectionString($dbInfo);
        }

        return $connectionInfo;
    }


    /**
     * Indicates is the specified path is an absolute path.
     *
     * @param string $path the path to check.
     *
     * @return boolean returns true of the path is an absoulte path,
     *     and false otherwise.
     */
    private function isAbsolutePath($path)
    {
        $isAbsolute = false;
        $path = trim($path);
        if (DIRECTORY_SEPARATOR === '/') {
            if (preg_match('/^\/.*/', $path) === 1) {
                $isAbsolute = true;
            }
        } else {  // Windows
            if (preg_match('/^(\/|\\\|[a-zA-Z]:(\/|\\\)).*/', $path) === 1) {
                $isAbsolute = true;
            }
        }
        return $isAbsolute;
    }

    public function getPropertiesFile()
    {
        return $this->propertiesFile;
    }

    private function setPropertiesFile($file)
    {
        $this->propertiesFile = $file;
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
    
    public function getDataExportFilter()
    {
        return $this->dataExportFilter;
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

    public function getLogFile()
    {
        return $this->logFile;
    }

    public function getLookupTableName()
    {
        return $this->lookupTableName;
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
}

<?php

namespace IU\REDCapETL;

use IU\PHPCap\RedCap;
use IU\PHPCap\PhpCapException;

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
    const DEFAULT_EMAIL_SUBJECT       = 'REDCap-ETL Error';

    const DEFAULT_GENERATED_INSTANCE_TYPE  = 'int';
    const DEFAULT_GENERATED_KEY_TYPE       = 'int';
    const DEFAULT_GENERATED_LABEL_TYPE     = 'varchar(255)';
    const DEFAULT_GENERATED_NAME_TYPE      = 'varchar(255)';
    const DEFAULT_GENERATED_RECORD_ID_TYPE = 'varchar(255)';
    const DEFAULT_GENERATED_SUFFIX_TYPE    = 'varchar(255)';

    const DEFAULT_LABEL_VIEW_SUFFIX = '_label_view';
    const DEFAULT_LOOKUP_TABLE_NAME = 'Lookup';
    const DEFAULT_TABLE_PREFIX      = '';   # i.e., No table prefix
    const DEFAULT_TIME_LIMIT        = 0;    # zero => no time limit

    private $logger;

    private $app;
    private $allowedServers;
    private $batchSize;
    
    private $caCertFile;
    private $calcFieldIgnorePattern;
    private $createLookupTable;
    
    private $dataSourceApiToken;
    private $dbConnection;

    private $extractedRecordCountCheck;

    private $generatedInstanceType;
    private $generatedKeyType;
    private $generatedLabelType;
    private $generatedNameType;
    private $generatedRecordIdType;
    private $generatedSuffixType;

    private $labelViewSuffix;
    private $logProjectApiToken;
    private $lookupTableName;
    
    private $postProcessingSqlFile;
    private $projectId;
    private $redcapApiUrl;
    
    private $sslVerify;
    private $tablePrefix;
    private $timeLimit;
    private $timezone;
    private $transformationRules;
    private $transformRulesSource;
    private $triggerEtl;

    private $properties;
    private $propertiesFile;
    private $configuration;
    private $configProject;

    private $emailFromAddres;
    private $emailSubject;
    private $emailToList;

    /**
     * Creates a Configuration object from either an array or properties
     * or the configuration file, and the configuration project,
     * and updates the logger based on the configuration information
     * found.
     *
     * @param Logger $logger logger for information and errors
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     * @param boolean $useWebScriptLogFile if true indicates that the
     *     web script log file should be used for logging; false indicates
     *     the standard logging file should be used.
     */
    public function __construct($logger, $properties, $useWebScriptLogFile = false)
    {
        $this->logger = $logger;
        $this->app = $this->logger->getApp();
        $this->propertiesFile = null;

        #--------------------------------------------------------------------
        # Read the properties file
        #--------------------------------------------------------------------
        if (empty($properties)) {
            $message = 'No properties or properties file was specified.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        } elseif (is_array($properties)) {
            $this->properties = $properties;
        } elseif (is_string($properties)) {
            $this->propertiesFile = trim($properties);

            # If this is a JSON configuration file
            if (preg_match('/\.json$/', $this->propertiesFile) === 1) {
                $propertiesFileContents = file_get_contents($this->propertiesFile);
                if ($propertiesFileContents === false) {
                    $message = 'The JSON properties file \"'.$this->propertiesFile.'\" could not be read.';
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
            } else {
                # suppress errors for this, because it should be
                # handled by the check for $properties being false
                @ $this->properties = parse_ini_file($this->propertiesFile);
                if ($this->properties === false) {
                    $message = 'The properties file \"'.$this->propertiesFile.'\" could not be read.';
                    $code    = EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                }
            }
        }


        #-----------------------------------------------------------------------------
        # Get the log file and set it in the logger, so that messages
        # will start to log to the file
        #-----------------------------------------------------------------------------
        $this->logFile = null;
        if ($useWebScriptLogFile) {
            if (array_key_exists(ConfigProperties::WEB_SCRIPT_LOG_FILE, $this->properties)) {
                $this->logFile = $this->properties[ConfigProperties::WEB_SCRIPT_LOG_FILE];
            }
        } else {
            if (array_key_exists(ConfigProperties::LOG_FILE, $this->properties)) {
                $this->logFile = $this->properties[ConfigProperties::LOG_FILE];
            }
        }
        
        
        if (!empty($this->logFile)) {
            $this->logFile = $this->processFile($this->logFile, $fileShouldExist = false);
            $this->logger->setLogFile($this->logFile);
        }

       
        #-----------------------------------------------------------
        # Error e-mail notification information
        #-----------------------------------------------------------
        $this->emailFromAddress  = null;
        $this->emailToList   = null;
        if (array_key_exists(ConfigProperties::EMAIL_FROM_ADDRESS, $this->properties)) {
            $this->emailFromAddress = $this->properties[ConfigProperties::EMAIL_FROM_ADDRESS];
        }

        $this->emailSubject = self::DEFAULT_EMAIL_SUBJECT;
        if (array_key_exists(ConfigProperties::EMAIL_SUBJECT, $this->properties)) {
            $this->emailSubject = $this->properties[ConfigProperties::EMAIL_SUBJECT];
        }

        if (array_key_exists(ConfigProperties::EMAIL_TO_LIST, $this->properties)) {
            $this->emailToList = $this->properties[ConfigProperties::EMAIL_TO_LIST];
        }

        #------------------------------------------------------
        # Set email logging information
        #------------------------------------------------------
        if (!empty($this->emailFromAddress) && !empty($this->emailToList)) {
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

        #---------------------------------------------------------------
        # Get SSL verify flag
        #
        # Indicates if verification should be done for the SSL
        # connection to REDCap. Setting this to false is not secure.
        #---------------------------------------------------------------
        if (array_key_exists(ConfigProperties::SSL_VERIFY, $this->properties)) {
            $sslVerify = $this->properties[ConfigProperties::SSL_VERIFY];
            if (strcasecmp($sslVerify, 'false') === 0 || $sslVerify === '0' || $sslVerify === '') {
                $this->sslVerify = false;
            } elseif (!isset($sslVerify) || $sslVerify === ''
                    || strcasecmp($sslVerify, 'true') === 0 || $sslVerify === '1') {
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
            } elseif (!isset($countCheck) || $countCheck === ''
                    || strcasecmp($countCheck, 'true') === 0 || $countCheck === '1') {
                $this->extractedRecordCountCheck = true;
            } else {
                $message = 'Unrecognized value \"'.$countCheck.'\" for '
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

        #--------------------------------------------------
        # Get the API token for the configuration project
        #--------------------------------------------------
        $configProjectApiToken = null;
        if (array_key_exists(ConfigProperties::CONFIG_API_TOKEN, $this->properties)) {
            $configProjectApiToken = $this->properties[ConfigProperties::CONFIG_API_TOKEN];
        } else {
            $message = 'No configuration project API token property was defined.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
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
        
        #------------------------------------------------------
        # If a configuration project API token was defined,
        # process the configuration project
        #------------------------------------------------------
        if (!empty($configProjectApiToken)) {
            $this->properties = $this->processConfigurationProject($configProjectApiToken, $this->properties);
        }


        #--------------------------------
        # Process allowed servers
        #--------------------------------
        if (array_key_exists(ConfigProperties::ALLOWED_SERVERS, $this->properties)) {
            $this->allowedServers = $this->properties[ConfigProperties::ALLOWED_SERVERS];
        }


        #----------------------------------------------------------------
        # Get the data source project API token
        #----------------------------------------------------------------
        $this->dataSourceApiToken = '';
        if (array_key_exists(ConfigProperties::DATA_SOURCE_API_TOKEN, $this->properties)) {
            $this->dataSourceApiToken = $this->properties[ConfigProperties::DATA_SOURCE_API_TOKEN];
        } else {
            $message = 'No data source API token was found in the configuration project.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
        
        #-------------------------------------------------------------------------------
        # Get the logging project (where log records are written to) API token (if any)
        #-------------------------------------------------------------------------------
        # $startLog = microtime(true);
        if (array_key_exists(ConfigProperties::LOG_PROJECT_API_TOKEN, $this->properties)) {
            $this->logProjectApiToken = $this->properties[ConfigProperties::LOG_PROJECT_API_TOKEN];
        } else {
            $this->logProjectApiToken = null;
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


        #----------------------------------------------------------------------
        # Record whether or not the actual ETL should be run. This is
        # used by the DET handler program, but not the batch program
        #----------------------------------------------------------------------
        if (array_key_exists(ConfigProperties::TRIGGER_ETL, $this->properties)) {
            $this->triggerEtl = $this->properties[ConfigProperties::TRIGGER_ETL];
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
            
            # If this property was defined in a file and uses the CSV database
            # type and a relative path was used, replace the relative path with
            # an absolute path
            if ($this->isFromFile(ConfigProperties::DB_CONNECTION)) {
                list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($this->dbConnection);
                if ($dbType === DbConnectionFactory::DBTYPE_CSV) {
                    $dbString = $this->processDirectory($dbString);
                    $this->dbConnection = DbConnectionFactory::createConnectionString($dbType, $dbString);
                }
            }
        } else {
            $message = 'No database connection was specified in the configuration.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
    
        return true;
    }


    /**
     * Processes a configuration project. Sets the configuration array to the
     * values in the configuration project.
     *
     * @param string $configProjectApiToken the REDCap API token for the configiration
     *     project.
     *
     * @param array $properties the current properties array, a map from property name to
     *     property value.
     *
     * @return array returns an updated properties array, where non-blank values defined
     *     in the configuration project replace the ones from the configuration file.
     */
    private function processConfigurationProject($configProjectApiToken, $properties)
    {
        #---------------------------------------------------------------------
        # Create RedCap object to use for getting the configuration projects
        #---------------------------------------------------------------------
        $superToken = null; // There is no need to create projects, so this is not needed

        try {
            $redCap = new RedCap(
                $this->redcapApiUrl,
                $superToken,
                $this->sslVerify,
                $this->caCertFile
            );
        } catch (PhpCapException $exception) {
            $message = 'Unable to set up RedCap object.';
            throw new EtlException($message, EtlException::PHPCAP_ERROR, $exception);
        }

        #-----------------------------------------------------------------------
        # Get the Configuration Project
        #-----------------------------------------------------------------------
        try {
            $this->configProject = $redCap->getProject($configProjectApiToken);
            $results = $this->configProject->exportRecords();
        } catch (PhpCapException $exception) {
            $error = "Could not get Configuration data";
            throw new EtlException($error, EtlException::PHPCAP_ERROR, $exception);
        }
        $this->configuration = $results[0];

        #----------------------------------------------------------------------
        # Merge the properties from the configuration project that do not end
        # with "_complete" with those from the configuration file, with
        # non-blank values from the configuration project replacing those
        # from the configuration file.
        #----------------------------------------------------------------------
        foreach ($this->configuration as $key => $value) {
            # If the property name is a valid property that
            # may be set from the configuration project
            if (ConfigProperties::isValidInConfigProject($key)) {
                if ($value != null) {
                    $value = trim($value);
                }
                
                if (array_key_exists($key, $properties)) {
                    if (!empty($value)) {
                        $properties[$key] = $value;
                    }
                } else {
                    $properties[$key] = $value;
                }
            }
        }
        $this->properties = $properties;

        #--------------------------------------------------------------
        # Now that the configuration project has been read,
        # if it specified an admin e-mail, replace the notifier
        # sender with this e-mail address.
        #--------------------------------------------------------------
        if (!empty($this->emailFromAddress)) {
            if (array_key_exists(ConfigProperties::EMAIL_TO_LIST, $properties)) {
                $this->emailToList = trim($properties[ConfigProperties::EMAIL_TO_LIST]);
                if (!empty($this->emailToList)) {
                    $this->logger->setLogToEmail($this->emailToList);
                }
            }
        }

        #------------------------------------------------------
        # Get project id
        #------------------------------------------------------
        try {
            $this->projectId = $this->configProject->exportProjectInfo()['project_id'];
        } catch (PhpCapException $exception) {
            $message = "Unable to retrieve project_id.";
            throw new EtlException($message, EtlException::PHPCAP_ERROR, $exception);
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
            if ($this->isFromFile(ConfigProperties::TRANSFORM_RULES_FILE)) {
                $file = $properties[ConfigProperties::TRANSFORM_RULES_FILE];
                $file = $this->processFile($file);
                $this->transformationRules = file_get_contents($file);
            } else {
                $results = $this->configProject->exportFile(
                    $properties['record_id'],
                    ConfigProperties::TRANSFORM_RULES_FILE
                );
                $this->transformationRules = $results;
                if ($this->transformationRules == '') {
                    $error = 'No transformation rules file was found.';
                    throw new EtlException($error, EtlException::FILE_ERROR);
                }
            }
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
                $baseDir = realpath(__DIR__);
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
            $connectionInfo = explode(':', $dbInfo);
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


    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Gets the specified property.
     */
    public function getProperty($name)
    {
        return $this->properties[$name];
    }

    /**
     * Indicates if the current value for the specified property
     * is from the configuration file or array argument
     * (as opposed to the configuration project).
     *
     * @param string $property the property to check.
     *
     * @return boolean returns true if the property is from the
     *     configuration file, and false otherwise.
     */
    public function isFromFile($property)
    {
        $isFromFile = true;
        if (isset($this->configuration) && is_array($this->configuration)) {
            if (array_key_exists($property, $this->configuration)) {
                if ($this->properties[$property] === $this->configuration[$property]) {
                    $isFromFile = false;
                }
            }
        }
        return $isFromFile;
    }
    
    public function getPropertyInfo($property)
    {
        $info = '';
        if (ConfigProperties::isValid($property)) {
            if (array_key_exists($property, $this->properties)) {
                $info = $properties[$property];
                
                if ($this->isFromFile($property)) {
                    if (emtpy($this->propertiesFile)) {
                        $info .= ' - defined in array argument';
                    } else {
                        $info .= ' - defined in file: '.$this->propertiesFile;
                    }
                } else {
                    $info .= ' - defined in configuration project';
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

    public function getAllowedServers()
    {
        return $this->allowedServers;
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
    
    public function getConfigProject()
    {
        return $this->configProject;
    }

    public function getCreateLookupTable()
    {
        return $this->createLookupTable;
    }

    public function getDataSourceApiToken()
    {
        return $this->dataSourceApiToken;
    }

    public function getDbConnection()
    {
        return $this->dbConnection;
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

    public function getLogProjectApiToken()
    {
        return $this->logProjectApiToken;
    }

    public function getLookupTableName()
    {
        return $this->lookupTableName;
    }
    
    public function getPostProcessingSqlFile()
    {
        return $this->postProcessingSqlFile;
    }
    
    public function getProjectId()
    {
        return $this->projectId;
    }

    public function getRecordId()
    {
        $recordId = null;
        if (array_key_exists(ConfigProperties::RECORD_ID)) {
            $recordId = $this->properties[ConfigProperties::RECORD_ID];
        }
        return $recordId;
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

    public function getTransformRulesSource()
    {
        return $this->transformRulesSource;
    }

    public function getTriggerEtl()
    {
        return $this->triggerEtl;
    }
}

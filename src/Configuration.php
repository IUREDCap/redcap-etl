<?php

namespace IU\REDCapETL;

use IU\PHPCap\RedCap;
use IU\PHPCap\PhpCapException;

class Configuration
{
    const DEFAULT_EMAIL_SUBJECT = 'REDCap ETL Error';

    #----------------------------------------------------------------
    # Configuration properties
    #----------------------------------------------------------------
    const ADMIN_EMAIL_LIST_PROPERTY       = 'admin_list_email';
    const ALLOWED_SERVERS_PROPERTY        = 'allowed_servers';
    const BATCH_SIZE_PROPERTY             = 'batch_size';
    const CA_CERT_FILE_PROPERTY           = 'ca_cert_file';
    const CONFIG_API_TOKEN_PROPERTY       = 'config_api_token';
    const DATA_SOURCE_API_TOKEN_PROPERTY  = 'data_source_api_token';
    const DB_CONNECTION_PROPERTY          = 'db_connection';
    const EMAIL_SUBJECT_PROPERTY          = 'email_subject';
    const FROM_EMAIL_ADDRESS_PROPERTY     = 'from_email_address';
    const LABEL_VIEW_SUFFIX_PROPERTY      = 'label_view_suffix';
    const LOG_FILE_PROPERTY               = 'log_file';
    const LOG_PROJECT_API_TOKEN_PROPERTY  = 'log_project_api_token';
    const REDCAP_API_URL_PROPERTY         = 'redcap_api_url';
    const SSL_VERIFY_PROPERTY             = 'ssl_verify';
    const TABLE_PREFIX_PROPERTY           = 'table_prefix';
    const TIME_LIMIT_PROPERTY             = 'time_limit';
    const TIMEZONE_PROPERTY               = 'timezone';
    const TRANSFORM_RULES_CHECK_PROPERTY  = 'transform_rules_check';
    const TRANSFORM_RULES_FILE_PROPERTY   = 'transform_rules_file';
    const TRANSFORM_RULES_SOURCE_PROPERTY = 'transform_rules_source';
    const TRANSFORM_RULES_TEXT_PROPERTY   = 'transform_rules_text';
    
    const TRIGGER_ETL_PROPERTY            = 'trigger_etl';
    const WEB_SCRIPT_PROPERTY             = 'web_script';
    const WEB_SCRIPT_LOG_FILE_PROPERTY    = 'web_script_log_file';
    const WEB_SCRIPT_URL_PROPERTY         = 'web_script_url';
    

    # Transform rules source values
    const TRANSFORM_RULES_TEXT    = '1';
    const TRANSFORM_RULES_FILE    = '2';
    const TRANSFORM_RULES_DEFAULT = '3';

    # Default values
    const DEFAULT_TIME_LIMIT = 0;    # zero => no time limit
    
    private $logger;
    private $errorHandler;

    private $app;
    private $adminEmailList;
    private $allowedServers;
    private $batchSize;
    private $caCertFile;
    private $dataSourceApiToken;
    private $dbConnection;
    private $labelViewSuffix;
    private $logProjectApiToken;
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
    private $emailSubject;
    private $fromEmailAddres;

    /**
     * Creates a Configuration object from either an array or properties
     * or the configuration file, and the configuration project,
     * and updates the logger based on the configuration information
     * found.
     *
     * @param Logger2 $logger logger for information and errors
     * @param array $properties associative array or property names and values.
     * @param string $propertiesFile the name of the properties file to use
     *     (used as an alternative to the properties array).
     */
    public function __construct($logger, $properties = null, $propertiesFile = null)
    {
        $this->logger = $logger;
        $this->errorHandler = new EtlErrorHandler();

        $this->app = $logger->getApp();

        $this->propertiesFile = $propertiesFile;

        #--------------------------------------------------------------------
        # If there isn't a properties array, then read the properties file
        #--------------------------------------------------------------------
        if (!isset($properties) || !is_array($properties)) {
            if (!isset($propertiesFile) || trim($propertiesFile) === '') {
                $message = 'No properties or properties file was specified.';
                $code    = EtlException::INPUT_ERROR;
                $this->errorHandler->throwException($message, $code);
            } else {
                $propertiesFile = trim($propertiesFile);
                $properties = parse_ini_file($propertiesFile);
                if ($properties === false) {
                    $message = 'The properties file \"'.$propertiesFile.'\" could not be read.';
                    $code    = EtlException::INPUT_ERROR;
                    $this->errorHandler->throwException($message, $code);
                }
            }
        }

        $this->properties = $properties;


        #-----------------------------------------------------------------------------
        # Get the log file and set it in the logger, so that messages
        # will start to log to the file
        #-----------------------------------------------------------------------------
        $this->logFile = null;
        if (array_key_exists(Configuration::LOG_FILE_PROPERTY, $properties)) {
            $this->logFile = $properties[Configuration::LOG_FILE_PROPERTY];
            if (!empty($this->logFile)) {
                $extension = pathinfo($this->logFile, PATHINFO_EXTENSION);
                if ($extension === '') {
                    $this->logFile = preg_replace("/$extension$/", '.'.$this->app, $this->logFile);
                } else {
                    $this->logFile = preg_replace(
                        "/$extension$/",
                        $this->app.'.'.$extension,
                        $this->logFile
                    );
                }

                $this->logger->setLogFile($this->logFile);
            }
        }


        #-----------------------------------------------------------
        # Error e-mail notification information
        #-----------------------------------------------------------
        $this->fromEmailAddress  = null;
        $this->adminEmailList   = null;
        if (array_key_exists(Configuration::FROM_EMAIL_ADDRESS_PROPERTY, $properties)) {
            $this->fromEmailAddress = $properties[Configuration::FROM_EMAIL_ADDRESS_PROPERTY];
        }

        $this->emailSubject = Configuration::DEFAULT_EMAIL_SUBJECT;
        if (array_key_exists(Configuration::EMAIL_SUBJECT_PROPERTY, $properties)) {
            $this->emailSubject = $properties[Configuration::EMAIL_SUBJECT_PROPERTY];
        }

        if (array_key_exists(Configuration::ADMIN_EMAIL_LIST_PROPERTY, $properties)) {
            $this->adminEmailList = $properties[Configuration::ADMIN_EMAIL_LIST];
        }

        #------------------------------------------------------
        # Set email logging information
        #------------------------------------------------------
        if (!empty($this->fromEmailAddress) && !empty($this->adminEmailList)) {
            $this->logger->setLogEmail(
                $this->fromEmailAddress,
                $this->adminEmailList,
                $this->emailSubject
            );
        }

        #------------------------------------------------
        # Get the REDCap API URL
        #------------------------------------------------
        if (array_key_exists(Configuration::REDCAP_API_URL_PROPERTY, $properties)) {
            $this->redcapApiUrl = $properties[Configuration::REDCAP_API_URL_PROPERTY];
        } else {
            $message = 'No REDCap API URL property was defined.';
            $this->logger->logInfo($message);
        }

        #---------------------------------------------------------------
        # Get SSL verify flag
        #
        # Indicates if verification should be done for the SSL
        # connection to REDCap. Setting this to false is not secure.
        #---------------------------------------------------------------
        if (array_key_exists(Configuration::SSL_VERIFY_PROPERTY, $properties)) {
            $sslVerify = $properties[Configuration::SSL_VERIFY_PROPERTY];
            if (!isset($sslVerify) || $sslVerify === '' || $sslVerify === '0') {
                $this->sslVerify = false;
            } elseif ($sslVerify === '1') {
                $this->sslVerify = true;
            } else {
                $message = 'Unrecognized value \"'.$sslVerify.'\" for '
                    .Configuration::SSL_VERIFY_PROPERTY
                    .' property; a true a false value should be specified.';
                $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
            }
        } else {
            $this->sslVerify = true;
        }

        #---------------------------------------------------------
        # Get CA (Certificate Authority) Certificate File
        #
        # The CA (Certificate Authority) certificate file used
        # for veriying the REDCap site's SSL certificate (i.e.,
        # for verifying that the REDCap site that is connected
        # to is the one specified).
        #---------------------------------------------------------
        if (array_key_exists(Configuration::CA_CERT_FILE_PROPERTY, $properties)) {
            $this->caCertFile = null;
            $caCertFile = $properties[Configuration::CA_CERT_FILE_PROPERTY];
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
        if (array_key_exists(Configuration::CONFIG_API_TOKEN_PROPERTY, $properties)) {
            $configProjectApiToken = $properties[Configuration::CONFIG_API_TOKEN_PROPERTY];
        } else {
            $message = 'No configuration project API token property was defined.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }

        #------------------------------------------------------
        # If a configuration project API token was defined,
        # process the configuration project
        #------------------------------------------------------
        if (!empty($configProjectApiToken)) {
            $properties = $this->processConfigurationProject($configProjectApiToken, $properties);
        }


        if (array_key_exists(Configuration::ALLOWED_SERVERS_PROPERTY, $properties)) {
            $this->allowedServers = $properties[Configuration::ALLOWED_SERVERS_PROPERTY];
        }


        #----------------------------------------------------------------
        # Get the data source project API token
        #----------------------------------------------------------------
        $this->dataSourceApiToken = '';
        if (array_key_exists(Configuration::DATA_SOURCE_API_TOKEN_PROPERTY, $properties)) {
            $this->dataSourceApiToken = $properties[Configuration::DATA_SOURCE_API_TOKEN_PROPERTY];
        } else {
            $message = 'No data source API token was found in the configuration project.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }
        
        #-------------------------------------------------------------------------------
        # Get the logging project (where log records are written to) API token (if any)
        #-------------------------------------------------------------------------------
        # $startLog = microtime(true);
        if (array_key_exists(Configuration::LOG_PROJECT_API_TOKEN_PROPERTY, $properties)) {
            $this->logProjectApiToken = $properties[Configuration::LOG_PROJECT_API_TOKEN_PROPERTY];
        } else {
            $this->logProjectApiToken = null;
        }

        #----------------------------------------------------------
        # Set the time limit; if none is provided, use the default
        #----------------------------------------------------------
        if (array_key_exists(Configuration::TIME_LIMIT_PROPERTY, $properties)) {
            $this->timeLimit = $properties[Configuration::TIME_LIMIT_PROPERTY];
        } else {
            $this->timeLimit = self::DEFAULT_TIME_LIMIT;
        }

        #-----------------------------------------------
        # Get the timezone, if any
        #-----------------------------------------------
        if (array_key_exists(Configuration::TIMEZONE_PROPERTY, $properties)) {
            $this->timeZone = $properties[Configuration::TIMEZONE_PROPERTY];
        }


        // Record whether or not the actual ETL should be run. This is
        // used by the DET handler program, but not the batch program
        if (array_key_exists(Configuration::TRIGGER_ETL_PROPERTY, $properties)) {
            $this->triggerEtl = $properties[Configuration::TRIGGER_ETL_PROPERTY];
        }

        // Determine the batch size to use (how many records to process at once)
        // Batch size is expected to be a positive integer. The Configuration
        // project should enforce that.
        $this->batchSize = $properties[Configuration::BATCH_SIZE_PROPERTY];

        $this->processTransformationRules($properties);

        #----------------------------------------------------------------
        # Get the table prefix (if any)
        #----------------------------------------------------------------
        if (array_key_exists(Configuration::TABLE_PREFIX_PROPERTY, $properties)) {
            $this->tablePrefix = $properties[Configuration::TABLE_PREFIX_PROPERTY];
        }


        #----------------------------------------------------------------
        # Get the label view suffix (if any)
        #----------------------------------------------------------------
        if (array_key_exists(Configuration::LABEL_VIEW_SUFFIX_PROPERTY, $properties)) {
            $this->labelViewSuffix = $properties[Configuration::LABEL_VIEW_SUFFIX_PROPERTY];
        }


        #---------------------------------------------------
        # Create a database connection for the database
        # where the transformed REDCap data will be stored
        #---------------------------------------------------
        if (array_key_exists(Configuration::DB_CONNECTION_PROPERTY, $properties)) {
            $this->dbConnection = $properties[Configuration::DB_CONNECTION_PROPERTY];
        } else {
            $message = 'No database connection was specified in the '
                . 'configuration project.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }
    
        return true;
    }

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
            $this->errorHandler->throwException($message, EtlException::PHPCAP_ERROR, $exception);
        }

        #-----------------------------------------------------------------------
        # Get the Configuration Project
        #-----------------------------------------------------------------------
        try {
            $this->configProject = $redCap->getProject($configProjectApiToken);
            $results = $this->configProject->exportRecords();
        } catch (PhpCapException $exception) {
            $error = "Could not get Configuration data";
            $this->errorHandler->throwException($error, EtlException::PHPCAP_ERROR, $exception);
        }
        $this->configuration = $results[0];

        #----------------------------------------------------------------------
        # Merge the properties from the configuration project with those from
        # from the configuration file, with non-blank values from the
        # configuration project replacing those from the configuration file.
        #----------------------------------------------------------------------
        foreach ($this->configuration as $key => $value) {
            if (array_key_exists($key, $properties)) {
                if (trim($value) !== '') {
                    $properties[$key] = $value;
                }
            } else {
                $properties[$key] = $value;
            }
        }
        $this->properties = $properties;

        #--------------------------------------------------------------
        # Now that the configuration project has been read,
        # if it specified an admin e-mail, replace the notifier
        # sender with this e-mail address.
        #--------------------------------------------------------------
        if (!empty($this->fromEmailAddress)) {
            if (array_key_exists(Configuration::ADMIN_EMAIL_LIST_PROPERTY, $properties)) {
                $this->adminEmailList = trim($properties[Configuration::ADMIN_EMAIL_LIST_PROPERTY]);
                if (!empty($this->adminEmailList)) {
                    $this->logger->setLogEmailTo($this->adminEmailList);
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
            $this->errorHandler->throwException($message, EtlException::PHPCAP_ERROR, $exception);
        }

        return $properties;
    }


    /**
     * Gets the transformation rules.
     */
    private function processTransformationRules($properties)
    {
        $this->transformRulesSource = $properties[Configuration::TRANSFORM_RULES_SOURCE_PROPERTY];

        if ($this->transformRulesSource === self::TRANSFORM_RULES_TEXT) {
            $this->transformationRules = $properties[Configuration::TRANSFORM_RULES_TEXT_PROPERTY];
            if ($this->transformationRules == '') {
                $error = 'No transformation rules were entered.';
                $this->errorHandler->throwException($error, EtlException::FILE_ERROR);
            }
        } elseif ($this->transformRulesSource === self::TRANSFORM_RULES_FILE) {
            if ($this->isFromFile(Configuration::TRANSFORM_RULES_FILE_PROPERTY)) {
                $file = $properties[Configuration::TRANSFORM_RULES_FILE_PROPERTY];
                if ($this->isAbsolutePath($file)) {
                    $file = realpath($file);
                } else {
                    if (empty($this->propertiesFile)) {
                        # if no properties file was specified, and a relative
                        # path was used, make it relative to this file
                        $file = realpath(__DIR__.'/'.$file);
                    } else {
                        # take path relative to properties file
                        $propertiesFileDir = dirname(realpath($this->propertiesFile));
                        $file = realpath($propertiesFileDir . '/' . $file);
                    }
                }
                $this->transformationRules = file_get_contents($file);
            } else {
                $results = $this->configProject->exportFile(
                    $properties['record_id'],
                    Configuration::TRANSFORM_RULES_FILE_PROPERTY
                );
                $this->transformationRules = $results;
                if ($this->transformationRules == '') {
                    $error = 'No transformation rules file was found.';
                    $this->errorHandler->throwException($error, EtlException::FILE_ERROR);
                }
            }
        } else {
            $message = 'Unrecognized transformation rules source: '.$this->transformRulesSource;
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }
    }


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
     * Gets the specified (configuration file) property.
     */
    public function getProperty($name)
    {
        return $this->properties[$name];
    }

    /**
     * Indicates if the current value for the specified property is from
     * the configuration file (as opposed to the configuration project).
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

    public function getCaCertFile()
    {
        return $this->caCertFile;
    }

    public function getConfigProject()
    {
        return $this->configProject;
    }

    public function getDataSourceApiToken()
    {
        return $this->dataSourceApiToken;
    }

    public function getDbConnection()
    {
        return $this->dbConnection;
    }

    public function getEmailSubject()
    {
        return $this->emailSubject;
    }

    public function getFromEmailAddress()
    {
        return $this->fromEmailAddress;
    }

    public function getLabelViewSuffix()
    {
        return $this->labelViewSuffix;
    }

    public function getLogFile()
    {
        return $this->logFile;
    }

    public function getProjectId()
    {
        return $this->projectId;
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

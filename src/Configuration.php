<?php

namespace IU\REDCapETL;

use IU\PHPCap\RedCap;
use IU\PHPCap\PhpCapException;

use IU\REDCapETL\Database\DBConnectFactory;

class Configuration
{
    # Transform rules source values
    const TRANSFORM_RULES_TEXT    = '1';
    const TRANSFORM_RULES_FILE    = '2';
    const TRANSFORM_RULES_DEFAULT = '3';

    # Default values
    const DEFAULT_EMAIL_SUBJECT = 'REDCap ETL Error';
    const DEFAULT_TIME_LIMIT    = 0;    # zero => no time limit
    
    private $logger;
    private $errorHandler;

    private $app;
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

    private $emailFromAddres;
    private $emailSubject;
    private $emailToList;


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
    public function __construct($logger, $properties = null, $propertiesFile = null, $useWebScriptLogFile = false)
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
        if ($useWebScriptLogFile
                && array_key_exists(ConfigProperties::WEB_SCRIPT_LOG_FILE, $properties)) {
            $this->logFile = $properties[ConfigProperties::WEB_SCRIPT_LOG_FILE];
        } elseif (array_key_exists(ConfigProperties::LOG_FILE, $properties)) {
            $this->logFile = $properties[ConfigProperties::LOG_FILE];
        }
        
        
        if (!empty($this->logFile)) {
            #--------------------------------------------------------
            # If the logging file property used was set in a file,
            # allow a relative path
            #---------------------------------------------------------
            if (!($this->isAbsolutePath($this->logFile))) {
                if (empty($this->propertiesFile)) {
                    # if no properties file was specified, and a relative
                    # path was used, make it relative to this file
                    $this->logFile = __DIR__.'/'.$this->logFile;
                } else {
                    # take path relative to properties file
                    $propertiesFileDir = dirname(realpath($this->propertiesFile));
                    $this->logFile = $propertiesFileDir.'/'.$this->logFile;
                }
            }
            $this->logger->setLogFile($this->logFile);
        }

       
        #-----------------------------------------------------------
        # Error e-mail notification information
        #-----------------------------------------------------------
        $this->emailFromAddress  = null;
        $this->emailToList   = null;
        if (array_key_exists(ConfigProperties::EMAIL_FROM_ADDRESS, $properties)) {
            $this->emailFromAddress = $properties[ConfigProperties::EMAIL_FROM_ADDRESS];
        }

        $this->emailSubject = self::DEFAULT_EMAIL_SUBJECT;
        if (array_key_exists(ConfigProperties::EMAIL_SUBJECT, $properties)) {
            $this->emailSubject = $properties[ConfigProperties::EMAIL_SUBJECT];
        }

        if (array_key_exists(ConfigProperties::EMAIL_TO_LIST, $properties)) {
            $this->emailToList = $properties[ConfigProperties::EMAIL_TO_LIST];
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
        if (array_key_exists(ConfigProperties::REDCAP_API_URL, $properties)) {
            $this->redcapApiUrl = $properties[ConfigProperties::REDCAP_API_URL];
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
        if (array_key_exists(ConfigProperties::SSL_VERIFY, $properties)) {
            $sslVerify = $properties[ConfigProperties::SSL_VERIFY];
            if (!isset($sslVerify) || $sslVerify === '' || $sslVerify === '0') {
                $this->sslVerify = false;
            } elseif ($sslVerify === '1') {
                $this->sslVerify = true;
            } else {
                $message = 'Unrecognized value \"'.$sslVerify.'\" for '
                    .ConfigProperties::SSL_VERIFY
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
        if (array_key_exists(ConfigProperties::CA_CERT_FILE, $properties)) {
            $this->caCertFile = null;
            $caCertFile = $properties[ConfigProperties::CA_CERT_FILE];
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
        if (array_key_exists(ConfigProperties::CONFIG_API_TOKEN, $properties)) {
            $configProjectApiToken = $properties[ConfigProperties::CONFIG_API_TOKEN];
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


        if (array_key_exists(ConfigProperties::ALLOWED_SERVERS, $properties)) {
            $this->allowedServers = $properties[ConfigProperties::ALLOWED_SERVERS];
        }


        #----------------------------------------------------------------
        # Get the data source project API token
        #----------------------------------------------------------------
        $this->dataSourceApiToken = '';
        if (array_key_exists(ConfigProperties::DATA_SOURCE_API_TOKEN, $properties)) {
            $this->dataSourceApiToken = $properties[ConfigProperties::DATA_SOURCE_API_TOKEN];
        } else {
            $message = 'No data source API token was found in the configuration project.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }
        
        #-------------------------------------------------------------------------------
        # Get the logging project (where log records are written to) API token (if any)
        #-------------------------------------------------------------------------------
        # $startLog = microtime(true);
        if (array_key_exists(ConfigProperties::LOG_PROJECT_API_TOKEN, $properties)) {
            $this->logProjectApiToken = $properties[ConfigProperties::LOG_PROJECT_API_TOKEN];
        } else {
            $this->logProjectApiToken = null;
        }

        #----------------------------------------------------------
        # Set the time limit; if none is provided, use the default
        #----------------------------------------------------------
        if (array_key_exists(ConfigProperties::TIME_LIMIT, $properties)) {
            $this->timeLimit = $properties[ConfigProperties::TIME_LIMIT];
        } else {
            $this->timeLimit = self::DEFAULT_TIME_LIMIT;
        }

        #-----------------------------------------------
        # Get the timezone, if any
        #-----------------------------------------------
        if (array_key_exists(ConfigProperties::TIMEZONE, $properties)) {
            $this->timeZone = $properties[ConfigProperties::TIMEZONE];
        }


        // Record whether or not the actual ETL should be run. This is
        // used by the DET handler program, but not the batch program
        if (array_key_exists(ConfigProperties::TRIGGER_ETL, $properties)) {
            $this->triggerEtl = $properties[ConfigProperties::TRIGGER_ETL];
        }

        // Determine the batch size to use (how many records to process at once)
        // Batch size is expected to be a positive integer. The Configuration
        // project should enforce that.
        $this->batchSize = $properties[ConfigProperties::BATCH_SIZE];

        $this->processTransformationRules($properties);

        #----------------------------------------------------------------
        # Get the table prefix (if any)
        #----------------------------------------------------------------
        if (array_key_exists(ConfigProperties::TABLE_PREFIX, $properties)) {
            $this->tablePrefix = $properties[ConfigProperties::TABLE_PREFIX];
        }


        #----------------------------------------------------------------
        # Get the label view suffix (if any)
        #----------------------------------------------------------------
        if (array_key_exists(ConfigProperties::LABEL_VIEW_SUFFIX, $properties)) {
            $this->labelViewSuffix = $properties[ConfigProperties::LABEL_VIEW_SUFFIX];
        }


        #---------------------------------------------------
        # Create a database connection for the database
        # where the transformed REDCap data will be stored
        #---------------------------------------------------
        if (array_key_exists(ConfigProperties::DB_CONNECTION, $properties)) {
            $this->dbConnection = $properties[ConfigProperties::DB_CONNECTION];
            
            # If this property was defined in a file and uses the CSV database
            # type and a relative path was used, replace the relative path with
            # an absolute path
            if ($this->isFromFile(ConfigProperties::DB_CONNECTION)) {
                list($dbType, $dbString) = DBConnectFactory::parseConnectionString($this->dbConnection);
                if ($dbType === DBConnectFactory::DBTYPE_CSV) {
                    if (!$this->isAbsolutePath($dbString)) {
                        //$this->dbConnection = ???;
                        if (empty($this->propertiesFile)) {
                            # if no properties file was specified, and a relative
                            # path was used, make it relative to this file
                            $dbString = __DIR__.'/'.$dbString;
                        } else {
                            # take path relative to properties file
                            $propertiesFileDir = dirname(realpath($this->propertiesFile));
                            $dbString = $propertiesFileDir.'/'.$dbString;
                        }
                        $this->dbConnection = DBConnectFactory::createConnectionString($dbType, $dbString);
                    }
                }
            }
        } else {
            $message = 'No database connection was specified in the '
                . 'configuration project.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
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
        if (!empty($this->emailFromAddress)) {
            if (array_key_exists(ConfigProperties::EMAIL_TO_LIST, $properties)) {
                $this->emailToList = trim($properties[ConfigProperties::EMAIL_TO_LIST]);
                if (!empty($this->emailToList)) {
                    $this->logger->setLogEmailTo($this->emailToList);
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
        $this->transformRulesSource = $properties[ConfigProperties::TRANSFORM_RULES_SOURCE];

        if ($this->transformRulesSource === self::TRANSFORM_RULES_TEXT) {
            $this->transformationRules = $properties[ConfigProperties::TRANSFORM_RULES_TEXT];
            if ($this->transformationRules == '') {
                $error = 'No transformation rules were entered.';
                $this->errorHandler->throwException($error, EtlException::FILE_ERROR);
            }
        } elseif ($this->transformRulesSource === self::TRANSFORM_RULES_FILE) {
            if ($this->isFromFile(ConfigProperties::TRANSFORM_RULES_FILE)) {
                $file = $properties[ConfigProperties::TRANSFORM_RULES_FILE];
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
                    ConfigProperties::TRANSFORM_RULES_FILE
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

    public function getEmailFromAddress()
    {
        return $this->emailFromAddress;
    }

    public function getEmailSubject()
    {
        return $this->emailSubject;
    }

    public function getToEmailList()
    {
        return $this->toEmailList;
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

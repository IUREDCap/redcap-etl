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
    const ADMIN_EMAIL_PROPERTY            = 'admin_email';
    const ALLOWED_SERVERS_PROPERTY        = 'allowed_servers';
    const API_TOKEN_PROPERTY              = 'api_token';
    const BATCH_SIZE_PROPERTY             = 'batch_size';
    const CA_CERT_FILE_PROPERTY           = 'ca_cert_file';
    const DATA_SOURCE_API_TOKEN_PROPERTY  = 'data_source_api_token';
    const DB_CONNECTION_PROPERTY          = 'db_connection';
    const EMAIL_SUBJECT_PROPERTY          = 'email_subject';
    const FROM_EMAIL_ADDRESS_PROPERTY     = 'from_email_address';
    const INITIAL_EMAIL_ADDRESS_PROPERTY  = 'initial_email_address';
    const LABEL_VIEW_SUFFIX_PROPERTY      = 'label_view_suffix';
    const LOG_FILE_PROPERTY               = 'log_file';
    const LOG_PROJECT_API_TOKEN_PROPERTY  = 'log_project_api_token';
    const REDCAP_API_URL_PROPERTY         = 'redcap_api_url';
    const SSL_VERIFY_PROPERTY             = 'ssl_verify';
    const TABLE_PREFIX_PROPERTY           = 'table_prefix';
    const TRANSFORM_RULES_FILE_PROPERTY   = 'transform_rules_file';
    const TRANSFORM_RULES_SOURCE_PROPERTY = 'transform_rules_source';
    const TRANSFORM_RULES_TEXT_PROPERTY   = 'transform_rules_text';
    const TRIGGER_ETL_PROPERTY            = 'trigger_etl';

    // Properties file
    const PROPERTIES_FILE = 'redcap_etl.properties';


    private $logger;
    private $errorHandler;

    private $app;
    private $adminEmail;
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
    private $transformationRules;
    private $triggerEtl;

    private $emailSubject;
    private $fromEmailAddres;

    /**
     * Constructor.
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


        #-----------------------------------------------------------------------------
        # Get the log file and set it in the logger, so that messages
        # will start to log to the file
        #-----------------------------------------------------------------------------
        $this->logFile = null;
        if (array_key_exists(Configuration::LOG_FILE_PROPERTY, $properties)) {
            $this->logFile = $properties[Configuration::LOG_FILE_PROPERTY];
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


        #-----------------------------------------------------------
        # Error e-mail notification information
        #-----------------------------------------------------------
        $this->fromEmailAddress  = null;
        $this->adminEmailAddress = null;
        if (array_key_exists(Configuration::FROM_EMAIL_ADDRESS_PROPERTY, $properties)) {
            $this->fromEmailAddress = $properties[Configuration::FROM_EMAIL_ADDRESS_PROPERTY];
        }

        $this->emailSubject = Configuration::DEFAULT_EMAIL_SUBJECT;
        if (array_key_exists(Configuration::EMAIL_SUBJECT_PROPERTY, $properties)) {
            $this->emailSubject = $properties[Configuration::EMAIL_SUBJECT_PROPERTY];
        }

        if (array_key_exists(Configuration::INITIAL_EMAIL_ADDRESS_PROPERTY, $properties)) {
            $this->adminEmailAddress = $properties[Configuration::INITIAL_EMAIL_ADDRESS_PROPERTY];
        }

        #------------------------------------------------------
        # Set email logging information
        #------------------------------------------------------
        if (!empty($this->fromEmailAddress) && !empty($this->adminEmailAddress)) {
            $this->logger->setLogEmail(
                $this->fromEmailAddress,
                $this->adminEmailAddress,
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
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
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
        if (array_key_exists(Configuration::API_TOKEN_PROPERTY, $properties)) {
            $configProjectApiToken = $properties[Configuration::API_TOKEN_PROPERTY];
        } else {
            $message = 'No API token property was defined.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }

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
        # Read in Configuration Project
        #-----------------------------------------------------------------------
        try {
            $this->configProject = $redCap->getProject($configProjectApiToken);
            $results = $this->configProject->exportRecords();
        } catch (PhpCapException $exception) {
            $error = "Could not get Configuration data";
            $this->errorHandler->throwException($error, EtlException::PHPCAP_ERROR, $exception);
        }

        $configuration = $results[0];

        #--------------------------------------------------------------
        # Now that the configuration project has been read,
        # if it specified an admin e-mail, replace the notifier
        # sender with this e-mail address.
        #--------------------------------------------------------------
        if (!empty($this->fromEmailAddress)) {
            if (array_key_exists(Configuration::ADMIN_EMAIL_PROPERTY, $configuration)) {
                $this->adminEmail = trim($configuration[Configuration::ADMIN_EMAIL_PROPERTY]);
                if (!empty($this->adminEmail)) {
                    $this->logger->setLogEmailTo($this->adminEmail);
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

        $this->allowedServers = $configuration[Configuration::ALLOWED_SERVERS_PROPERTY];


        #----------------------------------------------------------------
        # Get the data source project API token
        #----------------------------------------------------------------
        $this->dataSourceApiToken = '';
        if (array_key_exists(Configuration::DATA_SOURCE_API_TOKEN_PROPERTY, $configuration)) {
            $this->dataSourceApiToken = $configuration[Configuration::DATA_SOURCE_API_TOKEN_PROPERTY];
        } else {
            $message = 'No data source API token was found in the configuration project.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }
        
        #------------------------------------------------------------
        # Get the logging project (where log records are written to)
        #------------------------------------------------------------
        # $startLog = microtime(true);
        if (array_key_exists(Configuration::LOG_PROJECT_API_TOKEN_PROPERTY, $configuration)) {
            $this->logProjectApiToken = $configuration[Configuration::LOG_PROJECT_API_TOKEN_PROPERTY];
        } else {
            $this->logProjectApiToken = null;
        }

        // Record whether or not the actual ETL should be run. This is
        // used by the DET handler program, but not the batch program
        $this->triggerEtl = $configuration[Configuration::TRIGGER_ETL_PROPERTY];

        // Determine the batch size to use (how many records to process at once)
        // Batch size is expected to be a positive integer. The Configuration
        // project should enforce that.
        $this->batchSize = $configuration[Configuration::BATCH_SIZE_PROPERTY];

        #-----------------------------------------------
        # Get the Transformation Rules
        #-----------------------------------------------
        $transformRulesSource = $configuration[Configuration::TRANSFORM_RULES_SOURCE_PROPERTY];
        if ($transformRulesSource === 2) {
            $results = $this->configProject->exportFile(
                $configuration['record_id'],
                Configuration::TRANSFORM_RULES_FILE_PROPERTY
            );
            $this->transformationRules = $results;
            if ($this->transformationRules == '') {
                $error = 'No transformation rules file was found.';
                $this->errorHandler->throwException($error, EtlException::FILE_ERROR);
            }
        } else {
            $this->transformationRules = $configuration[Configuration::TRANSFORM_RULES_TEXT_PROPERTY];
            if ($this->transformationRules == '') {
                $error = 'No transformation rules were entered.';
                $this->errorHandler->throwException($error, EtlException::FILE_ERROR);
            }
        }


        #----------------------------------------------------------------
        # Get the table prefix (if any)
        #----------------------------------------------------------------
        if (array_key_exists(Configuration::TABLE_PREFIX_PROPERTY, $configuration)) {
            $this->tablePrefix = $configuration[Configuration::TABLE_PREFIX_PROPERTY];
        }


        #----------------------------------------------------------------
        # Get the label view suffix (if any)
        #----------------------------------------------------------------
        if (array_key_exists(Configuration::LABEL_VIEW_SUFFIX_PROPERTY, $configuration)) {
            $this->labelViewSuffix = $configuration[Configuration::LABEL_VIEW_SUFFIX_PROPERTY];
        }


        #---------------------------------------------------
        # Create a database connection for the database
        # where the transformed REDCap data will be stored
        #---------------------------------------------------
        if (array_key_exists(Configuration::DB_CONNECTION_PROPERTY, $configuration)) {
            $this->dbConnection = $configuration[Configuration::DB_CONNECTION_PROPERTY];
        } else {
            $message = 'No database connection was specified in the '
                . 'configuration project.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }
    
        return true;
    }

    public function isValidEmail($email)
    {
        $isValid = false;
        if (preg_match('/^[^@]+@[^@]+$/', $email) === 1) {
            $isValid = true;
        }
        return $isValid;
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

    public function getTransformationRules()
    {
        return $this->transformationRules;
    }

    public function getTriggerEtl()
    {
        return $this->triggerEtl;
    }
}

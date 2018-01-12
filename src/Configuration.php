<?php

namespace IU\REDCapETL;

use IU\PHPCap\RedCap;
use IU\PHPCap\PhpCapException;

class Configuration
{
    const DEFAULT_EMAIL_SUBJECT = 'REDCap ETL Error';

    const DATA_SOURCE_TOKEN_KEY = 'data_source_api_token';

    const LOG_FILE_PROPERTY_NAME = 'log_file';

    // Properties file
    const PROPERTIES_FILE = 'redcap_etl.properties';

    const ALLOWED_SERVERS_FIELD = 'allowed_servers';

    private $logger;
    private $errorHandler;

    private $app;
    private $adminEmail;
    private $allowedServers;
    private $batchSize;
    private $dataSourceApiToken;
    private $dbConnection;
    private $labelViewSuffix;
    private $logProjectApiToken;
    private $redcapApiUrl;
    private $tablePrefix;
    private $triggerEtl;

    private $emailSubject;
    private $fromEmailAddres;

    /**
     * Constructor.
     *
     * @param Logger2 $logger logger for information and errors
     * @param boolean $sslVerify indicates if verification should be done for the SSL
     *     connection to REDCap. Setting this to false is not secure.
     * @param string $caCertificateFile
     *     the CA (Certificate Authority) certificate file used for veriying the REDCap site's
     *     SSL certificate (i.e., for verifying that the REDCap site that is
     *     connected to is the one specified).
     * @param array $properties associative array or property names and values.
     */
    public function __construct(
        $logger,
        $sslVerify = true,
        $caCertificateFile = null,
        $properties = null,
        $propertiesFile = RedCapEtl::PROPERTIES_FILE
    ) {
        $this->logger = $logger;
        $this->errorHandler = new EtlErrorHandler();

        $this->app = $logger->getApp();

        #--------------------------------------------------------------------
        # If there isn't a properties array, then read the properties file
        #--------------------------------------------------------------------
        if (!isset($properties) || !is_array($properties)) {
            if (!isset($propertiesFile) || trim($propertiesFile) === '') {
                $propertiesFile = RedCapEtl::PROPERTIES_FILE;
            }
            $properties = parse_ini_file($propertiesFile);
            if ($properties === false) {
                $message = 'No properties argument, and unable to read properties file ';
                $code    = RedCapEtl::PROPERTIES_FILE;
                $this->errorHandler->throwException($message, $code);
            }
        }


        #-----------------------------------------------------------------------------
        # Get the log file and set it in the logger, so that messages
        # will start to log to the file
        #-----------------------------------------------------------------------------
        $this->logFile = null;
        if (array_key_exists('log_file', $properties)) {
            $this->logFile = $properties['log_file'];
            $extension = pathinfo($this->logFile, PATHINFO_EXTENSION);
            if ($extension === '') {
                $this->logFile = preg_replace("/$extension$/", '.'.$this->app, $this->logFile);
            } else {
                $this->logFile = preg_replace("/$extension$/", $this->app.'.'.$extension, $this->logFile);
            }

            $this->logger->setLogFile($this->logFile);
        }


        $this->fromEmailAddress = '';
        if (array_key_exists('from_email_address', $properties)) {
            $this->fromEmailAddress = $properties['from_email_address'];
        }

        $this->emailSubject = Configuration::DEFAULT_EMAIL_SUBJECT;
        if (array_key_exists('email_subject', $properties)) {
            $this->emailSubject = $properties['email_subject'];
        }

        #------------------------------------------------------
        # Set email logging information
        #------------------------------------------------------
        if (isset($this->fromEmailAddress) && array_key_exists('initial_email_address', $properties)) {
            $this->adminEmailAddress = $properties['initial_email_address'];
            $this->logger->setLogEmail(
                $this->fromEmailAddress,
                $properties['initial_email_address'],
                $this->emailSubject
            );
        }


        #------------------------------------------------
        # Get the REDCap API URL
        #------------------------------------------------
        if (array_key_exists('redcap_api_url', $properties)) {
            $this->redcapApiUrl = $properties['redcap_api_url'];
        } else {
            $this->errorHandler->throwException('No REDCap API URL property was defined.', EtlException::INPUT_ERROR);
        }

        #--------------------------------------------------
        # Get the API token for the configuration project
        #--------------------------------------------------
        if (array_key_exists('api_token', $properties)) {
            $configProjectApiToken = $properties['api_token'];
        } else {
            $this->errorHandler->throwException('No API token property was defined.', EtlException::INPUT_ERROR);
        }

        #---------------------------------------------------------------------
        # Create RedCap object to use for getting the configuration projects
        #---------------------------------------------------------------------
        $superToken = null; // There is no need to create projects, so this is not needed

        try {
            $redCap = new RedCap($this->redcapApiUrl, $superToken, $sslVerify, $caCertificateFile);
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
            $error = "Could not get Configuration data.\n";
            $this->errorHandler->throwException($error, EtlException::PHPCAP_ERROR, $exception);
        }

        $configuration = $results[0];

        #--------------------------------------------------------------
        # Now that the configuration project has been read,
        # if it specified an admin e-mail, replace the notifier
        # sender with this e-mail address.
        #--------------------------------------------------------------
        if (array_key_exists('admin_email', $configuration)) {
            $this->adminEmail = $configuration['admin_email'];
            $this->logger->setLogEmailFrom($configuration['admin_email']);
        }

        #------------------------------------------------------
        # Create a REDCap DET (Data Entry Trigger) Handler,
        # in case it's needed.
        #------------------------------------------------------
        # $startDet = microtime(true);
        try {
            $projectId = $this->configProject->exportProjectInfo()['project_id'];
        } catch (PhpCapException $exception) {
            $message = "Unable to retrieve project_id.";
            $this->errorHandler->throwException($message, EtlException::PHPCAP_ERROR, $exception);
        }

        $this->allowedServers = $configuration['allowed_servers'];


        #----------------------------------------------------------------
        # Get the data source project API token
        #----------------------------------------------------------------
        $this->dataSourceApiToken = '';
        if (array_key_exists('data_source_api_token', $configuration)) {
            $this->dataSourceApiToken = $configuration['data_source_api_token'];
        } else {
            $message = 'No data source API token was found in the configuration project.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR, $exception);
        }
        
        #------------------------------------------------------------
        # Get the logging project (where log records are written to)
        #------------------------------------------------------------
        # $startLog = microtime(true);
        if (array_key_exists('log_project_api_token', $configuration)) {
            $this->logProjectApiToken = $configuration['log_project_api_token'];
        } else {
            $this->logProjectApiToken = null;
        }

        // Record whether or not the actual ETL should be run. This is
        // used by the DET handler program, but not the batch program
        $this->triggerEtl = $configuration['trigger_etl'];

        // Determine the batch size to use (how many records to process at once)
        // Batch size is expected to be a positive integer. The Configuration
        // project should enforce that.
        $this->batchSize = $configuration['batch_size'];

        #-----------------------------------------------
        # Get the Transformation Rules
        #-----------------------------------------------
        $transformRulesSource = $configuration['transform_rules_source'];
        if ($transformRulesSource === 2) {
            $results = $this->configProject->exportFile(
                $configuration['record_id'],
                'transform_rules_file'
            );
            $this->transformationRules = $results;
            if ($this->transformationRules == '') {
                $error = 'No transformation rules file was found.';
                $this->errorHandler->throwException($error, EtlException::FILE_ERROR);
            }
        } else {
            $this->transformationRules = $configuration['transform_rules_text'];
            if ($this->transformationRules == '') {
                $error = 'No transformation rules were entered.';
                $this->errorHandler->throwException($error, EtlException::FILE_ERROR);
            }
        }


        #----------------------------------------------------------------
        # Get the table prefix (if any)
        #----------------------------------------------------------------
        if (array_key_exists('table_prefix', $configuration)) {
            $this->tablePrefix = $configuration['table_prefix'];
        }


        #----------------------------------------------------------------
        # Get the label view suffix (if any)
        #----------------------------------------------------------------
        if (array_key_exists('label_view_suffix', $configuration)) {
            $this->labelViewSuffix = $configuration['label_view_suffix'];
        }


        #---------------------------------------------------
        # Create a database connection for the database
        # where the transformed REDCap data will be stored
        #---------------------------------------------------
        if (array_key_exists('db_connection', $configuration)) {
            $this->dbConnection = $configuration['db_connection'];
        } else {
            $message = 'No database connection was specified in the '
                . 'configuration project.';
            $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
        }
    
        return true;
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

    public function getRedCapApiUrl()
    {
        return $this->redcapApiUrl;
    }

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function getTriggerEtl()
    {
        return $this->triggerEtl;
    }
}

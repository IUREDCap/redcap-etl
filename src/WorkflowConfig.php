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
 * Class used to store ETL workflow configuration information from
 * a configuration file.
 *
 * WorkflowConfigs contain one or more task configurations that are
 * executed sequentially by REDCap-ETL with optional global properties
 * that override properties defined for individual task configurations.
 */
class WorkflowConfig
{
    # Keys used in JSON workflow config arrays that are the result of parsing a JSON file or string
    const JSON_WORKFLOW_KEY          = 'workflow';
    const JSON_GLOBAL_PROPERTIES_KEY = 'global_properties';
    const JSON_TASKS_KEY             = 'tasks';

    private $logger;

    private $baseDir;

    /** @var array array of global properties (as map from property name to property value) */
    private $globalProperties;

    /** @var array array of TaskConfigs */
    private $taskConfigs;

    private $configurationFile;

    private $workflowName;

    /** @var boolean true if the workflow configuration represents a workflow (has global properties
     *               or has multiple tasks, or is explicitly marked as a workflow), false
     *               if the configuration has represents a standalone task.
     */
    private $isWorkflow;

    /**
     * Creates a WorkflowConfig object from a workflow config file.
     */
    public function __construct()
    {
        $this->logger = null;
        $this->baseDir = null;
        $this->isWorkflow = false;
    }

    /**
     * Sets up a workflow configuration.
     *
     * @param Logger $logger logger for information and errors
     *
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     *     If a properties file name string is used, then it is assumed
     *     to be a JSON file if the file name ends with .json, and a
     *     .ini file if it ends with .ini.
     *
     * @param string $baseDir base directory for $properties, if it is an array.
     *     This is used for file properties that are specified as relative paths.
     */
    public function set(&$logger, $properties, $baseDir = null)
    {
        $this->logger = $logger;
        $this->baseDir = $baseDir;

        $this->globalProperties = array();
        $this->taskConfigs = array();

        if (empty($properties)) {
            $message = 'No configuration was specified.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        } elseif (is_array($properties)) {
            # Configuration properties specified as an array
            $this->processPropertiesArrayConfig($properties);
        } elseif (is_string($properties)) {
            # Configuration is in a file (properties is the name/path of the file)
            $this->configurationFile = trim($properties);

            $baseDir = realpath(dirname($this->configurationFile));

            if (preg_match('/\.ini$/i', $this->configurationFile) === 1) {
                #---------------------------
                # .ini configuration file
                #---------------------------
                $this->parseIniWorkflowConfigFile($this->configurationFile);
            } elseif (preg_match('/\.json$/i', $this->configurationFile) === 1) {
                #-----------------------------------------------------------------
                # JSON configuration file
                #-----------------------------------------------------------------
                $this->parseJsonWorkflowConfigFile($this->configurationFile);
            } else {
                $message = 'Configuration file "'.$this->configurationFile.'" is not a .ini or .json file.';
                $code    = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }
        } else {
            $message = 'Unrecognized configuration type "'.gettype($properties).'" was specified.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }
    }


    /**
     * Parses a JSON format workflow configuration file.
     */
    public function parseJsonWorkflowConfigFile($configurationFile)
    {
        $baseDir = realpath(dirname($configurationFile));
        $this->baseDir = $baseDir;

        $configurationFileContents = false;
        try {
            $configurationFileContents = file_get_contents($configurationFile);
        } catch (\Exception $exception) {
            $configurationFileContents = false;
        }

        if ($configurationFileContents === false) {
            $message = 'The workflow configuration file "'.$this->configurationFile.'" could not be read.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }

        $config = json_decode($configurationFileContents, true);
        if ($config == null && json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Error parsing JSON configuration file "'.$this->configurationFile.'": '.json_last_error();
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }

        if (array_key_exists(self::JSON_WORKFLOW_KEY, $config)) {
            $this->isWorkflow = true;

            # WorkflowConfig
            if (count($config) > 1) {
                throw new \Exception("Non-workflow properties at top-level of workflow configuration.");
            }

            $workflowConfig = $config[self::JSON_WORKFLOW_KEY];

            #------------------------------------------------
            # Process global properties
            #------------------------------------------------
            if (array_key_exists(self::JSON_GLOBAL_PROPERTIES_KEY, $workflowConfig)) {
                $this->globalProperties = $workflowConfig[self::JSON_GLOBAL_PROPERTIES_KEY];
                $this->globalProperties = $this->processJsonProperties($this->globalProperties);
                $this->globalProperties = TaskConfig::makeFilePropertiesAbsolute($this->globalProperties, $baseDir);

                # Set workflow logger based on the global properties
                $this->setLoggerWithProperties($this->globalProperties);

                if (!array_key_exists(ConfigProperties::WORKFLOW_NAME, $this->globalProperties)
                        || empty(trim($this->globalProperties[ConfigProperties::WORKFLOW_NAME]))) {
                    $message = 'No workflow name was specified.';
                    $code    = EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                } else {
                    $this->workflowName = trim($this->globalProperties[ConfigProperties::WORKFLOW_NAME]);
                    unset($this->globalProperties[ConfigProperties::WORKFLOW_NAME]);
                }
            } else {
                $message = 'No global properties section was specified.';
                $code    = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }

            #-----------------------------------------
            # Process tasks
            #-----------------------------------------
            if (array_key_exists(self::JSON_TASKS_KEY, $workflowConfig)) {
                $taskConfigs = $workflowConfig[self::JSON_TASKS_KEY];
                foreach ($taskConfigs as $taskName => $taskProperties) {
                    $taskProperties = $this->processJsonProperties($taskProperties);
                    $taskProperties = TaskConfig::makeFilePropertiesAbsolute($taskProperties, $baseDir);

                    $includedTaskProperties = $this->extractIncludedTaskProperties($taskProperties);

                    $properties = $includedTaskProperties;
                    $properties = TaskConfig::overrideProperties($properties, $this->globalProperties);
                    $properties = TaskConfig::overrideProperties($properties, $taskProperties);

                    #print "\n\nPROPERTIES:\n";
                    #print_r($properties);
                    $taskConfig = new TaskConfig();
                    $taskConfig->set($this->logger, $properties, $taskName, $this->baseDir);
                    $taskConfig->setWorkflowLogger($this->logger);
                    $this->taskConfigs[] = $taskConfig;
                }
            } else {
                throw new \Exception("No tasks defined for workflow.");
            }
        } else {
            $this->isWorkflow = false;

            # Single configuration
            $properties = $this->processJsonProperties($config);
            $properties = TaskConfig::makeFilePropertiesAbsolute($properties, $baseDir);

            #print "\n\nPROPERTIES:\n";
            #print_r($properties);

            $taskConfig = new TaskConfig();
            $taskConfig->set($this->logger, $properties, '', $this->baseDir);
            $this->taskConfigs[] = $taskConfig;
        }
    }
    
    /**
     * Processes the specified JSON property by combining array values into a single
     * value for properties that have multi-line values, and by converting boolean
     * properties to string values.
     *
     * @parameter array $properties map from property name to property value(s).
     */
    public function processJsonProperties($properties)
    {
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

        // Reset boolean property values to be string values
        foreach ($properties as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $properties[$key] = 'true';
                } else {
                    $properties[$key] = 'false';
                }
            }
        }
        return $properties;
    }

    /** Parses a .ini configuration file
     *
     * @param string $configurationFile full path of .ini configuration file to parse
     */
    public function parseIniWorkflowConfigFile($configurationFile)
    {
        if (!isset($this->baseDir)) {
            $this->baseDir = realpath(dirname($configurationFile));
        }

        # Check to make sure that no section names use config property names
        $sectionErrorMessage = '';
        $sections = self::getSections($configurationFile);
        $configPropertyNames = ConfigProperties::getProperties();
        foreach ($sections as $section) {
            if (in_array($section, $configPropertyNames)) {
                $sectionErrorMessage= 'Task "'.$section.'" uses the same name as a REDCap-ETL configuration property.';
                break;
            }
        }

        $processSections = true;
        $configurationArray = parse_ini_file($configurationFile, $processSections);

        $this->processPropertiesArrayConfig($configurationArray);

        # If there was a section name error, throw the exception now that logging has been set
        if (!empty($sectionErrorMessage)) {
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($sectionErrorMessage, $code);
        }
    }

    /**
     * Processes configuration that is specified as an array of properties and
     * sets the Workflow configuration based on these values.
     *
     * @param array $configurationArray array of configuration properties.
     */
    public function processPropertiesArrayConfig($configurationArray)
    {
        $baseDir = $this->baseDir;
        $isWorkflowConfig = $this->isIniWorkflowConfig($configurationArray);

        if ($isWorkflowConfig) {
            $this->isWorkflow = true;

            foreach ($configurationArray as $propertyName => $propertyValue) {
                if (is_array($propertyValue)) {
                    #-------------------------------------------------
                    # Section (that defines a task configuration)
                    #-------------------------------------------------
                    $section = $propertyName;
                    $sectionProperties = $propertyValue;
                    $sectionProperties = TaskConfig::makeFilePropertiesAbsolute($sectionProperties, $baseDir);
                    $configFile = null;

                    $globalProperties = $this->globalProperties;

                    $this->setLoggerWithProperties($globalProperties);

                    # Process the workflow name
                    if (!array_key_exists(ConfigProperties::WORKFLOW_NAME, $globalProperties)
                            || empty(trim($globalProperties[ConfigProperties::WORKFLOW_NAME]))) {
                        $message = 'No workflow name was specified.';
                        $code    = EtlException::INPUT_ERROR;
                        throw new EtlException($message, $code);
                    } else {
                        $this->workflowName = trim($globalProperties[ConfigProperties::WORKFLOW_NAME]);
                        unset($globalProperties[ConfigProperties::WORKFLOW_NAME]);
                    }

                    #-----------------------------------------------------
                    # Add included task config properties, if any
                    #-----------------------------------------------------
                    $includedTaskProperties = $this->extractIncludedTaskProperties($sectionProperties);

                    #--------------------------------------------------------------
                    # Combine the config file, global and section properties
                    #
                    # Properties used from lowest to highest precedence:
                    # config file properties, global properties, properties defined in section
                    #--------------------------------------------------------------
                    $properties = $includedTaskProperties;
                    $properties = TaskConfig::overrideProperties($properties, $globalProperties);
                    $properties = TaskConfig::overrideProperties($properties, $sectionProperties);

                    $taskConfig = new TaskConfig();
                    $taskConfig->set($this->logger, $properties, $section, $this->baseDir);
                    $taskConfig->setWorkflowLogger($this->logger);

                    $this->taskConfigs[] = $taskConfig;
                } else {
                    #------------------------------
                    # Global property
                    #------------------------------
                    $this->globalProperties[$propertyName] =  TaskConfig::getAbsoluteFilePropertyValue(
                        $propertyName,
                        $propertyValue,
                        $baseDir
                    );
                }
            }
        } else {
            #---------------------------------
            # Stand alone task
            #---------------------------------
            $this->isWorkflow = false;
            $taskConfig = new TaskConfig();
            $taskConfig->set($this->logger, $configurationArray, '', $this->baseDir);
            array_push($this->taskConfigs, $taskConfig);
        }
    }

    public function extractIncludedTaskProperties(&$properties)
    {
        $includedTaskProperties = array();

        if (array_key_exists(ConfigProperties::TASK_CONFIG_FILE, $properties)) {
            if (array_key_exists(ConfigProperties::TASK_CONFIG, $properties)) {
                $message = 'Only one of the following properties can be used in the same task: '
                    . '"' . ConfigProperties::TASK_CONFIG . '"' . ", "
                    . '"' . ConfigProperties::TASK_CONFIG_FILE . '"'
                    ;
                $code = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }
            # Task config file property - task included as file
            $configFile = $properties[ConfigProperties::TASK_CONFIG_FILE];
            unset($properties[ConfigProperties::TASK_CONFIG_FILE]);
            $includedTaskProperties = TaskConfig::getPropertiesFromFile($configFile);
            $includedTaskProperties =
            TaskConfig::makeFilePropertiesAbsolute($includedTaskProperties, $this->baseDir);
        } elseif (array_key_exists(ConfigProperties::TASK_CONFIG, $properties)) {
            # Task config property - task included in place (as array)
            $includedTaskProperties = $properties[ConfigProperties::TASK_CONFIG];
            unset($properties[ConfigProperties::TASK_CONFIG]);
        }

        return $includedTaskProperties;
    }


    /**
     * Indicates if the the specified configuration is a a workflow (as opposed to
     * a single task).
     *
     * @param array $config configuration specified as a propeties array.
     *
     * @return true if this is a workflow configuration, false otherwise
     */
    public function isIniWorkflowConfig($config)
    {
        $isWorkflowConfig = false;
        $numArrays = 0;
        $numScalars = 0;
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $isWorkflowConfig = true;
                break;
            }
        }

        return $isWorkflowConfig;
    }

    /*
    public function toString()
    {
        $string = '';
        $string .= "Global Properties [\n";
        foreach ($this->globalProperties as $name => $value) {
            $string .= "    {$name}: {$value}\n";
        }
        $string .= "]\n";
        foreach ($this->taskConfigs as $name => $taskConfig) {
            $string .= "TaskConfig \"{$name}\": [\n";
            $string .= print_r($taskConfig, true);
            $string .= "]\n";
        }

        return $string;
    }
    */

    public function getGlobalProperties()
    {
        return $this->globalProperties;
    }

    public function getTaskConfigs()
    {
        return $this->taskConfigs;
    }

    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets the logger based on global properties.
     * Values are set so that logging can be done for the cases outside of task
     * processing when an error can occur:
     * 1) There is an error in the workflow cofiguration file
     * 2) For the remote server case with the external module, REDCap-ETL is unable
     *    to connect to the remote server.
     *
     * Note that only print logging and e-mail logging are set, because
     * file logging will not work in general (e.g., the remote server case), and
     * database logging might not work (remote server case).
     *
     * Also used for case e-mailing a single summary for a workflow, instead of
     * one for each task.
     *
     * @param array $properties properties array.
     */
    public function setLoggerWithProperties($properties)
    {
        $globalTaskConfig = new TaskConfig();
        $globalTaskConfig->setLogging($properties);

        $this->logger->setPrintLogging($globalTaskConfig->getPrintLogging());

        $this->logger->setEmailErrors($globalTaskConfig->getEmailErrors());
        $this->logger->setEmailSummary($globalTaskConfig->getEmailSummary());

        $this->logger->setLogEmail(
            $globalTaskConfig->getEmailFromAddress(),
            $globalTaskConfig->getEmailToList(),
            $globalTaskConfig->getEmailSubject()
        );
    }

    /**
     * Gets the section names of a .ini file. This method uses a regular expression
     * instead of the parse_ini_file, because the parse_ini_file cannot correctly
     * recognize the case where there is a top-level property and section that
     * have the same name (the section will effectively overwrite the top-level
     * property).
     *
     * @param string $iniFile the path of the .ini file for which sections should be retrieved.
     *
     * @return array an array of strings with the names of the sections in the .ini file.
     */
    public static function getSections($iniFile)
    {
        $sections = array();
        $matches = array();

        $fp = false;
        $errorMessage = null;
        try {
            $fp = fopen($iniFile, "r");
        } catch (\Exception $exception) {
            ;
        }

        if (!$fp) {
            $message = 'Unable to read file "'.$iniFile.'"';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }

        while (!feof($fp)) {
            $line = fgets($fp);
            preg_match('/^\[([^\]]*)\]/', $line, $matches);
            if (count($matches) >= 2) {
                $sections[] = $matches[1];
            }
        }
        fclose($fp);
        return $sections;
    }

    /**
     * Indicates if the workflow configuration represents a workflow.
     */
    public function isWorkflow()
    {
        return $this->isWorkflow;
    }
}

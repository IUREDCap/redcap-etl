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

    /**
     * Creates a WorkflowConfig object from a workflow config file.
     */
    public function __construct()
    {
        $this->logger = null;
        $this->baseDir = null;
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
     *     .ini file otherwise.
     *
     * @param string $baseDir base directory for $properties, if it is an array.
     *     This is used for file properties that are specified as relative paths.
     */
    public function set(& $logger, $properties, $baseDir = null)
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
            # TaskConfig specified as an array of properties
            $taskConfig = new TaskConfig();
            $taskConfig->set($logger, $properties, $this->baseDir);
            $this->taskConfigs[] = $taskConfig;
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
                $message = 'Non-JSON workflow configuration file specified.';
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

        $configurationFileContents = file_get_contents($configurationFile);
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
            # WorkflowConfig
            if (count($config) > 1) {
                throw new \Exception("Non-workflow properties at top-level of workflow configuration.");
            }

            $workflowConfig = $config[self::JSON_WORKFLOW_KEY];
            if (array_key_exists(self::JSON_GLOBAL_PROPERTIES_KEY, $workflowConfig)) {
                $this->globalProperties = $workflowConfig[self::JSON_GLOBAL_PROPERTIES_KEY];
                $this->globalProperties = $this->processJsonProperties($this->globalProperties);
                $this->globalProperties = TaskConfig::makeFilePropertiesAbsolute($this->globalProperties, $baseDir);

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
                $message = 'No workflow name was specified.';
                $code    = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }

            if (array_key_exists(self::JSON_TASKS_KEY, $workflowConfig)) {
                $taskConfigs = $workflowConfig[self::JSON_TASKS_KEY];
                foreach ($taskConfigs as $properties) {
                    $properties = $this->processJsonProperties($properties);
                    $properties = TaskConfig::makeFilePropertiesAbsolute($properties, $baseDir);
                    $properties = TaskConfig::overrideProperties($this->globalProperties, $properties);
                    #print "\n\nPROPERTIES:\n";
                    #print_r($properties);
                    $taskConfig = new TaskConfig();
                    $taskConfig->set($this->logger, $properties, $this->baseDir);
                    $this->taskConfigs[] = $taskConfig;
                }
            } else {
                throw new \Exception("No tasks defined for workflow.");
            }
        } else {
            # Single configuration
            $properties = $this->processJsonProperties($config);
            $properties = TaskConfig::makeFilePropertiesAbsolute($properties, $baseDir);

            #print "\n\nPROPERTIES:\n";
            #print_r($properties);

            $taskConfig = new TaskConfig();
            $taskConfig->set($this->logger, $properties, $this->baseDir);
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

        foreach ($properties as $key => $value) {
            if (is_bool($value)) {
                if ($key) {
                    $properties[$key] = 'true';
                } else {
                    $properties[$key] = 'false';
                }
            }
        }
        return $properties;
    }

    public function parseIniWorkflowConfigFile($configurationFile)
    {
        $baseDir = realpath(dirname($configurationFile));

        $processSections = true;
        $config = parse_ini_file($configurationFile, $processSections);

        $isWorkflowConfig = $this->isIniWorkflowConfig($config);

        if ($isWorkflowConfig) {
            foreach ($config as $propertyName => $propertyValue) {
                if (is_array($propertyValue)) {
                    #-------------------------------------------------
                    # Section (that defines a task configuration)
                    #-------------------------------------------------
                    $section = $propertyName;
                    $sectionProperties = $propertyValue;
                    $sectionProperties = TaskConfig::makeFilePropertiesAbsolute($sectionProperties, $baseDir);
                    $configFile = null;

                    #----------------------------
                    # Global properties
                    #----------------------------
                    $globalProperties = TaskConfig::makeFilePropertiesAbsolute($this->globalProperties, $baseDir);

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
                    # Add included config file properties, if any
                    #-----------------------------------------------------
                    $fileProperties = array();
                    if (array_key_exists(ConfigProperties::TASK_CONFIG_FILE, $sectionProperties)) {
                        # Config file property
                        $configFile = $sectionProperties[ConfigProperties::TASK_CONFIG_FILE];
                        unset($sectionProperties[ConfigProperties::TASK_CONFIG_FILE]);
                        $fileProperties = TaskConfig::getPropertiesFromFile($configFile);
                        $fileProperties = TaskConfig::makeFilePropertiesAbsolute($fileProperties, $baseDir);
                    }

                    #--------------------------------------------------------------
                    # Combine the config file, global and section properties
                    #
                    # Properties used from lowest to highest precedence:
                    # config file properties, global properties, properties defined in section
                    #--------------------------------------------------------------
                    $properties = $fileProperties;
                    $properties = TaskConfig::overrideProperties($properties, $globalProperties);
                    $properties = TaskConfig::overrideProperties($properties, $sectionProperties);

                    $taskConfig = new TaskConfig();
                    $taskConfig->set($this->logger, $properties, $this->baseDir);

                    $this->taskConfigs[] = $taskConfig;
                } else {
                    #------------------------------
                    # Global property
                    #------------------------------
                    $this->globalProperties[$propertyName] =  $propertyValue;
                }
            }
        } else {
            $taskConfig = new TaskConfig();
            $taskConfig->set($this->logger, $configurationFile, $this->baseDir);
            array_push($this->taskConfigs, $taskConfig);
        }

        # Parse file into properties and sections
        $properties = array();
        $sections = array();
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $sections[$key] = $value;
            } else {
                $properties[$key] = $value;
            }
        }
    }


    /**
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

    public function generateJson()
    {
        $data = [$this->globalProperties, $this->taskConfigs];
        $json = json_encode($data);
        #$json = json_encode($this, JSON_FORCE_OBJECT); // | JSON_PRETTY_PRINT);
        return $json;
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

    public function getTaskConfigs()
    {
        return $this->taskConfigs;
    }

    public function getWorkflowName()
    {
        return $this->workflowName;
    }
}

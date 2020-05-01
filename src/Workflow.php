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
 * Class used to store ETL workflow information from
 * a configuration file.
 *
 * Workflows contain one or more configurations that are
 * executed sequentially by REDCap-ETL with optional global properties
 * that override properties defined for individual configurations.
 */
class Workflow
{
    private $logger;

    /** @var array array of global properties (as map from property name to property value) */
    private $globalProperties;

    /** @var array array of Configurations */
    private $configurations;

    private $configurationFile;

    /**
     * Creates a Workflow object from a workflow file.
     *
     * @param Logger $logger logger for information and errors
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     *     If a properties file name string is used, then it is assumed
     *     to be a JSON file if the file name ends with .json, and a
     *     .ini file otherwise.
     */
    public function __construct(& $logger, $configurationFile)
    {
        $this->logger = $logger;

        $this->globalProperties = array();
        $this->configurations = array();

        if (empty($configurationFile)) {
            $message = 'No configuration file was specified.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        } else {
            $this->configurationFile = trim($configurationFile);

            if (preg_match('/\.ini/i', $this->configurationFile) === 1) {
                #---------------------------
                # .ini configuration file
                #---------------------------
                $this->parseIniWorkflowFile($this->configurationFile);
            } elseif (preg_match('/\.json$/i', $this->configurationFile) === 1) {
                #-----------------------------------------------------------------
                # JSON configuration file
                #-----------------------------------------------------------------
                $configurationFileContents = file_get_contents($this->configurationFile);
                if ($configurationFileContents === false) {
                    $message = 'The workflow file "'.$this->configurationFile.'" could not be read.';
                    $code    = EtlException::INPUT_ERROR;
                    throw new EtlException($message, $code);
                }

                $workflowJson = json_decode($configurationFileContents, true);
            } else {
                $message = 'Non-JSON workflow file specified.';
                $code    = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }
        }
    }

    public function parseIniWorkflowFile($configurationFile)
    {
        $processSections = true;
        $config = parse_ini_file($configurationFile, $processSections);

        $isWorkflow = $this->isWorkflow($config);

        if ($isWorkflow) {
            foreach ($config as $propertyName => $propertyValue) {
                if (is_array($propertyValue)) {
                    # Section (that defines a configuration)
                    $section = $propertyName;
                    $properties = $propertyValue;
                    $configFile = null;
                    if (array_key_exists(ConfigProperties::CONFIG_FILE, $properties)) {
                        $configFile = $properties[ConfigProperties::CONFIG_FILE];
                        unset($properties[ConfigProperties::CONFIG_FILE]);
                    }
                    $this->configurations[$section] = new Configuration($this->logger, $properties);
                } else {
                    # Global property
                    $this->globalProperties[$propertyName] =  $propertyValue;
                }
            }
        } else {
            $configuration = new Configuration($this->logger, $configurationFile);
            array_push($this->configurations, $configuration);
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
        #print "SECTIONS:\n";
        #print_r($sections);
        #print "\n\nPROPERTIES:\n";
        #print_r($properties);
    }


    /**
     * @return true if this is a workflow configuration, false otherwise
     */
    public function isWorkflow($config)
    {
        $isWorkflow = false;
        $numArrays = 0;
        $numScalars = 0;
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $isWorkflow = true;
                break;
            }
        }

        return $isWorkflow;
    }

    public function generateJson()
    {
        $data = [$this->globalProperties, $this->configurations];
        $json = json_encode($data, JSON_PRETTY_PRINT);
        return $json;
    }
}

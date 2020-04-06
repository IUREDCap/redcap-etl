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
class Workflow
{
    private $logger;
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
                $this->parseIniworkflowFile($this->configurationFile);
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
            foreach ($config as $section) {
                $properties = $section;
                # Check for config_file property
            }
        } else {
            $configuration = new Configuration($this->logger, $configurationFile);
            array_push($this->configurations, $configuration);
        }
    }


    /**
     * @return true if this is a workflow configuration, false if it is a
     *     single ETL configuration, and throws an exception if an
     *     error is found
     */
    public function isWorkflow($config)
    {
        $isWorkflow = false;
        $numArrays = 0;
        $numScalars = 0;
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $numArrays++;
            } else {
                $numScalars++;
            }
        }

        if ($numScalars > 0 && $numArrays > 0) {
            $message = 'Invalid configuration file. Some properties are in sections and some are not.'
                . ' Workflows must have all properties in sections, and single configurations'
                . ' must have no sections.';
            throw new \Exception($message);
        } elseif ($numArrays > 0 && $numScalars === 0) {
            $isWorkflow = true;
        }

        return $isWorkflow;
    }
}

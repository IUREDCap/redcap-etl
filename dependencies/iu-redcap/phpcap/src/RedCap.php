<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

/**
 * REDCap class used to represent a REDCap instance/site. This class
 * is typically only useful if your progam needs to create
 * REDCap projects and/or needs to access more than one
 * REDCap project.
 */
class RedCap
{
    protected $superToken;
    
    protected $errorHandler;
    
    /** connection to the REDCap API at the $apiURL. */
    protected $connection;

    /** function for creating project object */
    protected $projectConstructorCallback;
    
    /**
     *
     * @param string $apiUrl the URL for the API for your REDCap site.
     * @param string $superToken the user's super token. This needs to be provided if
     *     you are going to create projects.
     * @param boolean $sslVerify indicates if SSL connection to REDCap web site should be verified.
     * @param string $caCertificateFile the full path name of the CA (Certificate Authority)
     *     certificate file.
     * @param ErrorHandlerInterface $errorHandler the error handler that will be
     *    used. This would normally only be set if you want to override the PHPCap's default
     *    error handler.
     * @param RedCapApiConnectionInterface $connection the connection that will be used.
     *    This would normally only be set if you want to override the PHPCap's default
     *    connection. If this argument is specified, the $apiUrl, $sslVerify, and
     *    $caCertificateFile arguments will be ignored, and the values for these
     *    set in the connection will be used.
     */
    public function __construct(
        $apiUrl,
        $superToken = null,
        $sslVerify = false,
        $caCertificateFile = null,
        $errorHandler = null,
        $connection = null
    ) {
        # Need to set errorHandler to default to start in case there is an
        # error with the errorHandler passed as an argument
        # (to be able to handle that error!)
        $this->errorHandler = new ErrorHandler();
        if (isset($errorHandler)) {
            $this->errorHandler = $this->processErrorHandlerArgument($errorHandler);
        } // @codeCoverageIgnore
    
        if (isset($connection)) {
            $this->connection = $this->processConnectionArgument($connection);
        } else {
            $apiUrl    = $this->processApiUrlArgument($apiUrl);
            $sslVerify = $this->processSslVerifyArgument($sslVerify);
            $caCertificateFile = $this->processCaCertificateFileArgument($caCertificateFile);
        
            $this->connection = new RedCapApiConnection($apiUrl, $sslVerify, $caCertificateFile);
        }
        
        $this->superToken = $this->processSuperTokenArgument($superToken);
        
        $this->projectConstructorCallback = function (
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        ) {
            return new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile, $errorHandler, $connection);
        };
    }

    
 
    /**
     * Creates a REDCap project with the specified data.
     * A super token must have been specified for this method to be used.
     *
     * The data fields that can be set are as follows:
     * <ul>
     *   <li>
     *     <b>project_title</b> - the title of the project.
     *   </li>
     *   <li>
     *     <b>purpose</b> - the purpose of the project:
     *     <ul>
     *       <li>0 - Practice/Just for fun</li>
     *       <li>1 - Other</li>
     *       <li>2 - Research</li>
     *       <li>3 - Quality Improvement</li>
     *       <li>4 - Operational Support</li>
     *     </ul>
     *   </li>
     *   <li>
     *     <b>purpose_other</b> - text descibing purpose if purpose above is specified as 1.
     *   </li>
     *   <li>
     *     <b>project_notes</b> - notes about the project.
     *   </li>
     *   <li>
     *     <b>is_longitudinal</b> - indicates if the project is longitudinal (0 = False [default],
     *     1 = True).
     *   </li>
     *   <li>
     *     <b>surveys_enabled</b> - indicates if surveys are enabled (0 = False [default], 1 = True).
     *   </li>
     *   <li>
     *     <b>record_autonumbering_enabled</b> - indicates id record autonumbering is enabled
     *     (0 = False [default], 1 = True).
     *   </li>
     * </ul>
     *
     * @param mixed $projectData the data used for project creation. Note that if
     *     'php' format is used, the data needs to be an array where the keys are
     *     the field names and the values are the field values.
     * @param $format string the format used to export the arm data.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param string $odm string in CDISC ODM XML format that contains metadata and optionally data
     *     to be imported into the created project.
     *
     * @return RedCapProject the project that was created.
     */
    public function createProject(
        $projectData,
        $format = 'php',
        $odm = null
    ) {
        // Note: might want to clone error handler, in case state variables
        // have been added that should differ for different uses, e.g.,
        // a user message that is displayed where you have multiple project
        // objects
        $data = array(
                'token'        => $this->superToken,
                'content'      => 'project',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------------
        # Process the arguments
        #---------------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['data']   = $this->processImportDataArgument($projectData, 'projectData', $format);
        
        if (isset($odm)) {
            $data['odm'] = $this->processOdmArgument($odm);
        }
        
        #---------------------------------------
        # Create the project
        #---------------------------------------
        $apiToken = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($apiToken);
        
        $connection   = clone $this->connection;
        $errorHandler = clone $this->errorHandler;
        
        $projectConstructorCallback = $this->projectConstructorCallback;
        
        # Note: due to an issue with Xdebug, arguments to multi-line function
        #       calls, other than the last one, may not be handled correctly
        #       for code coverage, so the code coverage ignore annotations
        #       needed to be added below.
        $project = call_user_func(
            $projectConstructorCallback,  // @codeCoverageIgnore
            $apiUrl = null,               // @codeCoverageIgnore
            $apiToken,                    // @codeCoverageIgnore
            $sslVerify = null,            // @codeCoverageIgnore
            $caCertificateFile = null,    // @codeCoverageIgnore
            $errorHandler,                // @codeCoverageIgnore
            $connection
        );
        
        return $project;
    }


    /**
     * Gets the REDCap version number of the REDCap instance being used.
     * A super token must have been specified for this method to be used.
     *
     * @return string the REDCap version number of the REDCap instance being used by the project.
     */
    public function exportRedcapVersion()
    {
        $data = array(
            'token' => $this->superToken,
            'content' => 'version'
        );

        $redcapVersion = $this->connection->callWithArray($data);
        # ideally should have export processing here

        return $redcapVersion;
    }


    /**
     * Gets the REDCap project for the specified API token.
     *
     * @param string $apiToken the API token for the project to get.
     *
     * @return \IU\PHPCap\RedCapProject the project for the specified API token.
     */
    public function getProject($apiToken)
    {
        $apiToken = $this->processApiTokenArgument($apiToken);
        
        $connection   = clone $this->connection;
        $errorHandler = clone $this->errorHandler;
        
        $projectConstructorCallback = $this->projectConstructorCallback;
        
        # By default, this creates a RedCapProject
        $project = call_user_func(
            $projectConstructorCallback,   // @codeCoverageIgnore
            $apiUrl = null,                // @codeCoverageIgnore
            $apiToken,                     // @codeCoverageIgnore
            $sslVerify = null,             // @codeCoverageIgnore
            $caCertificateFile = null,     // @codeCoverageIgnore
            $errorHandler,                 // @codeCoverageIgnore
            $connection
        );
        
        return $project;
    }
    
    /**
     * Gets the function used to create projects.
     *
     * @return callable the function used by this class to create projects.
     */
    public function getProjectConstructorCallback()
    {
        return $this->projectConstructorCallback;
    }
    
    /**
     * Sets the function used to create projects in this class.
     * This method would normally only be used if you have extended
     * the RedCapProject class and want RedCap to return
     * projects using your extended class.
     *
     * @param callable $projectConstructorCallback the function to call to create a new project.
     *     The function will be passed the same arguments as the RedCapProject
     *     constructor gets.
     */
    public function setProjectConstructorCallback($projectConstructorCallback)
    {
        $this->projectConstructorCallback
            = $this->processProjectConstructorCallbackArgument($projectConstructorCallback);
    }
    
    /**
     * Gets the error handler being used.
     *
     * @return ErrorHandlerInterface the error handler being used.
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }
    
    /**
     * Set the error handler that is used.
     *
     * @param ErrorHandlerInterface $errorHandler the error handler to use.
     */
    public function setErrorHandler($errorHandler)
    {
        $this->errorHandler = $this->processErrorHandlerArgument($errorHandler);
    }
    
    /**
     * Gets the connection being used.
     *
     * @return RedCapApiConnectionInterface the connection being used.
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Sets the connection that is used.
     *
     * @param RedCapApiConnectionInterface $connection the connection to use.
     */
    public function setConnection($connection)
    {
        $this->connection = $this->processConnectionArgument($connection);
    }

    
    protected function processApiTokenArgument($apiToken)
    {
        if (!isset($apiToken)) {
            $message = 'The REDCap API token specified for the project was null or blank.';
            $code    =  ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } elseif (gettype($apiToken) !== 'string') {
            $message = 'The REDCap API token provided should be a string, but has type: '
                .gettype($apiToken);
            $code =  ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } elseif (!ctype_xdigit($apiToken)) {   // ctype_xdigit - check token for hexidecimal
            $message = 'The REDCap API token has an invalid format.'
                .' It should only contain numbers and the letters A, B, C, D, E and F.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } elseif (strlen($apiToken) != 32) { # Note: super tokens are not valid for project methods
            $message = 'The REDCap API token has an invalid format.'
                .' It has a length of '.strlen($apiToken).' characters, but should have a length of'
                .' 32.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        return $apiToken;
    }
    
    
    protected function processApiUrlArgument($apiUrl)
    {
        # Note: standard PHP URL validation will fail for non-ASCII URLs (so it was not used)
        if (!isset($apiUrl)) {
            $message = 'The REDCap API URL specified for the project was null or blank.';
            $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } elseif (gettype($apiUrl) !== 'string') {
            $message = 'The REDCap API URL provided ('.$apiUrl.') should be a string, but has type: '
                . gettype($apiUrl);
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        return $apiUrl;
    }
    
    
    protected function processCaCertificateFileArgument($caCertificateFile)
    {
        if (isset($caCertificateFile) && gettype($caCertificateFile) !== 'string') {
            $message = 'The value for $sslVerify must be a string, but has type: '
                .gettype($caCertificateFile);
            $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        return $caCertificateFile;
    }
    
    protected function processConnectionArgument($connection)
    {
        if (!($connection instanceof RedCapApiConnectionInterface)) {
            $message = 'The connection argument is not valid, because it doesn\'t implement '
                .RedCapApiConnectionInterface::class.'.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        return $connection;
    }
    
    protected function processErrorHandlerArgument($errorHandler)
    {
        if (!($errorHandler instanceof ErrorHandlerInterface)) {
            $message = 'The error handler argument is not valid, because it doesn\'t implement '
                .ErrorHandlerInterface::class.'.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        return $errorHandler;
    }
    
    protected function processFormatArgument(&$format, $legalFormats)
    {
        if (gettype($format) !== 'string') {
            $message = 'The format specified has type "'.gettype($format).'", but it should be a string.';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } // @codeCoverageIgnore
        
        $format = strtolower(trim($format));
        
        if (!in_array($format, $legalFormats)) {
            $message = 'Invalid format "'.$format.'" specified.'
                .' The format should be one of the following: "'.
                implode('", "', $legalFormats).'".';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } // @codeCoverageIgnore
        
        $dataFormat = '';
        if (strcmp($format, 'php') === 0) {
            $dataFormat = 'json';
        } else {
            $dataFormat = $format;
        }
        
        return $dataFormat;
    }
    
    protected function processImportDataArgument($data, $dataName, $format)
    {
        if (!isset($data)) {
            $message = "No value specified for required argument '".$dataName."'.";
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } elseif ($format === 'php') {
            if (!is_array($data)) {
                $message = "Argument '".$dataName."' has type '".gettype($data)."'"
                    .", but should be an array.";
                    $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
            } // @codeCoverageIgnore
            $data = array($data); // Needs to be an array within an array to work
            $data = json_encode($data);
            
            $jsonError = json_last_error();
            
            switch ($jsonError) {
                case JSON_ERROR_NONE:
                    break;
                default:
                    $message =  'JSON error ('.$jsonError.') "'. json_last_error_msg().
                    '"'." while processing argument '".$dataName."'.";
                    $this->errorHandler->throwException($message, ErrorHandlerInterface::JSON_ERROR);
                    break; // @codeCoverageIgnore
            }
        } else { // @codeCoverageIgnore
            // All non-php formats:
            if (gettype($data) !== 'string') {
                $message = "Argument '".$dataName."' has type '".gettype($data)."'"
                    .", but should be a string.";
                $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
            } // @codeCoverageIgnore
        }
        
        return $data;
    }
    
    protected function processNonExportResult(&$result)
    {
        $matches = array();
        $hasMatch = preg_match('/^[\s]*{"error":\s*"([^"]+)"}[\s]*$/', $result, $matches);
        if ($hasMatch === 1) {
            // note: $matches[0] is the complete string that matched
            //       $matches[1] is just the error message part
            $message = $matches[1];
            $this->errorHandler->throwException($message, ErrorHandlerInterface::REDCAP_API_ERROR);
        } // @codeCoverageIgnore
    }
    
    
    protected function processOdmArgument($odm)
    {
        if (isset($odm) && !is_string($odm)) {
            $message = 'The value for $odm must have type string, but has type: '
                .gettype($odm);
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        return $odm;
    }
    
    protected function processProjectConstructorCallbackArgument($callback)
    {
        if (!is_callable($callback)) {
            $message = 'The project constructor callback needs to be callable (i.e., be a function)'
                .', but it isn\'t.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        return $callback;
    }
    
    protected function processSslVerifyArgument($sslVerify)
    {
        if (isset($sslVerify) && gettype($sslVerify) !== 'boolean') {
            $message = 'The value for $sslVerify must be a boolean (true/false), but has type: '
                .gettype($sslVerify);
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        return $sslVerify;
    }
    
    
    protected function processSuperTokenArgument($superToken)
    {
        if (!isset($superToken) || trim($superToken) === '') {
            ;  // OK; just means that createProject can't be used
        } elseif (gettype($superToken) !== 'string') {
            $this->errorHandler->throwException("The REDCap super token provided should be a string, but has type: "
                . gettype($superToken), ErrorHandlerInterface::INVALID_ARGUMENT);
        } elseif (!ctype_xdigit($superToken)) {   // ctype_xdigit - check token for hexidecimal
            $this->errorHandler->throwException(
                "The REDCap super token has an invalid format."
                ." It should only contain numbers and the letters A, B, C, D, E and F.",
                ErrorHandlerInterface::INVALID_ARGUMENT
            );
        } elseif (strlen($superToken) != 64) {
            $this->errorHandler->throwException(
                "The REDCap super token has an invalid format."
                . " It has a length of ".strlen($superToken)." characters, but should have a length of"
                . " 64 characters.",
                ErrorHandlerInterface::INVALID_ARGUMENT
            );
        } // @codeCoverageIgnore
        
        return $superToken;
    }
}

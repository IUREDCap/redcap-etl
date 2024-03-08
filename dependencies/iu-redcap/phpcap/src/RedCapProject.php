<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/**
 * This file contains the REDCap project class for PHPCap.
 */
namespace IU\PHPCap;

/**
 * REDCap project class used to retrieve data from, and modify, REDCap projects.
 */
class RedCapProject
{
    const JSON_RESULT_ERROR_PATTERN = '/^[\s]*{"error":[\s]*"(.*)"}[\s]*$/';
    
    /** string REDCap API token for the project */
    protected $apiToken;
    
    /** RedCapApiConnection connection to the REDCap API at the $apiURL. */
    protected $connection;
    
    /** Error handler for the project. */
    protected $errorHandler;
 
    
    /**
     * Creates a REDCapProject object for the specifed project.
     *
     * Example Usage:
     * <pre>
     * <code class="phpdocumentor-code">
     * $apiUrl = 'https://redcap.someplace.edu/api/'; # replace with your API URL
     * $apiToken = '11111111112222222222333333333344'; # replace with your API token
     * $sslVerify = true;
     *
     * # See the PHPCap documentation for information on how to set this file up
     * $caCertificateFile = 'USERTrustRSACertificationAuthority.crt';
     *
     * $project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
     * </code>
     * </pre>
     *
     * @param string $apiUrl the URL for the API for the REDCap site that has the project.
     * @param string $apiToken the API token for this project.
     * @param boolean $sslVerify indicates if SSL connection to REDCap web site should be verified.
     * @param string $caCertificateFile the full path name of the CA (Certificate Authority)
     *     certificate file.
     * @param ErrorHandlerInterface $errorHandler the error handler used by the project.
     *    This would normally only be set if you want to override the PHPCap's default
     *    error handler.
     * @param RedCapApiConnectionInterface $connection the connection used by the project.
     *    This would normally only be set if you want to override the PHPCap's default
     *    connection. If this argument is specified, the $apiUrl, $sslVerify, and
     *    $caCertificateFile arguments will be ignored, and the values for these
     *    set in the connection will be used.
     *
     * @throws PhpCapException if any of the arguments are invalid
     */
    public function __construct(
        $apiUrl,
        $apiToken,
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
        }
        
        $this->apiToken = $this->processApiTokenArgument($apiToken);
        
        if (isset($connection)) {
            $this->connection = $this->processConnectionArgument($connection);
        } else {
            $apiUrl            = $this->processApiUrlArgument($apiUrl);
            $sslVerify         = $this->processSslVerifyArgument($sslVerify);
            $caCertificateFile = $this->processCaCertificateFileArgument($caCertificateFile);
            
            $this->connection = new RedCapApiConnection($apiUrl, $sslVerify, $caCertificateFile);
        }
    }

    
    /**
     * Exports the numbers and names of the arms in the project.
     *
     * @param $format string the format used to export the arm data.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param array $arms array of integers or numeric strings that are the numbers of the arms to export.
     *     If no arms are specified, then information for all arms will be returned.
     *
     * @return mixed For 'php' format, array of arrays that have the following keys:
     *     <ul>
     *       <li>'arm_num'</li>
     *       <li>'name'</li>
     *     </ul>
     */
    public function exportArms($format = 'php', $arms = [])
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'arm',
                'returnFormat' => 'json'
        );
        
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['arms']   = $this->processArmsArgument($arms);
        
        $arms = $this->connection->callWithArray($data);
        $arms = $this->processExportResult($arms, $format);
        
        return $arms;
    }
    
    /**
     * Imports the specified arms into the project.
     *
     * @param mixed $arms the arms to import. This will
     *     be a PHP array of associative arrays if no format, or 'php' format was specified,
     *     and a string otherwise. The field names (keys) used in both cases
     *     are: arm_num, name
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param boolean $override
     *     <ul>
     *       <li> false - [default] don't delete existing arms; only add new
     *       arms or renames existing arms.
     *       </li>
     *       <li> true - delete all existing arms before importing.</li>
     *     </ul>
     * @throws PhpCapException if an error occurs.
     *
     * @return integer the number of arms imported.
     */
    public function importArms($arms, $format = 'php', $override = false)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'arm',
                'action'       => 'import',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format']   = $this->processFormatArgument($format, $legalFormats);
        $data['data']     = $this->processImportDataArgument($arms, 'arms', $format);
        $data['override'] = $this->processOverrideArgument($override);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }
    
    /**
     * Deletes the specified arms from the project.
     *
     * @param array $arms array of arm numbers to delete.
     *
     * @throws PhpCapException if an error occurs, including if the arms array is null or empty
     *
     * @return integer the number of arms deleted.
     */
    public function deleteArms($arms)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'arm',
                'action'       => 'delete',
                'returnFormat' => 'json',
        );
        
        $data['arms'] = $this->processArmsArgument($arms, $required = true);
       
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }


     /**
     * Exports the Data Access Groups for a project.
     *
     * @param $format string the format used to export the data.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return mixed For 'php' format, array of arrays that have the following keys:
     *     <ul>
     *       <li>'data_access_group_name'</li>
             <li>'unique_group_name'</li>
     *     </ul>
     */
    public function exportDags($format = 'php')
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'dag',
                'returnFormat' => 'json'
        );
        
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $dags = $this->connection->callWithArray($data);
        $dags = $this->processExportResult($dags, $format);
        
        return $dags;
    }
    
     /**
     * Imports the specified dags into the project. Allows import of new DAGs or update of the
     * data_access_group_name of any existing DAGs. DAGs can be renamed by changing
     * the data_access_group_name. A DAG can be created by providing group name value with
     *  unique group name set to blank.
     *
     * @param mixed $dags the DAGs to import. This will
     *     be a PHP array of associative arrays if no format, or 'php' format was specified,
     *     and a string otherwise. The field names (keys) used in both cases
     *     are: data_access_group_name, unique_group_name
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @throws PhpCapException if an error occurs.
     *
     * @return integer the number of DAGs imported.
     */
    public function importDags($dags, $format = 'php')
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'dag',
                'action'       => 'import',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format']   = $this->processFormatArgument($format, $legalFormats);
        $data['data']     = $this->processImportDataArgument($dags, 'dag', $format);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }
    

     /**
     * Deletes the specified dags from the project.
     *
     * @param array $dags an array of the unique_group_names to delete.
     * @throws PhpCapException if an error occurs.
     *
     * @return integer the number of DAGs imported.
     */
    public function deleteDags($dags)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'dag',
                'action'       => 'delete',
                'returnFormat' => 'json'
        );
        
        $required = true;
        $data['dags'] = $this->processDagsArgument($dags, $required);

        $result = $this->connection->callWithArray($data);
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }


    /**
     * Switches the DAG (Data Access Group) to the specified DAG.
     *
     * @param string $dag the DAG to switch to.
     *
     * @return mixed "1" if the records was successfully renamed, and throws an exception otherwise.
     */
    public function switchDag($dag)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'dag',
                'action'       => 'switch',
                'returnFormat' => 'json'
        );
        
        $required = true;
        $data['dag'] = $this->processDagArgument($dag, $required);

        $result = $this->connection->callWithArray($data);

        if ($result != 1) {
            $message = "Error switching to DAG '{$dag}': {$result}.";
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        }

        return $result;
    }


    /**
     * Exports the User-DataAccessGroup assignaments for a project.
     *
     * @param $format string the format used to export the data.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return mixed For 'php' format, array of arrays that have the following keys:
     *     <ul>
     *       <li>'username'</li>
     *       <li>'redcap_data_access_group'</li>
     *     </ul>
     */
    public function exportUserDagAssignment($format)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'userDagMapping',
                'returnFormat' => 'json'
        );

        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $dagAssignments = $this->connection->callWithArray($data);
        $dagAssignments = $this->processExportResult($dagAssignments, $format);
        
        return $dagAssignments;
    }

     /**
     * Imports User-DAG assignments, allowing you to assign users to any
     * data access group.o the project. If you wish to modify an existing
     * mapping, you must provide its unique username and group name.
     * If the 'redcap_data_access_group' column is not provided, user
     * will not be assigned to any group. There should be only one record
     * per username.
     *
     * @param mixed $dagAssignments the User-DAG assignments to import.
     * This will be a PHP array of associative arrays if no format, or 'php'
     * format was specified, and a string otherwise. The field names (keys)
     * used in both cases are: username, recap_data_access_group
     *
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @throws PhpCapException if an error occurs.
     *
     * @return integer the number of DAGs imported.
     */
    public function importUserDagAssignment($dagAssignments, $format = 'php')
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'userDagMapping',
                'action'       => 'import',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format']   = $this->processFormatArgument($format, $legalFormats);
        $data['data']     = $this->processImportDataArgument($dagAssignments, 'userDagMapping', $format);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }


    /**
     * Exports information about the specified events.
     *
     * Example usage:
     * <pre>
     * <code class="phpdocumentor-code">
     * #export information about all events in CSV (Comma-Separated Values) format.
     * $eventInfo = $project->exportEvents('csv');
     *
     * # export events in XML format for arms 1 and 2.
     * $eventInfo = $project->exportEvents('xml', [1, 2]);
     * </code>
     * </pre>
     *
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param array $arms array of integers or numeric strings that are the arm numbers for
     *     which events should be exported.
     *     If no arms are specified, then all events will be returned.
     *
     * @return array information about the specified events. Each element of the
     *     array is an associative array with the following keys: 'event_name', 'arm_num',
     *         'day_offset', 'offset_min', 'offset_max', 'unique_event_name', 'custom_event_label'
     */
    public function exportEvents($format = 'php', $arms = [])
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'event',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['arms'] = $this->processArmsArgument($arms);
        
        
        #------------------------------------------------------
        # Get and process events
        #------------------------------------------------------
        $events = $this->connection->callWithArray($data);
        $events = $this->processExportResult($events, $format);
        
        return $events;
    }
    
    
    /**
     * Imports the specified events into the project.
     *
     * @param mixed $events the events to import. This will
     *     be a PHP array of associative arrays if no format, or 'php' format is specified,
     *     and a string otherwise. The field names (keys) used in both cases
     *     are: event_name, arm_num
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param boolean $override
     *     <ul>
     *       <li> false - [default] don't delete existing arms; only add new
     *       arms or renames existing arms.
     *       </li>
     *       <li> true - delete all existing arms before importing.</li>
     *     </ul>
     *
     * @throws PhpCapException if an error occurs.
     *
     * @return integer the number of events imported.
     */
    public function importEvents($events, $format = 'php', $override = false)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'event',
                'action'       => 'import',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $data['data'] = $this->processImportDataArgument($events, 'arms', $format);
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['override'] = $this->processOverrideArgument($override);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }

    
    /**
     * Deletes the specified events from the project.
     *
     * @param array $events array of event names of events to delete.
     *
     * @throws PhpCapException if an error occurs, including if the events array is null or empty.
     *
     * @return integer the number of events deleted.
     */
    public function deleteEvents($events)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'event',
                'action'       => 'delete',
                'returnFormat' => 'json',
        );
        
        $data['events'] = $this->processEventsArgument($events, $required = true);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }
    
    
    /**
     * Exports the fields names for a project.
     *
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param string $field the name of the field for which to export field
     *     name information. If no field is specified, information for all
     *     fields is exported.
     *
     * @return mixed information on the field specified, or all fields if no
     *     field was specified. If 'php' or no format was specified, results
     *     will be returned as a PHP array of maps (associative arrays), where the
     *     keys for the maps:
     *     <ul>
     *       <li>original_field_name</li>
     *       <li>choice_value</li>
     *       <li>export_field_name</li>
     *     </ul>
     */
    public function exportFieldNames($format = 'php', $field = null)
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'exportFieldNames',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['field']  = $this->processFieldArgument($field, $required = false);
        
        $fieldNames = $this->connection->callWithArray($data);
        $fieldNames = $this->processExportResult($fieldNames, $format);
        
        return $fieldNames;
    }

    /**
     * Exports the specified file. The contents of the file are returned by the method.
     * If the $fileInfo argument is used and set to an array before
     * the call to this method, the method will set it to contain information about the exported file.
     * Note that if a file does not have an event and/or repeat instance, do not include
     * these arguments or set them to null if you need to specify an argument that comes after them.
     *
     * Example usage:
     * <pre>
     * <code class="phpdocumentor-code">
     * ...
     * $recordId       = '1001';
     * $field          = 'patient_document';
     * $event          = 'enrollment_arm_1';
     * $repeatInstance = 1;
     * $fileInfo       = array();
     * $fileContents = $project->exportFile($recordId, $field, $event, $repeatInstance, $fileInfo);
     * print("File name: {$fileInfo['name']}, file MIME type: {$fileInfo['mime_type']}\n");
     * ...
     * </code>
     * </pre>

     *
     * @param string $recordId the record ID for the file to be exported.
     * @param string $field the name of the field containing the file to export.
     * @param string $event name of event for file export (for longitudinal studies).
     * @param integer $repeatInstance the instance (if repeating) of the record
     * @param array $fileInfo output array for getting file information. $fileInfo must be set
     *     to an array before calling this method, and on successful return it will include
     *     elements with the following keys:
     *     'name', 'mime_type', and 'charset' (if the file is a text file).
     *
     *
     * @throws PhpCapException if an error occurs.
     *
     * @return string the contents of the file that was exported.
     */
    public function exportFile($recordId, $field, $event = null, $repeatInstance = null, &$fileInfo = null)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'file',
                'action'       => 'export',
                'returnFormat' => 'json'
        );
        
        #--------------------------------------------
        # Process arguments
        #--------------------------------------------
        $data['record']           = $this->processRecordIdArgument($recordId);
        $data['field']            = $this->processFieldArgument($field);
        $data['event']            = $this->processEventArgument($event);
        $data['repeat_instance']  = $this->processRepeatInstanceArgument($repeatInstance);
        
        #-------------------------------
        # Get and process file
        #-------------------------------
        $file = $this->connection->callWithArray($data);
        $file = $this->processExportResult($file, $format = 'file');

        if (is_array($fileInfo)) {
            $callInfo = $this->connection->getCallInfo();
            $fileInfo = $this->getFileInfo($callInfo);
        }
        
        return $file;
    }
    
    
    /**
     * Imports the file into the field of the record
     * with the specified event and/or repeat instance, if any.
     *
     * Example usage:
     * <pre>
     * <code class="phpdocumentor-code">
     * ...
     * $file     = '../data/consent1001.txt';
     * $recordId = '1001';
     * $field    = 'patient_document';
     * $event    = 'enrollment_arm_1';
     * $project->importFile($file, $recordId, $field, $event);
     * ...
     * </code>
     * </pre>
     *
     * @param string $filename the name of the file to import.
     * @param string $recordId the record ID of the record to import the file into.
     * @param string $field the field of the record to import the file into.
     * @param string $event the event of the record to import the file into
     *     (only for longitudinal studies).
     * @param integer $repeatInstance the repeat instance of the record to import
     *     the file into (only for studies that have repeating events
     *     and/or instruments).
     *
     * @throws PhpCapException
     */
    public function importFile($filename, $recordId, $field, $event = null, $repeatInstance = null)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'file',
                'action'       => 'import',
                'returnFormat' => 'json'
        );
        
        #----------------------------------------
        # Process non-file arguments
        #----------------------------------------
        $data['file']             = $this->processFilenameArgument($filename);
        $data['record']           = $this->processRecordIdArgument($recordId);
        $data['field']            = $this->processFieldArgument($field);
        $data['event']            = $this->processEventArgument($event);
        $data['repeat_instance']  = $this->processRepeatInstanceArgument($repeatInstance);
        
        
        #---------------------------------------------------------------------
        # For unknown reasons, "call" (instead of "callWithArray") needs to
        # be used here (probably something to do with the 'file' data).
        # REDCap's "API Playground" (also) makes no data conversion for this
        # method.
        #---------------------------------------------------------------------
        $result = $this->connection->call($data);
        
        $this->processNonExportResult($result);
    }


    /**
     * Deletes the specified file.
     *
     * @param string $recordId the record ID of the file to delete.
     * @param string $field the field name of the file to delete.
     * @param string $event the event of the file to delete
     *     (only for longitudinal studies).
     * @param integer $repeatInstance repeat instance of the file to delete
     *     (only for studies that have repeating events
     *     and/or instruments).
     */
    public function deleteFile($recordId, $field, $event = null, $repeatInstance = null)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'file',
                'action'       => 'delete',
                'returnFormat' => 'json'
        );
        
        #----------------------------------------
        # Process arguments
        #----------------------------------------
        $data['record']           = $this->processRecordIdArgument($recordId);
        $data['field']            = $this->processFieldArgument($field);
        $data['event']            = $this->processEventArgument($event);
        $data['repeat_instance']  = $this->processRepeatInstanceArgument($repeatInstance);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return $result;
    }


    /**
     * Creates the specified folder in the file repository.
     *
     * @param string $name the name of the folder to be created.
     * @param integer $parentFolderId the id of the folder where the new folder should be created. If
     *     this is unset, then the folder will be created in the top-level folder.
     * @param integer $dagId the DAG (Data Access Group) ID that should be used to restrict access
     *     to this folder.
     * @param integer $roleId the role ID that should be used to restrict access to this folder.
     *
     * @return integer the folder ID of the folder that was created.
     */
    public function createFileRepositoryFolder($name, $parentFolderId = null, $dagId = null, $roleId = null)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'fileRepository',
                'action'       => 'createFolder',
                'format'       => 'json',
                'returnFormat' => 'json'
        );

        $data['name']      = $name;
        $data['folder_id'] = $this->processFolderIdArgument($parentFolderId);
        $data['dag_id']    = $this->processDagIdArgument($dagId);
        $data['role_id']   = $this->processRoleIdArgument($roleId);

        $jsonResult = $this->connection->callWithArray($data);

        $this->processNonExportResult($result);

        $folderId = null;
        $result = json_decode($jsonResult, true);
        if (is_array($result) && count($result) === 1 && array_key_exists('folder_id', $result[0])) {
            $folderId = (integer) $result[0]['folder_id'];
        }

        return $folderId;
    }

    /**
     * Exports a list of the files and folders contained within the specified folder.
     *
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param integer $folderId the folder ID of the folder to list. If no
     *     folder ID is specified, then a list of the top-level folder is returned.
     *
     * @return array an array of maps that include 'name' and 'doc_id' (for files) or 'folder_id' (for folders).
     */
    public function exportFileRepositoryList($format = 'php', $folderId = null)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'fileRepository',
                'action'       => 'list',
                'returnFormat' => 'json'
        );

        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format']    = $this->processFormatArgument($format, $legalFormats);
        $data['folder_id'] = $this->processFolderIdArgument($folderId);

        $list = $this->connection->callWithArray($data);

        $this->processExportResult($list, $format);
        
        return $list;
    }


    /**
     * Exports the specified file from the file repository.
     *
     * @param integer $docId the doc ID of the file to export.
     * @param array $fileInfo output array for getting file information. $fileInfo must be set
     *     to an array before calling this method, and on successful return it will include
     *     elements with the following keys:
     *     'name', 'mime_type', and 'charset' (if the file is a text file).
     *
     * @return string the contents of the file that was exported.
     */
    public function exportFileRepositoryFile($docId, &$fileInfo = null)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'fileRepository',
                'action'       => 'export',
                'returnFormat' => 'json'
        );

        $data['doc_id'] = $this->processDocIdArgument($docId);

        $file = $this->connection->callWithArray($data);

        $format = 'file';
        $this->processExportResult($file, $format);

        if (is_array($fileInfo)) {
            $callInfo = $this->connection->getCallInfo();
            $fileInfo = $this->getFileInfo($callInfo);
        }

        return $file;
    }


    /**
     * Imports the specified file from the file repository.
     *
     * @param string $filename the name of the file to import.
     * @param integer $folderId the ID of the folder in the file repositoryt from which the
     *     file should be imported. If no folder ID is specified, then the top-level
     *     folder of the file repository is used.
     */
    public function importFileRepositoryFile($filename, $folderId = null)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'fileRepository',
                'action'       => 'import',
                'returnFormat' => 'json'
        );

        $data['file']      = $this->processFilenameArgument($filename);
        $data['folder_id'] = $this->processFolderIdArgument($folderId);

        $result = $this->connection->call($data);
        $this->processNonExportResult($result);
    }


    /**
     * Deletes the specified file repository file.
     *
     * @param integer $docId the doc ID for the file to delete from the file repository.
     */
    public function deleteFileRepositoryFile($docId)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'fileRepository',
                'action'       => 'delete',
                'returnFormat' => 'json'
        );

        $data['doc_id'] = $this->processDocIdArgument($docId);

        $result = $this->connection->callWithArray($data);

        $this->processNonExportResult($result);

        return $result;
    }
    
    
    /**
     * Exports information about the instruments (data entry forms) for the project.
     *
     * Example usage:
     * <pre>
     * <code class="phpdocumentor-code">
     * $instruments = $project->getInstruments();
     * foreach ($instruments as $instrumentName => $instrumentLabel) {
     *     print "{$instrumentName} : {$instrumentLabel}\n";
     * }
     * </code>
     * </pre>
     *
     * @param $format string format instruments are exported in:
     *     <ul>
     *       <li>'php' - [default] returns data as a PHP array</li>
     *       <li>'csv' - string of CSV (comma-separated values)</li>
     *       <li>'json' - string of JSON encoded data</li>
     *       <li>'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return mixed For the 'php' format, and array map of instrument names to instrument labels is returned.
     *     For all other formats a string is returned.
     */
    public function exportInstruments($format = 'php')
    {
        $data = array(
                'token'       => $this->apiToken,
                'content'     => 'instrument',
                'returnFormat' => 'json'
        );
        
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $instrumentsData = $this->connection->callWithArray($data);
        
        $instrumentsData = $this->processExportResult($instrumentsData, $format);
        
        #------------------------------------------------------
        # If format is 'php', reformat the data as
        # a map from "instrument name" to "instrument label"
        #------------------------------------------------------
        if ($format == 'php') {
            $instruments = array ();
            foreach ($instrumentsData as $instr) {
                $instruments [$instr ['instrument_name']] = $instr ['instrument_label'];
            }
        } else {
            $instruments = $instrumentsData;
        }
        
        return $instruments;
    }
    
    /**
     * Exports a PDF version of the requested instruments (forms).
     *
     * @param string $file the name of the file (possibly with a path specified also)
     *     to store the PDF instruments in.
     * @param string $recordId if record ID is specified, the forms retrieved will
     *     be filled with values for that record. Otherwise, they will be blank.
     * @param string $event (only for longitudinal projects) a unique event name
     *     that is used when a record ID has been specified to return only
     *     forms that are in that event (for the specified records).
     * @param string $form if this is specified, only this form will be
     *     returned.
     * @param boolean $allRecords if this is set to true, all forms for all
     *     records will be retrieved (the $recordId, $event, and $form arguments
     *     will be ignored).
     *
     * @throws PhpCapException if an error occurs.
     *
     * @return string PDF content of requested instruments (forms).
     */
    public function exportPdfFileOfInstruments(
        $file = null,
        $recordId = null,
        $event = null,
        $form = null,
        $allRecords = null,
        $compactDisplay = null,
        $repeatInstance = null
    ) {
        $data = array(
                'token'       => $this->apiToken,
                'content'     => 'pdf',
                'returnFormat' => 'json'
        );
        
        $file = $this->processFileArgument($file);
        
        $data['record']     = $this->processRecordIdArgument($recordId, $required = false);
        $data['event']      = $this->processEventArgument($event);
        $data['instrument'] = $this->processFormArgument($form);

        $data['allRecords']      = $this->processAllRecordsArgument($allRecords);
        $data['compactDisplay']  = $this->processCompactDisplayArgument($compactDisplay);
        $data['repeat_instance'] = $this->processRepeatInstanceArgument($repeatInstance);

        $result = $this->connection->callWithArray($data);
        
        if (isset($file)) {
            FileUtil::writeStringToFile($result, $file);
        }
        
        return $result;
    }
   
    
    /**
     * Gets the instrument to event mapping for the project.
     *
     * For example, the following code:
     * <pre>
     * <code class="phpdocumentor-code">
     * $map = $project->exportInstrumentEventMappings();
     * print_r($map[0]); # print first element of map
     * </code>
     * </pre>
     * might generate the following output:
     * <pre>
     * Array
     * (
     *     [arm_num] => 1
     *     [unique_event_name] => enrollment_arm_1
     *     [form] => demographics
     * )
     * </pre>
     *
     * @param string $format the format in which to export the records:
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param array $arms array of integers or numeric strings that are the numbers of the arms
     *     for which instrument/event mapping infomation should be exported.
     *     If no arms are specified, then information for all arms will be exported.
     *
     * @return arrray an array of arrays that have the following keys:
     *     <ul>
     *       <li>'arm_num'</li>
     *       <li>'unique_event_name'</li>
     *       <li>'form'</li>
     *     </ul>
     */
    public function exportInstrumentEventMappings($format = 'php', $arms = [])
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'formEventMapping',
                'format'       => 'json',
                'returnFormat' => 'json'
        );
        
        #------------------------------------------
        # Process arguments
        #------------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['arms']   = $this->processArmsArgument($arms);
        
        #---------------------------------------------
        # Get and process instrument-event mappings
        #---------------------------------------------
        $instrumentEventMappings = $this->connection->callWithArray($data);
        $instrumentEventMappings = $this->processExportResult($instrumentEventMappings, $format);
        
        return $instrumentEventMappings;
    }

    /**
     * Imports the specified instrument-event mappings into the project.
     *
     * @param mixed $mappings the mappings to import. This will
     *     be a PHP array of associative arrays if no format, or
     *     'php' format, was specified,
     *     and a string otherwise. In all cases, the field names that
     *     are used in the mappings are:
     *     arm_num, unique_event_name, form
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @throws PhpCapException if an error occurs.
     *
     * @return integer the number of mappings imported.
     */
    public function importInstrumentEventMappings($mappings, $format = 'php')
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'formEventMapping',
            'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $data['data'] = $this->processImportDataArgument($mappings, 'mappings', $format);
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }


     /**
     * Exports the logging (audit trail) of all changes made to this project,
     * including data exports, data changes, project metadata changes,
     * modification of user rights, etc.
     *
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param string $logType type of logging to return. Defaults to NULL
     *     to return all types.
     * @param string $username returns only the events belong to specific
     *     username. If not specified, it will assume all users.
     * @param string $recordId the record ID for the file to be exported.
     * @param string $dag returns only the events belong to specific DAG
     *     (referring to group_id), provide a dag. If not specified, it will
     *     assume all dags.
     * @param string $beginTime specifies to return only those records
     *     *after* a given date/time, provide a timestamp in the format
     *     YYYY-MM-DD HH:MM (e.g., '2017-01-01 17:00' for January 1, 2017
     *     at 5:00 PM server time). If not specified, it will assume no
     *     begin time.
     * @param string $endTime returns only records that have been logged
     *     *before* a given date/time, provide a timestamp in the format
     *     YYYY-MM-DD HH:MM (e.g., '2017-01-01 17:00' for January 1, 2017
     *     at 5:00 PM server time). If not specified, it will use the current
     *     server time.
     * @throws PhpCapException if an error occurs.
     *
     * @return array information, filtered by event (logtype), listing all
     *     changes made to thise project. Each element of the array is an
     *     associative array with the following keys:
     *     'timestamp', 'username', 'action', 'details'
     */
    public function exportLogging(
        $format = 'php',
        $logType = null,
        $username = null,
        $recordId = null,
        $dag = null,
        $beginTime = null,
        $endTime = null
    ) {
        $required = false;
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'log',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format']    = $this->processFormatArgument($format, $legalFormats);

        $data['logtype']   = $this->processLogTypeArgument($logType);
        $data['user']      = $this->processUserArgument($username);
        $data['record']    = $this->processRecordIdArgument($recordId, $required);
        $data['dag']       = $this->processDagArgument($dag);
        $data['beginTime'] = $this->processDateRangeArgument($beginTime);
        $data['endTime']   = $this->processDateRangeArgument($endTime);

        $logs = $this->connection->callWithArray($data);
        $logs = $this->processExportResult($logs, $format);
        
        return $logs;
    }

    
    /**
     * Exports metadata about the project, i.e., information about the fields in the project.
     *
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param array $fields array of field names for which metadata should be exported
     * @param array $forms array of form names. Metadata will be exported for all fields in the
     *     specified forms.
     *
     * @return array associative array (map) of metatdata for the project, which consists of
     *         information about each field. Some examples of the information
     *         provided are: 'field_name', 'form_name', 'field_type', 'field_label'.
     *         See REDCap API documentation
     *         for more information, or use the print_r function on the results of this method.
     */
    public function exportMetadata($format = 'php', $fields = [], $forms = [])
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'metadata',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process format
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['forms']  = $this->processFormsArgument($forms);
        $data['fields'] = $this->processFieldsArgument($fields);
        
        #-------------------------------------------
        # Get and process metadata
        #-------------------------------------------
        $metadata = $this->connection->callWithArray($data);
        $metadata = $this->processExportResult($metadata, $format);
        
        return $metadata;
    }
    
    /**
     * Imports the specified metadata (field information) into the project.
     *
     * @param mixed $metadata the metadata to import. This will
     *     be a PHP associative array if no format, or 'php' format was specified,
     *     and a string otherwise.
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @throws PhpCapException if an error occurs.
     *
     * @return integer the number of fields imported.
     */
    public function importMetadata($metadata, $format = 'php')
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'metadata',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $data['data'] = $this->processImportDataArgument($metadata, 'metadata', $format);
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }

    
    /**
     * Exports information about the project, e.g., project ID, project title, creation time.
     *
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return array associative array (map) of project information. See REDCap API documentation
     *         for a list of the fields, or use the print_r function on the results of this method.
     */
    public function exportProjectInfo($format = 'php')
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'project',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process format
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        #---------------------------------------
        # Get and process project information
        #---------------------------------------
        $projectInfo = $this->connection->callWithArray($data);
        $projectInfo = $this->processExportResult($projectInfo, $format);
        
        return $projectInfo;
    }
    
    /**
     * Imports the specified project information into the project.
     * The valid fields that can be imported are:
     *
     * project_title, project_language, purpose, purpose_other, project_notes,
     * custom_record_label, secondary_unique_field, is_longitudinal,
     * surveys_enabled, scheduling_enabled, record_autonumbering_enabled,
     * randomization_enabled, project_irb_number, project_grant_number,
     * project_pi_firstname, project_pi_lastname, display_today_now_button
     * bypass_branching_erase_field_prompt
     *
     * You do not need to specify all of these fields when doing an import,
     * only the ones that you actually want to change. For example:
     * <pre>
     * <code class="phpdocumentor-code">
     * ...
     * # Set the project to be longitudinal and enable surveys
     * $projectInfo = ['is_longitudinal' => 1, 'surveys_enabled' => 1];
     * $project->importProjectInfo($projectInfo);
     * ...
     * </code>
     * </pre>
     *
     * @param mixed $projectInfo the project information to import. This will
     *     be a PHP associative array if no format, or 'php' format was specified,
     *     and a string otherwise.
     * @param string $format the format for the export.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @throws PhpCapException if an error occurs.
     *
     * @return integer the number of project info values specified that were valid,
     *     whether or not each valid value actually caused an update (i.e., was different
     *     from the existing value before the method call).
     */
    public function importProjectInfo($projectInfo, $format = 'php')
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'project_settings',
            'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $data['data'] = $this->processImportDataArgument($projectInfo, 'projectInfo', $format);
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }
    
    /**
     * Exports the specified information of project in XML format.
     *
     * @param boolean $returnMetadataOnly if this is set to true, only the metadata for
     *     the project is returned. If it is not set to true, the metadata and data for the project
     *     is returned.
     * @param array $recordIds array of strings with record id's that are to be retrieved.
     * @param array $fields array of field names to export
     * @param array $events array of event names for which fields should be exported
     * @param string $filterLogic logic used to restrict the records retrieved, e.g.,
     *     "[last_name] = 'Smith'".
     * @param boolean $exportSurveyFields specifies whether survey fields should be exported.
     *     <ul>
     *       <li> true - export the following survey fields:
     *         <ul>
     *           <li> survey identifier field ('redcap_survey_identifier') </li>
     *           <li> survey timestamp fields (instrument+'_timestamp') </li>
     *         </ul>
     *       </li>
     *       <li> false - [default] survey fields are not exported.</li>
     *     </ul>
     * @param boolean $exportDataAccessGroups specifies whether the data access group field
     *      ('redcap_data_access_group') should be exported.
     *     <ul>
     *       <li> true - export the data access group field if there is at least one data access group, and
     *                   the user calling the method (as identified by the API token) is not
     *                   in a data access group.</li>
     *       <li> false - [default] don't export the data access group field.</li>
     *     </ul>
     * @param boolean $exportFiles If this is set to true, files will be exported in the XML.
     *     If it is not set to true, files will not be exported.
     * @return string the specified information for the project in XML format.
     */
    public function exportProjectXml(
        $returnMetadataOnly = false,
        $recordIds = null,
        $fields = null,
        $events = null,
        $filterLogic = null,
        $exportSurveyFields = false,
        $exportDataAccessGroups = false,
        $exportFiles = false
    ) {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'project_xml',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------------
        # Process the arguments
        #---------------------------------------------
        $data['returnMetadataOnly'] = $this->processReturnMetadataOnlyArgument($returnMetadataOnly);

        $data['records']     = $this->processRecordIdsArgument($recordIds);
        $data['fields']      = $this->processFieldsArgument($fields);
        $data['events']      = $this->processEventsArgument($events);
        $data['filterLogic'] = $this->processFilterLogicArgument($filterLogic);
        
        $data['exportSurveyFields']     = $this->processExportSurveyFieldsArgument($exportSurveyFields);
        $data['exportDataAccessGroups'] = $this->processExportDataAccessGroupsArgument($exportDataAccessGroups);
        $data['exportFiles']            = $this->processExportFilesArgument($exportFiles);
        
        #---------------------------------------
        # Get the Project XML and process it
        #---------------------------------------
        $projectXml = $this->connection->callWithArray($data);
        $projectXml = $this->processExportResult($projectXml, $format = 'xml');
        
        return $projectXml;
    }
    
    /**
     * This method returns the next potential record ID for a project, but it does NOT
     * actually create a new record. The record ID returned will generally be the current maximum
     * record ID number incremented by one (but see the REDCap documentation for the case
     * where Data Access Groups are being used).
     * This method is intended for use with projects that have record-autonumbering enabled.
     *
     * @return string the next record name.
     */
    public function generateNextRecordName()
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'generateNextRecordName',
                'returnFormat' => 'json'
        );
        
        $nextRecordName = $this->connection->callWithArray($data);
        $nextRecordName = $this->processExportResult($nextRecordName, $format = 'number');
        
        return $nextRecordName;
    }
    
    
    /**
     * Exports the specified records.
     *
     * Example usage:
     *
     * <pre>
     * <code class="phpdocumentor-code">
     * $records = $project->exportRecords($format = 'csv', $type = 'flat');
     * $recordIds = [1001, 1002, 1003];
     * $records = $project->exportRecords('xml', 'eav', $recordIds);
     * </code>
     * </pre>
     *
     * Note: date ranges do not work for records that were imported at
     * the time the project was created.
     *
     * @param string $format the format in which to export the records:
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *       <li> 'odm' - string with CDISC ODM XML format, specifically ODM version 1.3.1</li>
     *     </ul>
     * @param string $type the type of records exported:
     *     <ul>
     *       <li>'flat' - [default] exports one record per row.</li>
     *       <li>'eav'  - exports one data point per row:, so,
     *         for non-longitudinal studies, each record will have the following
     *         fields: record_id, field_name, value. For longitudinal studies, each record
     *         will have the fields: record_id, field_name, value, redcap_event_name.
     *       </li>
     *     </ul>
     * @param array $recordIds array of strings with record id's that are to be retrieved.
     * @param array $fields array of field names to export
     * @param array $forms array of form names for which fields should be exported
     * @param array $events array of event names for which fields should be exported
     * @param string $filterLogic logic used to restrict the records retrieved, e.g.,
     *         "[last_name] = 'Smith'".
     * @param string $rawOrLabel indicates what should be exported for options of multiple choice fields:
     *     <ul>
     *       <li> 'raw' - [default] export the raw coded values</li>
     *       <li> 'label' - export the labels</li>
     *     </ul>
     * @param string $rawOrLabelHeaders when exporting with 'csv' format 'flat' type, indicates what format
     *         should be used for the CSV headers:
     *         <ul>
     *           <li> 'raw' - [default] export the variable/field names</li>
     *           <li> 'label' - export the field labels</li>
     *         </ul>
     * @param boolean $exportCheckboxLabel specifies the format for checkbox fields for the case where
     *         $format = 'csv', $rawOrLabel = true, and $type = 'flat'. For other cases this
     *         parameter is effectively ignored.
     *     <ul>
     *       <li> true - checked checkboxes will have a value equal to the checkbox option's label
     *           (e.g., 'Choice 1'), and unchecked checkboxes will have a blank value.
     *       </li>
     *       <li> false - [default] checked checkboxes will have a value of 'Checked', and
     *            unchecked checkboxes will have a value of 'Unchecked'.
     *       </li>
     *     </ul>
     * @param boolean $exportSurveyFields specifies whether survey fields should be exported.
     *     <ul>
     *       <li> true - export the following survey fields:
     *         <ul>
     *           <li> survey identifier field ('redcap_survey_identifier') </li>
     *           <li> survey timestamp fields (instrument+'_timestamp') </li>
     *         </ul>
     *       </li>
     *       <li> false - [default] survey fields are not exported.</li>
     *     </ul>
     * @param boolean $exportDataAccessGroups specifies whether the data access group field
     *      ('redcap_data_access_group') should be exported.
     *     <ul>
     *       <li> true - export the data access group field if there is at least one data access group, and
     *                   the user calling the method (as identified by the API token) is not
     *                   in a data access group.</li>
     *       <li> false - [default] don't export the data access group field.</li>
     *     </ul>
     * @param string $dateRangeBegin specifies to return only those records
     *      have been created or modified after the date entered. Date needs to be
     *      in YYYY_MM-DD HH:MM:SS, e.g., '2020-01-31 00:00:00'.
     * @param string $dateRangeEnd specifies to return only those records
     *      have been created or modified before the date entered. Date needs to be
     *      in YYYY_MM-DD HH:MM:SS, e.g., '2020-01-31 00:00:00'.
     * @param string $csvDelimiter specifies what delimiter is used to separate
     *     values in a CSV file (for CSV format only). Options are:
     *     <ul>
     *       <li> ',' - comma, this is the default </li>
     *       <li> 'tab' - tab </li>
     *       <li> ';' - semi-colon</li>
     *       <li> '|' - pipe</li>
     *       <li> '^' - caret</li>
     *     </ul>
     * @param string $decimalCharacter specifies what decimal format to apply to
     * numeric values being returned. Options are:
     *     <ul>
     *       <li> '.' - dot/full stop </li>
     *       <li> ',' - comma </li>
     *       <li> null - numbers will be exported using the fields' native decimal format</li>
     *     </ul>
     * @param boolean $exportBlankForGrayFormStatus indicates if the complete field for a form that
     *     has not been started should be returned as a blank (instead of a zero).
     *
     * @return mixed If 'php' format is specified, an array of records will be returned where the format
     *     of the records depends on the 'type'parameter (see above). For other
     *     formats, a string is returned that contains the records in the specified format.
     */
    public function exportRecords(
        $format = 'php',
        $type = 'flat',
        $recordIds = null,
        $fields = null,
        $forms = null,
        $events = null,
        $filterLogic = null,
        $rawOrLabel = 'raw',
        $rawOrLabelHeaders = 'raw',
        $exportCheckboxLabel = false,
        $exportSurveyFields = false,
        $exportDataAccessGroups = false,
        $dateRangeBegin = null,
        $dateRangeEnd = null,
        $csvDelimiter = ',',
        $decimalCharacter = null,
        $exportBlankForGrayFormStatus = false
    ) {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'record',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process the arguments
        #---------------------------------------
        $legalFormats = array('php', 'csv', 'json', 'xml', 'odm');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $data['type']    = $this->processTypeArgument($type);
        $data['records'] = $this->processRecordIdsArgument($recordIds);
        $data['fields']  = $this->processFieldsArgument($fields);
        $data['forms']   = $this->processFormsArgument($forms);
        $data['events']  = $this->processEventsArgument($events);
        
        $data['rawOrLabel']             = $this->processRawOrLabelArgument($rawOrLabel);
        $data['rawOrLabelHeaders']      = $this->processRawOrLabelHeadersArgument($rawOrLabelHeaders);
        $data['exportCheckboxLabel']    = $this->processExportCheckboxLabelArgument($exportCheckboxLabel);
        $data['exportSurveyFields']     = $this->processExportSurveyFieldsArgument($exportSurveyFields);
        $data['exportDataAccessGroups'] = $this->processExportDataAccessGroupsArgument($exportDataAccessGroups);
        
        $data['filterLogic'] = $this->processFilterLogicArgument($filterLogic);
        
        $data['dateRangeBegin'] = $this->processDateRangeArgument($dateRangeBegin);
        $data['dateRangeEnd'] = $this->processDateRangeArgument($dateRangeEnd);

        if ($data['format'] == 'csv') {
            $data['csvDelimiter'] = $this->processCsvDelimiterArgument($csvDelimiter, $format);
        };

        $data['decimalCharacter'] = $this->processDecimalCharacterArgument($decimalCharacter);

        $data['exportBlankForGrayFormStatus']
            = $this->processExportBlankForGrayFormStatusArgument($exportBlankForGrayFormStatus);

        #---------------------------------------
        # Get the records and process them
        #---------------------------------------
        $records = $this->connection->callWithArray($data);
        $records = $this->processExportResult($records, $format);
      
        return $records;
    }

    /**
     * Export records using an array parameter, where the keys of the array
     * passed to this method are the argument names, and the values are the
     * argument values. The argument names to use correspond to the variable
     * names in the exportRecords method.
     *
     * Example usage:
     *
     * <pre>
     * <code class="phpdocumentor-code">
     * # return all records with last name "Smith" in CSV format
     * $records = $project->exportRecordsAp(['format' => 'csv', 'filterLogic' => "[last_name] = 'Smith'"]);
     *
     * # export only records that have record ID 1001, 1002, or 1003
     * $result = $project->exportRecordsAp(['recordIds' => [1001, 1002, 1003]]);
     *
     * # export only the fields on the 'lab_data' form and field 'study_id'
     * $records = $project->exportRecordsAp(['forms' => ['lab_data'], 'fields' => ['study_id']]);
     * </code>
     * </pre>
     *
     * @see exportRecords()
     *
     * @param array $argumentArray array of arguments.
     * @return mixed the specified records.
     */
    public function exportRecordsAp($arrayParameter = [])
    {
        if (func_num_args() > 1) {
            $message = __METHOD__.'() was called with '.func_num_args().' arguments, but '
                    .' it accepts at most 1 argument.';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::TOO_MANY_ARGUMENTS);
        } elseif (!isset($arrayParameter)) {
            $arrayParameter = [];
        } elseif (!is_array($arrayParameter)) {
            $message = 'The argument has type "'
                    .gettype($arrayParameter)
                    .'", but it needs to be an array.';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } // @codeCoverageIgnore
        
        $num = 1;
        foreach ($arrayParameter as $name => $value) {
            if (gettype($name) !== 'string') {
                $message = 'Argument name number '.$num.' in the array argument has type '
                        .gettype($name).', but it needs to be a string.';
                $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
            } // @codeCoverageIgnore
            
            switch ($name) {
                case 'format':
                    $format = $value;
                    break;
                case 'type':
                    $type = $value;
                    break;
                case 'recordIds':
                    $recordIds = $value;
                    break;
                case 'fields':
                    $fields = $value;
                    break;
                case 'forms':
                    $forms = $value;
                    break;
                case 'events':
                    $events = $value;
                    break;
                case 'filterLogic':
                    $filterLogic = $value;
                    break;
                case 'rawOrLabel':
                    $rawOrLabel = $value;
                    break;
                case 'rawOrLabelHeaders':
                    $rawOrLabelHeaders = $value;
                    break;
                case 'exportCheckboxLabel':
                    $exportCheckboxLabel = $value;
                    break;
                case 'exportSurveyFields':
                    $exportSurveyFields = $value;
                    break;
                case 'exportDataAccessGroups':
                    $exportDataAccessGroups = $value;
                    break;
                case 'dateRangeBegin':
                    $dateRangeBegin = $value;
                    break;
                case 'dateRangeEnd':
                    $dateRangeEnd = $value;
                    break;
                case 'csvDelimiter':
                    $csvDelimiter = $value;
                    break;
                case 'decimalCharacter':
                    $decimalCharacter = $value;
                    break;
                case 'exportBlankForGrayFormStatus':
                    $exportBlankForGrayFormStatus = $value;
                    break;
                default:
                    $this->errorHandler->throwException(
                        'Unrecognized argument name "' . $name . '".',
                        ErrorHandlerInterface::INVALID_ARGUMENT
                    );
                    break; // @codeCoverageIgnore
            }
            $num++;
        }
        
        $records = $this->exportRecords(
            isset($format)                       ? $format                       : 'php',
            isset($type)                         ? $type                         : 'flat',
            isset($recordIds)                    ? $recordIds                    : null,
            isset($fields)                       ? $fields                       : null,
            isset($forms)                        ? $forms                        : null,
            isset($events)                       ? $events                       : null,
            isset($filterLogic)                  ? $filterLogic                  : null,
            isset($rawOrLabel)                   ? $rawOrLabel                   : 'raw',
            isset($rawOrLabelHeaders)            ? $rawOrLabelHeaders            : 'raw',
            isset($exportCheckboxLabel)          ? $exportCheckboxLabel          : false,
            isset($exportSurveyFields)           ? $exportSurveyFields           : false,
            isset($exportDataAccessGroups)       ? $exportDataAccessGroups       : false,
            isset($dateRangeBegin)               ? $dateRangeBegin               : null,
            isset($dateRangeEnd)                 ? $dateRangeEnd                 : null,
            isset($csvDelimiter)                 ? $csvDelimiter                 : ',',
            isset($decimalCharacter)             ? $decimalCharacter             : null,
            isset($exportBlankForGrayFormStatus) ? $exportBlankForGrayFormStatus : false
        );
        
        return $records;
    }

    
    /**
     * Imports the specified records into the project.
     *
     * @param mixed $records
     *            If the 'php' (default) format is being used, an array of associated arrays (maps)
     *            where each key is a field name,
     *            and its value is the value to store in that field. If any other format is being used, then
     *            the records are represented by a string.
     * @param string $format One of the following formats can be specified
     *            <ul>
     *              <li> 'php' - [default] array of maps of values</li>
     *              <li> 'csv' - string of CSV (comma-separated values)</li>
     *              <li> 'json' - string of JSON encoded values</li>
     *              <li> 'xml' - string of XML encoded data</li>
     *              <li> 'odm' - CDISC ODM XML format, specifically ODM version 1.3.1</li>
     *            </ul>
     * @param string $type
     *            <ul>
     *              <li> 'flat' - [default] each data element is a record</li>
     *              <li> 'eav' - each data element is one value</li>
     *            </ul>
     * @param string $overwriteBehavior
     *            <ul>
     *              <li>normal - [default] blank/empty values will be ignored</li>
     *              <li>overwrite - blank/empty values are valid and will overwrite data</li>
     *            </ul>
     * @param string $dateFormat date format which can be one of the following:
     *            <ul>
     *              <li>'YMD' - [default] Y-M-D format (e.g., 2016-12-31)</li>
     *              <li>'MDY' - M/D/Y format (e.g., 12/31/2016)</li>
     *              <li>'DMY' - D/M/Y format (e.g., 31/12/2016)</li>
     *           </ul>
     * @param string $returnContent specifies what should be returned:
     *           <ul>
     *             <li>'count' - [default] the number of records imported</li>
     *             <li>'ids' - an array of the record IDs imported is returned</li>
     *             <li>'auto_ids' - an array of comma-separated record ID pairs, with
     *                 the new ID created and the corresponding ID that
     *                 was sent, for the records that were imported.
     *                 This can only be used if $forceAutoNumber is set to true.</li>
     *           </ul>
     * @param boolean $forceAutoNumber enables automatic assignment of record IDs of imported
     *         records by REDCap.
     *         If this is set to true, and auto-numbering for records is enabled for the project,
     *         auto-numbering of imported records will be enabled.
     *
     * @param string $csvDelimiter specifies which delimiter separates the values in the CSV
     *         data file (for CSV format only).
     *         <ul>
     *           <li> ',' - comman [default] </li>
     *           <li> 'tab' </li>
     *           <li> ';' - semi-colon </li>
     *           <li> '|' - pipe </li>
     *           <li> '^' - caret </li>
     *         </ul>
     *
     * @return mixed if 'count' was specified for 'returnContent', then an integer will
     *         be returned that is the number of records imported.
     *         If 'ids' was specified, then an array of record IDs that were imported will
     *         be returned. If 'auto_ids' was specified, an array that maps newly created IDs
     *         to sent IDs will be returned.
     */
    public function importRecords(
        $records,
        $format = 'php',
        $type = 'flat',
        $overwriteBehavior = 'normal',
        $dateFormat = 'YMD',
        $returnContent = 'count',
        $forceAutoNumber = false,
        $csvDelimiter = ','
    ) {
            
        $data = array (
            'token'         => $this->apiToken,
            'content'       => 'record',
            'returnFormat'  => 'json'
        );
            
        #---------------------------------------
        # Process format
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'odm', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        if ($data['format'] == 'csv') {
            $data['csvDelimiter'] = $this->processCsvDelimiterArgument($csvDelimiter, $format);
        }
        $data['data']   = $this->processImportDataArgument($records, 'records', $format);
        $data['type']   = $this->processTypeArgument($type);
            
        $data['overwriteBehavior'] = $this->processOverwriteBehaviorArgument($overwriteBehavior);
        $data['forceAutoNumber']   = $this->processForceAutoNumberArgument($forceAutoNumber);
        $data['returnContent']     = $this->processReturnContentArgument($returnContent, $forceAutoNumber);
        $data['dateFormat']        = $this->processDateFormatArgument($dateFormat);
        
        $result = $this->connection->callWithArray($data);

        $this->processNonExportResult($result);
        
        
        #--------------------------------------------------------------------------
        # Process result, which should either be a count of the records imported,
        # or a list of the record IDs that were imported
        #
        # The result should be a string in JSON for all formats.
        # Need to convert the result to a PHP data structure.
        #--------------------------------------------------------------------------
        $phpResult = json_decode($result, true); // true => return as array instead of object
        
        $jsonError = json_last_error();
        
        switch ($jsonError) {
            case JSON_ERROR_NONE:
                $result = $phpResult;
                # If this is a count, then just return the count, and not an
                # array that has a count index with the count
                if (isset($result) && is_array($result) && array_key_exists('count', $result)) {
                    $result = $result['count'];
                }
                break;
            default:
                # Hopefully the REDCap API will always return valid JSON, and this
                # will never happen.
                $message =  'JSON error ('.$jsonError.') "'.json_last_error_msg().
                    '" while processing import return value: "'.
                $result.'".';
                $this->errorHandler->throwException($message, ErrorHandlerInterface::JSON_ERROR);
                break; // @codeCoverageIgnore
        }
        
        return $result;
    }
    
    
    /**
     * Deletes the specified records from the project.
     *
     * @param array $recordIds array of record IDs to delete
     * @param string $arm if an arm is specified, only records that have
     *     one of the specified record IDs that are in that arm will
     *     be deleted.
     * @param string $form form for which fields should be deleted
     *     (only these field will be deleted).
     * @param string $event event for which fields should be deleted
     *     (must be spefied for longitudinal studies if an event is spcified).
     * @param integer $repeatInstance for repeating events and forms, the instance for
     *     which fields should be deleted.
     * @param integer $deleteLogging flag that indicates if the logging associated
     *     with the records being deleted should also be deleted. This is only applicable
     *     for projects where the setting to delete logging for deleted records
     *     has been enabled by an admin.
     *     For these projects, set $deleteLogging to 1 (or leave it unset) to delete the
     *     logging for the deleted records,
     *     and set $deleteLogging to 0 to keep the logging for the deleted records.
     *
     * @throws PhpCapException
     *
     * @return integer the number of records deleted.
     */
    public function deleteRecords(
        $recordIds,
        $arm = null,
        $form = null,
        $event = null,
        $repeatInstance = null,
        $deleteLogging = null
    ) {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'record',
                'action'       => 'delete',
                'returnFormat' => 'json'
        );
        
        $data['records'] = $this->processRecordIdsArgument($recordIds);
        $data['arm']     = $this->processArmArgument($arm);

        $data['instrument']      = $this->processFormArgument($form, $required = false);
        $data['event']           = $this->processEventArgument($event);
        $data['repeat_instance'] = $this->processRepeatInstanceArgument($repeatInstance);

        $deleteLogging = $this->processDeleteLoggingArgument($deleteLogging);
        if ($deleteLogging !== null) {
            $data['delete_logging']  = $deleteLogging;
        }

        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return $result;
    }


    /**
     * Renames the specified record with the new specified record ID.
     *
     * @param string $recordId the record ID of the record to rename.
     * @param string $newRecordId the new record ID to set for the specified records.
     * @param string $arm if specified, the rename will only be done for the redcord ID
     *     in that arm.
     *
     * @return mixed "1" if the records was successfully renamed, and throws an exception otherwise.
     */
    public function renameRecord($recordId, $newRecordId, $arm = null)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'record',
                'action'       => 'rename',
                'returnFormat' => 'json'
        );

        $data['record']          = $this->processRecordIdArgument($recordId);
        $data['new_record_name'] = $this->processRecordIdArgument($newRecordId);
        $data['arm']             = $this->processArmArgument($arm);

        $result = $this->connection->callWithArray($data);

        if ($result != 1) {
            $message = "Error renaming record '{$recordId}': {$result}.";
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        }
        
        return $result;
    }


    /**
     * Exports the repeating instruments and events.
     *
     * @param string $format the format in which to export the records:
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *       <li> 'odm' - string with CDISC ODM XML format, specifically ODM version 1.3.1</li>
     *     </ul>
     *
     * @return mixed an array will be returned for the 'php' format, and a string for
     *     all other formats. For classic (non-longitudinal) studies, the
     *     'form name' and 'custom form label' will be returned for each
     *     repeating form. Longitudinal studies additionally return the
     *     'event name'. For repeating events in longitudinal studies, a blank
     *     value will be returned for the form_name. In all cases, a blank
     *     value will be returned for the 'custom form label' if it is not defined.
     */
    public function exportRepeatingInstrumentsAndEvents($format = 'php')
    {
        $data = array(
            'token' => $this->apiToken,
            'content' => 'repeatingFormsEvents',
            'returnFormat' => 'json'
        );

        #---------------------------------------
        # Process the arguments
        #---------------------------------------
        $legalFormats = array('php', 'csv', 'json', 'xml', 'odm');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processExportResult($result, $format);
        
        return $result;
    }


    /**
     * Imports the repeating instruments and events.
     *
     * @param mixed $formsEvents for 'php' format or if no format is specified,
     *     this will be a PHP array of associative arrays. For other formats,
     *     this will be a string formatted in the specified format (e.g. json).
     *
     * @param string $format the format in which to export the records:
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return integer the number of repeated instruments or repeated events imported.
     */
    public function importRepeatingInstrumentsAndEvents($formsEvents, $format = 'php')
    {
        $data = array(
            'token' => $this->apiToken,
            'content' => 'repeatingFormsEvents',
            'returnFormat' => 'json'
        );

        #---------------------------------------
        # Process the arguments
        #---------------------------------------
        $data['data'] = $this->processImportDataArgument(
            $formsEvents,
            'repeating instruments/events',
            $format
        );
        $legalFormats = array('php', 'csv', 'json', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        #---------------------------------------
        # Process the data
        #---------------------------------------
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }


    /**
     * Gets the REDCap version number of the REDCap instance being used by the project.
     *
     * @return string the REDCap version number of the REDCap instance being used by the project.
     */
    public function exportRedcapVersion()
    {
        $data = array(
            'token' => $this->apiToken,
            'content' => 'version'
        );
        
        $redcapVersion = $this->connection->callWithArray($data);
        $recapVersion = $this->processExportResult($redcapVersion, 'string');
        
        return $redcapVersion;
    }
    
    
    
    /**
     * Exports the records produced by the specified report.
     *
     * @param mixed $reportId integer or numeric string ID of the report to use.
     * @param string $format output data format.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param string $rawOrLabel indicates what should be exported for options of multiple choice fields:
     *     <ul>
     *       <li> 'raw' - [default] export the raw coded values</li>
     *       <li> 'label' - export the labels</li>
     *     </ul>
     * @param string $rawOrLabelHeaders when exporting with 'csv' format 'flat' type, indicates what format
     *         should be used for the CSV headers:
     *         <ul>
     *           <li> 'raw' - [default] export the variable/field names</li>
     *           <li> 'label' - export the field labels</li>
     *         </ul>
     * @param boolean $exportCheckboxLabel specifies the format for checkbox fields for the case where
     *         $format = 'csv', $rawOrLabel = true, and $type = 'flat'. For other cases this
     *         parameter is effectively ignored.
     *     <ul>
     *       <li> true - checked checkboxes will have a value equal to the checkbox option's label
     *           (e.g., 'Choice 1'), and unchecked checkboxes will have a blank value.
     *       </li>
     *       <li> false - [default] checked checkboxes will have a value of 'Checked', and
     *            unchecked checkboxes will have a value of 'Unchecked'.
     *       </li>
     *     </ul>
     * @param string $csvDelimiter specifies what delimiter is used to separate
     *     values in a CSV file (for CSV format only). Options are:
     *     <ul>
     *       <li> ',' - comma, this is the default </li>
     *       <li> 'tab' - tab </li>
     *       <li> ';' - semi-colon</li>
     *       <li> '|' - pipe</li>
     *       <li> '^' - caret</li>
     *     </ul>
     * @param string $decimalCharacter specifies what decimal format to apply to
     * numeric values being returned. Options are:
     *     <ul>
     *       <li> '.' - dot/full stop </li>
     *       <li> ',' - comma </li>
     *       <li> null - numbers will be exported using the fields' native decimal format</li>
     *     </ul>
     *
     * @return mixed the records generated by the specefied report in the specified format.
     */
    public function exportReports(
        $reportId,
        $format = 'php',
        $rawOrLabel = 'raw',
        $rawOrLabelHeaders = 'raw',
        $exportCheckboxLabel = false,
        $csvDelimiter = ',',
        $decimalCharacter = null
    ) {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'report',
                'returnFormat' => 'json'
        );
        
        #------------------------------------------------
        # Process arguments
        #------------------------------------------------
        $data['report_id'] = $this->processReportIdArgument($reportId);

        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);

        $data['rawOrLabel']          = $this->processRawOrLabelArgument($rawOrLabel);
        $data['rawOrLabelHeaders']   = $this->processRawOrLabelHeadersArgument($rawOrLabelHeaders);
        $data['exportCheckboxLabel'] = $this->processExportCheckboxLabelArgument($exportCheckboxLabel);
        if ($data['format'] == 'csv') {
            $data['csvDelimiter'] = $this->processCsvDelimiterArgument($csvDelimiter, $format);
        }
        $data['decimalCharacter'] = $this->processDecimalCharacterArgument($decimalCharacter);

        #---------------------------------------------------
        # Get and process records
        #---------------------------------------------------
        $records = $this->connection->callWithArray($data);
        $records = $this->processExportResult($records, $format);
         
        return $records;
    }

    
    /**
     * Exports the survey link for the specified inputs.
     *
     * @param string $recordId the record ID for the link.
     * @param string $form the form for the link.
     * @param string $event event for link (for longitudinal studies only).
     * @param integer $repeatInstance for repeatable forms, the instance of the form
     *     to return a link for.
     *
     * @return string survey link.
     */
    public function exportSurveyLink($recordId, $form, $event = null, $repeatInstance = null)
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'surveyLink',
                'returnFormat' => 'json'
        );
        
        #----------------------------------------------
        # Process arguments
        #----------------------------------------------
        $data['record']          = $this->processRecordIdArgument($recordId, $required = true);
        $data['instrument']      = $this->processFormArgument($form, $required = true);
        $data['event']           = $this->processEventArgument($event);
        $data['repeat_instance'] = $this->processRepeatInstanceArgument($repeatInstance);
        
        $surveyLink = $this->connection->callWithArray($data);
        $surveyLink = $this->processExportResult($surveyLink, 'string');
        
        return $surveyLink;
    }
    
    /**
     * Exports the list of survey participants for the specified form and, for
     * longitudinal studies, event.
     *
     * @param string $form the form for which the participants should be exported.
     * @param string $format output data format.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     * @param string $event the event name for which survey participants should be
     *     exported.
     *
     * @return mixed for the 'php' format, an array of arrays of participant
     *     information is returned, for all other formats, the data is returned
     *     in the specified format as a string.
     */
    public function exportSurveyParticipants($form, $format = 'php', $event = null)
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'participantList',
                'returnFormat' => 'json'
        );
        
        #----------------------------------------------
        # Process arguments
        #----------------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format']     = $this->processFormatArgument($format, $legalFormats);
        $data['instrument'] = $this->processFormArgument($form, $required = true);
        $data['event']      = $this->processEventArgument($event);
        
        $surveyParticipants = $this->connection->callWithArray($data);
        $surveyParticipants = $this->processExportResult($surveyParticipants, $format);
        
        return $surveyParticipants;
    }
    
    /**
     * Exports the survey queue link for the specified record ID.
     *
     * @param string $recordId the record ID of the survey queue link that should be returned.
     *
     * @return string survey queue link.
     */
    public function exportSurveyQueueLink($recordId)
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'surveyQueueLink',
                'returnFormat' => 'json'
        );
        
        #----------------------------------------------
        # Process arguments
        #----------------------------------------------
        $data['record'] = $this->processRecordIdArgument($recordId, $required = true);
        
        $surveyQueueLink = $this->connection->callWithArray($data);
        $surveyQueueLink = $this->processExportResult($surveyQueueLink, 'string');
        
        return $surveyQueueLink;
    }
    
    /**
     * Exports the code for returning to a survey that was not completed.
     *
     * @param string $recordId the record ID for the survey to return to.
     * @param string $form the form name of the survey to return to.
     * @param string $event the unique event name (for longitudinal studies) for the survey
     *     to return to.
     * @param integer $repeatInstance the repeat instance (if any) for the survey to return to.
     * @return string survey return code.
     */
    public function exportSurveyReturnCode($recordId, $form, $event = null, $repeatInstance = null)
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'surveyReturnCode',
                'returnFormat' => 'json'
        );
        
        #----------------------------------------------
        # Process arguments
        #----------------------------------------------
        $data['record']          = $this->processRecordIdArgument($recordId, $required = true);
        $data['instrument']      = $this->processFormArgument($form, $required = true);
        $data['event']           = $this->processEventArgument($event);
        $data['repeat_instance'] = $this->processRepeatInstanceArgument($repeatInstance);
        
        $surveyReturnCode = $this->connection->callWithArray($data);
        $surveyReturnCode = $this->processExportResult($surveyReturnCode, 'string');
        
        return $surveyReturnCode;
    }
    
    
    /**
     * Exports the users of the project.
     *
     * @param string $format output data format.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return mixed a list of users. For the 'php' format an array of associative
     *     arrays is returned, where the keys are the field names and the values
     *     are the field values. For all other formats, a string is returned with
     *     the data in the specified format.
     */
    public function exportUsers($format = 'php')
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'user',
            'returnFormat' => 'json'
        );
        
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        #---------------------------------------------------
        # Get and process users
        #---------------------------------------------------
        $users = $this->connection->callWithArray($data);
        $users = $this->processExportResult($users, $format);
        
        return $users;
    }
    
    /**
     * Imports the specified users into the project. This method
     * can also be used to update user priveleges by importing
     * a users that already exist in the project and
     * specifying new privleges for that user in the user
     * data that is imported.
     *
     * The available field names for user import are:
     * <pre>
     * <code class="phpdocumentor-code">
     * username, expiration, data_access_group, design,
     * user_rights, data_access_groups, data_export, reports, stats_and_charts,
     * manage_survey_participants, calendar, data_import_tool, data_comparison_tool,
     * logging, file_repository, data_quality_create, data_quality_execute,
     * api_export, api_import, mobile_app, mobile_app_download_data,
     * record_create, record_rename, record_delete,
     * lock_records_customization, lock_records, lock_records_all_forms,
     * forms, forms_export
     * </code>
     * </pre>
     *
     *
     * Privileges for fields above can be set as follows:
     * <ul>
     *   <li><b>Data Export:</b> 0=No Access, 2=De-Identified, 1=Full Data Set</li>
     *   <li><b>Form Rights:</b> 0=No Access, 2=Read Only,
     *       1=View records/responses and edit records (survey responses are read-only),
     *       3=Edit survey responses</li>
     *   <li><b>Other field values:</b> 0=No Access, 1=Access.</li>
     * </ul>
     *
     * See the REDCap API documentation for more information, or print the results
     * of PHPCap's exportUsers method to see what the data looks like for the current users.
     *
     * @param mixed $users for 'php' format, an array should be used that
     *     maps field names to field values. For all other formats a string
     *     should be used that has the data in the correct format.
     * @param string $format output data format.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return integer the number of users added or updated.
     */
    public function importUsers($users, $format = 'php')
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'user',
            'returnFormat' => 'json'
        );
        
        #----------------------------------------------------
        # Process arguments
        #----------------------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['data']   = $this->processImportDataArgument($users, 'users', $format);
        
        #---------------------------------------------------
        # Get and process users
        #---------------------------------------------------
        $result = $this->connection->callWithArray($data);
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }


    /**
     * Deletes the specified users.
     *
     * @param array $users array of usernames for users to delete.
     *
     * @return integer the number of users deleted.
     */
    public function deleteUsers($users)
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'user',
            'action'       => 'delete',
            'returnFormat' => 'json'
        );

        $data['users'] = $this->processUsersArgument($users);
        $result = $this->connection->callWithArray($data);

        return (integer) $result;
    }

    
    /**
     * Exports the user roles of the project.
     *
     * @param string $format output data format.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return mixed a list of user roles. For the 'php' format an array of associative
     *     arrays is returned, where the keys are the field names and the values
     *     are the field values. For all other formats, a string is returned with
     *     the data in the specified format.
     */
    public function exportUserRoles($format = 'php')
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'userRole',
            'returnFormat' => 'json'
        );
        
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        #---------------------------------------------------
        # Get and process user roles
        #---------------------------------------------------
        $userRoles = $this->connection->callWithArray($data);
        $userRoles = $this->processExportResult($userRoles, $format);
        
        return $userRoles;
    }
    
    /**
     * Imports the specified user roles into the project.
     * This method can also be used to update user roles by importing
     * a user role that already exist in the project and
     * specifying new values in the data that is imported.
     *
     * The available field names for user import are:
     * <pre>
     * <code class="phpdocumentor-code">
     * unique_role_name, role_label, design, user_rights, data_access_groups,
     * reports, stats_and_charts, manage_survey_participants, calendar,
     * data_import_tool, data_comparison_tool, logging, file_repository,
     * data_quality_create, data_quality_execute,
     * api_export, api_import, mobile_app, mobile_app_download_data,
     * record_create, record_rename, record_delete,
     * lock_records_customization, lock_records, lock_records_all_forms,
     * forms, forms_export
     * </code>
     * </pre>
     *
     *
     * See the REDCap API documentation for more information, or print the results
     * of PHPCap's exportUsers method to see what the data looks like for the current users.
     *
     * @param mixed $userRoles for 'php' format, an array should be used that
     *     maps field names to field values. For all other formats a string
     *     should be used that has the data in the correct format.
     *
     * @param string $format output data format.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return integer the number of user roles added or updated.
     */
    public function importUserRoles($userRoles, $format = 'php')
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'userRole',
            'returnFormat' => 'json'
        );
        
        #----------------------------------------------------
        # Process arguments
        #----------------------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['data']   = $this->processImportDataArgument($userRoles, 'userRoles', $format);

        #---------------------------------------------------
        # Get and process users
        #---------------------------------------------------
        $result = $this->connection->callWithArray($data);
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }

    /**
     * Deletes the specified user roles.
     *
     * @param array $userRoles array of unique roles names of roles to delete.
     *
     * @return integer the number of user roles deleted.
     */
    public function deleteUserRoles($userRoles)
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'userRole',
            'action'       => 'delete',
            'returnFormat' => 'json'
        );

        $data['roles'] = $this->processUserRolesArgument($userRoles);
        $result = $this->connection->callWithArray($data);

        return (integer) $result;
    }


    /**
     * Exports the user role assignments of the project.
     *
     * @param string $format output data format.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return mixed a list of user roles assignments.
     *     For the 'php' format an array of associative
     *     arrays is returned, where the keys are the field names and the values
     *     are the field values. For all other formats, a string is returned with
     *     the data in the specified format.
     */
    public function exportUserRoleAssignments($format = 'php')
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'userRoleMapping',
            'returnFormat' => 'json'
        );
        
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        #---------------------------------------------------
        # Get and process user role assignments
        #---------------------------------------------------
        $userRoleAssignments = $this->connection->callWithArray($data);
        $userRoleAssignments = $this->processExportResult($userRoleAssignments, $format);
        
        return $userRoleAssignments;
    }

    
    /**
     * Imports the specified user role assignments into the project.
     * This method can also be used to update user role assignments by importing
     * a user role that already exist in the project and
     * specifying new values in the data that is imported.
     *
     * The data is a map from username to unqiue_role_name.
     *
     * See the REDCap API documentation for more information, or print the results
     * of PHPCap's exportUsers method to see what the data looks like for the current users.
     *
     * @param mixed $userRoleAssignments for 'php' format, an array should be used that
     *     maps usernames to unique role names. For all other formats a string
     *     should be used that has the data in the correct format.
     *
     * @param string $format output data format.
     *     <ul>
     *       <li> 'php' - [default] array of maps of values</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *     </ul>
     *
     * @return integer the number of user roles added or updated.
     */
    public function importUserRoleAssignments($userRoleAssignments, $format = 'php')
    {
        $data = array(
            'token'        => $this->apiToken,
            'content'      => 'userRoleMapping',
            'action'       => 'import',
            'returnFormat' => 'json'
        );
        
        #----------------------------------------------------
        # Process arguments
        #----------------------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['data']   = $this->processImportDataArgument($userRoleAssignments, 'userRoleMapping', $format);

        #---------------------------------------------------
        # Get and process users
        #---------------------------------------------------
        $result = $this->connection->callWithArray($data);
        $this->processNonExportResult($result);
        
        return (integer) $result;
    }


    /**
     * Gets the PHPCap version number.
     */
    public function getPhpCapVersion()
    {
        return Version::RELEASE_NUMBER;
    }
 
    /**
     * Gets an array of record ID batches.
     *
     * These can be used for batch
     * processing of records exports to lessen memory requirements, for example:
     * <pre>
     * <code class="phpdocumentor-code">
     * ...
     * # Get all the record IDs of the project in 10 batches
     * $recordIdBatches = $project->getRecordIdBatches(10);
     * foreach ($recordIdBatches as $recordIdBatch) {
     *     $records = $project->exportRecordsAp(['recordIds' => $recordIdBatch]);
     *     ...
     * }
     * ...
     * </code>
     * </pre>
     *
     * @param integer $batchSize the batch size in number of record IDs.
     *     The last batch may have less record IDs. For example, if you had 500
     *     record IDs and specified a batch size of 200, the first 2 batches would have
     *     200 record IDs, and the last batch would have 100.
     * @param string $filterLogic logic used to restrict the records retrieved, e.g.,
     *     "[last_name] = 'Smith'". This could be used for batch processing a subset
     *     of the records.
     * @param $recordIdFieldName the name of the record ID field. Specifying this is not
     *     necessary, but will speed things up, because it will eliminate the need for
     *     this method to call the REDCap API to retrieve the value.
     * @return array an array or record ID arrays, where each record ID array
     *     is considered to be a batch. Each batch can be used as the value
     *     for the records IDs parameter for an export records method.
     */
    public function getRecordIdBatches($batchSize = null, $filterLogic = null, $recordIdFieldName = null)
    {
        $recordIdBatches = array();
        
        #-----------------------------------
        # Check arguments
        #-----------------------------------
        if (!isset($batchSize)) {
            $message = 'The number of batches was not specified.';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } elseif (!is_int($batchSize)) {
            $message = "The batch size argument has type '".gettype($batchSize).'", '
                .'but it should have type integer.';
                $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } elseif ($batchSize < 1) {
            $message = 'The batch size argument is less than 1. It needs to be at least 1.';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } // @codeCoverageIgnore
        
        $filterLogic = $this->processFilterLogicArgument($filterLogic);
        
        if (!isset($recordIdFieldName)) {
            $recordIdFieldName = $this->getRecordIdFieldName();
        }
        
        $records = $this->exportRecordsAp(
            ['fields' => [$recordIdFieldName], 'filterLogic' => $filterLogic]
        );
        $recordIds = array_column($records, $recordIdFieldName);
        $recordIds = array_unique($recordIds);  # Remove duplicate record IDs
        
        $numberOfRecordIds = count($recordIds);
        
        $position = 0;
        for ($position = 0; $position < $numberOfRecordIds; $position += $batchSize) {
            $recordIdBatch = array();
            $recordIdBatch = array_slice($recordIds, $position, $batchSize);
            array_push($recordIdBatches, $recordIdBatch);
        }
        
        return $recordIdBatches;
    }
    
    
    
    /**
     * Gets the record ID field name for the project.
     *
     * @return string the field name of the record ID field of the project.
     */
    public function getRecordIdFieldName()
    {
        $metadata = $this->exportMetaData();
        $recordIdFieldName = $metadata[0]['field_name'];
        return $recordIdFieldName;
    }

    /**
     * Gets the REDCap API URL specified to create the object.
     *
     * @return string the REDCap API URL for the project.
     */
    public function getApiUrl()
    {
        return $this->connection->getUrl();
    }
    
    /**
     * Gets the API token for the project.
     *
     * @return string the API token for the project.
     */
    public function getApiToken()
    {
        return $this->apiToken;
    }
    
    
    /**
     * Returns the underlying REDCap API connection being used by the project.
     * This can be used to make calls to the REDCap API, possibly to access functionality
     * not supported by PHPCap.
     *
     * @return RedCapApiConnectionInterface the underlying REDCap API connection being
     *         used by the project.
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Sets the connection used for calling the REDCap API.
     *
     * @param RedCapApiConnectionInterface $connection the connection to use
     *     for calls to the REDCap API.
     */
    public function setConnection($connection)
    {
        $this->connection = $this->processConnectionArgument($connection);
    }
    
    /**
     * Gets the error handler.
     *
     * @return ErrorHandlerInterface the error handler being used.
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }
    
    /**
     * Sets the error handler used by the project.
     *
     * @param ErrorHandlerInterface $errorHandler the error handler to use.
     */
    public function setErrorHandler($errorHandler)
    {
        $this->errorHandler = $this->processErrorHandlerArgument($errorHandler);
    }


    protected function getFileInfo($callInfo)
    {
        $fileInfo = array();
        if (isset($callInfo) && is_array($callInfo) && array_key_exists('content_type', $callInfo)) {
            if (!empty($callInfo)) {
                $contentType = $callInfo = explode(';', $callInfo['content_type']);
                if (count($contentType) >= 1) {
                    $fileInfo['mime_type'] = trim($contentType[0]);
                }
                if (count($contentType) >= 2) {
                    $fileName = trim($contentType[1]);
                    # remove starting 'name="' and ending '"'
                    $fileName = substr($fileName, 6, strlen($fileName) - 7);
                    $fileInfo['name'] = $fileName;
                }
                if (count($contentType) >= 3) {
                    $charset = trim($contentType[2]);
                    $charset = substr($charset, 8);
                    $fileInfo['charset'] = $charset;
                }
            }
        }
        return $fileInfo;
    }

    protected function processAllRecordsArgument($allRecords)
    {
        if (!isset($allRecords)) {
            ;  // That's OK
        } elseif (!is_bool($allRecords)) {
            $message = 'The allRecords argument has type "'.gettype($allRecords).
            '", but it should be a boolean (true/false).';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } elseif ($allRecords !== true) {
            $allRecords = null; // need to reset to null, because ANY (non-null) value
                                // will cause the REDCap API to return all records
        }
        
        return $allRecords;
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
    
    
    protected function processArmArgument($arm)
    {
        if (!isset($arm)) {
            ;  // That's OK
        } elseif (is_string($arm)) {
            if (! preg_match('/^[0-9]+$/', $arm)) {
                $this->errorHandler->throwException(
                    'Arm number "' . $arm . '" is non-numeric string.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            } // @codeCoverageIgnore
        } elseif (is_int($arm)) {
            if ($arm < 0) {
                $this->errorHandler->throwException(
                    'Arm number "' . $arm . '" is a negative integer.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            } // @codeCoverageIgnore
        } else {
            $message = 'The arm argument has type "'.gettype($arm)
                .'"; it should be an integer or a (numeric) string.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        return $arm;
    }
    
    protected function processArmsArgument($arms, $required = false)
    {
        if (!isset($arms)) {
            if ($required === true) {
                $this->errorHandler->throwException(
                    'The arms argument was not set.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            } // @codeCoverageIgnore
            $arms = array();
        } else {
            if (!is_array($arms)) {
                $this->errorHandler->throwException(
                    'The arms argument has invalid type "'.gettype($arms).'"; it should be an array.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            } elseif ($required === true && count($arms) < 1) {
                $this->errorHandler->throwException(
                    'No arms were specified in the arms argument; at least one must be specified.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            } // @codeCoverageIgnore
        }
        
        foreach ($arms as $arm) {
            if (is_string($arm)) {
                if (! preg_match('/^[0-9]+$/', $arm)) {
                    $this->errorHandler->throwException(
                        'Arm number "' . $arm . '" is non-numeric string.',
                        ErrorHandlerInterface::INVALID_ARGUMENT
                    );
                } // @codeCoverageIgnore
            } elseif (is_int($arm)) {
                if ($arm < 0) {
                    $this->errorHandler->throwException(
                        'Arm number "' . $arm . '" is a negative integer.',
                        ErrorHandlerInterface::INVALID_ARGUMENT
                    );
                } // @codeCoverageIgnore
            } else {
                $message = 'An arm was found in the arms array that has type "'.gettype($arm).
                '"; it should be an integer or a (numeric) string.';
                $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
            } // @codeCoverageIgnore
        }
        
        return $arms;
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
    

    protected function processCompactDisplayArgument($compactDisplay)
    {
        if (!isset($compactDisplay) || $compactDisplay === null) {
            ;  // That's OK
        } elseif (!is_bool($compactDisplay)) {
            $message = 'The compact display argument has type "'.gettype($compactDisplay).
            '", but it should be a boolean (true/false).';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        }
        return $compactDisplay;
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
    

    protected function processCsvDelimiterArgument($csvDelimiter, $format)
    {
        $legalCsvDelimiters = array(',',';','tab','|','^');
        if ($format == 'csv') {
            if (empty($csvDelimiter)) {
                $csvDelimiter = ',';
            }
            if (gettype($csvDelimiter) !== 'string') {
                $message = 'The csv delimiter specified has type "'.gettype($csvDelimiter)
                    .'", but it should be a string.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        
            $csvDelimiter = strtolower(trim($csvDelimiter));
        
            if (!in_array($csvDelimiter, $legalCsvDelimiters)) {
                $message = 'Invalid csv delimiter "'.$csvDelimiter.'" specified.'
                    .' Valid csv delimiter options are: "'.
                    implode('", "', $legalCsvDelimiters).'".';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $csvDelimiter;
    }


    protected function processDagArgument($dag, $required = false)
    {
        if (!empty($dag)) {
            if (!is_string($dag)) {
                $message = 'The dag argument has invalid type "'.gettype($dag)
                    .'"; it should be a string.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            }
        } elseif ($required) {
            $message = 'No DAG (Data Access Group) was specified.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        }

        return $dag;
    }


    protected function processDagsArgument($dags, $required = true)
    {
        if (!isset($dags)) {
            if ($required === true) {
                $this->errorHandler->throwException(
                    'The dags argument was not set.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            }
            // @codeCoverageIgnoreStart
            $dags = array();
            // @codeCoverageIgnoreEnd
        } else {
            if (!is_array($dags)) {
                $this->errorHandler->throwException(
                    'The dags argument has invalid type "'.gettype($dags).'"; it should be an array.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            } elseif ($required === true && count($dags) < 1) {
                $this->errorHandler->throwException(
                    'No dags were specified in the dags argument; at least one must be specified.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            }
        }
        
        foreach ($dags as $dag) {
            $type = gettype($dag);
            if (strcmp($type, 'string') !== 0) {
                $message = 'A dag with type "'.$type.'" was found in the dags array.'.
                    ' Dags should be strings.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            }
        }
        
        return $dags;
    }

    
    protected function processDagIdArgument($dagId)
    {
        if (!isset($dagId)) {
            $dagId = '';
        } elseif (!is_int($dagId)) {
            $message = 'The DAG ID has type "'.gettype($dagId)
                .'", but it should be an integer.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        }
        
        return $dagId;
    }
    

    protected function processDateFormatArgument($dateFormat)
    {
        if (!isset($dateFormat)) {
            $dateFormat = 'YMD';
        } else {
            if (gettype($dateFormat) === 'string') {
                $dateFormat = strtoupper($dateFormat);
            }
            
            $legalDateFormats = ['MDY', 'DMY', 'YMD'];
            if (!in_array($dateFormat, $legalDateFormats)) {
                $message = 'Invalid date format "'.$dateFormat.'" specified.'
                    .' The date format should be one of the following: "'
                    .implode('", "', $legalDateFormats).'".';
                    $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        
        return $dateFormat;
    }
    

    protected function processDateRangeArgument($date)
    {
        if (isset($date)) {
            if (trim($date) === '') {
                $date = null;
            } else {
                $legalFormat = 'Y-m-d H:i:s';
                $err = false;

                if (gettype($date) === 'string') {
                    $dt = \DateTime::createFromFormat($legalFormat, $date);
        
                    if (!($dt && $dt->format($legalFormat) == $date)) {
                        $err = true;
                    }
                } else {
                    $err = true;
                }

                if ($err) {
                    $errMsg = 'Invalid date format. ';
                    $errMsg .= "The date format for export dates is YYYY-MM-DD HH:MM:SS, ";
                    $errMsg .= 'e.g., 2020-01-31 00:00:00.';
                    $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                    $this->errorHandler->throwException($errMsg, $code);
                } // @codeCoverageIgnore
            }
        }
        return $date;
    }

    protected function processDocIdArgument($docId)
    {
        if (!isset($docId)) {
            $message = 'No doc ID specified';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } elseif (!is_int($docId)) {
            $message = 'The doc ID has type "'.gettype($docId)
                .'", but it should be an integer.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        }
        
        return $docId;
    }

    protected function processDecimalCharacterArgument($decimalCharacter)
    {
        $legalDecimalCharacters = array(',','.');
        if ($decimalCharacter) {
            if (!in_array($decimalCharacter, $legalDecimalCharacters)) {
                $message = 'Invalid decimal character of "'.$decimalCharacter.'" specified.'
                    .' Valid decimal character options are: "'.
                    implode('", "', $legalDecimalCharacters).'".';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $decimalCharacter;
    }

    protected function processDeleteLoggingArgument($deleteLogging)
    {
        if ($deleteLogging == null) {
            ; // OK
        } elseif (!is_int($deleteLogging) || ($deleteLogging != 0 && $deleteLogging != 1)) {
            $message = 'Invalid delete logging value. The delete logging value must be 0 or 1';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        }
        return $deleteLogging;
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
    
    protected function processEventArgument($event)
    {
        if (!isset($event)) {
            ; // This might be OK
        } elseif (gettype($event) !== 'string') {
            $message = 'Event has type "'.gettype($event).'", but should be a string.';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } // @codeCoverageIgnore
        return $event;
    }
    
    protected function processEventsArgument($events, $required = false)
    {
        if (!isset($events)) {
            if ($required === true) {
                $message = 'The events argument was not set.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
            $events = array();
        } else {
            if (!is_array($events)) {
                $message = 'The events argument has invalid type "'.gettype($events)
                    .'"; it should be an array.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } elseif ($required === true && count($events) < 1) {
                $message = 'No events were specified in the events argument;'
                    .' at least one must be specified.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } else { // @codeCoverageIgnore
                foreach ($events as $event) {
                    $type = gettype($event);
                    if (strcmp($type, 'string') !== 0) {
                        $message = 'An event with type "'.$type.'" was found in the events array.'.
                            ' Events should be strings.';
                        $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                        $this->errorHandler->throwException($message, $code);
                    } // @codeCoverageIgnore
                }
            }
        }
        
        return $events;
    }
    
    protected function processExportBlankForGrayFormStatusArgument($exportBlankForGrayFormStatus)
    {
        if ($exportBlankForGrayFormStatus == null) {
            $exportBlankForGrayFormStatus = false;
        } elseif (gettype($exportBlankForGrayFormStatus) !== 'boolean') {
            $this->errorHandler->throwException(
                'Invalid type for exportBlankForGrayFormStatus. It should be a boolean (true or false),'
                .' but has type: '.gettype($exportBlankForGrayFormStatus).'.',
                ErrorHandlerInterface::INVALID_ARGUMENT
            );
        }
        return $exportBlankForGrayFormStatus;
    }

    
    protected function processExportCheckboxLabelArgument($exportCheckboxLabel)
    {
        if ($exportCheckboxLabel == null) {
            $exportCheckboxLabel = false;
        } else {
            if (gettype($exportCheckboxLabel) !== 'boolean') {
                $this->errorHandler->throwException(
                    'Invalid type for exportCheckboxLabel. It should be a boolean (true or false),'
                    .' but has type: '.gettype($exportCheckboxLabel).'.',
                    ErrorHandlerInterface::INVALID_ARGUMENT
                );
            } // @codeCoverageIgnore
        }
        return $exportCheckboxLabel;
    }
    
    protected function processExportDataAccessGroupsArgument($exportDataAccessGroups)
    {
        if ($exportDataAccessGroups == null) {
            $exportDataAccessGroups = false;
        } else {
            if (gettype($exportDataAccessGroups) !== 'boolean') {
                $message = 'Invalid type for exportDataAccessGroups.'
                    .' It should be a boolean (true or false),'
                    .' but has type: '.gettype($exportDataAccessGroups).'.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $exportDataAccessGroups;
    }
    
    protected function processExportFilesArgument($exportFiles)
    {
        if ($exportFiles == null) {
            $exportFiles = false;
        } else {
            if (gettype($exportFiles) !== 'boolean') {
                $message = 'Invalid type for exportFiles. It should be a boolean (true or false),'
                    .' but has type: '.gettype($exportFiles).'.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $exportFiles;
    }
    
    /**
     * Processes an export result from the REDCap API.
     *
     * @param string $result
     * @param string $format
     * @throws PhpCapException
     */
    protected function processExportResult(&$result, $format)
    {
        if ($format == 'php') {
            $phpResult = json_decode($result, true); // true => return as array instead of object
                
            $jsonError = json_last_error();
                
            switch ($jsonError) {
                case JSON_ERROR_NONE:
                    $result = $phpResult;
                    break;
                default:
                    $message =  "JSON error (" . $jsonError . ") \"" . json_last_error_msg()
                        ."\" in REDCap API output."
                        ."\nThe first 1,000 characters of output returned from REDCap are:\n"
                        .substr($result, 0, 1000);
                    $code = ErrorHandlerInterface::JSON_ERROR;
                    $this->errorHandler->throwException($message, $code);
                    break; // @codeCoverageIgnore
            }
                
            if (array_key_exists('error', $result)) {
                $this->errorHandler->throwException($result ['error'], ErrorHandlerInterface::REDCAP_API_ERROR);
            } // @codeCoverageIgnore
        } else {
            // If this is a format other than 'php', look for a JSON error, because
            // all formats return errors as JSON
            $matches = array();
            $hasMatch = preg_match(self::JSON_RESULT_ERROR_PATTERN, $result ?? '', $matches);
            if ($hasMatch === 1) {
                // note: $matches[0] is the complete string that matched
                //       $matches[1] is just the error message part
                $message = $matches[1];
                $this->errorHandler->throwException($message, ErrorHandlerInterface::REDCAP_API_ERROR);
            } // @codeCoverageIgnore
        }
        
        return $result;
    }
    
    protected function processExportSurveyFieldsArgument($exportSurveyFields)
    {
        if ($exportSurveyFields == null) {
            $exportSurveyFields = false;
        } else {
            if (gettype($exportSurveyFields) !== 'boolean') {
                $message =  'Invalid type for exportSurveyFields.'
                    .' It should be a boolean (true or false),'
                    .' but has type: '.gettype($exportSurveyFields).'.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $exportSurveyFields;
    }
    
    protected function processFieldArgument($field, $required = true)
    {
        if (!isset($field)) {
            if ($required) {
                $message = 'No field was specified.';
                $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
            }  // @codeCoverageIgnore
            // else OK
        } elseif (gettype($field) !== 'string') {
            $message = 'Field has type "'.gettype($field).'", but should be a string.';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } // @codeCoverageIgnore
        return $field;
    }
    
    
    protected function processFieldsArgument($fields)
    {
        if (!isset($fields)) {
            $fields = array();
        } else {
            if (!is_array($fields)) {
                $message = 'Argument "fields" has the wrong type; it should be an array.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } else { // @codeCoverageIgnore
                foreach ($fields as $field) {
                    $type = gettype($field);
                    if (strcmp($type, 'string') !== 0) {
                        $message = 'A field with type "'.$type.'" was found in the fields array.'.
                            ' Fields should be strings.';
                        $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                        $this->errorHandler->throwException($message, $code);
                    } // @codeCoverageIgnore
                }
            }
        }
        
        return $fields;
    }

    protected function processFileArgument($file)
    {
        if (isset($file)) {
            if (gettype($file) !== 'string') {
                $message = "Argument 'file' has type '".gettype($file)."', but should be a string.";
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $file;
    }
    
    protected function processFilenameArgument($filename)
    {
        if (!isset($filename)) {
            $message = 'No filename specified.';
            $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } elseif (gettype($filename) !== 'string') {
            $message = "Argument 'filename' has type '".gettype($filename)."', but should be a string.";
            $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } elseif (!file_exists($filename)) {
            $message = 'The input file "'.$filename.'" could not be found.';
            $code    = ErrorHandlerInterface::INPUT_FILE_NOT_FOUND;
            $this->errorHandler->throwException($message, $code);
        } elseif (!is_readable($filename)) {
            $message = 'The input file "'.$filename.'" was unreadable.';
            $code    = ErrorHandlerInterface::INPUT_FILE_UNREADABLE;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        $basename = pathinfo($filename, PATHINFO_BASENAME);
        $curlFile = curl_file_create($filename, 'text/plain', $basename);
        
        return $curlFile;
    }
    
    
    protected function processFilterLogicArgument($filterLogic)
    {
        if ($filterLogic == null) {
            $filterLogic = '';
        } else {
            if (gettype($filterLogic) !== 'string') {
                $message = 'Invalid type for filterLogic. It should be a string, but has type "'
                    .gettype($filterLogic).'".';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $filterLogic;
    }
    
    protected function processFolderIdArgument($folderId)
    {
        if (!isset($folderId)) {
            $folderId = '';
        } elseif (!is_int($folderId)) {
            $message = 'The folder ID has type "'.gettype($folderId)
                .'", but it should be an integer.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        }
        
        return $folderId;
    }
    
    protected function processForceAutoNumberArgument($forceAutoNumber)
    {
        if ($forceAutoNumber == null) {
            $forceAutoNumber = false;
        } else {
            if (gettype($forceAutoNumber) !== 'boolean') {
                $message = 'Invalid type for forceAutoNumber.'
                    .' It should be a boolean (true or false),'
                    .' but has type: '.gettype($forceAutoNumber).'.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $forceAutoNumber;
    }
    
    protected function processFormArgument($form, $required = false)
    {
        if (!isset($form)) {
            if ($required === true) {
                $message = 'The form argument was not set.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
            $form = '';
        } elseif (!is_string($form)) {
            $message = 'The form argument has invalid type "'.gettype($form)
                .'"; it should be a string.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        return $form;
    }
    
    protected function processFormatArgument(&$format, $legalFormats)
    {
        if (!isset($format)) {
            $format = 'php';
        }
        
        if (gettype($format) !== 'string') {
            $message = 'The format specified has type "'.gettype($format)
                .'", but it should be a string.';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        $format = strtolower(trim($format));
        
        if (!in_array($format, $legalFormats)) {
            $message = 'Invalid format "'.$format.'" specified.'
                .' The format should be one of the following: "'.
                implode('", "', $legalFormats).'".';
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        $dataFormat = '';
        if (strcmp($format, 'php') === 0) {
            $dataFormat = 'json';
        } else {
            $dataFormat = $format;
        }
        
        return $dataFormat;
    }
    
    protected function processFormsArgument($forms)
    {
        if (!isset($forms)) {
            $forms = array();
        } else {
            if (!is_array($forms)) {
                $message = 'The forms argument has invalid type "'.gettype($forms)
                    .'"; it should be an array.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } else { // @codeCoverageIgnore
                foreach ($forms as $form) {
                    $type = gettype($form);
                    if (strcmp($type, 'string') !== 0) {
                        $message = 'A form with type "'.$type.'" was found in the forms array.'.
                            ' Forms should be strings.';
                        $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
                    } // @codeCoverageIgnore
                }
            }
        }
        
        return $forms;
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
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
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
        } else { // All other formats
            if (gettype($data) !== 'string') {
                $message = "Argument '".$dataName."' has type '".gettype($data)."'"
                    .", but should be a string.";
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        
        return $data;
    }
    

    protected function processLogTypeArgument($logType)
    {
        $legalLogTypes = array(
            'export',
            'manage',
            'user',
            'record',
            'record_add',
            'record_edit',
            'record_delete',
            'lock_record',
            'page_view'
        );
        if ($logType) {
            if (!in_array($logType, $legalLogTypes)) {
                $message = 'Invalid log type of "'.$logType.'" specified.'
                    .' Valid log types are: "'.
                    implode('", "', $legalLogTypes).'".';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            }
        }
        return $logType;
    }

    /**
     * Checks the result returned from the REDCap API for non-export methods.
     * PHPCap is set to return errors from REDCap using JSON, so the result
     * string is checked to see if there is a JSON format error, and if so,
     * and exception is thrown using the error message returned from the
     * REDCap API.
     *
     * @param string $result a result returned from the REDCap API, which
     *     should be for a non-export method.
     */
    protected function processNonExportResult(&$result)
    {
        $matches = array();
        $hasMatch = preg_match(self::JSON_RESULT_ERROR_PATTERN, $result ?? '', $matches);
        if ($hasMatch === 1) {
            // note: $matches[0] is the complete string that matched
            //       $matches[1] is just the error message part
            $message = $matches[1];
            $message = str_replace('\"', '"', $message);
            $message = str_replace('\n', "\n", $message);
             
            $code    = ErrorHandlerInterface::REDCAP_API_ERROR;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
    }
    
    
    protected function processOverrideArgument($override)
    {
        if ($override == null) {
            $override = false;
        } else {
            if (gettype($override) !== 'boolean') {
                $message = 'Invalid type for override. It should be a boolean (true or false),'
                    .' but has type: '.gettype($override).'.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        
        if ($override === true) {
            $override = 1;
        } else {
            $override = 0;
        }
        
        return $override;
    }
    
    protected function processOverwriteBehaviorArgument($overwriteBehavior)
    {
        if (!isset($overwriteBehavior)) {
            $overwriteBehavior = 'normal';
        } elseif ($overwriteBehavior !== 'normal' && $overwriteBehavior !== 'overwrite') {
            $message = 'Invalid value "'.$overwriteBehavior.'" specified for overwriteBehavior.'.
                " Valid values are 'normal' and 'overwrite'.";
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        return $overwriteBehavior;
    }
    
    protected function processRawOrLabelArgument($rawOrLabel)
    {
        if (!isset($rawOrLabel)) {
            $rawOrLabel = 'raw';
        } else {
            if ($rawOrLabel !== 'raw' && $rawOrLabel !== 'label') {
                $message =   'Invalid value "'.$rawOrLabel.'" specified for rawOrLabel.'
                    ." Valid values are 'raw' and 'label'.";
                    $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $rawOrLabel;
    }
    
    
    protected function processRawOrLabelHeadersArgument($rawOrLabelHeaders)
    {
        if (!isset($rawOrLabelHeaders)) {
            $rawOrLabelHeaders = 'raw';
        } else {
            if ($rawOrLabelHeaders !== 'raw' && $rawOrLabelHeaders !== 'label') {
                $message = 'Invalid value "'.$rawOrLabelHeaders.'" specified for rawOrLabelHeaders.'
                    ." Valid values are 'raw' and 'label'.";
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $rawOrLabelHeaders;
    }
    
    
    protected function processRecordIdArgument($recordId, $required = true)
    {
        if (!isset($recordId)) {
            if ($required) {
                $message = 'No record ID specified.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        } elseif (!is_string($recordId) && !is_int($recordId)) {
            $message = 'The record ID has type "'.gettype($recordId)
                .'", but it should be a string or integer.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        }  // @codeCoverageIgnore
        
        return $recordId;
    }
    
    protected function processRecordIdsArgument($recordIds)
    {
        if (!isset($recordIds)) {
            $recordIds = array();
        } else {
            if (!is_array($recordIds)) {
                $message = 'The record IDs argument has type "'.gettype($recordIds)
                    .'"; it should be an array.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } else { // @codeCoverageIgnore
                foreach ($recordIds as $recordId) {
                    $type = gettype($recordId);
                    if (strcmp($type, 'integer') !== 0 && strcmp($type, 'string') !== 0) {
                        $message = 'A record ID with type "'.$type.'" was found.'
                            .' Record IDs should be integers or strings.';
                            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                        $this->errorHandler->throwException($message, $code);
                    } // @codeCoverageIgnore
                }
            }
        }
        return $recordIds;
    }
    

    
    protected function processRepeatInstanceArgument($repeatInstance)
    {
        if (!isset($repeatInstance)) {
            ; // Might be OK
        } elseif (!is_int($repeatInstance)) {
            $message = 'The repeat instance has type "'.gettype($repeatInstance)
                .'", but it should be an integer.';
            $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        return $repeatInstance;
    }
    

    protected function processReportIdArgument($reportId)
    {
        if (!isset($reportId)) {
            $message = 'No report ID specified for export.';
            $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore

        if (is_string($reportId)) {
            if (!preg_match('/^[0-9]+$/', $reportId)) {
                $message = 'Report ID "'.$reportId.'" is non-numeric string.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        } elseif (is_int($reportId)) {
            if ($reportId < 0) {
                $message = 'Report ID "'.$reportId.'" is a negative integer.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        } else {
            $message = 'The report ID has type "'.gettype($reportId)
                .'", but it should be an integer or a (numeric) string.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        return $reportId;
    }
    

    protected function processReturnContentArgument($returnContent, $forceAutoNumber)
    {
        if (!isset($returnContent)) {
            $returnContent = 'count';
        } elseif ($returnContent === 'auto_ids') {
            if ($forceAutoNumber !== true) {
                $message = "'auto_ids' specified for returnContent,"
                    ." but forceAutoNumber was not set to true;"
                    ." 'auto_ids' can only be used when forceAutoNumber is set to true.";
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        } elseif ($returnContent !== 'count' && $returnContent !== 'ids') {
            $message = "Invalid value '".$returnContent."' specified for returnContent.".
                    " Valid values are 'count', 'ids' and 'auto_ids'.";
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
    
        return $returnContent;
    }
    
    protected function processReturnMetadataOnlyArgument($returnMetadataOnly)
    {
        if ($returnMetadataOnly== null) {
            $returnMetadataOnly= false;
        } else {
            if (gettype($returnMetadataOnly) !== 'boolean') {
                $message = 'Invalid type for returnMetadataOnly.'
                    .' It should be a boolean (true or false),'
                    .' but has type: '.gettype($returnMetadataOnly).'.';
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore
        }
        return $returnMetadataOnly;
    }
    
    protected function processRoleIdArgument($roleId)
    {
        if (!isset($roleId)) {
            $roleId = '';
        } elseif (!is_int($roleId)) {
            $message = 'The role ID has type "'.gettype($roleId)
                .'", but it should be an integer.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        }
        
        return $roleId;
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
    
    
    protected function processTypeArgument($type)
    {
        if (!isset($type)) {
            $type = 'flat';
        }
        $type = strtolower(trim($type));
        
        if (strcmp($type, 'flat') !== 0 && strcmp($type, 'eav') !== 0) {
            $message = "Invalid type '".$type."' specified. Type should be either 'flat' or 'eav'";
            $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        return $type;
    }


    protected function processUserArgument($username)
    {
        if ($username) {
            if (!is_string($username)) {
                $message = 'The user argument has invalid type "'.gettype($username)
                    .'"; it should be a string.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            }
        }
        return $username;
    }

    protected function processUsersArgument($users)
    {
        if (isset($users)) {
            if (!is_array($users)) {
                $message = 'The users argument has invalid type "' . gettype($users)
                    . '": it should be an array of strings that represent usernames.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } else {
                foreach ($users as $user) {
                    if (!is_string($user)) {
                        $message = 'The users argument contains an element of type "' . gettype($user)
                            . '": it should have type string.';
                        $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                        $this->errorHandler->throwException($message, $code);
                    }
                }
                $users = array_unique($users); // remove duplicate user roles (if any)
            }
        }

        return $users;
    }

    /* CHECK !!!!!!!!!!!!!!!!!!!!!!!!! */
    protected function processUserRolesArgument($userRoles)
    {
        if (isset($userRoles)) {
            if (!is_array($userRoles)) {
                $message = 'The user roles argument has invalid type "' . gettype($userRoles)
                    . '": it should be an array of strings that represent unique user role names.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
            } else {
                foreach ($userRoles as $userRole) {
                    if (!is_string($userRole)) {
                        $message = 'The user roles argument contains an element of type "' . gettype($userRole)
                            . '": it should have type string.';
                        $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                        $this->errorHandler->throwException($message, $code);
                    }
                }
                $userRoles = array_unique($userRoles); // remove duplicate user roles (if any)
            }
        }

        return $userRoles;
    }
}

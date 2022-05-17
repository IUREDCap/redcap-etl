<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

/**
 * REDCap Project class for REDCap-ETL that extends PHPCap's RedCapProject class.
 * This class provides caching of results from REDCap and extended functionality.
 */
class EtlRedCapProject extends \IU\PHPCap\RedCapProject
{
    /** @var string the name of the application */
    private $app;
    
    private $metadata     = null;
    private $projectInfo  = null;
    private $primaryKey;
    private $fieldNames;
    
    private $fieldTypeMap = null;  // map of field name to field type
    
    /**
     * Gets the project metadata, and uses caching so that
     * after the first retrieval of metadata from REDCap,
     * the cached values will be used to improve performance.
     *
     * @return array a map from name to value of the project's metadata.
     */
    public function getMetadata()
    {
        if (!isset($this->metadata)) {
            $this->metadata = $this->exportMetadata();
        }
        return $this->metadata;
    }

    public function getProjectInfo()
    {
        if (!isset($this->projectInfo)) {
            $this->projectInfo = $this->exportProjectInfo();
        }
        return $this->projectInfo;
    }

    /**
     * Gets a map from REDCap field name to REDCap field type.
     *
     * @return array a map from REDCap field name to REDCap field type.
     */
    public function getFieldTypeMap()
    {
        if (!isset($this->fieldTypeMap)) {
            $this->fieldTypeMap = array();
            $fields = $this->getMetadata();
            foreach ($fields as $field) {
                $this->fieldTypeMap[$field['field_name']] = $field['field_type'];
            }
        }
        return $this->fieldTypeMap;
    }
    
    /**
     * Gets the REDCap field type of the field with the specified field name.
     *
     * @return string the REDCap field type, e.g., "calc", "dropdown",
     *     radio", "text".
     */
    public function getFieldType($fieldName)
    {
        $fieldType = '';
        $fieldTypes = $this->getFieldTypeMap();
        if (array_key_exists($fieldName, $fieldTypes)) {
            $fieldType = $fieldTypes[$fieldName];
        }
        return $fieldType;
    }
    
    /**
     * Indicates if the project is longitudinal.
     *
     * @return boolean true if the project is longitudinal and false otherwise.
     */
    public function isLongitudinal()
    {
        $isLongitudinal = false;
        $projectInfo = $this->getProjectInfo(); # call getter method, since lazy loading is used
        $isLongitudinalValue = $projectInfo['is_longitudinal'];
        if ($isLongitudinalValue === 1) {
            $isLongitudinal = true;
        }
        return $isLongitudinal;
    }

    /**
     * Gets the name for the primary key (record ID) field
     * of the project.
     *
     * @return string the name of the primary key (record ID).
     */
    public function getPrimaryKey()
    {
        if (!isset($this->primaryKey)) {
            $this->primaryKey = $this->getRecordIdFieldName();
        }
        return $this->primaryKey;
    }


    /**
     * Gets information on multiple choice options in a project.
     *
     * @return array a map of field names to a map of values to labels
     *     for that field name.
     *
     *     array($fieldName1 => array($value1 => $label1, ...), ...)
     */
    public function getLookupChoices()
    {

        $results = array();

        // Get all metadata
        $error = 'Unable to retrieve metadata while getting lookup choices';
        $fields = $this->getMetadata();
     
        // Foreach field
        foreach ($fields as $field) {
            switch ($field['field_type']) {
                // If it's a radio, dropdown, or checkbox field
                case 'radio':
                case 'dropdown':
                case 'checkbox':
                      // Get the choices
                      $choicesString = $field['select_choices_or_calculations'];
                      $choices = array_map('trim', explode("|", $choicesString));

                      $fieldResults = array();

                      // Foreach choice
                    foreach ($choices as $choice) {
                        if ($choice === "") {
                             continue;
                        } elseif (strpos($choice, ',') === false) {
                            $message = 'Field "'.($field['field_name']).'" in form "'.($field['form_name'])
                                .'" does not have a label for choice value "'.$choice.'".';
                            $code = EtlException::INPUT_ERROR;
                            throw new EtlException($message, $code);
                        }

                        // Get the category and label
                        list ($category, $label) =
                          array_map('trim', explode(",", $choice, 2));

                        // Add them to the results for this field
                        $fieldResults[$category] = $label;
                    }

                      // Add this field to the overall results
                      $results[$field['field_name']] = $fieldResults;

                    break;

                default:
                    break;
            }  // end switch
        }  // end foreach

        return $results;
    }

    /**
     * Gets a map of the field names of the project.
     *
     * @return array map where the keys are the export field names
     *     from REDCap and the values are all 1.
     */
    public function getFieldNames()
    {
        if (empty($this->fieldNames)) {
            $this->fieldNames = array();

            $fields = $this->exportFieldNames();
            foreach ($fields as $field) {
                $this->fieldNames[$field['export_field_name']] = 1;
            }

            $metadata = $this->getMetadata();
            foreach ($metadata as $field) {
                if ($field['field_type'] === 'file') {
                    $this->fieldNames[$field['field_name']] = 1;
                }
            };
        }

        return $this->fieldNames;
    }


    /**
     * Get records for the specified record IDs, and return them
     * as a map from record ID to the records for that records ID.
     *
     * @param array $recordIds an array of REDCap record IDs
     *    for which records should be retrieved and returned
     *    in the batch of records.
     *
     * @return array a map from record IDs to the records for each
     *    record ID. A record ID can have multiple records because
     *    of multiple and/or repeatable events and repeatable forms.
     */
    public function getRecordBatch($recordIds, $filterLogic = null)
    {
        $primaryKey = $this->getPrimaryKey();
        $batch = array();

        if (empty($filterLogic)) {
            $results = $this->exportRecordsAp(
                ['recordIds' => $recordIds,
                'exportSurveyFields' => true,
                'exportDataAccessGroups' => true]
            );
        } else {
            $results = $this->exportRecordsAp(
                ['recordIds' => $recordIds,
                'exportSurveyFields' => true,
                'exportDataAccessGroups' => true,
                'filterLogic' => $filterLogic]
            );
        }

        // Set up $batch results
        foreach ($results as $result) {
            $recordId = $result[$primaryKey];

            // If no results yet for this record, create array
            if (!array_key_exists($recordId, $batch)) {
                $batch[$recordId] = array();
            }
            array_push($batch[$recordId], $result);
        }
        return($batch);
    }

    public function getApp()
    {
        return $this->app;
    }

    public function setApp($app)
    {
        $this->app = $app;
    }
}

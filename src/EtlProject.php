<?php

namespace IU\RedCapEtl;

/**
 * REDCap Project class for REDCap ETL that extends PHPCap's RedCapProject class.
 */
class EtlProject extends \IU\PHPCap\RedCapProject
{
    private $app;
    private $metadata;
    private $primaryKey;
    private $fieldNames;
    
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
     * @return array a map of field names to a map of categories to labels
     *     for that field name.
     *
     * $results = array($fieldName1 => array($category1 => $label1, ...), ...)
     */
    public function getLookupChoices()
    {

        $results = array();

        // Get all metadata
        $error = 'Unable to retrieve metadata while getting lookup choices';
        $fields = $this->getMetadata();
     
        // Foreach field
        foreach ($fields as $field) {
            // Check the type of field
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

    public function getFieldNames()
    {
        if (empty($this->fieldNames)) {
            $this->fieldNames = array();

            $fields = $this->exportFieldNames();
            foreach ($fields as $field) {
                $this->fieldNames[$field['export_field_name']] = 1;
            }
        }

        ###print_r($this->fieldNames);
        return $this->fieldNames;
    }


    /**
     * Get records for the specified record IDs, and return them
     * as a map from record ID to the records for that records ID.
     *
     * map($recordId => $records).
     */
    public function getRecordBatch($recordIds)
    {
        $primaryKey = $this->getPrimaryKey();
        $batch = array();

        $results = $this->exportRecordsAp(
            ['recordIds' => $recordIds,
            'exportDataAccessGroups' => true]
        );

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

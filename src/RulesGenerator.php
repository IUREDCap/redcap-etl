<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\FieldType;

/**
 * Rules generator class for automatically generating
 * transformation rules for a REDCap project.
 */
class RulesGenerator
{
    const REDCAP_XML_NAMESPACE = 'https://projectredcap.org';

    const FORM_COMPLETE_SUFFIX = '_complete';

    const DEFAULT_VARCHAR_SIZE = 255;
    
    private $isLongitudinal;
    private $instruments;
    private $recordId;
    private $metadata;
    private $projectXmlDom;
    private $eventMappings;

    private $addFormCompleteFields;
    private $addDagFields;
    private $addFileFields;

    /**
     * Generates transformation rules for the
     * specified data project.
     *
     * @param EtlRedCapProject $dataProject the REDCap project that
     *     contains the data for which rules are being generated.
     *
     * @param boolean $addFormCompleteFields indicates if form complete fields
     *     should be added to the rules for each table.
     *
     * @param boolean $addDagFields indicates if DAG (Data Access Group) fields
     *     should be added to the rules for each table.
     *
     * @param boolean $addFileFields indicates if file fields
     *     should be added to the rules for each table.
     *
     * @return string the auto-generated transformation rules.
     */
    public function generate(
        $dataProject,
        $addFormCompleteFields = false,
        $addDagFields = false,
        $addFileFields = false
    ) {
        $this->addFormCompleteFields = $addFormCompleteFields;
        $this->addDagFields          = $addDagFields;
        $this->addFileFields         = $addFileFields;

        $rules = '';

        #----------------------------------------------------
        # Get project information and metadata
        #----------------------------------------------------
        $projectInfo = $dataProject->exportProjectInfo();
        // echo "{ \"projectInfo\": ";
        // echo json_encode($projectInfo, JSON_PRETTY_PRINT);
        $this->isLongitudinal = $projectInfo['is_longitudinal'];
        $this->instruments = $dataProject->exportInstruments();
        // echo ", \"instruments\": ";
        // echo json_encode($this->instruments, JSON_PRETTY_PRINT);
        $this->metadata    = $dataProject->exportMetadata();
        // echo ", \"metadata\": ";
        // echo json_encode($this->metadata, JSON_PRETTY_PRINT);
        // echo "}";
        // echo "\n";
        // echo "\n";
        $projectXml  = $dataProject->exportProjectXml($metadataOnly = true);
        // echo "\n";
        // echo "\n";
        // echo $projectXml;
        // echo "\n";
        // echo "\n";
        $this->projectXmlDom = new \DomDocument();
        $this->projectXmlDom->loadXML($projectXml);

        $this->recordId = $this->metadata[0]['field_name'];

        if ($this->isLongitudinal) {
            $this->eventMappings = $dataProject->exportInstrumentEventMappings();
            // echo "\n";
            // echo "\n \"instrumentEventMappings\": ";
            // echo json_encode($this->eventMappings, JSON_PRETTY_PRINT);
            // echo "\n";
            // echo "\n";
            $rules = $this->generateLongitudinalProjectRules();
        } else {
            $rules = $this->generateClassicProjectRules();
        }

        // print_r($rules);
        return $rules;
    }
    
    protected function generateClassicProjectRules()
    {
        $rules = '';
        
        $rootForm = $this->getRootInstrument();
        $repeatingForms = $this->getRepeatingInstruments();
        
        if (in_array($rootForm, $repeatingForms)) {
            #--------------------------------------------------------
            # Generate record_id only table (with DAG if applicable)
            # Note: record_id added automatically
            #--------------------------------------------------------
            $rootTable = $rootForm . '_root';
            $primaryKey = strtolower($rootTable) . '_id';
            $rules .= "TABLE,{$rootTable},{$primaryKey},".RulesParser::ROOT."\n";
            if ($this->addDagFields) {
                $type = FieldType::VARCHAR . '(' . self::DEFAULT_VARCHAR_SIZE . ')';
                $rules .= "FIELD,".RedCapEtl::COLUMN_DAG.",{$type}\n";
            }
            $rules .= "\n";
        } else {
            $rootTable = $rootForm;
        }
        
        foreach ($this->instruments as $formName => $formLabel) {
            $primaryKey = strtolower($formName) . '_id';
            
            if (in_array($formName, $repeatingForms)) {
                $rules .= "TABLE,{$formName},{$rootTable},".RulesParser::REPEATING_INSTRUMENTS."\n";
            } else {
                $rules .= "TABLE,{$formName},{$primaryKey},".RulesParser::ROOT."\n";
            }
    
            $rules .= $this->generateFields($formName);
            $rules .= "\n";
        }
        return $rules;
    }
    
    protected function generateLongitudinalProjectRules()
    {
        $rules = '';
        
        #-------------------------------------------------------------
        # Create table with only record ID - it's possible that the
        # same record ID is in multiple arms
        #-------------------------------------------------------------
        $rootTable = 'root';
        $rules .= 'TABLE,'.$rootTable.',root_id,'.RulesParser::ROOT."\n";
        if ($this->addDagFields) {
            $type = FieldType::VARCHAR . '(' . self::DEFAULT_VARCHAR_SIZE . ')';
            $rules .= "FIELD,".RedCapEtl::COLUMN_DAG.",{$type}\n";
        }
        $rules .= "\n";

        foreach ($this->instruments as $formName => $formLabel) {
            $events = $this->getEvents($formName);

            $primaryKey = strtolower($formName) . '_id';
            
            if ($this->isInEvent($formName)) {
                $rules .= "TABLE,".$formName.",$rootTable,".RulesParser::EVENTS."\n";
                $rules .= $this->generateFields($formName);
                $rules .= "\n";
            }
            
            if ($this->isInRepeatingEvent($formName)) {
                $rules .= "TABLE,{$formName}_repeating_events,{$rootTable},".RulesParser::REPEATING_EVENTS."\n";
                $rules .= $this->generateFields($formName);
                $rules .= "\n";
            }
                        
            if ($this->isRepeatingInstrument($formName)) {
                $rules .= "TABLE,{$formName}_repeating_instruments,{$rootTable},"
                    .RulesParser::REPEATING_INSTRUMENTS."\n";
                $rules .= $this->generateFields($formName);
                $rules .= "\n";
            }
        }
        return $rules;
    }
    
    protected function generateFields($formName)
    {
        $fields = '';

        if ($this->addDagFields) {
            $field['field_name'] = RedCapEtl::COLUMN_DAG;
            $field['field_type'] = 'text';
            $field['text_validation_type_or_show_slider_number'] = '';
            $rule = $this->getFieldRule($field);
            $fields .= $rule;
        }

        foreach ($this->metadata as $field) {
            if ($field['form_name'] == $formName) {
                $rule = $this->getFieldRule($field);
                if (isset($rule)) {
                    $fields .= $rule;
                }
            }
        }

        if ($this->addFormCompleteFields) {
            $field['field_name'] = $formName . self::FORM_COMPLETE_SUFFIX;
            $field['field_type'] = 'dropdown';
            $field['text_validation_type_or_show_slider_number'] = '';
            $rule = $this->getFieldRule($field);
            $fields .= $rule;
        }

        return $fields;
    }


    /**
     * Gets the tranformation rule for the specified field.
     *
     * @param array $field REDCap metada for a field.
     *
     * @return Rule transformation rule for the specified field.
     */
    protected function getFieldRule($field)
    {
        $rule = null;
        $type = FieldType::STRING;
                
        $validationType = $field['text_validation_type_or_show_slider_number'];
        $fieldName      = $field['field_name'];
        $fieldType      = $field['field_type'];

        #----------------------------------------------
        # Set the rule type
        #----------------------------------------------
        if ($fieldName === RedCapEtl::COLUMN_DAG) {
            # DAG (Data Access Group) column
            $type = FieldType::VARCHAR . '(' . self::DEFAULT_VARCHAR_SIZE . ')';
        } elseif ($fieldType === 'checkbox') {
            $type = FieldType::CHECKBOX;
        } elseif ($fieldType === 'file') {
            $type = FieldType::VARCHAR . '(' . self::DEFAULT_VARCHAR_SIZE . ')';
        } elseif ($validationType === 'integer') {
            # The number be too large for the database int type,
            # so use a varchar here
            $type = FieldType::VARCHAR . '(' . self::DEFAULT_VARCHAR_SIZE . ')';
        } elseif (substr($validationType, 0, 6) === 'number') {
            # starts with 'number'
            $type = FieldType::FLOAT;
            #$type = FieldType::VARCHAR . '(' . self::DEFAULT_VARCHAR_SIZE . ')';
        } elseif ($fieldType === 'dropdown' || $fieldType === 'radio') {
            # values for multiple choice can have letters
            $type = FieldType::VARCHAR . '(' . self::DEFAULT_VARCHAR_SIZE . ')';
        } elseif (substr($validationType, 0, 5) === 'date_') {
            # starts with 'date_'
            $type = FieldType::DATE;
        } elseif (substr($validationType, 0, 9) === 'datetime_') {
            # starts with 'datetime_'
            $type = FieldType::DATETIME;
        } else {
            $type = FieldType::STRING;
        }

        #-----------------------------------------
        # Generate the rule
        #-----------------------------------------
        if ($fieldName === $this->recordId) {
            # REDCap record ID field
            ; // Don't do anything; this field will be generated automatically
        } elseif ($fieldType === 'descriptive') {
            ; // Don't do anything
        } elseif ($fieldType === 'file' && !($this->addFileFields)) {
            ; // Don't do anything
        } else {
            $rule .= "FIELD,".$fieldName.",".$type."\n";
        }
        
        return $rule;
    }
    
    /**
     * Gets the "root instrument", i.e., the one that contains the
     * record ID.
     */
    protected function getRootInstrument()
    {
        return $this->metadata[0]['form_name'];
    }

    protected function getRepeatingInstruments()
    {
        $repeatingInstruments = array();

        $repeatingInstrumentNodes = $this->projectXmlDom->getElementsByTagNameNS(
            self::REDCAP_XML_NAMESPACE,
            'RepeatingInstrument'
        );
    
        foreach ($repeatingInstrumentNodes as $instrumentNode) {
            $instrumentName = $instrumentNode->getAttribute('redcap:RepeatInstrument');
            
            if ($this->isLongitudinal) {
                $eventName      = $instrumentNode->getAttribute('redcap:UniqueEventName');
                $entry = array();
                $entry['form'] = $instrumentName;
                $entry['unique_event_name'] = $eventName;
                array_push($repeatingInstruments, $entry);
            } else {
                array_push($repeatingInstruments, $instrumentName);
            }
        }
        return $repeatingInstruments;
    }

    /**
     * Gets the repeating events.
     *
     * @return arrray list or unique event names for the repeating events.
     */
    protected function getRepeatingEvents()
    {
        $repeatingEvents = array();
        $repeatingEventNodes = $this->projectXmlDom->getElementsByTagNameNS(
            self::REDCAP_XML_NAMESPACE,
            'RepeatingEvent'
        );

        foreach ($repeatingEventNodes as $eventNode) {
            $eventName = $eventNode->getAttribute('redcap:UniqueEventName');
            array_push($repeatingEvents, $eventName);
        }
        return $repeatingEvents;
    }

    /**
     * Gets the events that contain the specified form.
     *
     * @param string the name of the form for which to get the events.
     *
     * @return array unique event names of events containing specified form.
     */
    protected function getEvents($form)
    {
        $events = array();
        foreach ($this->eventMappings as $mapping) {
            if ($mapping['form'] === $form) {
                array_push($events, $mapping['unique_event_name']);
            }
        }
        return $events;
    }


    /**
     * Indicates if the form is in a non-repeating event as a
     * non-repeating instrument.
     *
     * @return boolean true if the specified form is a non-repeating
     *     form in a non-repeating event, and false otherwise
     */
    protected function isInEvent($form)
    {
        $isInEvent = false;
        $events = $this->getEvents($form);
        $repeatingEvents = $this->getRepeatingEvents();
        $repeatingInstruments = $this->getRepeatingInstruments();

        $nonRepeatingEvents = array_diff($events, $repeatingEvents);

        foreach ($nonRepeatingEvents as $event) {
            $isRepeatingInstrument = false;
            foreach ($repeatingInstruments as $repeatingInstrument) {
                if ($repeatingInstrument['form'] === $form
                    && $repeatingInstrument['unique_event_name'] === $event) {
                    $isRepeatingInstrument = true;
                    break;
                }
            }
            if (!$isRepeatingInstrument) {
                $isInEvent = true;
                break;
            }
        }
        return $isInEvent;
    }

    /**
     * Indicates if the specified form is in a repeating event.
     *
     * @return boolean true if the specified form is in a repeating
     *     event, and false otherwise.
     */
    protected function isInRepeatingEvent($form)
    {
        $isInRepeatingEvent = false;
        $events = $this->getEvents($form);
        
        $repeatingEvents = $this->getRepeatingEvents();
        
        foreach ($events as $event) {
            if (in_array($event, $repeatingEvents)) {
                $isInRepeatingEvent = true;
                break;
            }
        }
        return $isInRepeatingEvent;
    }

    protected function isRepeatingInstrument($form)
    {
        $isRepeatingInstrument = false;
        
        $repeatingInstruments = $this->getRepeatingInstruments();
        if ($this->isLongitudinal) {
            $repeatingInstruments = array_column($repeatingInstruments, 'form');
        }
        
        if (in_array($form, $repeatingInstruments)) {
            $isRepeatingInstrument = true;
        }
        return $isRepeatingInstrument;
    }
}

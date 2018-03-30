<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\FieldType;

/**
 * Rules generated class for automatically generating
 * transformation rules for a REDCap project.
 */
class RulesGenerator
{
    const REDCAP_XML_NAMESPACE = 'https://projectredcap.org';
    
    /**
     * Generates transformation rules for the
     * specified data project.
     *
     * @return string the auto-generated transformation rules.
     */
    public function generate($dataProject)
    {
        $rules = '';

        #----------------------------------------------------
        # Get project information and metadata
        #----------------------------------------------------
        $projectInfo = $dataProject->exportProjectInfo();
        $isLongitudinal = $projectInfo['is_longitudinal'];
                
        $instruments = $dataProject->exportInstruments();
        $metadata    = $dataProject->exportMetadata();
        
        $projectXml  = $dataProject->exportProjectXml($metadataOnly = true);
        $projectXmlDom = new \DomDocument();
        $projectXmlDom->loadXML($projectXml);

        $recordId = $metadata[0]['field_name'];


        $rootInstrument = $this->getRootInstrument($metadata);
        print "ROOT FORM: {$rootInstrument}\n";
        
        if ($isLongitudinal) {
            $rules = $this->generateLongitudinalProjectRules($instruments, $metadata);
        } else {
            $rules = $this->generateClassicProjectRules($instruments, $metadata);
        }
        return $rules;
    }
    
    public function generateClassicProjectRules($instruments, $metadata)
    {
        $rules = '';
                       
        foreach ($instruments as $formName => $formLabel) {
            $primaryKey = strtolower($formName) . '_id';
            $rules .= "TABLE,".$formName.",$primaryKey,".RulesParser::ROOT."\n";
    
            foreach ($metadata as $field) {
                if ($field['form_name'] == $formName) {
                    $rule = $this->getFieldRule($field);
                    if (isset($rule)) {
                        $rules .= $rule;
                    }
                }
            }
            $rules .= "\n";
        }
        return $rules;
    }
    
    public function generateLongitudinalProjectRules($instruments, $metadata)
    {
        $rules = '';
        
        foreach ($instruments as $formName => $formLabel) {
            $primaryKey = strtolower($formName) . '_id';
            $rules .= "TABLE,".$formName.",$primaryKey,".RulesParser::ROOT."\n";
    
            foreach ($metadata as $field) {
                if ($field['form_name'] == $formName) {
                    $rule = $this->getFieldRule($field);
                    if (isset($rule)) {
                        $rules .= $rule;
                    }
                }
            }
            $rules .= "\n";
        }
        return $rules;
    }

    
    public function getFieldRule($field)
    {
        $rule = null;
        $type = FieldType::STRING;
                
        $validationType = $field['text_validation_type_or_show_slider_number'];
        $fieldType      = $field['field_type'];

        if ($fieldType === FieldType::CHECKBOX) {
            $type = FieldType::CHECKBOX;
        } elseif ($validationType === FieldType::INT) {
            # The value may be too large for the database int type,
            # so use a string here
            $type = FieldType::STRING;
        } elseif ($fieldType === 'dropdown' || $fieldType === 'radio') {
            $type = FieldType::INT;
        } elseif (substr($validationType, 0, 5) === 'date_') {
            # starts with 'date_'
            $type = FieldType::DATE;
        } elseif (substr($validationType, 0, 9) === 'datetime_') {
            # starts with 'datetime_'
            $type = FieldType::DATETIME;
        }
               
        if ($fieldType === 'descriptive' || $fieldType === 'file') {
            ; // Don't do anything
        } else {
            $rule .= "FIELD,".$field['field_name'].",".$type."\n";
        }
        
        return $rule;
    }
    
    public function getRootInstrument($metadata)
    {
        return $metadata[0]['form_name'];
    }

    public function getRepeatingInstruments($projectXmlDom)
    {
        $repeatingInstruments = array();

        $repeatingInstrumentNodes = $projectXmlDom->getElementsByTagNameNS(
            self::REDCAP_XML_NAMESPACE,
            'RepeatingInstrument'
        );
    
        foreach ($repeatingInstrumentNodes as $instrumentNode) {
            $instrumentName = $instrumentNode->getAttribute('redcap:RepeatInstrument');
            array_push($repeatingInstruments, $instrumentName);
        }
        return $repeatingInstruments;
    }
}

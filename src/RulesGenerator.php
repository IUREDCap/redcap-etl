<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\FieldType;

/**
 * Rules generated class for automatically generating
 * transformation rules for a REDCap project.
 */
class RulesGenerator
{
    /**
     * Generates transformation rules for the
     * specified data project.
     *
     * @return string the auto-generated transformation rules.
     */
    public function generate($dataProject)
    {
        $rules = '';

        $projectInfo = $dataProject->exportProjectInfo();
        $instruments = $dataProject->exportInstruments();
        $metadata    = $dataProject->exportMetadata();

        $recordId = $metadata[0]['field_name'];

        foreach ($instruments as $formName => $formLabel) {
            $primaryKey = strtolower($formName) . '_id';
            $rules .= "TABLE,".$formName.",$primaryKey,".RulesParser::ROOT."\n";

            foreach ($metadata as $field) {
                if ($field['form_name'] == $formName) {
                    $type = FieldType::STRING;
                
                    $validationType = $field['text_validation_type_or_show_slider_number'];
                    $fieldType      = $field['field_type'];

                    #print "{$validationType}\n";

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
                        $rules .= "FIELD,".$field['field_name'].",".$type."\n";
                    }
                }
            }
            $rules .= "\n";
        }
        return $rules;
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\FieldTypeSpecifier;
use IU\REDCapETL\Schema\Row;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Table;

/**
 * Table class for storing metadata for REDCap project
 * from which data is extracted.
 */
class MetadataTable extends Table
{
    const DEFAULT_NAME       = 'redcap_metadata';
    
    const FIELD_PRIMARY_ID       = 'redcap_data_source';
    const FIELD_FIELD_NAME       = 'field_name';
    const FIELD_FORM_NAME        = 'form_name';
    const FIELD_FIELD_TYPE       = 'field_type';
    const FIELD_IDENTIFIER       = 'identifier';

    /*
    [field_name] => weight_pounds
    [form_name] => weight
    [section_header] =>
    [field_type] => text
    [field_label] => Weight (lbs.)
    [select_choices_or_calculations] =>
    [field_note] =>
    [text_validation_type_or_show_slider_number] => number
    [text_validation_min] =>
    [text_validation_max] =>
    [identifier] =>
    [branching_logic] =>
    [required_field] => y
    [custom_alignment] =>
    [question_number] =>
    [matrix_group_name] =>
    [matrix_ranking] =>
    [field_annotation] =>
    */

    public function __construct($tablePrefix = '', $name = self::DEFAULT_NAME)
    {
        $fieldTypePrimary = new FieldTypeSpecifier(FieldType::AUTO_INCREMENT);

        parent::__construct(
            $tablePrefix . $name,
            self::FIELD_PRIMARY_ID,
            $fieldTypePrimary,
            array(RowsType::ROOT),
            array()
        );

        #-----------------------------------------------
        # Create and add fields for the lookup table
        #-----------------------------------------------
        $fieldFieldName  = new Field(self::FIELD_FIELD_NAME, FieldType::VARCHAR, 255);
        $fieldFormName   = new Field(self::FIELD_FORM_NAME, FieldType::VARCHAR, 255);
        $fieldFieldType  = new Field(self::FIELD_FIELD_TYPE, FieldType::VARCHAR, 255);
        $fieldIdentifier = new Field(self::FIELD_IDENTIFIER, FieldType::VARCHAR, 1);

        $this->addField($fieldFieldName);
        $this->addField($fieldFormName);
        $this->addField($fieldFieldType);
        $this->addField($fieldIdentifier);
    }
    
        
    public function createDataRow($taskId, $metadata)
    {
        $row = new Row($this);
        $row->addValue(self::FIELD_PRIMARY_ID, $taskId);
        $row->addValue(self::FIELD_FIELD_NAME, $metadata[self::FIELD_FIELD_NAME]);
        $row->addValue(self::FIELD_FORM_NAME, $metadata[self::FIELD_FORM_NAME]);
        $row->addValue(self::FIELD_FIELD_TYPE, $metadata[self::FIELD_FIELD_TYPE]);
        $row->addValue(self::FIELD_IDENTIFIER, $metadata[self::FIELD_IDENTIFIER]);

        return $row;
    }
}

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
 * Table class for storing project information for REDCap project
 * from which data is extracted.
 */
class ProjectInfoTable extends Table
{
    const DEFAULT_NAME       = 'redcap_project_info';
    
    const FIELD_PRIMARY_ID       = 'redcap_data_source';
    const FIELD_API_URL          = 'api_url';
    const FIELD_PROJECT_ID       = 'project_id';
    const FIELD_PROJECT_TITLE    = 'project_title';
    const FIELD_PROJECT_LANGUAGE = 'project_language';

    public function __construct($name = self::DEFAULT_NAME)
    {
        $fieldTypePrimary = new FieldTypeSpecifier(FieldType::INT);

        parent::__construct(
            $name,
            self::FIELD_PRIMARY_ID,
            $fieldTypePrimary,
            array(RowsType::ROOT),
            array()
        );

        #-----------------------------------------------
        # Create and add fields for the lookup table
        #-----------------------------------------------
        $fieldApiUrl          = new Field(self::FIELD_API_URL, FieldType::VARCHAR, 255);
        $fieldProjectId       = new Field(self::FIELD_PROJECT_ID, FieldType::INT);
        $fieldProjectTitle    = new Field(self::FIELD_PROJECT_TITLE, FieldType::VARCHAR, 255);
        $fieldProjectLanguage = new Field(self::FIELD_PROJECT_LANGUAGE, FieldType::VARCHAR, 255);
        
        $this->addField($fieldApiUrl);
        $this->addField($fieldProjectId);
        $this->addField($fieldProjectTitle);
        $this->addField($fieldProjectLanguage);
    }
    
        
    public function createDataRow($taskId, $apiUrl, $projectInfo)
    {
        $row = new Row($this);
        $row->addValue(self::FIELD_PRIMARY_ID, $taskId);
        $row->addValue(self::FIELD_API_URL, $apiUrl);
        $row->addValue(self::FIELD_PROJECT_ID, $projectInfo[self::FIELD_PROJECT_ID]);
        $row->addValue(self::FIELD_PROJECT_TITLE, $projectInfo[self::FIELD_PROJECT_TITLE]);
        $row->addValue(self::FIELD_PROJECT_LANGUAGE, $projectInfo[self::FIELD_PROJECT_LANGUAGE]);

        return $row;
    }

    public function merge($table, $mergeData = true)
    {
        if ($this->getName() !== $table->getName()) {
            $message = "Project Info table names \"{$this->getName()}\" and \"{$table->getName()}\" do not match.";
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        $mergedProjectInfo = parent::merge($table, $mergeData);

        return $mergedProjectInfo;
    }
}

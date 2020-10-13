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
    
    const FIELD_PRIMARY_ID       = 'redcap_project';
    const FIELD_API_URL          = 'api_url';
    const FIELD_PROJECT_ID       = 'project_id';
    const FIELD_PROJECT_NAME     = 'project_name';
    const FIELD_PROJECT_LANGUAGE = 'project_language';

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
        $fieldApiUrl          = new Field(self::FIELD_API_URL, FieldType::VARCHAR, 255);
        $fieldProjectId       = new Field(self::FIELD_PROJECT_ID, FieldType::INT);
        $fieldProjectName     = new Field(self::FIELD_PROJECT_NAME, FieldType::VARCHAR, 255);
        $fieldProjectLanguage = new Field(self::FIELD_PROJECT_LANGUAGE, FieldType::VARCHAR, 255);
        
        $this->addField($fieldApiUrl);
        $this->addField($fieldProjectId);
        $this->addField($fieldProjectName);
        $this->addField($fieldProjectLanguage);
    }
    
    
    public function getRowData($apiUrl, $taskId, $projectName, $projectLanguage)
    {
        $row = new Row($this);
        $row->addValue(self::FIELD_PRIMARY_ID, $taskId);
        $row->addValue(self::FIELD_API_URL, $apiUrl);
        $row->addValue(self::FIELD_PROJECT_ID, $projectId);
        $row->addValue(self::FIELD_PROJECT_NAME, $projectName);
        $row->addValue(self::FIELD_PROJECT_LANGUAGE, $projectLanguage);

        return $row;
    }
    
        
    public function createDataRow($taskId, $apiUrl, $projectInfo)
    {
        $row = new Row($this);
        $row->addValue(self::FIELD_PRIMARY_ID, $taskId);
        $row->addValue(self::FIELD_API_URL, $apiUrl);
        $row->addValue(self::FIELD_PROJECT_ID, $projectInfo['project_id']);
        $row->addValue(self::FIELD_PROJECT_NAME, $projectInfo['project_title']);
        $row->addValue(self::FIELD_PROJECT_LANGUAGE, $projectInfo['project_language']);

        return $row;
    }
}

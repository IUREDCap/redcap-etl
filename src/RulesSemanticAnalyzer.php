<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Rules\Rule;
use IU\REDCapETL\Rules\Rules;
use IU\REDCapETL\Rules\FieldRule;
use IU\REDCapETL\Rules\TableRule;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\RowsType;

/**
 * Semantic analysis class for transformation rules.
 */
class RulesSemanticAnalyzer
{
    /** @var array map of table name to line number containing the rule for that table */
    private $tables;
    
    public function __construct()
    {
        $this->tables = array();
    }
    
    /**
     * Perform semantic analysis on parsed transformation rules.
     *
     * @param Rules $rules the rules to check.
     * @param Rules $metadata REDCap project metadata with information about the project's fields.
     */
    public function check(&$rules, $metadata = null)
    {
        #print "TRANSFORMATION RULES:\n";
        #print_r($rules);

        # Set up map of tables and check for duplicate table rule definitions
        foreach ($rules->getRules() as $rule) {
            if ($rule instanceof TableRule) {
                if (array_key_exists($rule->tableName, $this->tables)) {
                    $previousLine = $this->tables[$rule->tableName];
                    $error = 'Duplicate table rule for table "'.$rule->tableName
                        .'" on line '.$rule->getLineNumber()
                        .' (previously defined on line '.$previousLine.')'
                        .': "'.$rule->getLine().'"';
                    $rule->addError($error);
                } else {
                    $this->tables[$rule->tableName] = $rule->getLineNumber();
                }
            }
        }
        
        # Check for undefined parent tables
        foreach ($rules->getRules() as $rule) {
            if ($rule instanceof TableRule && !$rule->isRootTable()) {
                if (!array_key_exists($rule->parentTable, $this->tables)) {
                    $error = 'Parent table "'.$rule->parentTable.'"'
                        .' undefined for table "'.$rule->tableName
                        .'" on line '.$rule->getLineNumber()
                        .': "'.$rule->getLine().'"';
                    $rule->addError($error);
                }
            }
        }

        # Check for legal field types (e.g., can't use CHECKBOX type for non-checkbox field)
        if (isset($metadata)) {
            # create metadata map, so it's easier to look up metadata by field name
            $metadataMap = array();
            foreach ($metadata as $metadataItem) {
                $fieldName = $metadataItem['field_name'];
                $metadataMap[$fieldName] = $metadataItem;
            }

            foreach ($rules->getRules() as $rule) {
                if ($rule instanceof FieldRule) {
                    $redCapFieldName = $rule->getRedCapFieldName();
                    if (array_key_exists($redCapFieldName, $metadataMap)) {
                        $dbFieldType = $rule->getDbFieldType();

                        $fieldInfo = $metadataMap[$redCapFieldName];
                        $redcapFieldName = $fieldInfo['field_name'];
                        $redcapFieldType = $fieldInfo['field_type'];

                        if ($dbFieldType === FieldType::CHECKBOX
                            || $dbFieldType === FieldType::DROPDOWN
                            || $dbFieldType == FieldType::RADIO) {
                            if ($redcapFieldType !== $dbFieldType) {
                                $error = 'REDCap field "' . $redcapFieldName . '"'
                                    . ' was defined with type "' . $dbFieldType . '"'
                                    . ', but it is not a "' . $dbFieldType
                                    . '" on line '.$rule->getLineNumber()
                                    . ': "'.$rule->getLine().'"';
                                $rule->addError($error);
                            }
                        }
                    } // end array key exists
                }
            }
        }

        return $rules;
    }
}

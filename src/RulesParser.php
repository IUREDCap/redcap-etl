<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Rules\FieldRule;
use IU\REDCapETL\Rules\Rule;
use IU\REDCapETL\Rules\Rules;
use IU\REDCapETL\Rules\TableRule;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\RowsType;

/**
 * Parser class for ETL transformation rules.
 */
class RulesParser
{
    # Separators; example: "TABLE,  Fifth, Main, EVENTS:a;b"
    const ELEMENTS_SEPARATOR  = ',';
    const ROWS_DEF_SEPARATOR   = ':';   # row type separator
    const SUFFIXES_SEPARATOR   = ';';
    const ROWSTYPE_SEPARATOR   = '&';
    
    const ELEMENT_TABLE       = 'TABLE';
    const ELEMENT_FIELD       = 'FIELD';
    
    const RULE_TYPE_POS       = 0;

    const TABLE_NAME_POS      = 1;
    const TABLE_PARENT_POS    = 2;
    const TABLE_ROWSTYPE_POS  = 3;

    const FIELD_NAME_POS      = 1;
    const FIELD_TYPE_POS      = 2;
    const FIELD_DB_NAME_POS   = 3;
    
    # Table types (non-ROOT types represent 1:many relationships)
    const ROOT                  = 'ROOT';
    const EVENTS                = 'EVENTS';
    const SUFFIXES              = 'SUFFIXES';
    const REPEATING_INSTRUMENTS = 'REPEATING_INSTRUMENTS';
    const REPEATING_EVENTS      = 'REPEATING_EVENTS';

    public function __construct()
    {
    }
    
    /**
     * Parses rules text and translates it into an array of Rule objects,
     * in effect an AST (Abstract Syntax Tree).
     */
    public function parse($rulesString)
    {
        $rules = new Rules();
        
        //------------------------------------------------------
        // The rules language is a line-based language, so
        // start by breaking up the rules string into
        // separate lines (handle Windows and Linux end of
        // line conventions).
        //------------------------------------------------------
        $lines = [];
        if (!empty($rulesString)) {
            $lines = preg_split('/\r\n|\r|\n/', $rulesString);
        }
        
        $lastTableRule = null;

        // Process line by line
        $lineNumber = 1;
        $tableRulesCount = 0;
        foreach ($lines as $line) {
            // If line is nothing but whitespace and commas, skip it
            if (preg_match('/^[\s,]*$/', $line) === 1) {
                ; // don't do anything
            } elseif (preg_match('/^[\s]*#/', $line) === 1) {
                # comment line: first non-blank character is '#'
                ; // skip
            } else {
                // Get (comma-separated) values of the line, trimmed
                // trim removes leading and trailing whitespace
                $values = array_map('trim', explode(self::ELEMENTS_SEPARATOR, $line));
                $ruleType = $values[self::RULE_TYPE_POS];
                switch ($ruleType) {
                    case self::ELEMENT_TABLE:
                        $rule = $this->parseTableRule($values, $line, $lineNumber);
                        $lastTableRule = $rule;
                        $tableRulesCount++;
                        break;
                    case self::ELEMENT_FIELD:
                        $rule = $this->parseFieldRule($values, $lastTableRule, $line, $lineNumber);
                        if ($tableRulesCount === 0) {
                            $rule->addError('Field rule specified before any Table rule on line '
                                . $lineNumber . ': "'.$line.'"');
                        }
                        break;
                    default:
                        $msg = 'Unrecognized rule type "'.$ruleType.'" on line '.$lineNumber.': "'
                                .$line.'"';
                        $rule = new Rule($line, $lineNumber);
                        $rule->addError($msg);
                        break;
                }
               
                $rules->addRule($rule);
            }
           
            $lineNumber++;
        }

        return $rules;
    }
    
    
    private function parseTableRule($values, $line, $lineNumber)
    {
        $tableRule = new TableRule($line, $lineNumber);
        
        if (count($values) < 4) {
            $error = 'Not enough values (less than 4) on line '.$lineNumber.': "'
                    .$line.'"';
            $tableRule->addError($error);
        } else {
            $tableRule->tableName     = $this->cleanTableName($values[self::TABLE_NAME_POS]);
            $tableRule->parentTable   = $this->cleanTableName($values[self::TABLE_PARENT_POS]);
            $tableRule->tableRowsType = $this->cleanRowsDef($values[self::TABLE_ROWSTYPE_POS]);

            if (empty($tableRule->tableName)) {
                $error = 'Missing table name on line '.$lineNumber.': "'
                    .$line.'"';
                $tableRule->addError($error);
            } elseif (empty($tableRule->parentTable)) {
                $error = 'Missing table parent/primary key on line '.$lineNumber.': "'
                    .$line.'"';
                $tableRule->addError($error);
            } elseif (empty($tableRule->tableRowsType)) {
                $error = 'Missing table rows type on line '.$lineNumber.': "'.$line.'"';
                $tableRule->addError($error);
            } else {
                try {
                    list($rowsType, $suffixes) = $this->parseRowsDef($tableRule->tableRowsType);
                    if ($rowsType === false) {
                        $tableRule->addError('Unrecognized rows type on line '.$lineNumber.': '.$line);
                    } else {
                        # No errors found
                        $tableRule->rowsType = $rowsType;
                        $tableRule->suffixes = $suffixes;
                        # print "\n==============================================\nROWS TYPE AND SUFFIXES:\n";
                        # print_r($rowsType);
                        # print_r($suffixes);
                    }
                } catch (\Exception $exception) {
                    $tableRule->addError($exception->getMessage() . ' on line '.$lineNumber.': '.$line);
                }
            }
        }
        
        return $tableRule;
    }


    /**
     * Parses a Field Rule.
     *
     * @param array $values array of string values from
     *     the line that has the rule to be parsed.
     * @param TableRule $tableRule the table rule for this field
     * @param string $line the original line containing the rule; used
     *     for error messages.
     * @param int $lineNumber the line number of the rule in the original
     *     transformation rules text.
     * @return FieldRule
     */
    private function parseFieldRule($values, $tableRule, $line, $lineNumber)
    {
        $fieldRule = new FieldRule($line, $lineNumber);
           
        #-----------------------------------------
        # Parse REDCap field name
        #-----------------------------------------
        if (!array_key_exists(self::FIELD_NAME_POS, $values)) {
            $error = "Missing field name on line {$lineNumber}: '".$line."'";
            $fieldRule->addError($msg);
        } else {
            $fieldRule->redCapFieldName = $this->cleanFieldName($values[self::FIELD_NAME_POS]);
            if (empty($fieldRule->redCapFieldName)) {
                $msg = "Missing field name on line {$lineNumber}: '".$line."'";
                $fieldRule->addError($msg);
            }
        }
        
        #------------------------------------------
        # Parse database field type
        #------------------------------------------
        if (!array_key_exists(self::FIELD_TYPE_POS, $values)) {
            $error = "Missing field name on line number {$lineNumber}: '".$line."'";
            $fieldRule->addError($msg);
        } else {
            $fieldTypeSpecification = $this->cleanFieldType($values[self::FIELD_TYPE_POS]);
            
            if (empty($fieldTypeSpecification)) {
                $msg = "Missing field type on line {$lineNumber}: '".$line."'";
                $fieldRule->addError($msg);
            } else {
                if (preg_match('/([a-zA-Z]+)\(([0-9]+)\)/', $fieldTypeSpecification, $matches) === 1) {
                    $fieldRule->dbFieldType = $matches[1];
                    $fieldRule->dbFieldSize = $matches[2];
                } else {
                    $fieldRule->dbFieldType = $fieldTypeSpecification;
                    $fieldRule->dbFieldSize = null;
                }
                
                if (!FieldType::isValid($fieldRule->dbFieldType)) {
                    $msg = 'Invalid field type "'.$fieldRule->dbFieldType.'" on line '
                        .$lineNumber.': "'.$line.'"';
                    $fieldRule->addError($msg);
                }
            }
        }
        
        #---------------------------------------------------------
        # Parse optional database field name, if it exists
        #---------------------------------------------------------
        if (array_key_exists(self::FIELD_DB_NAME_POS, $values)) {
            # it's OK if this ends up being empty; it's optional
            $fieldRule->dbFieldName = $this->cleanFieldName($values[self::FIELD_DB_NAME_POS]);
        } elseif ($tableRule->hasSuffixes()) {
            # If this is a suffixes table and the database field name was not explicitly defined,
            # remove suffix from the database field name
            $dbFieldName = $fieldRule->redCapFieldName;
            foreach ($tableRule->getSuffixes() as $suffix) {
                $matches = array();
                if (preg_match("/(.*){$suffix}$/", $dbFieldName, $matches) === 1) {
                    $dbFieldName = $matches[1];
                }
            }
            $fieldRule->dbFieldName = $dbFieldName;
        }

        return $fieldRule;
    }


    private function parseRowsDef($rowsDef)
    {
        $rowsDef = trim($rowsDef);

        $rowsType = array();
        $suffixes = array();
        $firstSuffixes = array();
        $rowsTypeSuffixes = array();

        $rowsEncode = explode(self::ROWSTYPE_SEPARATOR, $rowsDef);
        $isFirstRowsType = true;

        foreach ($rowsEncode as $rowType) {
            $suffixes = array();

            $rowsTypeSuffixes = $this->assignRowsType($rowType);
            array_push($rowsType, $rowsTypeSuffixes[0]);

            foreach ($rowsTypeSuffixes[1] as $rowsTypeSuffix) {
                array_push($suffixes, $rowsTypeSuffix);
            }

            if ($isFirstRowsType) {
                $firstSuffixes = $suffixes;
            } else {
                if ($suffixes != $firstSuffixes) {
                    throw new \Exception('Suffixes for rows types do not match');
                }
            }

            $isFirstRowsType = false;
        }
        return (array($rowsType,$firstSuffixes));
    }

    /**
     * Returns an array where the first element contains the rows type and the second element contains
     * an array of suffixes.
     */
    private function assignRowsType($rowType)
    {
        $suffixes = array();
        $regex = '/' . self::SUFFIXES_SEPARATOR . '/';

        list($rowsEncode, $suffixesDef) = array_pad(explode(self::ROWS_DEF_SEPARATOR, $rowType), 2, null);

        switch ($rowsEncode) {
            case self::ROOT:
                $rowsType = RowsType::ROOT;
                break;

            case self::EVENTS:
                if (empty($suffixesDef)) {
                    $suffixes = [''];
                } else {
                    $suffixes = explode(self::SUFFIXES_SEPARATOR, $suffixesDef);
                }
                $rowsType = (empty($suffixes[0])) ? RowsType::BY_EVENTS : RowsType::BY_EVENTS_SUFFIXES;
                break;

            case self::REPEATING_INSTRUMENTS:
                if (empty($suffixesDef)) {
                    $suffixes = [''];
                } else {
                    $suffixes = explode(self::SUFFIXES_SEPARATOR, $suffixesDef);
                }

                if (empty($suffixes[0])) {
                    $rowsType = RowsType::BY_REPEATING_INSTRUMENTS;
                } else {
                    $rowsType = RowsType::BY_REPEATING_INSTRUMENTS_SUFFIXES;
                }
                break;

            case self::REPEATING_EVENTS:
                if (empty($suffixesDef)) {
                    $suffixes = [''];
                } else {
                    $suffixes = explode(self::SUFFIXES_SEPARATOR, $suffixesDef);
                }

                if (empty($suffixes[0])) {
                    $rowsType = RowsType::BY_REPEATING_EVENTS;
                } else {
                    $rowsType = RowsType::BY_REPEATING_EVENTS_SUFFIXES;
                }
                break;

            case self::SUFFIXES:
                $suffixes = explode(self::SUFFIXES_SEPARATOR, $suffixesDef);
                $rowsType = (empty($suffixes[0])) ? false : RowsType::BY_SUFFIXES;
                break;

            case (preg_match($regex, $rowsEncode) ? true : false):
                $suffixes = explode(self::SUFFIXES_SEPARATOR, $rowsEncode);
                $rowsType = (empty($suffixes[0])) ? false : RowsType::BY_SUFFIXES;
                break;

            default:
                $rowsType = false;
        }
        return (array($rowsType,$suffixes));
    }


    protected function cleanTableName($tableName)
    {
        return $this->generalSqlClean($tableName);
    }


    protected function cleanRowsDef($rowsDef)
    {
        return $this->generalSqlClean($rowsDef);
    }


    protected function cleanFieldName($fieldName)
    {
        return $this->generalSqlClean($fieldName);
    }


    protected function cleanFieldType($fieldType)
    {
        # remove all characters except for letters, digits,
        # underscores, and parentheses
        $cleanedFieldType = preg_replace("/[^a-zA-Z0-9_()]+/i", "", $fieldType);
        return $cleanedFieldType;
    }


    protected function generalSqlClean($input)
    {
        $cleaned = preg_replace("/[^a-zA-Z0-9_;:&]+/i", "", $input);
        return $cleaned;
    }


    protected function isEmptyString($str)
    {
        return !(isset($str) && (strlen(trim($str)) > 0));
    }
}

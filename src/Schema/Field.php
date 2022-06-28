<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use IU\REDCapETL\EtlException;
use IU\REDCapETL\RedCapEtl;

/**
 * Field is used to store information about a relational table's field
 */
class Field
{
    /** @var string REDCap field name, and default database field name. For suffix fields, this
        name will be the root of multiple REDCap field names, for example, you might have REDCap fields
        "phone1", "phone2", "phone3", which would be represented by a Field object with name "phone". */
    public $name = '';
    
    /** @var string the REDCap type of the field, or blank if the field
                    does not have a corresponding REDCap field, or
                    if metadata for the REDCap field is not available */
    public $redcapType = '';
    
    public $type = '';
    public $size = null;
    
    /** @var string database field name - the name of the field in the database where the REDCap field values
                    will be stored. By default, this will be the same as the REDCap field name. */
    public $dbName = '';

    /** @var mixed the lookup field name (string) if this field uses the lookup table,
             i.e., it is a multiple-choice field, and as a results will
             have a value to label mapping entry in the lookup table. And false, if
             this field is not a multiple-choice field.
    */
    private $usesLookup = false;
    
    /** @var array map from values to labels for multiple-choice fields. */
    public $valueToLabelMap;

    /** @var string the label for a checkbox field */
    public $checkboxLabel;

    /** @var boolean indicates if the field is a label field, which is used to display the label
     *     (as opposed to the code/value) for a multiple choice field. */
    public $isLabel; // only set for label fields

    /**
     * Creates a Field object that is used to describe a field in REDCap and
     * mapping information for that field to the database where the extraced
     * and transformed REDCap data is loaded.
     *
     * @param string $name the name of the field in REDCap, and the default name of
     *     the field in the database (where the extracted REDCap data is loaded).
     *
     * @param string $type the database type for the field (e.g., char, int, varchar).
     *
     * @param integer $size the database type size for the field, e.g., 255 for the a
     *     "varchar(255)" field
     *
     * @param string $dbName the name to use for the field in the database. If this is not
     *     specified, then the REDCap field name is used for the database field name.
     *
     * @param string $redcapType the REDCap type for the field, e.g., "radio".
     */
    public function __construct($name, $type, $size = null, $dbName = '', $redcapType = '')
    {
        $this->name       = $name;
        $this->redcapType = $redcapType;
        
        $this->type = $type;
        $this->size = $size;

        $this->valueToLabelMap = array();

        #-------------------------------------------------
        # If a database field name was specified, use it;
        # otherwise, use the REDCap field name as the
        # database field name also
        #-------------------------------------------------
        if (!empty($dbName)) {
            $this->dbName = $dbName;
        } else {
            $this->dbName = $name;
        }

        $this->isLabel = false;
        $this->checkboxLabel = null;
    }

    /**
     * Returns a merged field if possible, or throws an exception if not.
     * The merged field will have the greater of the 2 fields sizes, if the
     * fields ihave sizes set and match on values other than size.
     */
    public function merge($field, $task = null)
    {
        $mergedField = new Field($this->name, $this->type, $this->size, $this->dbName, $this->redcapType);
        $mergedField->usesLookup      = $this->usesLookup;
        $mergedField->valueToLabelMap = $this->valueToLabelMap;
        $mergedField->isLabel         = $this->isLabel;
        $mergedField->checkboxLabel   = $this->checkboxLabel;

        #------------------------------------
        # Set error message prefix
        #------------------------------------
        $errorMessage = 'Error in field "'.$this->dbName.'"';
        if (isset($task)) {
            $taskName = $task->getName();
            if (!empty($taskName)) {
                $errorMessage .= ' from task "'.$taskName.'"';
            }
            $errorMessage .= ' for database "'.$task->getDbId().'"';
        }
        $errorMessage .= ': ';

        # Check that fields' database names match
        if ($this->dbName !== $field->dbName) {
            $message = $errorMessage .  $message = 'database field names "' . $this->dbName . '"'
                . ' and "' . $field->dbName . '" do not match.';
            $code = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }
        
        # If the field (database) types don't match, check to make sure they are compatible
        if ($this->type !== $field->type) {
            if ($this->type === FieldType::CHAR && $field->type === FieldType::VARCHAR) {
                $mergedField->type = FieldType::VARCHAR;
            } elseif ($this->type === FieldType::VARCHAR && $field->type === FieldType::CHAR) {
                $mergedField->type = FieldType::VARCHAR;
            } elseif ($this->type === FieldType::STRING || $field->type === FieldType::STRING) {
                $mergedField->type = FieldType::STRING;
                $mergedField->size = null;
            } elseif ($this->type === FieldType::INT && ! $this->usesLookup() && $field->type === FieldType::FLOAT) {
                # convert non-multiple choice int to float
                $mergedField->type = FieldType::FLOAT;
                $mergedField->size = null;
            } elseif ($field->type === FieldType::INT && ! $field->usesLookup() && $this->type === FieldType::FLOAT) {
                # convert non-multiple choice int to float
                $mergedField->type = FieldType::FLOAT;
                $mergedField->size = null;
            } else {
                $message = $errorMessage . 'the database field type "'.$field->type.'"'.' does not match'
                    .' the previously defined type "'.$this->type.'" for this field.';
                $code    = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }
        }
        
        # Check for compatible redcap type
        if ($this->redcapType !== $field->redcapType) {
            if ($this->redcapType === 'checkbox' || $field->redcapType === 'checkbox') {
                $message = $errorMessage . 'the REDCap field type "'.$field->redcapType.'"'.' does not match'
                    .' the previously defined REDCap type "'.$this->redcapType.'" for this field.';
                $code    = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }
        }
 
        if ($this->usesLookup !== $field->usesLookup) {
            $message = 'is inconsistently defined as a multiple-choice field.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }
        
        if ($this->usesLookup && $field->usesLookup) {
            if ($this->valueToLabelMap !== $field->valueToLabelMap) {
                $message = $erroMessage
                    .'the multiple-choice options for this field do not match those previously defined.';
                $code    = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }
        }
        
        # Changed merged size to max of field sizes
        if (isset($this->size) && isset($field->size)) {
            $mergedField->size = max($this->size, $field->size);
        }

        return $mergedField;
    }
    
    public function toString($indent = 0)
    {
        $in = str_repeat(' ', $indent);
        $string = '';

        $string .= "{$in}{$this->name} {$this->redcapType} : {$this->dbName} ";
        if (isset($this->size)) {
            $string .= "{$this->type}({$this->size})";
        } else {
            $string .= "{$this->type}";
        }

        if (!empty($this->valueToLabelMap)) {
            $string .= ' [';
            $isFirst = true;
            foreach ($this->valueToLabelMap as $value => $label) {
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    $string .= ', ';
                }
                $string .= "{$value} => {$label}";
            }
            $string .= ']';
        }

        $string .= "\n";
        return $string;
    }

    public function usesLookup()
    {
        return $this->usesLookup;
    }

    public function setUsesLookup($usesLookup)
    {
        $this->usesLookup = $usesLookup;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Indicates if this field is a label for a REDCAp mutliple choice (radio, checkbox, dropdown) field.
     * If this is a label field, its redcapType should have "_label" appended to it.
     */
    public function isLabel()
    {
        return $this->isLabel;
    }

    public function isCheckbox()
    {
        // ORIGINAL CHECK:
        // return preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $this->name);
        return $this->redcapType === 'checkbox';
    }

    public function isSurveyTimestamp()
    {
        return (preg_match('/_timestamp$/', $this->name) === 1 && $this->type === FieldType::DATETIME);
    }
}

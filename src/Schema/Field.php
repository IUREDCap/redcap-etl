<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use IU\REDCapETL\RedCapEtl;

/**
 * Field is used to store information about a relational table's field
 */
class Field
{
    /** @var string REDCap field name, and default database field name */
    public $name = '';
    
    /** @var string the REDCap type of the field, or blank if the field
                    does not have a corresponding REDCap field, or
                    if metadata for the REDCap field is not available */
    public $redcapType = '';
    
    public $type = '';
    public $size = null;
    
    /** @var string database field name */
    public $dbName = '';

    /** @var mixed the lookup field name (string) if this field uses the lookup table,
             i.e., it is a multiple-choice field, and as a results will
             have a value to label mapping entry in the lookup table. And false, if
             this field is not a multiple-choice field.
    */
    public $usesLookup = false;
    
    /** @var array map from values to labels for multiple-choice fields. */
    public $lookupMap;

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
    }

    /**
     * Indicates if the specified field is equal to the field on which this method is invoked
     * for the field that will be generated in the database.
     */
    public function isDatabaseEquivalent($field, $lookupTable)
    {
        $matches = false;
        if ($this->dbName === $field->dbName && $this->type === $field->type && $this->size === $field->size) {
            if ($this->usesLookup && $field->usesLookup) {
                # If both fields use lookup, i.e., are multiple choice fields
                # Compare choice values...
                # Need the lookup table here
                //$lt1 = $lookupTable->getValueLabelMap($tableName, $fieldName);
            } elseif (!$this->usesLookup && !$field->usesLookup) {
                # If both fields do NOT use lookup, i.e., are NOT multiple choice fields
                $matches = true;
            }
        }
        return $matches;
    }
    
    public function toString($indent)
    {
        $in = str_repeat(' ', $indent);
        $string = '';

        $string .= "{$in}{$this->name} {$this->redcapType} : {$this->dbName} ";
        if (isset($this->size)) {
            $string .= "{$this->type}({$this->size})\n";
        } else {
            $string .= "{$this->type}\n";
        }
        return $string;
    }
}

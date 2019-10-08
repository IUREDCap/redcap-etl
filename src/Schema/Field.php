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
             this field is not a multiple-choise field.
    */
    public $usesLookup = false;

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

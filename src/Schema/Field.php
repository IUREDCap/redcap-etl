<?php

namespace IU\REDCapETL\Schema;

use IU\REDCapETL\RedCapEtl;

/**
 * Field is used to store information about a relational table's field
 */
class Field
{
    public $name = '';   # REDCap field name, and default database field name
    public $type = '';
    public $size = null;
    public $dbName = '';   # database field name

    public $usesLookup = false;

    public function __construct($name, $type, $size = null, $dbName = '')
    {
        $this->name = $name;
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
        if (isset($this->size)) {
            $string .= "{$in}{$this->name} : {$this->type}({$this->size})\n";
        } else {
            $string .= "{$in}{$this->name} : {$this->type}\n";
        }
        return $string;
    }
}

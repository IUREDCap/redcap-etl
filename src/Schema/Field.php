<?php

namespace IU\REDCapETL\Schema;

/**
 * Field is used to store information about a relational table's field
 */
class Field
{
    public $name = '';
    public $type = '';
    public $size = null;

    public $usesLookup = false;

    public function __construct($name, $type, $size = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
    }

    public function toString($indent)
    {
        $in = str_repeat(' ', $indent);
        $string = '';
        if (isset($size)) {
            $string .= "{$in}{$this->name} : {$this->type}({$this->size})\n";
        } else {
            $string .= "{$in}{$this->name} : {$this->type}\n";
        }
        return $string;
    }
}

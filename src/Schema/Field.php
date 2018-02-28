<?php

namespace IU\REDCapETL\Schema;

/**
 * Field is used to store information about a relational table's field
 */
class Field
{
    public $name = '';
    public $type = '';

    public $usesLookup = false;

    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function toString($indent)
    {
        $in = str_repeat(' ', $indent);
        $string = '';
        $string .= "{$in}{$this->name} : {$this->type}\n";
        return $string;
    }
}

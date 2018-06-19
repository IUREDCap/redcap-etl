<?php

namespace IU\REDCapETL\Rules;

class TableRule extends Rule
{
    public $tableName;
    public $parentTable;
    public $primaryKey;
    public $rowsType;
    public $suffixes;
    
    public function __construct($line, $lineNumber)
    {
        $this->suffixes = array();
        $this->rowsType = array();
        parent::__construct($line, $lineNumber);
    }
}

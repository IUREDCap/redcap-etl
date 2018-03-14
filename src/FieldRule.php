<?php

namespace IU\REDCapETL;

class FieldRule extends Rule
{
    public $redCapFieldName;
    public $dbFieldType;
    public $dbFieldSize;
    public $dbFieldName;  # database field name, specified
                          # if different from REDCap field name

    public function __construct($line, $lineNumber)
    {
        parent::__construct($line, $lineNumber);
    }
}

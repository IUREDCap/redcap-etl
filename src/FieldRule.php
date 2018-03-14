<?php

namespace IU\REDCapETL;

class FieldRule extends Rule
{
    public $fieldName;
    public $fieldType;

    public function __construct($line, $lineNumber)
    {
        parent::__construct($line, $lineNumber);
    }
}

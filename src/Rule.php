<?php

namespace IU\REDCapETL;

class Rule
{
    private $line;   // text of rule line in source file
    private $lineNumber;   // line number of rule in source file
    private $errors;   // array of errors
    
    public function __construct($line, $lineNumber)
    {
        $this->line = $line;
        $this->lineNumber = $lineNumber;
        $this->errors = array();
    }
    
    public function getLine()
    {
        return $this->line;
    }
    
    public function getLineNumber()
    {
        return $this->lineNumber;
    }
    
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }

    public function addError($error)
    {
        array_push($this->errors, $error);
    }
}

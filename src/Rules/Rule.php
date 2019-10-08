<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Rules;

/**
 * Class representing a transformation rule.
 */
class Rule
{
    /** @var string text of rule line in source file */
    private $line;
    
    /** @var int line number of rule in source file */
    private $lineNumber;
    
    /** @var array array of error message generated for this rule */
    private $errors;
    
    /**
     * Creates a rule.
     *
     * @param string $line text of rule line in source file.
     * @param int $lineNumber the line number of the rule in the
     *     original rules text.
     */
    public function __construct($line, $lineNumber)
    {
        $this->line = $line;
        $this->lineNumber = $lineNumber;
        $this->errors = array();
    }
    
    /**
     * Gets the line of text that corresponds to this rule.
     */
    public function getLine()
    {
        return $this->line;
    }
    
    /**
     * Gets the line number of this rule in the original rules text.
     */
    public function getLineNumber()
    {
        return $this->lineNumber;
    }
    
    /**
     * Indicates if this rule currently has any errors.
     *
     * @return boolean returns true if this rule has 1 or more errors,
     *     and false otherwise.
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Added the specifed error to this rule's errors.
     *
     * @param string $error the error to add to this rule's errors.
     */
    public function addError($error)
    {
        array_push($this->errors, $error);
    }
}

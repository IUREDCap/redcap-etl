<?php

namespace IU\REDCapETL\Rules;

class Rules
{
    /** @var array an array of Rule objects */
    private $rules;

    public function __construct()
    {
        $this->rules = array();
    }

    public function addRule($rule)
    {
        array_push($this->rules, $rule);
    }

    public function getRules()
    {
        return $this->rules;
    }
}

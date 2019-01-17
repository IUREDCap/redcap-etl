<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Rules\Rule;
use IU\REDCapETL\Rules\Rules;
use IU\REDCapETL\Rules\TableRule;
use IU\REDCapETL\Schema\RowsType;

/**
 * Semantic analysis class for transformation rules.
 */
class RulesSemanticAnalyzer
{
    /** @var array map of table name to line number containing the rule for that table */
    private $tables;
    
    public function __construct()
    {
        $this->tables = array();
    }
    
    /**
     * Perform semantic analysis on parsed transformation rules.
     *
     * @param Rules $rules the rules to check.
     */
    public function check(& $rules)
    {
        #print "TRANSFORMATION RULES:\n";
        #print_r($rules);

        # Set up map of tables and check for duplicate table rule definitions
        foreach ($rules->getRules() as $rule) {
            if ($rule instanceof TableRule) {
                if (array_key_exists($rule->tableName, $this->tables)) {
                    $previousLine = $this->tables[$rule->tableName];
                    $error = 'Duplicate table rule for table "'.$rule->tableName
                        .'" on line '.$rule->getLineNumber()
                        .' (previously defined on line '.$previousLine.')'
                        .': "'.$rule->getLine().'"';
                    $rule->addError($error);
                } else {
                    $this->tables[$rule->tableName] = $rule->getLineNumber();
                }
            }
        }
        
        # Check for undefined parent tables
        foreach ($rules->getRules() as $rule) {
            if ($rule instanceof TableRule && !$rule->isRootTable()) {
                if (!array_key_exists($rule->parentTable, $this->tables)) {
                    $error = 'Parent table "'.$rule->parentTable.'"'
                        .' undefined for table "'.$rule->tableName
                        .'" on line '.$rule->getLineNumber()
                        .': "'.$rule->getLine().'"';
                    $rule->addError($error);
                }
            }
        }

        return $rules;
    }
}

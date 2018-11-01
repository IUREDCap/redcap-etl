<?php

namespace IU\REDCapETL\Rules;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\RedCapEtl;

/** Need to test addRule,getRules,getParsedLineCount */

/**
 * PHPUnit tests for the Rules class.
 */
class RulesTest extends TestCase
{
  
    public function testAddRules()
    {
        // Test that a Rules object can be created
        $rules = new Rules();
        $this->assertNotNull($rules, 'rules not null');
        

        // Test that Rule objects can be added to a Rules object
        $parseableLine = 'Parseable Line';
        $parseableLineNumber = 1;
        $parseableRule = new Rule($parseableLine, $parseableLineNumber);
        $unparseableLine = 'UnparsebleLine';
        $unparseableLineNumber = 2;
        $unparseableError = 'Unparseable Line is unparseable';
        $unparseableRule = new Rule($parseableLine, $parseableLineNumber);
        $unparseableRule->addError($unparseableError);
        
        $rules->addRule($parseableRule);
        $rules->addRule($unparseableRule);
        
        $retrievedRules = $rules->getRules();
        $this->assertEquals(2, count($retrievedRules), 'Rules added check');
        
        
        // Test that the parsedLineCount can be retrieved
        $parsedLineCount = $rules->getParsedLineCount();
        $this->assertEquals(1, $parsedLineCount, 'ParsedLineCount check');
    }
}

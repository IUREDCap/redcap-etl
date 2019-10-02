<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\Rules\Rules;

/**
 * PHPUnit tests for the Logger class.
 */
class RulesSemanticAnalyzerTest extends TestCase
{
    public function setUp()
    {
    }
     
    public function testDuplicateTableCheck()
    {
        $analyzer = new RulesSemanticAnalyzer();
        $this->assertNotNull($analyzer, 'Analyzer parser not null');
        
        $parser = new RulesParser();
        $rulesText =
            "TABLE,test,test_id,ROOT\n"
            ."TABLE,test,test_id,ROOT\n";
        $rules = $parser->parse($rulesText);
        
        $checkedRules = $analyzer->check($rules);
        $this->assertInstanceOf(Rules::class, $checkedRules, 'Checked rules type');
        
        $rule2 = $checkedRules->getRules()[1];
        $errors = $rule2->getErrors();
        $this->assertEquals(1, count($errors), 'Number of errors');
    }
    
         
    public function testUndefinedParentTableCheck()
    {
        $analyzer = new RulesSemanticAnalyzer();
        $this->assertNotNull($analyzer, 'Analyzer parser not null');
        
        $parser = new RulesParser();
        $rulesText = "TABLE,test,parent,EVENTS\n";
        $rules = $parser->parse($rulesText);
        
        $checkedRules = $analyzer->check($rules);
        $this->assertInstanceOf(Rules::class, $checkedRules, 'Checked rules type');
        
        $rule1 = $checkedRules->getRules()[0];
        $errors = $rule1->getErrors();
        $this->assertEquals(1, count($errors), 'Number of errors');
    }
}

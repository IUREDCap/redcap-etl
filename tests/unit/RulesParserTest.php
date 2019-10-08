<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\Rules\Rules;
use IU\REDCapETL\Rules\TableRule;

/**
 * PHPUnit tests for the Logger class.
 */
class RulesParserTest extends TestCase
{
    public function setUp()
    {
    }
    
    public function testParseTableRule()
    {
        $parser = new RulesParser();
        $this->assertNotNull($parser, 'Rules parser not null');
        
        $rules = $parser->parse("TABLE,test,test_id,ROOT\n");
        $this->assertNotNull($rules, 'Parsed rules not null');

        $this->assertInstanceOf(Rules::class, $rules, 'Rules type');
        
        $rulesArray = $rules->getRules();
        $this->assertTrue(is_array($rulesArray), 'Rules array is array');
        
        $this->assertEquals(1, count($rulesArray), 'Rules array count');
        
        $rule = $rulesArray[0];
        $this->assertInstanceOf(TableRule::class, $rule, TableRule::class, 'Rule type');
        
        $this->assertEquals('test', $rule->getTableName(), 'Table name');
    }
}

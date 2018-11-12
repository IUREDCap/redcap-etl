<?php

namespace IU\REDCapETL\Rules;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\RedCapEtl;

/**
 * PHPUnit tests for the TableRule class.
 */
class TableRuleTest extends TestCase
{
    public function testCreateRule()
    {

        // Test that a TableRule can be created
        $expectedLine = 'Test Table Rule String';
        $expectedLineNumber = 37;

        $rule = new TableRule($expectedLine, $expectedLineNumber);
        $this->assertNotNull($rule, 'rule not null');

        // Test that the rule returns the expected line, as a stand-in
        // for testing that the TableRule was created
        $line = $rule->getLine();
        $this->assertEquals($expectedLine, $line, 'line check');
    }
}

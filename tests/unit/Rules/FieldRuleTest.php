<?php

namespace IU\REDCapETL\Rules;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\RedCapEtl;

/**
 * PHPUnit tests for the FieldRule class.
 */
class FieldRuleTest extends TestCase
{
    public function testCreateRule()
    {

        // Test that a FieldRule can be created
        $expectedLine = 'Test Field Rule String';
        $expectedLineNumber = 37;

        $rule = new FieldRule($expectedLine, $expectedLineNumber);
        $this->assertNotNull($rule, 'rule not null');

        // Test that the rule returns the expected line, as a stand-in
        // for testing that the FieldRule was created
        $line = $rule->getLine();
        $this->assertEquals($expectedLine, $line, 'line check');
    }
}

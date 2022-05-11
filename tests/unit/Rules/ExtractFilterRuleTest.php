<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Rules;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\RedCapEtl;

/**
 * PHPUnit tests for the TableRule class.
 */
class ExtractfilterRuleTest extends TestCase
{
    public function testCreateRule()
    {

        // Test that a TableRule can be created
        $expectedLine = 'Extract Filter Rule String';
        $expectedLineNumber = 27;

        $rule = new ExtractFilterRule($expectedLine, $expectedLineNumber);
        $this->assertNotNull($rule, 'rule not null');

        // Test that the rule returns the expected line, as a stand-in
        // for testing that the TableRule was created
        $line = $rule->getLine();
        $this->assertEquals($expectedLine, $line, 'line check');
    }
}

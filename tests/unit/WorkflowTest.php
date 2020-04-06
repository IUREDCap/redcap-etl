<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the Logger class.
 */
class WorkflownTest extends TestCase
{
    public function setUp()
    {
    }


    public function testWorkflow()
    {
        $propertiesFile = __DIR__.'/../data/config-testconfiguration.ini';
        $logger = new Logger('test-app');

        $workflow = new Workflow($logger, $propertiesFile);
        $this->assertNotNull($workflow, 'config not null check');
    }
}

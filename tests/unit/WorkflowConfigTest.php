<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the WorkflowConfig class.
 */
class WorkflowConfigTest extends TestCase
{
    public function setUp()
    {
    }


    public function testWorkflowConfig()
    {
        $propertiesFile = __DIR__.'/../data/config-testconfiguration.ini';
        $logger = new Logger('test-app');

        $workflowConfig = new WorkflowConfig($logger, $propertiesFile);
        $this->assertNotNull($workflowConfig, 'Workflow config not null check');
    }
}

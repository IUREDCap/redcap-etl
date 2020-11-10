<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the EtlWorkflow class.
 */
class EtlWorkflowTest extends TestCase
{

    public function setUp()
    {
    }
    
    public function testConstructor()
    {
        $loggerMock = $this->createMock(Logger::class);
        $properties = array();

        $workflowConfigMock = $this->getMockBuilder(WorkflowConfig::class)
            ->setMethods(array())
            ->disableOriginalConstructor()
            ->getMock();
        $workflowConfigMock->method('getTaskConfigs')->will($this->returnValue(array()));

        $etlWorkflow = new EtlWorkflow($workflowConfigMock, $loggerMock);
        $this->assertNotNull($etlWorkflow, 'ETL workflow not null check');
    }
}

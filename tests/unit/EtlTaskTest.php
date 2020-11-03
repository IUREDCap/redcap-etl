<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the EtlTask class.
 */
class EtlTaskTest extends TestCase
{

    public function setUp()
    {
    }
    
    public function testConstructor()
    {
        $taskId = 1;
        $etlTask = new EtlTask($taskId);
        $this->assertNotNull($etlTask, 'ETL task not null check');

        $this->assertEquals($taskId, $etlTask->getId(), 'Task ID check');
    }
}

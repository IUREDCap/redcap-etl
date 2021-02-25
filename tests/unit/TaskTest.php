<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Task class.
 */
class TaskTest extends TestCase
{

    public function setUp(): void
    {
    }
    
    public function testConstructor()
    {
        $taskId = 1;
        $task = new Task($taskId);
        $this->assertNotNull($task, 'ETL task not null check');

        $this->assertEquals($taskId, $task->getId(), 'Task ID check');
    }
}

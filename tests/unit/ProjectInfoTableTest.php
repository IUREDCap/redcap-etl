<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\FieldTypeSpecifier;

/**
* PHPUnit tests for the ProjectInfoTable class
*/

class ProjectInfoTableTest extends TestCase
{
    public function testConstructor()
    {
        $name = 'metadata';

        $projectInfoTable = new ProjectInfoTable($name);
        $this->assertNotNull($projectInfoTable, 'ProjectInfoTable object not null check');

        $this->assertEquals($name, $projectInfoTable->getName(), 'ProjectInfoTable name check');
    }
}

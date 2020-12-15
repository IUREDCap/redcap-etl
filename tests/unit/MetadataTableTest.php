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
* PHPUnit tests for the MetadataTable class
*/

class MetadataTableTest extends TestCase
{
    public function testConstructor()
    {
        $name = 'metadata';

        $metadataTable = new MetadataTable($name);
        $this->assertNotNull($metadataTable, 'MetadataTable object not null check');

        $this->assertEquals($name, $metadataTable->getName(), 'MetadataTable name check');
    }
}

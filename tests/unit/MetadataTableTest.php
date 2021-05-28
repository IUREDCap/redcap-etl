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

    public function testMerge()
    {
        $name1 = 'metadata1';
        $name2 = 'metadata2';

        $metadataTable1 = new MetadataTable($name1);
        $this->assertNotNull($metadataTable1, 'metadataTable1 object not null check');
        $this->assertEquals($name1, $metadataTable1->getName(), 'metadataTable1 name check');

        $metadataTable2 = new MetadataTable($name2);
        $this->assertNotNull($metadataTable2, 'metadataTable2 object not null check');
        $this->assertEquals($name2, $metadataTable2->getName(), 'metadataTable2 name check');

        $exceptionCaught = false;
        try {
            $metadataTable1->merge($metadataTable2);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            $message = $exception->getMessage();
        }
        $this->assertTrue($exceptionCaught, 'Exception caught');
        $this->assertStringContainsString('do not match', $message, 'Exception message');
    }
}

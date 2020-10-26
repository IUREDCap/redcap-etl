<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the Field class.
 */
class FieldTest extends TestCase
{
    public function testConstructor()
    {
        $name = 'id';
        $type = FieldType::INT;
        $field = new Field($name, $type);
        $this->assertEquals($name, $field->name, 'Name check');
        $this->assertEquals($type, $field->type, 'Type check');
    }

    public function testToString()
    {
        $name = 'email';
        $type = FieldType::STRING;

        $field = new Field($name, $type);

        $value = $field->toString(2);

        $expectedValue = "  {$name}  : {$name} {$type}\n";

        $this->assertEquals($expectedValue, $value, 'toString value check');
    }
    
    public function testToStringForCharType()
    {
        $name = 'email';
        $type = FieldType::CHAR;
        $size = 40;

        $field = new Field($name, $type, $size);

        $value = $field->toString(4);

        $expectedValue = "    {$name}  : {$name} {$type}({$size})\n";

        $this->assertEquals($expectedValue, $value, 'toString value check');
    }

    public function testMergeCharFieldWithDifferentSizes()
    {
        $name = 'email';
        $type = FieldType::CHAR;
        $size1 = 40;

        $field1 = new Field($name, $type, $size1);

        $size2 = 60;
        $field2 = new Field($name, $type, $size2);

        $mergedField = $field1->merge($field2);

        $this->assertNotNull($mergedField, 'Merged field not null check');

        $this->assertEquals($name, $mergedField->name, 'Field name check');
        $this->assertEquals(FieldType::CHAR, $mergedField->type, 'Field type check');
        $this->assertEquals(max($size1, $size2), $mergedField->size, 'Field size check');
    }

    public function testMergeVarCharFieldWithDifferentSizes()
    {
        $name = 'email';
        $type = FieldType::VARCHAR;
        $size1 = 40;

        $field1 = new Field($name, $type, $size1);

        $size2 = 60;
        $field2 = new Field($name, $type, $size2);

        $mergedField = $field1->merge($field2);

        $this->assertNotNull($mergedField, 'Merged field not null check');

        $this->assertEquals($name, $mergedField->name, 'Field name check');
        $this->assertEquals(FieldType::VARCHAR, $mergedField->type, 'Field type check');
        $this->assertEquals(max($size1, $size2), $mergedField->size, 'Field size check');
    }
}

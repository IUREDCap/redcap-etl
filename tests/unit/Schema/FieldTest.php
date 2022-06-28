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

    public function testMergeCharAndVarChar()
    {
        $name = 'email';
        $type1 = FieldType::CHAR;
        $size1 = 40;

        $field1 = new Field($name, $type1, $size1);

        $size2 = 60;
        $type2 = FieldType::VARCHAR;
        $field2 = new Field($name, $type2, $size2);

        $mergedField = $field1->merge($field2);

        $this->assertNotNull($mergedField, 'Merged field not null check');

        $this->assertEquals($name, $mergedField->name, 'Field name check');
        $this->assertEquals(FieldType::VARCHAR, $mergedField->type, 'Field type check');
        $this->assertEquals(max($size1, $size2), $mergedField->size, 'Field size check');
    }

    public function testMergeVarCharAndChar()
    {
        $name = 'email';
        $type1 = FieldType::VARCHAR;
        $size1 = 40;

        $field1 = new Field($name, $type1, $size1);

        $size2 = 60;
        $type2 = FieldType::CHAR;
        $field2 = new Field($name, $type2, $size2);

        $mergedField = $field1->merge($field2);

        $this->assertNotNull($mergedField, 'Merged field not null check');

        $this->assertEquals($name, $mergedField->name, 'Field name check');
        $this->assertEquals(FieldType::VARCHAR, $mergedField->type, 'Field type check');
        $this->assertEquals(max($size1, $size2), $mergedField->size, 'Field size check');
    }

    public function testMergeVarCharAndString()
    {
        $name = 'email';
        $type1 = FieldType::VARCHAR;
        $size1 = 40;

        $field1 = new Field($name, $type1, $size1);

        $type2 = FieldType::STRING;
        $field2 = new Field($name, $type2);

        $mergedField = $field1->merge($field2);

        $this->assertNotNull($mergedField, 'Merged field not null check');

        $this->assertEquals($name, $mergedField->name, 'Field name check');
        $this->assertEquals(FieldType::STRING, $mergedField->type, 'Field type check');
        $this->assertEquals(null, $mergedField->size, 'Field size check');
    }

    public function testMergeCharAndString()
    {
        $name = 'email';
        $type1 = FieldType::CHAR;
        $size1 = 40;

        $field1 = new Field($name, $type1, $size1);

        $type2 = FieldType::STRING;
        $field2 = new Field($name, $type2);

        $mergedField = $field1->merge($field2);

        $this->assertNotNull($mergedField, 'Merged field not null check');

        $this->assertEquals($name, $mergedField->name, 'Field name check');
        $this->assertEquals(FieldType::STRING, $mergedField->type, 'Field type check');
        $this->assertEquals(null, $mergedField->size, 'Field size check');
    }

    public function testMergeIntAndFloat()
    {
        $name = 'score';
        $type1 = FieldType::INT;

        $field1 = new Field($name, $type1);

        $type2 = FieldType::FLOAT;
        $field2 = new Field($name, $type2);

        $mergedField = $field1->merge($field2);

        $this->assertNotNull($mergedField, 'Merged field not null check');

        $this->assertEquals($name, $mergedField->name, 'Field name check');
        $this->assertEquals(FieldType::FLOAT, $mergedField->type, 'Field type check');
        $this->assertEquals(null, $mergedField->size, 'Field size check');
    }

    public function testMergeFloatAndInt()
    {
        $name = 'score';
        $type1 = FieldType::FLOAT;

        $field1 = new Field($name, $type1);

        $type2 = FieldType::INT;
        $field2 = new Field($name, $type2);

        $mergedField = $field1->merge($field2);

        $this->assertNotNull($mergedField, 'Merged field not null check');

        $this->assertEquals($name, $mergedField->name, 'Field name check');
        $this->assertEquals(FieldType::FLOAT, $mergedField->type, 'Field type check');
        $this->assertEquals(null, $mergedField->size, 'Field size check');
    }

    public function testNameSetAndGet()
    {
        $name = 'mail';
        $type = FieldType::CHAR;
        $size = 40;

        $field = new Field($name, $type, $size);

        $getName = $field->getName();
        $this->assertEquals($name, $getName, 'Initial get name check');

        $name = 'email';
        $field->setName($name);

        $getName = $field->getName();
        $this->assertEquals($name, $getName, 'After set call get name check');
    }

    public function testMergeWithDifferentDbNames()
    {
        $name = 'email';
        $type = FieldType::CHAR;
        $size = 40;

        $field1 = new Field($name, $type, $size);
        $field2 = new Field($name, $type, $size);
        $field2->dbName = 'e_mail';

        $excetionCaught = false;
        try {
            $mergedField = $field1->merge($field2);
        } catch (\Exception $e) {
            $exceptionCaught = true;
        }
        
        $this->assertTrue($exceptionCaught, 'DbName exception caught check');
    }
}

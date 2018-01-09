<?php

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Logger class.
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

        $expectedValue = '  '.$name.' : '.$type."\n";

        $this->assertEquals($expectedValue, $value, 'toString value check');
    }
}

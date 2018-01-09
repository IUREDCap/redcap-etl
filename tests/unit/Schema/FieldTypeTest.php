<?php

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Logger class.
 */
class FieldTypeTest extends TestCase
{

    public function testIsIntValid()
    {
        $type = FieldType::INT;

        $result = FieldType::isValid($type);

        $this->assertTrue($result, 'int valid type check');
    }

    public function testIsFloatValid()
    {
        $type = FieldType::FLOAT;

        $result = FieldType::isValid($type);

        $this->assertTrue($result, 'float valid type check');
    }

    public function testIsInvalid()
    {
        $type = 'invalid';

        $result = FieldType::isValid($type);

        $this->assertFalse($result, 'invalid type check');
    }
}

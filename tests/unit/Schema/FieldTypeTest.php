<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the FieldType class.
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

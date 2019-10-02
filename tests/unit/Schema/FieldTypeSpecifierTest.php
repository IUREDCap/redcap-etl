<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\EtlException;

/**
 * PHPUnit tests for the FieldTypeSpecifier class.
 */
class FieldTypeSpecifierTest extends TestCase
{

    public function testConstructor()
    {
        $fieldType = FieldType::VARCHAR;
        $fieldSize = 20;

        $fieldTypeSpecifier = new FieldTypeSpecifier($fieldType, $fieldSize);

        $retrievedFieldType = $fieldTypeSpecifier->getType();
        $retrievedFieldSize = $fieldTypeSpecifier->getSize();

        $this->assertEquals($fieldType, $retrievedFieldType, 'Field type check');
        $this->assertEquals($fieldSize, $retrievedFieldSize, 'Field size check');
    }

    public function testCreateWithNullFieldTypeDefinition()
    {
        $caughtException = false;
        try {
            $fieldTypeSpecifier = FieldTypeSpecifier::create(null);
        } catch (EtlException $exception) {
            $this->assertEquals(EtlException::INPUT_ERROR, $exception->getCode(), 'Exception code check');
            $caughtException = true;
        }
        $this->assertTrue($caughtException, 'Caught exception');
    }

    public function testCreateWithNonStringFieldTypeDefinition()
    {
        $caughtException = false;
        try {
            $fieldTypeSpecifier = FieldTypeSpecifier::create(123);
        } catch (EtlException $exception) {
            $this->assertEquals(EtlException::INPUT_ERROR, $exception->getCode(), 'Exception code check');
            $caughtException = true;
        }
        $this->assertTrue($caughtException, 'Caught exception');
    }

    public function testCreateWithBlankFieldTypeDefinition()
    {
        $caughtException = false;
        try {
            $fieldTypeSpecifier = FieldTypeSpecifier::create(' ');
        } catch (EtlException $exception) {
            $this->assertEquals(EtlException::INPUT_ERROR, $exception->getCode(), 'Exception code check');
            $caughtException = true;
        }
        $this->assertTrue($caughtException, 'Caught exception');
    }

    public function testCreateWithInvalidFieldTypeDefinition()
    {
        $caughtException = false;
        try {
            $fieldTypeSpecifier = FieldTypeSpecifier::create('not_a_real_field_type');
        } catch (EtlException $exception) {
            $this->assertEquals(EtlException::INPUT_ERROR, $exception->getCode(), 'Exception code check');
            $caughtException = true;
        }
        $this->assertTrue($caughtException, 'Caught exception');
    }
}

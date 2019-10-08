<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the RowsType class.
 */
class RowsTypeTest extends TestCase
{
    public function testCreateRow()
    {
        $this->assertTrue(RowsType::isValid(RowsType::ROOT));
        $this->assertTrue(RowsType::isValid(RowsType::BY_REPEATING_INSTRUMENTS));

        $this->assertFalse(RowsType::isValid(1000));
    }
}

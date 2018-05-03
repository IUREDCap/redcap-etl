<?php

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

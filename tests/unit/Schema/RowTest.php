<?php

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the Row class.
 */
class RowTest extends TestCase
{
    public function testCreateRow()
    {
        $row = new Row(null);
        $expectedRowData = array('id' => 100, 'name' => 'Bob');
        $expectedRowString = " (id: 100, name: Bob)\n";

        foreach ($expectedRowData as $fieldName => $value) {
            $row->addValue($fieldName, $value);
        }

        $rowData = $row->getData();
        $this->assertEquals($expectedRowData, $rowData, 'row data check');

        $rowString = $row->toString(1);
        $this->assertEquals($expectedRowString, $rowString, 'row string check');
    }
}

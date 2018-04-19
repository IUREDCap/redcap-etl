<?php

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\RedCapEtl;

/**
 * PHPUnit tests for Table class.
 */
class TableTest extends TestCase
{
    public function testCreateTables()
    {
        $name = 'test';
        $parent = 'test_id';
        $rowsType = RowsType::ROOT;
        $suffixes = '';
        $recordIdFieldName = 'recordId';

        $table = new Table($name, $parent, $rowsType, $suffixes, $recordIdFieldName);
        $this->assertNotNull($table, 'table not null');

        $this->assertEquals($name, $table->getName(), 'getName test');

        $childTable = new Table('child', $table, RowsType::BY_EVENTS, '', $recordIdFieldName);
        $this->assertNotNull($childTable, 'child table not null');

        #$expectedRowData = array('id' => 100, 'name' => 'Bob');
        #$expectedRowString = " (id: 100, name: Bob)\n";

        #foreach ($expectedRowData as $fieldName => $value) {
        #    $row->addValue($fieldName, $value);
        #}

        #$rowData = $row->getData();
        #$this->assertEquals($expectedRowData, $rowData, 'row data check');

        #$rowString = $row->toString(1);
        #$this->assertEquals($expectedRowString, $rowString, 'row string check');
    }
}

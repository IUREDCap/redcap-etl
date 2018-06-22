<?php

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the Table class.
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

        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        
        $table = new Table($name, $parent, $keyType, array($rowsType), $suffixes, $recordIdFieldName);
        $this->assertNotNull($table, 'table not null');

        $this->assertEquals($name, $table->getName(), 'getName test');

        $childTable = new Table('child', $table, $keyType, array(RowsType::BY_EVENTS), '', $recordIdFieldName);
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

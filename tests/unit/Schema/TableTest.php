<?php

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\RedCapEtl;

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

        $table = new Table($name, $parent, $keyType, $rowsType, $suffixes, $recordIdFieldName);
        $this->assertNotNull($table, 'table not null');

        $this->assertEquals($name, $table->getName(), 'getName test');

        $suffixes = array(1, 2, 3);
        $childTable = new Table('child', $table, $keyType, RowsType::BY_EVENTS, $suffixes , $recordIdFieldName);
        $this->assertNotNull($childTable, 'child table not null');

<<<<<<< HEAD
        $expectedRowData = array('id' => 100, 'name' => 'Bob');
        $expectedRowString = " (id: 100, name: Bob)\n";
=======
        $childTable->setForeign($table);

        //Create primary key for child table
        $childTable->createPrimary();

        //Add childTable as a child of table
        $table->addChild($childTable);

        $child = $table->getChildren();
        $this->assertNotNull($child, 'child for primary table not null');

        // Haven't added any rows, so count should be zero
        $rowsCount = $table->getNumRows();
        $this->assertEquals(0, $rowsCount);

        $table->addField(new Field('name',FieldType::VARCHAR,20));

        //Add fields to child table
        $fields = array(
          new Field('biology',FieldType::INT,3),
          new Field('chemistry',FieldType::INT,3)
        );

        foreach ($fields as $field) {
          $childTable->addField($field);
        }

        //Create row for parent and child tables
        $parentRows = array(
          $table->createRow(array('name' => 'Bob'),'',''),
          $table->createRow(array('name' => 'Tina'),'',''),
          $table->createRow(array('name' => 'Mark'),'','')
        );

        foreach ($parentRows as $row) {
          // code...
          $table->addRow($row);
        }

        $this->assertCount(3, $table->getRows());

        $childRows = array(
          $childTable->createRow(array('chemistry' => 86, 'biology' => 93),1, ''),
          $childTable->createRow(array('chemistry' => 75, 'biology' => 81),3, '')
        );
        foreach ($childRows as $row) {
          // code...
          $childTable->addRow($row);
        }

        $childTable->getPossibleSuffixes();
        $table->getPossibleSuffixes();

        // Child table should have 2 rows
        $rowsCount = $childTable->getNumRows();
        $this->assertEquals(4, $rowsCount);


        $this->assertCount(4, $childTable->getRows());
        #print_r($childRows);

        //call toString function for both classes and print them
        #$childTable->toString();

        //This causes an error at line 342 in class Table
        #print_r($table->toString());

        #Child table should have a primary key, foreign key from parent table,
        # and two more fields added above
        $this->assertCount(4, $childTable->getAllFields());

        $table->emptyRows();
        #expectedRowData = array('id' => 100, 'name' => 'Bob');
        #$expectedRowString = " (id: 100, name: Bob)\n";
>>>>>>> eba72b217f84bc5d597fd902d258624707bb6eb2

        foreach ($expectedRowData as $fieldName => $value) {
            $row->addValue($fieldName, $value);
        }

        $rowData = $row->getData();
        $this->assertEquals($expectedRowData, $rowData, 'row data check');

        $rowString = $row->toString(1);
        $this->assertEquals($expectedRowString, $rowString, 'row string check');
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TaskConfig;
use IU\REDCapETL\Logger;
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

    public function testCreateRowWithInvalidData()
    {
        $name = 'test';
        $parent = 'test_id';
        $rowsType = RowsType::ROOT;
        $suffixes = '';
        $recordIdFieldName = 'recordId';

        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        
        $table = new Table($name, $parent, $keyType, array($rowsType), $suffixes, $recordIdFieldName);
        $this->assertNotNull($table, 'table not null');

        $data = ['record_id' => 100];
        $foreignKey = null;
        $suffix = null;
        $result = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_REPEATING_INSTRUMENTS);
        $this->assertFalse($result, 'Row with no repeating instrument field');


        $data = [
            'record_id' => 100,
            RedCapEtl::COLUMN_EVENT => 'initial_visit',
            RedCapEtl::COLUMN_REPEATING_INSTRUMENT => 'visit',
            RedCapEtl::COLUMN_REPEATING_INSTANCE => ''
        ];
        $result = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_REPEATING_INSTRUMENTS);
        $this->assertFalse($result, 'Repeating instrument rows type with blank instance');


        $result = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_EVENTS);
        $this->assertFalse($result, 'Events rows type with non-empty repeating instrument');


        $data = [
            'record_id' => 100,
            RedCapEtl::COLUMN_EVENT => 'initial_visit',
            RedCapEtl::COLUMN_REPEATING_INSTANCE => '1'
        ];
        $result = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_EVENTS);
        $this->assertFalse($result, 'Events rows type with non-emtpy repeating instance');


        $data = [
            'record_id' => 100,
            RedCapEtl::COLUMN_REPEATING_INSTRUMENT => 'visit',
            RedCapEtl::COLUMN_REPEATING_INSTANCE => '1'
        ];
        $result = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_REPEATING_EVENTS);
        $this->assertFalse($result, 'Repeating Events rows type with no events column');


        $data = [
            'record_id' => 100,
            RedCapEtl::COLUMN_EVENT => 'initial_visit',
            RedCapEtl::COLUMN_REPEATING_INSTRUMENT => 'visit',
            RedCapEtl::COLUMN_REPEATING_INSTANCE => '1'
        ];
        $result = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_REPEATING_EVENTS);
        $this->assertFalse($result, 'Repeating Events rows type with non-empty repeating instrument');


        $data = [
            'record_id' => 100,
            RedCapEtl::COLUMN_EVENT => '',
            RedCapEtl::COLUMN_REPEATING_INSTRUMENT => '',
            RedCapEtl::COLUMN_REPEATING_INSTANCE => '1'
        ];
        $result = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_REPEATING_EVENTS);
        $this->assertFalse($result, 'Repeating Events rows type with empty event');


        $data = [
            'record_id' => 100,
            RedCapEtl::COLUMN_EVENT => 'visit',
            RedCapEtl::COLUMN_REPEATING_INSTRUMENT => '',
            RedCapEtl::COLUMN_REPEATING_INSTANCE => ''
        ];
        $result = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_REPEATING_EVENTS);
        $this->assertFalse($result, 'Repeating Events rows type with empty instance');
    }


    public function testCreateRowWithValidData()
    {
        $logger = new Logger('test');

        $taskConfig = new TaskConfig();
        $taskConfig->set($logger, __DIR__.'/../../data/config-test.ini');
        $name = 'test';
        $parent = 'test_id';
        $rowsType = RowsType::ROOT;
        $suffixes = '';
        $recordIdFieldName = 'record_id';

        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        
        $rootTable = new Table($name, $parent, $keyType, array($rowsType), $suffixes, $recordIdFieldName);
        $this->assertNotNull($rootTable, 'Root table not null');

        $rowsType = array(RowsType::BY_REPEATING_INSTRUMENTS);
        $table = new Table('child', $rootTable, $keyType, $rowsType, $suffixes, $recordIdFieldName);
        $this->assertNotNull($table, 'Child table not null');


        # Create EVENT field
        $fieldTypeSpecifier = $taskConfig->getGeneratedNameType();
        $field = new Field(RedCapEtl::COLUMN_EVENT, $fieldTypeSpecifier->getType(), $fieldTypeSpecifier->getSize());
        $table->addField($field);

        # Create REPEATING_INSTRUMENT field
        $fieldTypeSpecifier = $taskConfig->getGeneratedInstanceType();
        $field = new Field(
            RedCapEtl::COLUMN_REPEATING_INSTRUMENT,
            $fieldTypeSpecifier->getType(),
            $fieldTypeSpecifier->getSize()
        );
        $table->addField($field);

        # Create REPEATING_INSTANCE field
        $fieldTypeSpecifier = $taskConfig->getGeneratedInstanceType();
        $field = new Field(
            RedCapEtl::COLUMN_REPEATING_INSTANCE,
            $fieldTypeSpecifier->getType(),
            $fieldTypeSpecifier->getSize()
        );
        $table->addField($field);


        $field = new Field(
            'weight',
            FieldType::INT,
            null
        );
        $table->addField($field);

        $expectedPrimaryKey = 1;
        $data = [
            'child_id' => 100,
            RedCapEtl::COLUMN_EVENT => 'initial_visit',
            RedCapEtl::COLUMN_REPEATING_INSTRUMENT => 'visit',
            RedCapEtl::COLUMN_REPEATING_INSTANCE => '1',
            'weight' => 77
        ];
        $foreignKey = null;
        $suffix = null;
        $primaryKey = $table->createRow($data, $foreignKey, $suffix, RowsType::BY_REPEATING_INSTRUMENTS);
        $this->assertEquals($expectedPrimaryKey, $primaryKey, 'Primary key check');
    }
}

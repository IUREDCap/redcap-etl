<?php

namespace IU\REDCapETL\Schema;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\RedCapEtl;

/**
 * PHPUnit tests for the Schema class.
 */
class SchemaTest extends TestCase
{
    public function testCreateSchema()
    {
        $schema = new Schema();
        $this->assertNotNull($schema, 'schema not null');

        $expectedSchemaString = "Number of tables: 0\n"
            ."Number of root tables: 0\n"
            ."\n"
            ."Root tables\n"
            ."\n"
            ."Tables\n"
            ;

        $schemaString = $schema->toString();
        $this->assertEquals($expectedSchemaString, $schemaString, 'schema to string check');

        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
      
        $rootTable = new Table('root', 'root_id', $keyType, RowsType::ROOT, array(), 'record_id');
        $table     = new Table('events', $rootTable, $keyType, RowsType::BY_EVENTS, array(), 'record_id');
        $schema->addTable($rootTable);
        $schema->addTable($table);

        $expectedRootTables = array($rootTable);
        $expectedTables     = array($rootTable, $table);

        $rootTables = $schema->getRootTables();
        $this->assertEquals($expectedRootTables, $rootTables, 'root tables check');

        $tables = $schema->getTables();
        $this->assertEquals($expectedTables, $tables, 'tables check');

        $eventsTable = $schema->getTable('events');
        $this->assertEquals($table, $eventsTable, 'single table check');

        $nonExistentTableName = 'not_a_table';
        $table = $schema->getTable($nonExistentTableName);
        $this->assertEquals($nonExistentTableName, $table, 'single table check');

        $schemaString = $schema->toString(0);
        #$this->assertContains('root', $schemaString, 'root in schema string');
        #$this->assertContains('events', $schemaString, 'events in schema string');
    }
}

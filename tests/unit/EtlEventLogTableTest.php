<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\Schema\Field;

/**
* PHPUnit tests for the EtlEventLogTable class
*/

class EtlEventLogTableTest extends TestCase
{
    public function testConstructor()
    {
        $name = "etlEventLog";

        #test object creation
        $etlEventLogTable = new EtlEventLogTable($name);
        $this->assertNotNull($etlEventLogTable, 'etlEventLogTable object not null check');

        #test table name correctly
        $this->assertEquals($name, $etlEventLogTable->getName(), 'etlEventLogTable name check');

        #test fields added correctly
        $expectedCount = 3;
        $expectedName = ['log_id','time','message'];
        $expectedType = ['int','datetime','string'];
        $i = 0;
        $fieldsOk = true;
        foreach ($etlEventLogTable->getFields() as $field) {
            if (($field->name !== $expectedName[$i]) || ($field->type !== $expectedType[$i])) {
                $fieldsOk = false;
            }
            $i++;
        }
        $this->assertEquals($expectedCount, $i, 'etlEventLogTable field count check');
        $this->assertTrue($fieldsOk, 'etlEventLogTable fields ok check');
    }

    public function testCreateEventLogDataRowTest()
    {
        $now = time();
        $tableName = "etlEventLog";
        $etlEventLogTable = new EtlEventLogTable($tableName);

        $logId = 123;
        $message = 'This is an event log message.';

        $expectedTableParent = 'log_event_id';
        $expectedPrimaryName = 'log_event_id';
        $expectedPrimaryType = 'auto_increment';
        $expectedPrimaryDbName = 'log_event_id';

        $result = $etlEventLogTable->createEventLogDataRow($logId, $message);

        $this->assertEquals($tableName, $result->table->getName(), 'testCreateEventLogDataRow table name check');
        $this->assertEquals(
            $expectedTableParent,
            $result->table->parent,
            'testCreateEventLogDataRow table parent check'
        );
        $this->assertEquals(
            $expectedPrimaryName,
            $result->table->primary->name,
            'testCreateEventLogDataRow primary name check'
        );
        $this->assertEquals(
            $expectedPrimaryType,
            $result->table->primary->type,
            'testCreateEventLogDataRow primary type check'
        );
        $this->assertEquals(
            $expectedPrimaryDbName,
            $result->table->primary->dbName,
            'testCreateEventLogDataRow primary dbName check'
        );
        $this->assertEquals($logId, $result->data['log_id'], 'testCreateEventLogDataRow data1 check');
        $this->assertGreaterThanOrEqual(
            $now,
            strtotime($result->data['time']),
            'testCreateEventLogDataRow data2 check'
        );
        $this->assertEquals($message, $result->data['message'], 'testCreateEventLogDataRow data3 check');
    }
}

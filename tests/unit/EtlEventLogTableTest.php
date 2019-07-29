<?php

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
        $this->assertEquals($name, $etlEventLogTable->name, 'etlEventLogTable name check');

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

    public function createEventLogDataRowTest()
    {
        $name = "etlEventLog";
        $etlEventLogTable = new EtlEventLogTable($name);

        $logId = 123;
        $message = 'This is an event log message.';
        $result = $etlEventLogTable->createEventLogDataRow($logId, $message);
        $this->assertNull($result, 'addLookupField return check');
        print "printing row: ";
        print_r($result);
    }
}

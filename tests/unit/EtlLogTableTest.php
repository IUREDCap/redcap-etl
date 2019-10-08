<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\Schema\Field;

/**
* PHPUnit tests for the EtlLogTable class
*/

class EtlLogTableTest extends TestCase
{
    public function testConstructor()
    {
        $name = "etlLog";

        #test object creation
        $etlLogTable = new EtlLogTable($name);
        $this->assertNotNull($etlLogTable, 'etlLogTable object not null check');

        #test table name correctly
        $this->assertEquals($name, $etlLogTable->name, 'etlLogTable name check');

        #test fields added correctly
        $expectedCount = 8;
        $expectedSize = [6, 60,60,'',10,40,40,''];
        $expectedName = [
            'start_time'
            ,'app'
            ,'table_prefix'
            ,'batch_size'
            ,'redcap_etl_version'
            ,'php_version'
            ,'timezone'
            ,'utc_offset'
        ];
        $expectedType = [
            'datetime'
            ,'varchar'
            ,'varchar'
            ,'int'
            ,'char'
            ,'varchar'
            ,'varchar'
            ,'int'
        ];
        $expectedSize = [6, 60,60,'',10,40,40,''];
        $i = 0;
        $fieldsOk = true;
        foreach ($etlLogTable->getFields() as $field) {
            if (($field->name !== $expectedName[$i])
                || ($field->type !== $expectedType[$i])
                || ($field->size != $expectedSize[$i])) {
                $fieldsOk = false;
            }
            $i++;
        }
        $this->assertEquals($expectedCount, $i, 'etlLogTable field count check');
        $this->assertTrue($fieldsOk, 'etlLogTable fields ok check');
    }

    public function testCreateLogDataRowTest()
    {
        $now = time();
        $tableName = "etlLog2";
        $etlLogTable = new EtlLogTable($tableName);

        $app = "app1";
        $tablePrefix = "helpMe";
        $batchSize = "333";
        $result = $etlLogTable->createLogDataRow($app, $tablePrefix, $batchSize);
        $this->assertNotNull($result, 'testCreateLogDataRow return check');

        $expectedTableParent = 'log_id';
        $expectedPrimaryName = 'log_id';
        $expectedPrimaryDbName = 'log_id';
        $expectedPrimaryType = 'auto_increment';

        $this->assertEquals($tableName, $result->table->name, 'testCreateLogDataRow table name check');
        $this->assertEquals($expectedTableParent, $result->table->parent, 'testCreateLogDataRow table parent check');
        $this->assertEquals(
            $expectedPrimaryName,
            $result->table->primary->name,
            'testCreateLogDataRow primary name check'
        );
        $this->assertEquals(
            $expectedPrimaryType,
            $result->table->primary->type,
            'testCreateLogDataRow primary type check'
        );
        $this->assertEquals(
            $expectedPrimaryDbName,
            $result->table->primary->dbName,
            'testCreateLogDataRow primary dbName check'
        );
        $this->assertGreaterThanOrEqual(
            $now,
            strtotime($result->data['start_time']),
            'testCreateLogDataRow data1 check'
        );
        $this->assertEquals($app, $result->data['app'], 'testCreateLogDataRow data2 check');
        $this->assertEquals($tablePrefix, $result->data['table_prefix'], 'testCreateLogDataRow data3 check');
        $this->assertEquals($batchSize, $result->data['batch_size'], 'testCreateLogDataRow data4 check');
        $this->assertNotNull($result->data['redcap_etl_version'], 'testCreateLogDataRow data5 check');
        $this->assertNotNull($result->data['php_version'], 'testCreateLogDataRow data6 check');
        $this->assertNotNull($result->data['timezone'], 'testCreateLogDataRow data7 check');
        $this->assertNotNull($result->data['utc_offset'], 'testCreateLogDataRow data8 check');
    }
}

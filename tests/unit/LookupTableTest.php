<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\EtlException;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\FieldTypeSpecifier;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\Table;

/**
* PHPUnit tests for the LookupTable class.
*/

class LookupTableTest extends TestCase
{
    public function testConstructor()
    {
        $lookupChoices = [
            "sex" => ['female', 'male']
        ];
        $tablePrefix = null;
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $lookupTable = new LookupTable($lookupChoices, $tablePrefix, $keyType);

        #test object creation
        $lookupTable = new LookupTable($lookupChoices, $tablePrefix, $keyType);
        $this->assertNotNull($lookupTable, 'lookupTable object not null check');
    }

    /**
    * To verify the map part of the addLookupField method, the getLabelMap
    * method needs to be invoked, so testing for both addLookupField
    * and getLabelMap have been combined into one test.
    */
    public function testAddLookupFieldAndGetLabelMap()
    {
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);

        # create the lookup-table object that has the label values
        $lookupChoices = [
            "marital_status" => ['single', 'married', 'widowed', 'divorced']
        ];
        $tablePrefix = null;
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $lookupTable = new LookupTable($lookupChoices, $tablePrefix, $keyType);

        #test addLookupField
        $tableName = 'addLookupTest';
        $fieldName = 'marital_status';
        $result = $lookupTable->addLookupField($tableName, $fieldName);
        $this->assertNull($result, 'addLookupField return check');

        $rows = $lookupTable->getRows();
        $expectedLabel = ['single','married','widowed','divorced'];

        $i = 0;
        $contentsOk = true;
        foreach ($rows as $row) {
            $rowData = $row->getData();

            if ($rowData['table_name'] !== $tableName) {
                $contentsOk = false;
                #print "for i  $i, tableName fail" . PHP_EOL;
            } elseif ($rowData['field_name'] !== $fieldName) {
                $contentsOk = false;
                #print "for i  $i, fieldName fail" . PHP_EOL;
            } elseif ($rowData['value'] != $i) {
                $contentsOk = false;
                #print "for i  $i, value fail" . PHP_EOL;
            } elseif ($rowData['label'] !== $expectedLabel[$i]) {
                $contentsOk = false;
                #print "for i  $i, label fail" . PHP_EOL;
            } elseif ($rowData['lookup_id'] !== $i+1) {
                $contentsOk = false;
                #print "for i  $i, lookup id fail" . PHP_EOL;
            }
            $i++;
        }
        $this->assertTrue($contentsOk, 'addLookupField fields added check');

        #check map update
        $expectedMap = ['single', 'married', 'widowed', 'divorced'];
        $valueLabelMap = $lookupTable->getValueLabelMap($tableName, $fieldName);
        $this->assertEquals($expectedMap, $valueLabelMap, 'addLookupField map updated check');
    }

    public function testGetLabel()
    {
        $lookupChoices = [
            "sex" => ['female', 'male']
        ];
        $tablePrefix = null;
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $lookupTable = new LookupTable($lookupChoices, $tablePrefix, $keyType);

        $tableName = 'testGetLabelTest';
        $fieldName = 'sex';
        $lookupTable->addLookupField($tableName, $fieldName);

        #run test
        $testValues = ['',0,1];
        $expectedLabels = ['','female','male'];
        $i=0;
        $labelsOk = true;
        foreach ($testValues as $testValue) {
            $label = $lookupTable->getLabel($tableName, $fieldName, $testValue);
            if ($label !== $expectedLabels[$i]) {
                $labelsOk = false;
            }
            $i++;
        }
        $this->assertTrue($labelsOk, 'getLabel check');
    }
}

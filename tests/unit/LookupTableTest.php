<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\FieldTypeSpecifier;

/**
* PHPUnit tests for the LookupTable class
*/

class LookupTableTest extends TestCase
{
    public function testConstructor()
    {
        $name = "lookup";

        #test object creation
        $lookupTable = new LookupTable(array(), '', new FieldTypeSpecifier(FieldType::INT), $name);
        $this->assertNotNull($lookupTable, 'lookupTable object not null check');

        $this->assertEquals($name, $lookupTable->getName(), 'lookupTable name check');
    }

    public function testRowsAdded()
    {
        $name = "lookup";
        $lookupChoices = [
            'gender' => ['0' => 'male', '1' => 'female'],
            'race' => [
                '0' => 'American Indian/Alaska Native',
                '1' => 'Asian',
                '2' => 'Native Hawaiian or Other Pacific Islander',
                '3' => 'Black or African American',
                '4' => 'White',
                '5' => 'Other'
            ]
        ];
        $lookupTable = new LookupTable($lookupChoices, '', new FieldTypeSpecifier(FieldType::INT), $name);
        $this->assertNotNull($lookupTable, 'lookupTable object not null check');

        $lookupTable->addLookupField('test', 'gender');
        $lookupTable->addLookupField('test', 'race');

        $expectedMap = ['test' => $lookupChoices];

        $this->assertEquals($expectedMap, $lookupTable->getMap(), 'Lookup table map check');

        # print_r($lookupTable->getMap());
    }
}

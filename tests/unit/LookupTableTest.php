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
        $lookupTable = new LookupTable(array(), new FieldTypeSpecifier(FieldType::INT), $name);
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
        $lookupTable = new LookupTable($lookupChoices, new FieldTypeSpecifier(FieldType::INT), $name);

        $this->assertNotNull($lookupTable, 'lookupTable object not null check');

        $lookupTable->addLookupField('test', 'gender');
        $lookupTable->addLookupField('test', 'race');

        $expectedMap = ['test' => $lookupChoices];

        $this->assertEquals($expectedMap, $lookupTable->getMap(), 'Lookup table map check');

        # print_r($lookupTable->getMap());
    }

    public function testMerge()
    {
        $name1 = "lookup";
        $lookupChoices1 = [
            'gender' => ['0' => 'male', '1' => 'female']
        ];
        $lookupTable1 = new LookupTable($lookupChoices1, new FieldTypeSpecifier(FieldType::INT), $name1);
        $lookupTable1->addLookupField('info', 'gender');
        $this->assertNotNull($lookupTable1, 'lookupTable1 object not null check');

        $name2 = "lookup";
        $lookupChoices2 = [
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
        $lookupTable2 = new LookupTable($lookupChoices2, new FieldTypeSpecifier(FieldType::INT), $name2);
        $lookupTable2->addLookupField('demographics', 'race');
        $lookupTable2->addLookupField('info', 'gender');
        $this->assertNotNull($lookupTable2, 'lookupTable2 object not null check');

        $name3 = "lookup";
        $lookupChoices3 = [
            'rating' => ['0' => 'poor', '1' => 'fair', '2' => 'good']
        ];
        $lookupTable3 = new LookupTable($lookupChoices3, new FieldTypeSpecifier(FieldType::INT), $name3);
        $lookupTable3->addLookupField('assesment', 'rating');
        $this->assertNotNull($lookupTable3, 'lookupTable3 object not null check');

        $mergedLookupTable = $lookupTable1->merge($lookupTable2);
        $mergedLookupTable = $mergedLookupTable->merge($lookupTable3);
        $this->assertNotNull($mergedLookupTable, 'mergedLookupTable object not null check');

        #print "\n\n\n";
        #print $mergedLookupTable->toString();
    }
}

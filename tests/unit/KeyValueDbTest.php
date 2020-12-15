<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\PhpCapException;

use IU\REDCapETL\TestProject;

class KeyValueDbTest extends TestCase
{
    private $project;
    private static $customLog;

    public function testKeys()
    {
        KeyValueDb::initialize();

        $db = 'testdb';
        $table = 'enrollment';

        $key = KeyValueDb::getNextKeyValue($db, $table);
        $this->assertEquals(1, $key, 'First key check');

        $key = KeyValueDb::getNextKeyValue($db, $table);
        $this->assertEquals(2, $key, 'Second key check');

        $expectedKeyValues = [$db => [$table => 2]];
        $keyValues = KeyValueDb::getKeyValues();
        $this->assertEquals($expectedKeyValues, $keyValues, 'Key values check');
    }
}

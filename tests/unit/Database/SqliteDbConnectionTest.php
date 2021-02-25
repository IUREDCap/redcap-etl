<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\EtlException;

/**
 * PHPUnit tests for the SqliteDbConnection class.
 * (Unable to test error condition.)
 */

class SqliteDbConnectionTest extends TestCase
{
    public function testConstructor()
    {
        $dbString = '/notarealdirectory/test.db';

        $exceptionCaught = false;
        try {
            $sqliteDbConnection = SqliteDbConnection::getPdoConnection($dbString);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor exception caught check'
        );
    }
}

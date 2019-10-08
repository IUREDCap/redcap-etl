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
        $ssl = null;
        $sslVerify = null;
        $caCertFile = null;
        $labelViewSuffix = null;
        $tablePrefix = null;
        $dbString = './tests/output/sqliteTest.db';

        $sqliteDbConnection = new SqliteDbConnection(
            $dbString,
            $ssl,
            $sslVerify,
            $caCertFile,
            $tablePrefix,
            $labelViewSuffix
        );

        $this->assertNotNull(
            $sqliteDbConnection,
            'sqliteDbConnection object created successfully check'
        );
    }
}

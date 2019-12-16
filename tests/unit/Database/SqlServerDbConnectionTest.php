<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\Configuration;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Logger;

/**
* PHPUnit tests for the SqlServerDbConnection class.
* Only a part of the constructor is unit tested.
* The remainder of the constructor and all of the
* methods are tested in a system test.
*/

class SqlServerDbConnectionTest extends TestCase
{
    private $expectedCode = EtlException::DATABASE_ERROR;
    private $ssl = null;
    private $sslVerify = null;
    private $caCertFile = null;
    private $labelViewSuffix = null;
    private $tablePrefix = null;

    public function testConstructor()
    {
        $message = 'The database connection is not correctly formatted: ';

        #############################################################
        #test object creation with too few connection parameters
        #############################################################
        $exceptionCaught1 = false;
        $expectedMessage1 = $message . 'not enough values.';
        $dbString = 'sqlsrv:(local)';
        try {
            $sqlServerDbConnection = new SqlServerDbConnection(
                $dbString,
                $this->ssl,
                $this->sslVerify,
                $this->caCertFile,
                $this->tablePrefix,
                $this->labelViewSuffix
            );
        } catch (EtlException $exception) {
            $exceptionCaught1 = true;
        }

        $this->assertTrue(
            $exceptionCaught1,
            'SqlServerDbConnection too few connection string values exception caught'
        );
        $this->assertEquals(
            $this->expectedCode,
            $exception->getCode(),
            'SqlServerDbConnection too few connection string values code check'
        );
        $this->assertEquals(
            $expectedMessage1,
            $exception->getMessage(),
            'SqlServerDbConnection too few connection string values errer message check'
        );

        #############################################################
        #test object creation with too many connection parameters
        #############################################################
        $dbString2 = '(local):tester:magicpassword:adatabase:aport:extraneous';
        $exceptionCaught2 = false;
        $expectedMessage2 = $message . 'too many values.';
        try {
            $sqlServerDbConnection = new SqlServerDbConnection(
                $dbString2,
                $this->ssl,
                $this->sslVerify,
                $this->caCertFile,
                $this->tablePrefix,
                $this->labelViewSuffix
            );
        } catch (EtlException $exception) {
            $exceptionCaught2 = true;
        }

        $this->assertTrue(
            $exceptionCaught2,
            'SqlServerDbConnection too many connection string values exception caught'
        );
        $this->assertEquals(
            $this->expectedCode,
            $exception->getCode(),
            'SqlServerDbConnection too many connection string values code check'
        );
        $this->assertEquals(
            $expectedMessage2,
            $exception->getMessage(),
            'SqlServerDbConnection too many connection string values errer message check'
        );
    }
}

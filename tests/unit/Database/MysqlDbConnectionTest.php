<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\EtlException;

/**
* PHPUnit tests for the MysqlDbConnection class.
* Only a part of the constructor is unit tested.
* The remainder of the constructor and all of the
* methods are tested in an integration test.
*/

class MysqlDbConnectionTest extends TestCase
{
    private $expectedCode = EtlException::DATABASE_ERROR;
    private $sslVerify = null;
    private $caCertFile = null;
    private $labelViewSuffix = null;
    private $tablePrefix = null;

    public function testConstructorInvalidNumberOfParameters()
    {
        $ssl = null;
        $message = 'The database connection is not correctly formatted: ';

        #############################################################
        #test object creation with too few connection parameters
        #############################################################
        $exceptionCaught1 = false;
        $expectedMessage1 = $message . 'not enough values.';
        $dbString = 'dbhost.somewhere.edu:tester';
        try {
            $mysqlDbConnection = new MysqlDbConnection(
                $dbString,
                $ssl,
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
            'mysqlDbConnection too few connection string values exception caught'
        );
        $this->assertEquals(
            $this->expectedCode,
            $exception->getCode(),
            'mysqlDbConnection too few connection string values code check'
        );
        $this->assertEquals(
            $expectedMessage1,
            $exception->getMessage(),
            'mysqlDbConnection too few connection string values errer message check'
        );

        #############################################################
        #test object creation with too many connection parameters
        #############################################################
        $dbString = 'dbhost.somewhere.edu:tester:magicpassword:adatabase:aport:extraneous';
        $exceptionCaught2 = false;
        $expectedMessage2 = $message . 'too many values.';
        try {
            $mysqlDbConnection = new MysqlDbConnection(
                $dbString,
                $ssl,
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
            'mysqlDbConnection too many connection string values exception caught'
        );
        $this->assertEquals(
            $this->expectedCode,
            $exception->getCode(),
            'mysqlDbConnection too many connection string values code check'
        );
        $this->assertEquals(
            $expectedMessage2,
            $exception->getMessage(),
            'mysqlDbConnection too many connection string values errer message check'
        );
    }

    public function testConstructorErrorCondition()
    {
        $ssl = true;
        $dbString3 = 'someserver.here.there.edu:idonotexist:somewonderfulpassword:adb';
        $exceptionCaught3 = false;
        $expectedMessage3 = "Name or service not known";
        $mysqlDbConnection = null;

        try {
            $mysqlDbConnection = new MysqlDbConnection(
                $dbString3,
                $ssl,
                $this->sslVerify,
                $this->caCertFile,
                $this->tablePrefix,
                $this->labelViewSuffix
            );
        } catch (EtlException $exception) {
            $exceptionCaught3 = true;
        }

        $this->assertTrue(
            $exceptionCaught3,
            'mysqlDbConnection expected error for invalid user exception caught'
        );

        $this->assertEquals(
            $this->expectedCode,
            $exception->getCode(),
            'mysqlDbConnection expected error for invalid user code check'
        );

        $this->assertStringEndsWith(
            $expectedMessage3,
            $exception->getMessage(),
            'mysqlDbConnection expected error for invalid user message check'
        );
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\EtlException;

/**
 * PHPUnit tests for the DbConnectionFactory class.
 * Some of the tests for this class are in the
 * DatabasesTest integration test.
 */
class DbConnectionFactoryTest extends TestCase
{
    public function testCreateDbConnection()
    {
        ###test CSV
        $dbConnectionFactoryCsv = new DbConnectionFactory();

        $connectionString = 'CSV:./tests/output/';
        $ssl = null;
        $sslVerify = null;
        $caCertFile = null;
        $labelViewSuffix = null;
        $tablePrefix = null;
        
        $dbConnectionFactoryCsv->createDbConnection(
            $connectionString,
            $ssl,
            $sslVerify,
            $caCertFile,
            $tablePrefix,
            $labelViewSuffix
        );

        $this->assertNotNull(
            $dbConnectionFactoryCsv,
            'DbConnectionFactoryTest createDbConnection CSV check'
        );

        ###test exception
        $dbConnectionFactory = new DbConnectionFactory();

        $connectionString = 'other:nonsense';
        $ssl = null;
        $sslVerify = null;
        $caCertFile = null;
        $labelViewSuffix = null;
        $tablePrefix = null;
  
        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Invalid database type: "other". Valid types are: CSV, MySQL and SQLite.';
        
        try {
            $dbConnectionFactory->createDbConnection(
                $connectionString,
                $ssl,
                $sslVerify,
                $caCertFile,
                $tablePrefix,
                $labelViewSuffix
            );
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'DbConnectionFactoryTest createDbConnection exception caught'
        );

        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'DbConnectionFactoryTest createDbConnection exception error code check'
        );

        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'DbConnectionFactoryTest createDbConnection exception error message check'
        );
    }

    public function testParseConnectionString()
    {
        $expectedDbType   = 'CSV';
        $expectedDbString = '/tmp/';
        $connectionString = $expectedDbType.':'.$expectedDbString;
        
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($connectionString);
        
        $this->assertEquals($expectedDbType, $dbType);
        $this->assertEquals($expectedDbString, $dbString);
    }

    public function testCreateConnectionString()
    {
        $expectedDbType   = 'CSV';
        $expectedDbString = '/tmp/';
        $expectedConnectionString = "$expectedDbType:$expectedDbString";

        $connectionString = DbConnectionFactory::createConnectionString(
            $expectedDbType,
            $expectedDbString
        );

        $this->assertEquals($expectedConnectionString, $connectionString);
    }
}

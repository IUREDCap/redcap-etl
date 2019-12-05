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
* methods are tested in an integration test.
*/

class SqlServerDbConnectionTest extends TestCase
{
    private $expectedCode = EtlException::DATABASE_ERROR;
    private $sslVerify = null;
    private $caCertFile = null;
    private $labelViewSuffix = null;
    private $tablePrefix = null;

    public function testConstructor()
    {
        $ssl = null;
        $message = 'The database connection is not correctly formatted: ';

        #############################################################
        #test object creation with too few connection parameters
        #############################################################
        $exceptionCaught1 = false;
        $expectedMessage1 = $message . 'not enough values.';
        $dbString = '(local)';
        try {
            $sqlServerDbConnection = new SqlServerDbConnection(
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

        #############################################################
        #test error condition
        #############################################################
        $ssl = true;
        $dbString3 = 'localhost:idonotexist:somewonderfulpassword:adb';
        $exceptionCaught3 = false;
        #Checking for only the first part of the message because the error text will be different
        #depending on whether SQL Server is running or not.
        $expectedMessage3 = 'Database connection error for database "adb": SQLSTATE[';
        $sqlServerDbConnection = null;

        try {
            $sqlServerDbConnection = new SqlServerDbConnection(
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
            'SqlServerDbConnection expected error exception caught'
        );

        $this->assertEquals(
            $this->expectedCode,
            $exception->getCode(),
            'SqlServerDbConnection expected error exception code check'
        );

        $this->assertEquals(
            $expectedMessage3,
            substr($exception->getMessage(), 0, strlen($expectedMessage3)),
            #$exception->getMessage(),
            'SqlServerDbConnection expected error exception message check'
        );

        #############################################################
        #test object is created with valid configuration
        #############################################################
        $ssl = false;
        $logger = new Logger('sql_server_connect_test');
        $configFile = __DIR__.'/../../config/sqlserver.ini';
        $configuration = new Configuration($logger, $configFile);
        $dbConnection = $configuration->getDbConnection();

        $sqlServerDbConnection4 = new SqlServerDbConnection(
            $dbConnection,
            $ssl,
            $this->sslVerify,
            $this->caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );
  
        $this->assertNotNull(
            $sqlServerDbConnection4,
            'SqlServerDbConnection object created check'
        );
    }
}

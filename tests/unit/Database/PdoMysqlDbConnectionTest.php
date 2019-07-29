<?php

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;

/**
* PHPUnit tests for the PdoMysqlDbConnection class.
*/

class PdoMysqlDbConnectionTest extends TestCase
{
    public function testConstructor()
    {
        $ssl = null;
        $sslVerify = null;
        $caCertFile = null;
        $labelViewSuffix = null;
        $tablePrefix = null;
        $message = 'The database connection is not correctly formatted: ';

        #test object creation with too few connection parameters
        $exceptionCaught1 = false;
        $expectedCode1 = EtlException::DATABASE_ERROR;
        $expectedMessage1 = $message . 'not enough values.';
        $dbString = 'dbhost.somewhere.edu:tester';
        try {
            $pdoMysqlDbConnection = new PdoMysqlDbConnection(
                $dbString,
                $ssl,
                $sslVerify,
                $caCertFile,
                $tablePrefix,
                $labelViewSuffix
            );
        } catch (EtlException $exception) {
            $exceptionCaught1 = true;
        }

        $this->assertTrue(
            $exceptionCaught1,
            'PdoMysqlsDbConnection too few connection string values exception caught'
        );
        $this->assertEquals(
            $expectedCode1,
            $exception->getCode(),
            'PdoMysqlsDbConnection too few connection string values code check'
        );
        $this->assertEquals(
            $expectedMessage1,
            $exception->getMessage(),
            'PdoMysqlsDbConnection too few connection string values errer message check'
        );


        #test object creation with too many connection parameters
        $dbString = 'dbhost.somewhere.edu:tester:magicpassword:adatabase:aport:extraneous';
        $exceptionCaught2 = false;
        $expectedCode2 = EtlException::DATABASE_ERROR;
        $expectedMessage2 = $message . 'too many values.';
        try {
            $pdoMysqlDbConnection = new PdoMysqlDbConnection(
                $dbString,
                $ssl,
                $sslVerify,
                $caCertFile,
                $tablePrefix,
                $labelViewSuffix
            );
        } catch (EtlException $exception) {
            $exceptionCaught2 = true;
        }

        $this->assertTrue(
            $exceptionCaught2,
            'PdoMysqlsDbConnection too many connection string values exception caught'
        );
        $this->assertEquals(
            $expectedCode2,
            $exception->getCode(),
            'mysqlsDbConnection too many connection string values code check'
        );
        $this->assertEquals(
            $expectedMessage2,
            $exception->getMessage(),
            'PdoMysqlsDbConnection too many connection string values errer message check'
        );


/*
        # test with 4 connection parameters
        $dbString = 'dbhost.somewhere.edu:tester:magicpassword:adatabase';
        $exceptionCaught3 = false;
        $expectedCode3 = EtlException::DATABASE_ERROR;
        $expectedMessage3 = 'MySQL error [';
        try {
            $mysqlDbConnection = new MysqlDbConnection(
                $dbString,
                $ssl,
                $sslVerify,
                $caCertFile,
                $tablePrefix,
                $labelViewSuffix
            );
        } catch (EtlException $exception) {
            $exceptionCaught3 = true;
        }

        $this->assertTrue(
            $exceptionCaught3,
            'mysqlsDbConnection expected error for four parameters exception caught'
        );
 /*       $this->assertEquals(
            $expectedCode3,
            $exception->getCode(),
            'mysqlsDbConnection expected error for four parameters code check'
        );
        $this->assertEquals(
            $expectedMessage3,
            $exception->getMessage(),
            'mysqlsDbConnection expected error for four parameters message check'
        );


        # test with 5 connection parameters
        $dbString = 'dbhost.somewhere.edu:tester:magicpassword:adatabase:9999';
        $exceptionCaught4 = false;
        $expectedCode4 = EtlException::DATABASE_ERROR;
        $expectedMessage4 = 'MySQL error [';
        try {
            $mysqlDbConnection = new MysqlDbConnection(
                $dbString,
                $ssl,
                $sslVerify,
                $caCertFile,
                $tablePrefix,
                $labelViewSuffix
            );
        } catch (EtlException $exception) {
            $exceptionCaught4 = true;
        }

        $this->assertTrue(
            $exceptionCaught4,
            'mysqlsDbConnection expected error for five parameters exception caught'
        );
        $this->assertEquals(
            $expectedCode4,
            $exception->getCode(),
            'mysqlsDbConnection expected error for five parameters code check'
        );
        $this->assertEquals(
            $expectedMessage4,
            $exception->getMessage(),
            'mysqlsDbConnection expected error for five parameters message check'
        );
*/
    }
}

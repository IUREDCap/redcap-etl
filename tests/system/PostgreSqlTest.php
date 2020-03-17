<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\Configuration;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Logger;

/**
* System tests for PostgreSqlDbConnection class.
*/

class PostgreSqlTest extends TestCase
{
    /**
     */
    public function testConnectionWithTooFewArguments()
    {
        $dbString   = 'localhost:non_existent_usert:invalid_password';
        $ssl        = false;
        $sslVerify  = false;
        $caCertFile = null;

        $exceptionCaught = false;

        $postgreSqlDbConnection = null;

        try {
            $postgreSqlDbConnection = PostgreSqlDbConnection::getPdoConnection(
                $dbString,
                $ssl,
                $sslVerify,
                $caCertFile
            );
        } catch (EtlException $exception) {
            $exceptionCaught = true;
            $exceptionCode = $exception->getCode();
        }

        $this->assertTrue($exceptionCaught, 'Too few arguments check');
        $this->assertEquals(EtlException::DATABASE_ERROR, $exceptionCode);
    }

    public function testConnectionWithTooManyArguments()
    {
        $dbString   = 'localhost:non_existent_usert:invalid_password:db:schema:port:extra';
        $ssl        = false;
        $sslVerify  = false;
        $caCertFile = null;

        $exceptionCaught = false;

        $postgreSqlDbConnection = null;

        try {
            $postgreSqlDbConnection = PostgreSqlDbConnection::getPdoConnection(
                $dbString,
                $ssl,
                $sslVerify,
                $caCertFile
            );
        } catch (EtlException $exception) {
            $exceptionCaught = true;
            $exceptionCode = $exception->getCode();
        }

        $this->assertTrue($exceptionCaught, 'Too many arguments check');
        $this->assertEquals(EtlException::DATABASE_ERROR, $exceptionCode);
    }

    public function testConnectionWithInvalidArguments()
    {
        $dbString   = 'localhost:non_existent_usert:invalid_password:db:schema:1234';
        $ssl        = true;
        $sslVerify  = true;
        $caCertFile = './data/not-an-actual-file.txt';

        $exceptionCaught = false;

        $postgreSqlDbConnection = null;

        try {
            $postgreSqlDbConnection = PostgreSqlDbConnection::getPdoConnection(
                $dbString,
                $ssl,
                $sslVerify,
                $caCertFile
            );
        } catch (EtlException $exception) {
            $exceptionCaught = true;
            $exceptionCode = $exception->getCode();
        }

        $this->assertTrue($exceptionCaught, 'Invalid arguments check');
        $this->assertEquals(EtlException::DATABASE_ERROR, $exceptionCode);
    }
    
    public function testConnectionWithInvalidArguments2()
    {
        $dbString   = 'localhost:non_existent_usert:invalid_password:db';
        $ssl        = false;
        $sslVerify  = false;
        $caCertFile = null;

        $exceptionCaught = false;

        $postgreSqlDbConnection = null;

        try {
            $postgreSqlDbConnection = PostgreSqlDbConnection::getPdoConnection(
                $dbString,
                $ssl,
                $sslVerify,
                $caCertFile
            );
        } catch (EtlException $exception) {
            $exceptionCaught = true;
            $exceptionCode = $exception->getCode();
        }

        $this->assertTrue($exceptionCaught, 'Invalid arguments check');
        $this->assertEquals(EtlException::DATABASE_ERROR, $exceptionCode);
    }
}

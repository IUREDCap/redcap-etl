<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\TaskConfig;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Logger;
use IU\REDCapETL\LookupTable;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Row;
use IU\REDCapETL\Schema\FieldTypeSpecifier;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\Table;

/**
* Integration tests for database classes.
*/

class MysqlSslTest extends TestCase
{
    private static $logger;
    const CA_CERT_FILE = __DIR__.'/../config/ca.crt';
    const CONFIG_FILE  = __DIR__.'/../config/repeating-events-mysql-ssl.ini';
    private static $expectedCode = EtlException::DATABASE_ERROR;

    protected $ssl = null;
    protected $labelViewSuffix = null;
    protected $tablePrefix = null;
    protected $suffixes = '';
    protected $rowsType = RowsType::ROOT;
    protected $recordIdFieldName = 'record_id';

    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('databases_integration_test');
    }


    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        } elseif (!file_exists(self::CA_CERT_FILE)) {
            $this->markTestSkipped("Required CA (Certificate Authority) certificate file not set for this test.");
        }
    }

    /**
     * This tests the SSL MySQL connection option of the MysqlDbConnection class
     * using branch1 of the redcap MySQL database server. It depends on the
     * SSL certificate being in tests/config/ca.crt. If the certificate cannot
     *be found, the test is skipped.
     */
    public function testMysqlDbConnectionConstructorWithSsl()
    {
        $configuration = new TaskConfig(self::$logger, self::CONFIG_FILE);
        $dbInfo = $configuration->getMySqlConnectionInfo();
        $dbString = implode(":", $dbInfo);

        # Create the MysqlDbConnection
        $sslVerify = true;
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            self::CA_CERT_FILE,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        # verify object was created
        $this->assertNotNull(
            $mysqlDbConnection,
            'DatabasesTest, mysqlDbConnection object created, ssl db user check'
        );
    }
}

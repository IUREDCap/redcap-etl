<?php

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\Configuration;
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
    private static $configFile = __DIR__.'/../config/repeating-events-mysql.ini';
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

    /**
     * This tests the SSL MySQL connection option of the MysqlDbConnection class
     * using branch1 of the redcap MySQL database server. It depends on the
     * SSL certificate being in tests/config/ca.crt. If the certificate cannot
     *be found, the test is skipped.
     */
    public function testMysqlDbConnectionConstructorWithSsl()
    {
        $caCertFile = __DIR__.'/../config/ca.crt';
        $configFile = __DIR__.'/../config/mysql-ssl.ini';
        $skipTestMessage = "DatabasesTest, testMysqlDbConnectionConstructorWithSsl skipped.";

        if (file_exists($configFile)) {
            $configuration = new Configuration(self::$logger, $configFile);
            if (file_exists($caCertFile)) {
                $dbInfo = $configuration->getMySqlConnectionInfo();
                $dbString = implode(":", $dbInfo);

                # Create the MysqlDbConnection
                $sslVerify = true;
                $mysqlDbConnection = new MysqlDbConnection(
                    $dbString,
                    $this->ssl,
                    $sslVerify,
                    $caCertFile,
                    $this->tablePrefix,
                    $this->labelViewSuffix
                );

                # verify object was created
                $this->assertNotNull(
                    $mysqlDbConnection,
                    'DatabasesTest, mysqlDbConnection object created, ssl db user check'
                );
            } else {
                $this->markTestSkipped($skipTestMessage . " {$caCertFile} not found.");
            }
        } else {
            $this->markTestSkipped($skipTestMessage . " {$configFile} not found.");
        }
    }
}

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
* System tests for database SqlServer class.
*/

class SqlServerTest extends TestCase
{
    private static $logger;
    const CONFIG_FILE     = __DIR__.'/../config/repeating-events-sqlserver.ini';
    const CONFIG_FILE_SSL = __DIR__.'/../config/repeating-events-sqlserver-ssl.ini';
    private static $expectedCode = EtlException::DATABASE_ERROR;

    protected $ssl = null;
    protected $sslVerify = null;
    protected $caCertFile = null;
    protected $labelViewSuffix = null;
    protected $tablePrefix = null;
    protected $suffixes = '';
    protected $rowsType = RowsType::ROOT;
    protected $recordidFieldName = 'recordid';

    public static function setUpBeforeClass(): void
    {
        self::$logger = new Logger('sqlserver_databases_system_test');
    }

    public function setUp(): void
    {
        #These tests depend on the sqlsrv and pdo_sqlsrv drivers being installed.
        #If they are not loaded in PHP, all tests will be skipped.
     
        #print_r(get_loaded_extensions());
        if (!extension_loaded('sqlsrv') || !extension_loaded('pdo_sqlsrv')) {
            $this->markTestSkipped('The sqlsrv and pdo_sqlsrv drivers are not available.');
        } elseif (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }

    /**
     * This test tries to connect to a nonexistent database.
     */
    public function testConnectorErrorCondition()
    {
        $dbString3 = 'localhost:idonotexist:somewonderfulpassword:adb';
        $exceptionCaught3 = false;
        #Checking for only the first part of the message because the error text
        #will be different depending on whether SQL Server is running or not.
        $expectedMessage3 = 'Database connection error for database "adb": SQLSTATE[';
        $sqlServerDbConnection = null;

        try {
            $sqlServerDbConnection = new SqlServerDbConnection(
                $dbString3,
                $this->ssl,
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
            'SqlServerTest,testConnectorErrorCondition expected error exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'SqlServerTest,testConnectorErrorCondition expected error exception code check'
        );

        $this->assertEquals(
            $expectedMessage3,
            substr($exception->getMessage(), 0, strlen($expectedMessage3)),
            #$exception->getMessage(),
            'SqlServerTest,testConnectorErrorCondition expected error exception message check'
        );
    }

    public function testConnectorValidConnectionWithSsl()
    {
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::CONFIG_FILE_SSL);

        $dbConnection = $configuration->getDbConnection();
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);
 
        #The configuration file should have db_ssl set to 1 so that the
        #SQL Server encryption parameter will be to true and use the self-signed
        #cert stored in the database instance.
        $ssl = $configuration->getDbSsl();

        #The configuration file should have db_ssl_verify set to 0 so that the
        #SQL Server trustServerCertificate is set to true
        $sslVerify = $configuration->getDbSslVerify();
         
        $sqlServerDbConnection4 = new SqlServerDbConnection(
            $dbString,
            $ssl,
            $sslVerify,
            $this->caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );
  
        $this->assertNotNull(
            $sqlServerDbConnection4,
            'SqlServerTest,testValidConnectionWithSssl object created check'
        );
    }

    /**
     * This test creates an empty table.
     */
    public function testSqlServerDbConnectionCreateTableWithPortAndEncryption()
    {
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

        $port = '1433';
        $dbString .= ':'.$port;

        # Create the SqlServerDbConnection
        $ssl = true; #set encryption to true, using the self-signed cert
        $caCertFile = null;

        # Set TrustServerCertificate to true, so that the cert is not verified.
        # (Since the cert is self-signed, there is no 3rd party to verify the cert.
        #  The login will fail with a self-signed cert and TrustServerCertificate = false.
        $sslVerify = false;
        $sqlServerDbConnection = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $this->sslVerify,
            $this->caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        #############################################################
        # create the table object
        #############################################################
        $name = 'test_etl_table';
        $parent = 'test_etl_table_id';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);

        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordidFieldName
        );

        #### Create fields in the Table object
        $field0 = new Field(
            'recordid',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'fullname',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $field2 = new Field(
            'weight',
            FieldType::INT,
            null
        );
        $rootTable->addField($field2);

        #############################################################
        # Execute the tests for this method
        #############################################################
        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processSqlSrv($dbString, "DROP TABLE $name;");

        # run the createTable method to create the table in the database
        $createResult = $sqlServerDbConnection->createTable($rootTable, false);
        $expected = 1;
        $this->assertEquals(
            $createResult,
            $expected,
            'SqlServerTest, sqlServerDbConnection createTable successful return check'
        );

        #Check to see if the table was created by reading the column names.
        $sql = "SELECT column_name FROM information_schema.columns ";
        $sql .= "WHERE table_name = '$name' ORDER BY ordinal_position;";
        $result = $this->processSqlSrv($dbString, $sql);
        $this->assertNotNull(
            $result,
            'SqlServerTest, sqlSrvDbConnection createTable check'
        );

        $expectedColumns = ['test_etl_table_id','recordid','fullname','weight'];
        $i = 0;
        foreach ($result as $row) {
            // phpcs:disable
            $this->assertEquals(
                $expectedColumns[$i],
                $row->column_name,
                "SqlServerTest, SqlServerDbConnection createTable $expectedColumns[$i] column check"
            );
            // phpcs:enable
            $i++;
            #print $row->column_name . PHP_EOL;
        }

        #Check to see if an error will be generated as expected by trying to create a table
        #that already exists.
        $exceptionCaught4 = false;
        $expectedMessage4 = "There is already an object named 'test_etl_table' in the database.";

        try {
            $sqlServerDbConnection->createTable($rootTable, false);
        } catch (EtlException $exception) {
            $exceptionCaught4 = true;
        }

        $this->assertTrue(
            $exceptionCaught4,
            'SqlServerTest, SqlServerDbConnection exception caught for table already exists'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'SqlServerTest, SqlServerDbConnection table already exists error code check'
        );

        $this->assertEquals(
            $expectedMessage4,
            substr($exception->getMessage(), -1*strlen($expectedMessage4)),
            'SqlServerTest, SqlServerDbConnection table already exists error message check'
        );

        # try to create the table by setting $ifNotExists and make sure an error is not generated
        $ifExists = true;
        $exceptionCaught5 = false;
        try {
            $sqlServerDbConnection->createTable($rootTable, $ifExists);
        } catch (EtlException $exception) {
            $exceptionCaught5 = true;
        }

        $this->assertFalse(
            $exceptionCaught5,
            'SqlServerTest, SqlServerDbConnection createTable If Exists check'
        );

        #drop the table
        $this->processSqlSrv($dbString, "DROP TABLE $name;");
    }

    public function testSqlServerDbConnectionInsertRow()
    {
        #############################################################
        # create the table object
        #############################################################
        $name = 'test1010';
        $parent = 'testid';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordidFieldName
        );

       #create fields in the Table object
        $field0 = new Field(
            'pid',
            FieldType::AUTO_INCREMENT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'recordid',
            FieldType::INT,
            null
        );
        $rootTable->addField($field1);

        $field2 = new Field(
            'fullname',
            FieldType::CHAR,
            30
        );
        $rootTable->addField($field2);

        $foreignKey = null;
        $suffix = null;
        $data = [
            'redcap_data_source' => 1,
            'recordid' => 1001,
            'fullname' => 'Ima Tester'
        ];
        $rootTable->createRow($data, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data1 = [
            'redcap_data_source' => 1,
            'recordid' => 1002,
            'fullname' => 'Person That Has Way TOOOOO Many Letters in Their Name'
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processSqlSrv($dbString, "DROP TABLE $name;");

        # Create the SqlServerDbConnection
        $caCertFile = null;
        $sslVerify = false;
        $sqlServerDbConnection = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        $sqlServerDbConnection->createTable($rootTable, false);

        #############################################################
        # Execute the tests for this method
        #############################################################
        #insert one row to see if it processes correctly
        $data = [
            'redcap_data_source' => 1,
            'recordid' => 1001,
            'name' => 'Some Other Person'
        ];
        $rootTable->createRow($data, $foreignKey, $suffix, RowsType::BY_EVENTS);
        $rows = $rootTable->getRows();
        $row0 = $rows[0];
        $result = $sqlServerDbConnection->insertRow($row0);
        $expected = 0;
        $this->assertEquals(
            $expected,
            $result,
            'SqlServerTest, SqlServerDbConnection insertRow return check'
        );

        #Verify the row was written as expected.
        $sql = "select * from $name order by recordid;";
        $contents = $this->processSqlSrv($dbString, $sql);
        $expectedRow = new \stdClass;
        $expectedRow->testid = 1;
        $expectedRow->pid = 0;
        $expectedRow->recordid = 1001;
        $expectedRow->fullname = 'Ima Tester';
        
        $contents[0]->fullname = trim($contents[0]->fullname);
        $this->assertEquals(
            $expectedRow,
            $contents[0],
            "SqlServerTest, SqlServerDbConnection insertRow content check"
        );

        #Check to see if an error will be generated by inserting a row in which
        #the fullname field exceeds its defined length.
        $exceptionCaught6 = false;
        $expectedMessage6 = "String or binary data would be truncated.";
        $row1 = $rows[1];
        try {
            $result = $sqlServerDbConnection->insertRow($row1);
        } catch (EtlException $exception) {
            $exceptionCaught6 = true;
        }

         $this->assertTrue(
             $exceptionCaught6,
             'SqlServerTest, SqlServerDbConnection insertRow expected error exception caught'
         );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'SqlServerTest, SqlServerDbConnection insertRow expected error error code check'
        );

        $this->assertRegexp(
            '/'.$expectedMessage6.'/',
            $exception->getMessage(),
            'SqlServerTest, SqlServerDbConnection insertRow expected error error message check'
        );

        #drop the table
        $this->processSqlSrv($dbString, "drop table $name;");
    }

   /**
    * Testing the insertRows method by calling the DbConnection->storeRows method.
    */
    public function testSqlServerDbConnectionInsertRows()
    {
        #############################################################
        # create the table object
        #############################################################
        $name = 'test2000';
        $parent = 'testid';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordidFieldName
        );

        #create fields in the Table object
        $field0 = new Field(
            'recordid',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'fullname',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $field2 = new Field(
            'score',
            FieldType::FLOAT,
            null
        );
        $rootTable->addField($field2);

        $field3 = new Field(
            'updatedate',
            FieldType::DATE,
            null
        );
        $rootTable->addField($field3);

        $field3 = new Field(
            'exercise___0',
            FieldType::CHECKBOX,
            null
        );
        $rootTable->addField($field3);

        $foreignKey = null;
        $suffix = null;
        #updatedate deliberately not populated in any of the rows
        $data1 = [
            'redcap_data_source' => 1,
            'recordid' => 1001,
            'fullname' => 'Ima Tester',
            'score' => 12.3,
            'updatedate' => '',
            'exercise___0' => ''
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
            'redcap_data_source' => 1,
            'recordid' => 1002,
            'fullname' => 'Spider Webb',
            'score' => 4.56,
            'updatedate' => '2017-Jan-31',
            'exercise___0' => '1'
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processSqlSrv($dbString, "DROP TABLE $name;");

        # Create the SqlServerDbConnection
        $caCertFile = null;
        $sslVerify = false;
        $sqlServerDbConnection = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        $sqlServerDbConnection->createTable($rootTable, false);
        #############################################################
        # Execute the tests for this method
        #############################################################
       
        ###test normal execution
        $sqlServerDbConnection->storeRows($rootTable);

        #Verify that the rows were created as expected.
        $expectedRows = array(new \stdClass, new \stdClass);
        $expectedRows[0]->testid = 1;
        $expectedRows[0]->recordid = 1001;
        $expectedRows[0]->fullname = 'Ima Tester';
        $expectedRows[0]->score = 12.3;
        $expectedRows[0]->updatedate = null;
        $expectedRows[0]->exercise___0 = 0;
        $expectedRows[1]->testid = 2;
        $expectedRows[1]->recordid = 1002;
        $expectedRows[1]->fullname = 'Spider Webb';
        $expectedRows[1]->score = 4.56;
        $expectedRows[1]->updatedate = '2017-Jan-31';
        $expectedRows[1]->exercise___0 = 1;

        $sql = "select * from $name order by testid;";
        $contents = $this->processSqlSrv($dbString, $sql);
        $i = 0;
        foreach ($contents as $row) {
            $row->fullname = trim($row->fullname);
            if ($row->updatedate) {
                 $row->updatedate = date_format($row->updatedate, 'Y-M-d');
            }
            $this->assertEquals(
                $expectedRows[$i],
                $row,
                "SqlServerTest, SqlServerDbConnection insertRows content check"
            );
            $i++;
        }

        ###test an error condition. In this case, create a table and
        ###insert a row that has more than the allowed number of
        ###characters for a field.
        $name2 = 'test2200';
        $parent2 = 'testid';
        $keyType2 = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable2 = new Table(
            $name2,
            $parent2,
            $keyType2,
            array($this->rowsType),
            $this->suffixes,
            $this->recordidFieldName
        );

        #create fields in the Table object
        $field20 = new Field(
            'recordid',
            FieldType::INT,
            null
        );
        $rootTable2->addField($field20);

        $field21 = new Field(
            'fullname',
            FieldType::CHAR,
            30
        );
        $rootTable2->addField($field21);

        $foreignKey = null;
        $suffix = null;
        $data21 = [
            'redcap_data_source' => 1,
            'recordid' => 1001,
            'fullname' => 'Ima Tester'
        ];
        $rootTable2->createRow($data21, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data22 = [
            'redcap_data_source' => 1,
            'recordid' => 1002,
            'fullname' => 'Person That Has Way TOOOOO Many Letters in Their Name'
        ];
        $rootTable2->createRow($data22, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #create the table in the database
        #before creating the table, run the command to drop it
        $this->processSqlSrv($dbString, "DROP TABLE $name2;");
        $sqlServerDbConnection->createTable($rootTable2, false);

        $exceptionCaught = false;
        $expectedMessage = "String or binary data would be truncated.";
 
        try {
            $sqlServerDbConnection->storeRows($rootTable2);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'SqlServerTest, SqlServerDbConnection insertRows exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'SqlServerTest, SqlServerDbConnection insertRows exception error code check'
        );

        $this->assertRegexp(
            '/'.$expectedMessage.'/',
            $exception->getMessage(),
            'SqlServerTest, SqlServerDbConnection insertRows exception error message check'
        );

        #drop the table and view
        $this->processSqlSrv($dbString, "drop table $name;");
        $this->processSqlSrv($dbString, "drop table $name2;");
    }

    /**
     * This tests the PdoDbConnection->processQueryFile method, which also ends up
     * testing the PdoDbConnection->processQueries method.
     */
    public function testPdoDbProcessQueryFile()
    {
        #Create the SqlServerDbConnection object
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

        $caCertFile = null;
        $sslVerify = false;
        $sqlServerDbConnection = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        #############################################################
        # Execute the tests for this method
        #############################################################

        ###Test file with valid queries that create two tables
        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processSqlSrv($dbString, "drop table queryTest01;");
        $this->processSqlSrv($dbString, "drop table queryTest02;");

        #Create the file that contains the queries
        $sql = 'create table queryTest01 (rec_id int primary key, title varchar(255), start date);';
        $sql .= 'create table queryTest02 (p_id int primary key, descr text, completion date);';
        $qFile = './tests/output/queryTestFile.sql';
        $fh = fopen($qFile, 'w');
        fwrite($fh, $sql);
        fclose($fh);

        $result = $sqlServerDbConnection->processQueryFile($qFile);
        $expected = 0;
        $this->assertEquals(
            $expected,
            $result,
            'SqlServerTest, SqlServerDbConnection processQueryFile return check'
        );

        #Check to see if the tables were created as expected.
        $sql = "SELECT column_name, data_type from information_schema.columns ";
        $sql .= "WHERE table_name = ";

        $query = $sql . "'queryTest01'";
        $contents = $this->processSqlSrv($dbString, $query);
        $this->assertNotNull(
            $contents,
            'SqlServerTest, SqlServerDbConnection processQueryFile sql command query1 check'
        );

        $expectedRows = array(new \stdClass, new \stdClass, new \stdClass);
        $expectedRows[0]->column_name = 'rec_id';
        $expectedRows[0]->data_type = 'int';
        $expectedRows[1]->column_name = 'title';
        $expectedRows[1]->data_type = 'varchar';
        $expectedRows[2]->column_name = 'start';
        $expectedRows[2]->data_type = 'date';
        $i = 0;
        foreach ($contents as $row) {
            $this->assertEquals(
                $expectedRows[$i],
                $row,
                "SqlServerTest, SqlServerDbConnection processQueryFile query1 column check"
            );
            $i++;
        }

        $query = $sql . "'queryTest02'";
        $contents = $this->processSqlSrv($dbString, $query);
        $this->assertNotNull(
            $contents,
            'SqlServerTest, SqlServerDbConnection processQueryFile sql command query2 check'
        );

        $expectedRows[0]->column_name = 'p_id';
        $expectedRows[0]->data_type = 'int';
        $expectedRows[1]->column_name = 'descr';
        $expectedRows[1]->data_type = 'text';
        $expectedRows[2]->column_name = 'completion';
        $expectedRows[2]->data_type = 'date';
        $i = 0;
        foreach ($contents as $row) {
            $this->assertEquals(
                $expectedRows[$i],
                $row,
                "SqlServerTest, SqlServerDbConnection processQueryFile query2 column check"
            );
            $i++;
        }

        ###Test with invalid file name
        $badFile = './tests/output/imaBadFile.xyz';
        $exceptionCaught7 = false;
        $expectedMessage7 = 'Could not access query file';

        try {
            $sqlServerDbConnection->processQueryFile($badFile);
        } catch (EtlException $exception) {
            $exceptionCaught7 = true;
        }

        $this->assertTrue(
            $exceptionCaught7,
            'SqlServerTest, SqlServerDbConnection processQueryFile bad file name exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'SqlServerTest, SqlServerDbConnection processQueryFile bad file name error code check'
        );

        $this->assertEquals(
            $expectedMessage7,
            substr($exception->getMessage(), 0, 27),
            'SqlServerTest, SqlServerDbConnection processQueryFile bad file name error message check'
        );

        ###Test with an error condition in the 1st query by testing an empty file
        $emptyFile = './tests/output/emptyFile.sql';
        $fh = fopen($emptyFile, 'w');
        fwrite($fh, '');
        fclose($fh);

        $exceptionCaught8 = false;
        $expectedMessage8 = 'SQL query failed:';

        try {
            $sqlServerDbConnection->processQueryFile($emptyFile);
        } catch (EtlException $exception) {
            $exceptionCaught8 = true;
        }

        $this->assertFalse(
            $exceptionCaught8,
            'Empty query file is OK check'
        );
 
        /* (An empty file should not be an exception)
        $this->assertTrue(
            $exceptionCaught8,
            'DatabasesTest, SqlServerTest, SqlServerDbConnection processQueryFile file exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'SqlServerTest, SqlServerDbConnection processQueryFile file error code check'
        );

        $this->assertEquals(
            $expectedMessage8,
            substr($exception->getMessage(), 0, strlen($expectedMessage8)),
            'SqlServerTest, SqlServerDbConnection processQueryFile file error message check'
        );
         */

        ###Test with an error condition in a query other than the first on by sending
        ###a bad third query
        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processSqlSrv($dbString, "drop table queryTest03;");
        $this->processSqlSrv($dbString, "drop table queryTest04;");

        #Create the file that contains the queries, with the third query misspelling 'table'
        $sql3 = 'create table queryTest03 (rec_id int primary key, title varchar(255), start date);';
        $sql3 .= 'create table queryTest04 (p_id int primary key, descr text, completion date);';
        $sql3 .= 'create tabl queryTest05 (r_id int primary key, fname);';
        $qFile3 = './tests/output/badThirdQuery.sql';
        $fh = fopen($qFile3, 'w');
        fwrite($fh, $sql3);
        fclose($fh);

        $exceptionCaught9 = false;
        $expectedMessage9 = "Unknown object type 'tabl' used in a CREATE, DROP, or ALTER statement.";

        try {
            $sqlServerDbConnection->processQueryFile($qFile3);
        } catch (EtlException $exception) {
            $exceptionCaught9 = true;
        }

        $this->assertTrue(
            $exceptionCaught9,
            'SqlServerTest, SqlServerDbConnection processQueryFile query error exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'SqlServerTest, SqlServerDbConnection processQueryFile query error code check'
        );

        $this->assertEquals(
            $expectedMessage9,
            substr($exception->getMessage(), -1*strlen($expectedMessage9)),
            'SqlServerTest, SqlServerDbConnection processQueryFile query error message check'
        );

        #drop the tables
        $this->processSqlSrv($dbString, "drop table queryTest01;");
        $this->processSqlSrv($dbString, "drop table queryTest02;");
        $this->processSqlSrv($dbString, "drop table queryTest03;");
        $this->processSqlSrv($dbString, "drop table queryTest04;");

        #delete the files
        unlink($qFile);
        unlink($emptyFile);
        unlink($qFile3);
    }
 
    public function testSqlServerDbConnectionReplaceLookupViewWithoutLookup()
    {
        #############################################################
        # create the table object
        #############################################################
        $name = 'test1011';
        $parent = 'testid';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordidFieldName
        );

        #create fields in the Table object
        $field0 = new Field(
            'recordid',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'fullname',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $foreignKey = null;
        $suffix = null;
        $data1 = [
            'redcap_data_source' => 1,
            'recordid' => 1001,
            'fullname' => 'Ima Tester'
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
            'redcap_data_source' => 1,
            'recordid' => 1002,
            'fullname' => 'Spider Webb'
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

        $caCertFile = null;
        $sslVerify = false;
        $sqlServerDbConnection1 = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processSqlSrv($dbString, "drop table $name;");

        $sqlServerDbConnection1->createTable($rootTable, false);
        $sqlServerDbConnection1->storeRows($rootTable);

        #############################################################
        # Execute the tests for this method
        #############################################################

        ### test normal execution
        $labelViewSuffix = 'View';
        $sqlServerDbConnection = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $labelViewSuffix
        );
        $result1 = $sqlServerDbConnection->replaceLookupView($rootTable, null);
        $expected = 1;
        $this->assertEquals(
            $expected,
            $result1,
            'SqlServerTest, SqlServerDbConnection replaceLookupView Without Lookup return check'
        );
        
        #Verify that the view was created as expected.
        $sql = "select * from $name$labelViewSuffix order by 1;";
        $contents = $this->processSqlSrv($dbString, $sql);

        $expectedRows = array(new \stdClass, new \stdClass);
        $expectedRows[0]->testid = 1;
        $expectedRows[0]->recordid = 1001;
        $expectedRows[0]->fullname = 'Ima Tester';
        $expectedRows[1]->testid = 2;
        $expectedRows[1]->recordid = 1002;
        $expectedRows[1]->fullname = 'Spider Webb';
        $i = 0;
        foreach ($contents as $row) {
            $row->fullname = trim($row->fullname);
            $this->assertEquals(
                $expectedRows[$i],
                $row,
                "SqlServerTest, SqlServerDbConnection replaceLookupView Without Lookup column check"
            );
            $i++;
        }

        ### test an error condition. In this case, the view and table
        ### have the same name (set labelViewSuffix to null.)
        $sqlServerDbConnection = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        $exceptionCaught1 = false;
        $expectedMessage1 = 'Error in database query';
        try {
            $sqlServerDbConnection->replaceLookupView($rootTable, null);
        } catch (EtlException $exception) {
            $exceptionCaught1 = true;
        }

        $this->assertTrue(
            $exceptionCaught1,
            'SqlServerTest, SqlServerDbConnection replaceLookupView Without Lookup exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'SqlServerTest, SqlServerDbConnection replaceLookupView Without Lookup exception error code check'
        );

        $this->assertEquals(
            $expectedMessage1,
            substr($exception->getMessage(), 0, 23),
            'SqlServerTest, SqlServerDbConnection replaceLookupView Without Lookup exception error message check'
        );

        #drop the table and view
        $this->processSqlSrv($dbString, "drop table $name;");
        $this->processSqlSrv($dbString, "drop view  $name$labelViewSuffix;");
    }

    public function testSqlServerDbConnectionReplaceLookupViewWithLookup()
    {
        #############################################################
        # create the table object
        #############################################################
        $name = 'test1012';
        $parent = 'testid';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordidFieldName
        );

        # Create fields in the Table object
        $field0 = new Field(
            'recordid',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'fullname',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $field2 = new Field(
            'maritalstatus',
            FieldType::INT,
            null
        );
        $field2->setUsesLookup('maritalstatus');
        $rootTable->addField($field2);

        $foreignKey = null;
        $suffix = null;
        $data1 = [
            'redcap_data_source' => 1,
            'recordid' => 1001,
            'fullname' => 'Ima Tester',
            'maritalstatus' => 0
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
            'redcap_data_source' => 1,
            'recordid' => 1002,
            'fullname' => 'Spider Webb',
            'maritalstatus' => 3
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the lookup table object that has the label values
        #############################################################
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $lookupChoices = [
            "maritalstatus" => ['single', 'married', 'widowed', 'divorced']
        ];
        $tablePrefix = null;
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $lookupTable = new LookupTable($lookupChoices, $keyType);

        #identify maritalstatus as a lookup field in the data table
        $fieldName = 'maritalstatus';
        $result = $lookupTable->addLookupField($name, $fieldName);

        #############################################################
        # create the data table in the database
        #############################################################
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

        $caCertFile = null;
        $sslVerify = false;
        $labelViewSuffix = '_view';
        $sqlServerDbConnection = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $labelViewSuffix
        );

        #first, drop the tables, in case it wasn't dropped from a prior test.
        $this->processSqlSrv($dbString, "drop table $name;");

        $sqlServerDbConnection->createTable($rootTable, false);
        $sqlServerDbConnection->storeRows($rootTable);

        #############################################################
        # Execute the tests for this method
        #############################################################

        ###run the replaceLookupView method and verify it returns successfully
        $expected = 1;

        $result = $sqlServerDbConnection->replaceLookupView($rootTable, $lookupTable);
        $this->assertEquals(
            $expected,
            $result,
            'SqlServerTest, SqlServerDbConnection replaceLookupViewWithLookup return check'
        );
   
        #Verify that the view was created as expected.
        $sql = "select * from $name$labelViewSuffix order by 1;";
        $contents = $this->processSqlSrv($dbString, $sql);

        $expectedRows = array(new \stdClass, new \stdClass);
        $expectedRows[0]->testid = 1;
        $expectedRows[0]->recordid = 1001;
        $expectedRows[0]->fullname = 'Ima Tester';
        $expectedRows[0]->maritalstatus = 'single';
        $expectedRows[1]->testid = 2;
        $expectedRows[1]->recordid = 1002;
        $expectedRows[1]->fullname = 'Spider Webb';
        $expectedRows[1]->maritalstatus = 'divorced';

        $i = 0;
        foreach ($contents as $row) {
            $row->fullname = trim($row->fullname);
            $row->maritalstatus = trim($row->maritalstatus);
            $this->assertEquals(
                $expectedRows[$i],
                $row,
                "SqlServerTest, SqlServerDbConnection replaceLookupViewWithLookup column check"
            );
            $i++;
        }

        #drop the table
        $this->processSqlSrv($dbString, "drop table $name;");
    }

   /* This tests the SqlServerDbConnection->dropTable protected function
    * by using PHPUnit Reflection
    */
    public function testSqlServerDbConnectionDropTable()
    {
        #create the table object
        $name = 'dropTabletest';
        $parent = 'testid';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordidFieldName
        );

        #create the SqlServerDbConnection
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::CONFIG_FILE);

        $dbConnection = $configuration->getDbConnection();
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($dbConnection);

        $caCertFile = null;
        $sslVerify = false;
        $sqlServerDbConnection = new SqlServerDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );


        #create the table in the database (drop it first, in case it already exists)
        $sql = "drop table $name;";
        $this->processSqlSrv($dbString, $sql);
        $sql = "create table $name (pid int, title char(25));";
        $this->processSqlSrv($dbString, $sql);

        #execute the method to drop the table
        $reflection = new \ReflectionClass(get_class($sqlServerDbConnection));
        $method = $reflection->getMethod('dropTable');
        $method->setAccessible(true);
        $ifExists = false;
        $parameters[0] = $rootTable;
        $parameters[1] = $ifExists;

        $result = $method->invokeArgs($sqlServerDbConnection, $parameters);

        #check to see if it was actually dropped
        $sql = "select 1 from sys.tables where name = '$name';";
        #contents should be an empty array
        $contents = $this->processSqlSrv($dbString, $sql);
        $this->assertEmpty(
            $contents,
            'SqlServerTest, SqlServerDbConnection dropTable table dropped check'
        );
    }

    /*
     * Executes various sql commands used to verify tests
     */
    private function processSqlSrv($dbString, $sql)
    {
        $dbValues = DbConnection::parseConnectionString($dbString);
        if (count($dbValues) == 4) {
            list($host,$username,$password,$database) = DbConnection::parseConnectionString($dbString);
        } elseif (count($dbValues) == 5) {
            list($host,$username,$password,$database,$port) = DbConnection::parseConnectionString($dbString);
            $port = intval($port);
            $host .= ",$port";
        } else {
            return null;
        }

        $connectionInfo = array( "Database"=>"$database", "UID"=>"$username", "PWD"=>"$password");
        $conn = sqlsrv_connect($host, $connectionInfo);

        $queryResults = array();
        $i=0;

        if ($conn) {
            $result = sqlsrv_query($conn, $sql);
            if ($result !== false) {
                while ($obj = sqlsrv_fetch_object($result)) {
                    $queryResults[$i] = $obj;
                    $i++;
                    #print PHP_EOL . "obj:" . PHP_EOL;
                    #print_r($obj);
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
        return $queryResults;
    }
}

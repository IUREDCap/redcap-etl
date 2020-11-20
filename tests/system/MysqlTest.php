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

class DatabasesTest extends TestCase
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

    public function setUp()
    {
        if (!file_exists(self::$configFile)) {
            $this->markTestSkipped("Required configuration not set for this test.");
        }
    }

    /**
     * This test creates an empty table.
     */
    public function testMysqlDbConnectionCreateTableWithPort()
    {
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::$configFile);

        $dbInfo = $configuration->getMySqlConnectionInfo();
        $port = '3306';
        $dbString = implode(":", $dbInfo).":$port";

        # Create the MysqlDbConnection
        $caCertFile = null;
        $sslVerify = false;
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
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
            $this->recordIdFieldName
        );

        #### Create fields in the Table object
        $field0 = new Field(
            'record_id',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'full_name',
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
        $this->processMysqli($dbString, "drop table $name;");

        # run the createTable method to create the table in the MySQL database
        $createResult = $mysqlDbConnection->createTable($rootTable, false);
        $expected = 1;
        $this->assertEquals(
            $createResult,
            $expected,
            'DatabasesTest, mysqlDbConnection createTable successful return check'
        );

        #Check to see if the table was created as expected.
        $sql = "describe $name;";
        $result = $this->processMysqli($dbString, $sql);
        $this->assertNotNull(
            $result,
            'DatabasesTest, mysqlDbConnection createTable check'
        );

        $expectedColumns = ['test_etl_table_id','record_id','full_name','weight'];
        $i = 0;
        while ($row = $result->fetch_assoc()) {
            $this->assertEquals(
                $expectedColumns[$i],
                $row['Field'],
                "DatabasesTest, mysqlDbConnection createTable $expectedColumns[$i] column check"
            );
            $i++;
        }

        #Check to see if an error will be generated as expected by trying to create a table
        #that already exists.
        $exceptionCaught4 = false;
        $expectedMessage4 = "[1050]: Table 'test_etl_table' already exists";

        try {
            $mysqlDbConnection->createTable($rootTable, false);
        } catch (EtlException $exception) {
            $exceptionCaught4 = true;
        }

        $this->assertTrue(
            $exceptionCaught4,
            'DatabasesTest, mysqlsDbConnection exception caught for table already exists'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'DatabasesTest, mysqlsDbConnection table already exists error code check'
        );

        $this->assertEquals(
            $expectedMessage4,
            substr($exception->getMessage(), -1*strlen($expectedMessage4)),
            'DatabasesTest, mysqlsDbConnection table already exists error message check'
        );

        # try to create the table by setting $ifNotExists and make sure an error is not generated
        $ifExists = true;
        $exceptionCaught5 = false;
        try {
            $mysqlDbConnection->createTable($rootTable, $ifExists);
        } catch (EtlException $exception) {
            $exceptionCaught5 = true;
        }

        $this->assertFalse(
            $exceptionCaught5,
            'DatabasesTest, mysqlsDbConnection createTable If Exists check'
        );

        #drop the table
        $this->processMysqli($dbString, "drop table $name;");
    }

   /**
    * Testing the insertRows method by calling the storeRows method.
    */
    public function testMysqlDbConnectionInsertRows()
    {
        #############################################################
        # create the table object
        #############################################################
        $name = 'test2000';
        $parent = 'test_id';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordIdFieldName
        );

        #create fields in the Table object
        $field0 = new Field(
            'record_id',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'full_name',
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
            'update_date',
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
        #update_date deliberately not populated in any of the rows
        $data1 = [
            'redcap_data_source' => 1,
            'record_id' => 1001,
            'full_name' => 'Ima Tester',
            'score' => 12.3,
            'update_date' => '',
            'exercise___0' => ''
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
            'redcap_data_source' => 1,
            'record_id' => 1002,
            'full_name' => 'Spider Webb',
            'score' => 4.56,
            'update_date' => '',
            'exercise___0' => 1
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::$configFile);

        $dbInfo = $configuration->getMySqlConnectionInfo();
        $dbString = implode(":", $dbInfo);

        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processMysqli($dbString, "drop table $name;");

        $caCertFile = null;
        $sslVerify = false;
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );
        $mysqlDbConnection->createTable($rootTable, false);
        
        #############################################################
        # Execute the tests for this method
        #############################################################
       
        ###test normal execution
        $mysqlDbConnection->storeRows($rootTable);

        #Verify that the view was created as expected.
        $expectedColumns = 'test_id,record_id,full_name,score,update_date,exercise___0';
        $expectedRows = [
            '1,1001,Ima Tester,12.3,,0',
            '2,1002,Spider Webb,4.56,,1'
        ];

        $sql = "select * from $name order by 1;";
        $contents = $this->processMysqli($dbString, $sql);
        $i = 0;
        while ($row = $contents->fetch_assoc()) {
            #Make sure the columns are as expected
            if ($i === 0) {
                $columnsArray = array_keys($row);
                $columnsAsString = implode(',', $columnsArray);
                $this->assertEquals(
                    $expectedColumns,
                    $columnsAsString,
                    "DatabasesTest, mysqlDbConnection insertRows column check"
                );
            }

           #Make sure the rows are as expected
            $rowAsString = implode(',', $row);
            $this->assertEquals(
                $expectedRows[$i],
                $rowAsString,
                'DatabasesTest, mysqlDbConnection insertRows rows check'
            );
            $i++;
        }

        ###test an error condition. In this case, create a table and
        ###insert a row that has more than the allowed number of
        ###characters for a field.
        $name2 = 'test2200';
        $parent2 = 'test_id';
        $keyType2 = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable2 = new Table(
            $name2,
            $parent2,
            $keyType2,
            array($this->rowsType),
            $this->suffixes,
            $this->recordIdFieldName
        );

        #create fields in the Table object
        $field20 = new Field(
            'record_id',
            FieldType::INT,
            null
        );
        $rootTable2->addField($field20);

        $field21 = new Field(
            'full_name',
            FieldType::CHAR,
            30
        );
        $rootTable2->addField($field21);

        $foreignKey = null;
        $suffix = null;
        $data21 = [
            'redcap_data_source' => 1,
            'record_id' => 1001,
            'full_name' => 'Ima Tester'
        ];
        $rootTable2->createRow($data21, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data22 = [
            'redcap_data_source' => 1,
            'record_id' => 1002,
            'full_name' => 'Person That Has Way TOOOOO Many Letters in Their Name'
        ];
        $rootTable2->createRow($data22, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #create the table in the database
        #before creating the table, run the command to drop it
        $this->processMysqli($dbString, "drop table $name2;");
        $mysqlDbConnection->createTable($rootTable2, false);

        $exceptionCaught = false;
        $expectedMessage = "[1406]: Data too long for column 'full_name' at row 2";
 
        try {
            $mysqlDbConnection->storeRows($rootTable2);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'DatabasesTest, mysqlDbConnection insertRows exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'DatabasesTest, mysqlDbConnection insertRows exception error code check'
        );

        $this->assertEquals(
            $expectedMessage,
            substr($exception->getMessage(), -1*strlen($expectedMessage)),
            'DatabasesTest, mysqlDbConnection insertRows exception error message check'
        );

        #drop the table and view
        $this->processMysqli($dbString, "drop table $name;");
        $this->processMysqli($dbString, "drop table $name2;");
    }

    public function testMysqlDbConnectionInsertRow()
    {
        #############################################################
        # create the table object
        #############################################################
        $name = 'test1010';
        $parent = 'test_id';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordIdFieldName
        );

        #create fields in the Table object
        $field0 = new Field(
            'record_id',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'full_name',
            FieldType::CHAR,
            30
        );
        $rootTable->addField($field1);

        $foreignKey = null;
        $suffix = null;
        $data = [
            'redcap_data_source' => 1,
            'record_id' => 1001,
            'full_name' => 'Ima Tester'
        ];
        $rootTable->createRow($data, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data1 = [
            'redcap_data_source' => 1,
            'record_id' => 1002,
            'full_name' => 'Person That Has Way TOOOOO Many Letters in Their Name'
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::$configFile);

        $dbInfo = $configuration->getMySqlConnectionInfo();
        $dbString = implode(":", $dbInfo);

        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processMysqli($dbString, "drop table $name;");

        $caCertFile = null;
        $sslVerify = false;
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        $mysqlDbConnection->createTable($rootTable, false);

        #############################################################
        # Execute the tests for this method
        #############################################################
        #insert one row to see if it processes correctly
        $data = [
            'redcap_data_source' => 1,
            'record_id' => 1001,
            'name' => 'Some Other Person'
        ];
        $rootTable->createRow($data, $foreignKey, $suffix, RowsType::BY_EVENTS);
        $rows = $rootTable->getRows();
        $row0 = $rows[0];
        $result = $mysqlDbConnection->insertRow($row0);
        $expected = 0;
        $this->assertEquals(
            $expected,
            $result,
            'DatabasesTest, mysqlDbConnection insertRow return check'
        );

        #Verify the row was written as expected.
        $sql = "select * from $name order by 1;";
        $contents = $this->processMysqli($dbString, $sql);
        $expectedRows = [
            '1,1001,Ima Tester'
        ];
        $i = 0;
        while ($row = $contents->fetch_assoc()) {
            $rowAsString = implode(',', $row);
            $this->assertEquals(
                $expectedRows[$i],
                $rowAsString,
                'DatabasesTest, mysqlDbConnection insertRow content check'
            );
            $i++;
        }
  
        #Check to see if an error will be generated by inserting a row in which
        #the full_name field exceeds its defined length.
        $exceptionCaught6 = false;
        $expectedMessage6 = "[1406]: Data too long for column 'full_name' at row 1";
        $row1 = $rows[1];
        try {
            $result = $mysqlDbConnection->insertRow($row1);
        } catch (EtlException $exception) {
            $exceptionCaught6 = true;
        }

         $this->assertTrue(
             $exceptionCaught6,
             'DatabasesTest, mysqlsDbConnection insertRow expected error exception caught'
         );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'DatabasesTest, mysqlsDbConnection insertRow expected error error code check'
        );

        $this->assertEquals(
            $expectedMessage6,
            substr($exception->getMessage(), -1*strlen($expectedMessage6)),
            'DatabasesTest, mysqlsDbConnection insertRow expected error error message check'
        );

        #drop the table
        $this->processMysqli($dbString, "drop table $name;");
    }

    /**
     * This tests the MysqlDbConnection->processQueryFile method, which also ends up
     * testing the MysqlDbConnection->processQueries method.
     */
    public function testMysqlDbConnectionProcessQueryFile()
    {
        #Create the mysqlDbConnection object
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::$configFile);

        $dbInfo = $configuration->getMySqlConnectionInfo();
        $dbString = implode(":", $dbInfo);

        $caCertFile = null;
        $sslVerify = false;
        $mysqlDbConnection = new MysqlDbConnection(
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
        $this->processMysqli($dbString, "drop table queryTest01;");
        $this->processMysqli($dbString, "drop table queryTest02;");

        #Create the file that contains the queries
        $sql = 'create table queryTest01 (rec_id int, title varchar(255), start date, primary key (rec_id));';
        $sql .= 'create table queryTest02 (p_id int, descr text, end date, primary key (p_id));';
        $qFile = './tests/output/queryTestFile.sql';
        $fh = fopen($qFile, 'w');
        fwrite($fh, $sql);
        fclose($fh);

        $result = $mysqlDbConnection->processQueryFile($qFile);
        $expected = 0;
        $this->assertEquals($expected, $result, 'DatabasesTest, mysqlDbConnection processQueryFile return check');

        #Check to see if the tables were created as expected.
        $sql1 = "describe queryTest01;";
        $result1 = $this->processMysqli($dbString, $sql1);
        $this->assertNotNull(
            $result1,
            'DatabasesTest, mysqlDbConnection processQueryFile sql command query1 check'
        );

        $expectedColumns1 = ['rec_id', 'title', 'start'];
        $i = 0;
        while ($row = $result1->fetch_assoc()) {
            $this->assertEquals(
                $expectedColumns1[$i],
                $row['Field'],
                'DatabasesTest, mysqlDbConnection processQueryFile query1 column check'
            );
            $i++;
        }

        $sql2 = "describe queryTest02;";
        $result2 = $this->processMysqli($dbString, $sql2);
        $this->assertNotNull(
            $result2,
            'DatabasesTest, mysqlDbConnection processQueryFile sql command query2 check'
        );

        $expectedColumns2 = ['p_id', 'descr', 'end'];
        $i = 0;
        while ($row = $result2->fetch_assoc()) {
            $this->assertEquals(
                $expectedColumns2[$i],
                $row['Field'],
                'DatabasesTest, mysqlDbConnection processQueryFile query2 column check'
            );
            $i++;
        }

        ###Test with invalid file name
        $badFile = './tests/output/imaBadFile.abc';
        $exceptionCaught7 = false;
        $expectedMessage7 = 'Could not access query file';

        try {
            $mysqlDbConnection->processQueryFile($badFile);
        } catch (EtlException $exception) {
            $exceptionCaught7 = true;
        }

        $this->assertTrue(
            $exceptionCaught7,
            'DatabasesTest, mysqlDbConnection processQueryFile bad file name exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'DatabasesTest, mysqlDbConnection processQueryFile bad file name error code check'
        );

        $this->assertEquals(
            $expectedMessage7,
            substr($exception->getMessage(), 0, 27),
            'DatabasesTest, mysqlsDbConnection processQueryFile bad file name error message check'
        );

        ###Test with an error condition in the 1st query by testing an empty file
        $emptyFile = './tests/output/emptyFile.sql';
        $fh = fopen($emptyFile, 'w');
        fwrite($fh, '');
        fclose($fh);

        $exceptionCaught8 = false;
        $expectedMessage8 = 'SQL query 1 failed:';

        try {
            $mysqlDbConnection->processQueryFile($emptyFile);
        } catch (EtlException $exception) {
            $exceptionCaught8 = true;
        }

        $this->assertTrue(
            $exceptionCaught8,
            'DatabasesTest, mysqlsDbConnection processQueryFile file exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'DatabasesTest, mysqlsDbConnection processQueryFile file error code check'
        );

        $this->assertEquals(
            $expectedMessage8,
            substr($exception->getMessage(), 0, strlen($expectedMessage8)),
            'DatabasesTest, mysqlsDbConnection processQueryFile file error message check'
        );

        ###Test with an error condition in a query other than the first on by sending
        ###a bad third query
        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processMysqli($dbString, "drop table queryTest03;");
        $this->processMysqli($dbString, "drop table queryTest04;");

        #Create the file that contains the queries, with the third query misspelling 'table'
        $sql3 = 'create table queryTest03 (rec_id int, title varchar(255), start date, primary key (rec_id));';
        $sql3 .= 'create table queryTest04 (p_id int, descr text, end date, primary key (p_id));';
        $sql3 .= 'create tabl queryTest05 (r_id int, fname, primary key (r_id));';
        $qFile3 = './tests/output/badThirdQuery.sql';
        $fh = fopen($qFile3, 'w');
        fwrite($fh, $sql3);
        fclose($fh);

        $exceptionCaught9 = false;
        $expectedMessage9 = 'SQL query 2 failed:';

        try {
            $mysqlDbConnection->processQueryFile($qFile3);
        } catch (EtlException $exception) {
            $exceptionCaught9 = true;
        }

        $this->assertTrue(
            $exceptionCaught9,
            'DatabasesTest, mysqlDbConnection processQueryFile query error exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'DatabasesTest, mysqlDbConnection processQueryFile query error code check'
        );

        $this->assertEquals(
            $expectedMessage9,
            substr($exception->getMessage(), 0, strlen($expectedMessage9)),
            'DatabasesTest, mysqlDbConnection processQueryFile query error message check'
        );

        #drop the tables
        $this->processMysqli($dbString, "drop table queryTest01;");
        $this->processMysqli($dbString, "drop table queryTest02;");
        $this->processMysqli($dbString, "drop table queryTest03;");
        $this->processMysqli($dbString, "drop table queryTest04;");

        #delete the files
        unlink($qFile);
        unlink($emptyFile);
        unlink($qFile3);
    }
  
    public function testMysqlDbConnectionReplaceLookupViewWithoutLookup()
    {
        #############################################################
        # create the table object
        #############################################################
        $name = 'test1011';
        $parent = 'test_id';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordIdFieldName
        );

        #create fields in the Table object
        $field0 = new Field(
            'record_id',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'full_name',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $foreignKey = null;
        $suffix = null;
        $data1 = [
            'redcap_data_source' => 1,
            'record_id' => 1001,
            'full_name' => 'Ima Tester'
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
            'redcap_data_source' => 1,
            'record_id' => 1002,
            'full_name' => 'Spider Webb'
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::$configFile);

        $dbInfo = $configuration->getMySqlConnectionInfo();
        $dbString = implode(":", $dbInfo);

        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processMysqli($dbString, "drop table $name;");

        $caCertFile = null;
        $sslVerify = false;
        $mysqlDbConnection1 = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );
        $mysqlDbConnection1->createTable($rootTable, false);
        $mysqlDbConnection1->storeRows($rootTable);

        #############################################################
        # Execute the tests for this method
        #############################################################

        ### test normal execution
        $labelViewSuffix = 'View';
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $labelViewSuffix
        );
        $result1 = $mysqlDbConnection->replaceLookupView($rootTable, null);
        $expected = 1;
        $this->assertEquals(
            $expected,
            $result1,
            'DatabasesTest, mysqlsDbConnection replaceLookupView Without Lookup return check'
        );
        
        #Verify that the view was created as expected.
        $expectedColumns = 'test_id,record_id,full_name';
        $expectedRows = [
            '1,1001,Ima Tester',
            '2,1002,Spider Webb'
        ];

        $sql = "select * from $name$labelViewSuffix order by 1;";
        $contents = $this->processMysqli($dbString, $sql);
        $i = 0;
        while ($row = $contents->fetch_assoc()) {
           #Make sure the columns are as expected
            if ($i === 0) {
                $columnsArray = array_keys($row);
                $columnsAsString = implode(',', $columnsArray);
                $this->assertEquals(
                    $expectedColumns,
                    $columnsAsString,
                    "DatabasesTest, mysqlDbConnection replaceLookupView Without Lookup column check"
                );
            }

           #Make sure the rows are as expected
            $rowAsString = implode(',', $row);
            $this->assertEquals(
                $expectedRows[$i],
                $rowAsString,
                'DatabasesTest, mysqlDbConnection replaceLookupView Without Lookup rows check'
            );
            $i++;
        }

        ### test an error condition. In this case, the view and table
        ### have the same name (set labelViewSuffix to null.)
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        $exceptionCaught1 = false;
        $expectedMessage1 = 'MySQL error in query';
        try {
            $mysqlDbConnection->replaceLookupView($rootTable, null);
        } catch (EtlException $exception) {
            $exceptionCaught1 = true;
        }

        $this->assertTrue(
            $exceptionCaught1,
            'DatabasesTest, mysqlDbConnection replaceLookupView Without Lookup exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'DatabasesTest, mysqlDbConnection replaceLookupView Without Lookup exception error code check'
        );

        $this->assertEquals(
            $expectedMessage1,
            substr($exception->getMessage(), 0, 20),
            'DatabasesTest, mysqlDbConnection replaceLookupView Without Lookup exception error message check'
        );

        #drop the table and view
        $this->processMysqli($dbString, "drop table $name;");
        $this->processMysqli($dbString, "drop view  $name$labelViewSuffix;");
    }
  
    public function testMysqlDbConnectionReplaceLookupViewWithLookup()
    {
        #Create the MysqlDbConnection
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::$configFile);

        $dbInfo = $configuration->getMySqlConnectionInfo();
        $dbString = implode(":", $dbInfo);

        $caCertFile = null;
        $sslVerify = false;
        $labelViewSuffix = '_view';
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $labelViewSuffix
        );

        #############################################################
        # create the table object
        #############################################################
        $name = 'test1012';
        $parent = 'test_id';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordIdFieldName
        );

        # Create fields in the Table object
        $field0 = new Field(
            'record_id',
            FieldType::INT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'full_name',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $field2 = new Field(
            'marital_status',
            FieldType::INT,
            null
        );
        $field2->setUsesLookup('marital_status');
        $rootTable->addField($field2);

        $foreignKey = null;
        $suffix = null;
        $data1 = [
            'redcap_data_source' => 1,
            'record_id' => 1001,
            'full_name' => 'Ima Tester',
            'marital_status' => 0
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
            'redcap_data_source' => 1,
            'record_id' => 1002,
            'full_name' => 'Spider Webb',
            'marital_status' => 3
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the lookup table object that has the label values
        #############################################################
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $lookupChoices = [
            "marital_status" => ['single', 'married', 'widowed', 'divorced']
        ];
        $tablePrefix = null;
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $lookupTable = new LookupTable($lookupChoices, $keyType);

        #identify marital_status as a lookup field in the data table
        $fieldName = 'marital_status';
        $result = $lookupTable->addLookupField($name, $fieldName);

        #############################################################
        # create the data table in the database
        #############################################################
        #first, drop the tables, in case it wasn't dropped from a prior test.
        $this->processMysqli($dbString, "drop table $name;");

        $mysqlDbConnection->createTable($rootTable, false);
        $mysqlDbConnection->storeRows($rootTable);

        #############################################################
        # Execute the tests for this method
        #############################################################

        ###run the replaceLookupView method and verify it returns successfully
        $expected = 1;

        $result = $mysqlDbConnection->replaceLookupView($rootTable, $lookupTable);
        $this->assertEquals(
            $expected,
            $result,
            'DatabasesTest, mysqlDbConnection replaceLookupViewWithLookup return check'
        );
        
        #Verify that the view was created as expected.
        $expectedColumns = 'test_id,record_id,full_name,marital_status';
        $expectedRows = [
            '1,1001,Ima Tester,single',
            '2,1002,Spider Webb,divorced'
        ];

        $sql = "select * from $name$labelViewSuffix order by 1;";
        $contents = $this->processMysqli($dbString, $sql);
        $i = 0;
        while ($row = $contents->fetch_assoc()) {
           #Make sure the columns are as expected
            if ($i === 0) {
                $columnsArray = array_keys($row);
                $columnsAsString = implode(',', $columnsArray);
                $this->assertEquals(
                    $expectedColumns,
                    $columnsAsString,
                    'DatabasesTest, mysqlDbConnection replaceLookupViewWithLookup column check'
                );
            }

           #Make sure the rows are as expected
            $rowAsString = implode(',', $row);
            $this->assertEquals(
                $expectedRows[$i],
                $rowAsString,
                'DatabasesTest, mysqlDbConnection replaceLookupViewWithLookup rows check'
            );
            $i++;
        }

        #drop the table
        $this->processMysqli($dbString, "drop table $name;");
    }

    public function testMysqlDbConnectionParseSqlQueries()
    {
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::$configFile);

        $dbInfo = $configuration->getMySqlConnectionInfo();
        $dbString = implode(":", $dbInfo);

        # Create the MysqlDbConnection
        $caCertFile = null;
        $sslVerify = false;
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        $sql = 'create table test01 (rec_id int, title varchar(255) primary key (rec_id));';
        $sql .= "insert into test01 (rec_id, title) values (0, 'some text & a \ and a /')";
        $sql .= '--this is a comment' . chr(10) .'--and another comment';

        $queryResults = $mysqlDbConnection->parseSqlQueries($sql);
        $this->assertNotNull(
            $queryResults,
            'DatabasesTest, mysqlDbConnection parseSqlQueries return check'
        );

        $expected[0] = 'create table test01 (rec_id int, title varchar(255) primary key (rec_id))';
        $expected[1] = "insert into test01 (rec_id, title) values (0, 'some text & a \ and a /')";

        $i = 0;
        foreach ($queryResults as $query) {
            $this->assertEquals(
                $expected[$i],
                $query,
                'DatabasesTest, mysqlDbConnection parseSqlQueries array check'
            );
            $i++;
        }
    }

   /* This tests the mysqlDbConnection->dropTable protected function
    * by using PHPUnit Reflection
    */
    public function testMysqlDbConnectionDropTable()
    {
        #create the table object
        $name = 'dropTabletest';
        $parent = 'test_id';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table(
            $name,
            $parent,
            $keyType,
            array($this->rowsType),
            $this->suffixes,
            $this->recordIdFieldName
        );

        #create the MysqlDbConnection
        $configuration = new TaskConfig();
        $configuration->set(self::$logger, self::$configFile);

        $dbInfo = $configuration->getMySqlConnectionInfo();
        $dbString = implode(":", $dbInfo);

        $caCertFile = null;
        $sslVerify = false;
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        #create the table in the database (drop it first, in case it already exists)
        $sql = "drop table $name;";
        $this->processMysqli($dbString, $sql);
        $sql = "create table $name (pid int, title char(25));";
        $this->processMysqli($dbString, $sql);

        #execute the method to drop the table
        $reflection = new \ReflectionClass(get_class($mysqlDbConnection));
        $method = $reflection->getMethod('dropTable');
        $method->setAccessible(true);
        $ifExists = false;
        $parameters[0] = $rootTable;
        $parameters[1] = $ifExists;

        $result = $method->invokeArgs($mysqlDbConnection, $parameters);
        $expected = 1;
        $this->assertEquals(
            $expected,
            $result,
            'DatabasesTest, mysqlDbConnection dropTable return check'
        );

        #check to see if it was actually dropped
        $sql = "desc $name;";
        $tableDesc = $this->processMysqli($dbString, $sql);
        $this->assertFalse(
            $tableDesc,
            'DatabasesTest, mysqlDbConnection dropTable table dropped check'
        );
    }

    /*
     * Executes various sql commands used to verify tests
     */
    private function processMysqli($dbString, $sql)
    {
        list($host, $user, $pw, $database) = explode(":", $dbString);

        $mysqli = mysqli_init();
        $mysqli->real_connect($host, $user, $pw, $database);

        $result = $mysqli->query($sql);
        $mysqli->close();

        return $result;
    }
}

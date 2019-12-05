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
    private static $configFile = __DIR__.'/../config/sqlserver.ini';
    private static $expectedCode = EtlException::DATABASE_ERROR;

    protected $ssl = null;
    protected $labelViewSuffix = null;
    protected $tablePrefix = null;
    protected $suffixes = '';
    protected $rowsType = RowsType::ROOT;
    protected $recordIdFieldName = 'record_id';

    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('sqlserver_databases_system_test');
    }

    /**
     * This test creates an empty table.
     */
    public function testSqlServerDbConnectionCreateTableWithPort()
    {
        $configuration = new Configuration(self::$logger, self::$configFile);
        $dbInfo = $configuration->getDbConnection();
        $port = '1433';
        $dbString =$dbInfo . ":$port";

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

        $expectedColumns = ['test_etl_table_id','record_id','full_name','weight'];
        $i = 0;
        foreach ($result as $row) {
            $this->assertEquals(
                $expectedColumns[$i],
                $row->column_name,
                "SqlServerTest, SqlServerDbConnection createTable $expectedColumns[$i] column check"
            );
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
            'pid',
            FieldType::AUTO_INCREMENT,
            null
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'record_id',
            FieldType::INT,
            null
        );
        $rootTable->addField($field1);

        $field2 = new Field(
            'full_name',
            FieldType::CHAR,
            30
        );
        $rootTable->addField($field2);

        $foreignKey = null;
        $suffix = null;
        $data = [
            'record_id' => 1001,
            'full_name' => 'Ima Tester'
        ];
        $rootTable->createRow($data, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data1 = [
            'record_id' => 1002,
            'full_name' => 'Person That Has Way TOOOOO Many Letters in Their Name'
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new Configuration(self::$logger, self::$configFile);
        $dbString = $configuration->getDbConnection();

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
            'record_id' => 1001,
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
        $sql = "select * from $name order by 1;";
        $contents = $this->processSqlSrv($dbString, $sql);
        $expectedRow = new \stdClass;
        $expectedRow->test_id = 1;
        $expectedRow->pid = 0;
        $expectedRow->record_id = 1001;
        $expectedRow->full_name = 'Ima Tester';
        
        $contents[0]->full_name = trim($contents[0]->full_name);
        $this->assertEquals(
            $expectedRow,
            $contents[0],
            "SqlServerTest, SqlServerDbConnection insertRow content check"
        );

        #Check to see if an error will be generated by inserting a row in which
        #the full_name field exceeds its defined length.
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

        $this->assertEquals(
            $expectedMessage6,
            substr($exception->getMessage(), -1*strlen($expectedMessage6)),
            'SqlServerTest, SqlServerDbConnection insertRow expected error error message check'
        );

        #drop the table
        $this->processSqlSrv($dbString, "drop table $name;");
    }

   /**
    * Testing the insertRows method by calling the storeRows method.
    */
    public function testSqlServerDbConnectionInsertRows()
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
            'record_id' => 1001,
            'full_name' => 'Ima Tester',
            'score' => 12.3,
            'update_date' => '',
            'exercise___0' => '' #sending a null '' is a problem--converts null to 0 w/s int, but checkbox is assigned text; changedd Pdo to assign Checkbox to int
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
            'record_id' => 1002,
            'full_name' => 'Spider Webb',
            'score' => 4.56,
            'update_date' => '2017-Jan-31',
            'exercise___0' => '1'
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new Configuration(self::$logger, self::$configFile);
        $dbString = $configuration->getDbConnection();

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
        $expectedRows[0]->test_id = 1;       
        $expectedRows[0]->record_id = 1001;
        $expectedRows[0]->full_name = 'Ima Tester';
        $expectedRows[0]->score = 12.3;
        $expectedRows[0]->update_date = null;
        $expectedRows[0]->exercise___0 = 0;
        $expectedRows[1]->test_id = 2;
        $expectedRows[1]->record_id = 1002;
        $expectedRows[1]->full_name = 'Spider Webb';
        $expectedRows[1]->score = 4.56;
        $expectedRows[1]->update_date = '2017-Jan-31';
        $expectedRows[1]->exercise___0 = 1;

        $sql = "select * from $name order by test_id;";
        $contents = $this->processSqlSrv($dbString, $sql);
        $i = 0;
        foreach ($contents as $row) {
            $row->full_name = trim($row->full_name);
            if ($row->update_date) {
                 $row->update_date = date_format($row->update_date,'Y-M-d');
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
            'record_id' => 1001,
            'full_name' => 'Ima Tester'
        ];
        $rootTable2->createRow($data21, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data22 = [
            'record_id' => 1002,
            'full_name' => 'Person That Has Way TOOOOO Many Letters in Their Name'
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

        $this->assertEquals(
            $expectedMessage,
            substr($exception->getMessage(), -1*strlen($expectedMessage)),
            'SqlServerTest, SqlServerDbConnection insertRows exception error message check'
        );

        #drop the table and view
        $this->processSqlSrv($dbString, "drop table $name;");
        $this->processSqlSrv($dbString, "drop table $name2;");
    }
#========================================================================================
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
                while( $obj = sqlsrv_fetch_object( $result ) ) {
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

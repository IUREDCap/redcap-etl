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
* Integration tests for the MysqlDbConnection class.
*/

class MysqlDatabaseTest extends TestCase
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
        self::$logger = new Logger('mysqldatabase_integration_test');
    }

    /**
    * This tests the SSL MySQL connection option using branch1 of the redcap MySQL
    * database server. It depends on the certificate being in tests/config/interim.crt.
    * If the certificate cannot be found, the test is skipped.
    */
    public function testConstructorWithSsl()
    {
        $caCertFile = __DIR__.'/../config/interim.crt';
        $configFile = __DIR__.'/../config/mysql-ssl.ini';
        $configuration = new Configuration(self::$logger, $configFile);
        if (file_exists($configFile)) {
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
                    'mysqlsDbConnection object created, ssl db user check'
                );
            } else {
                $this->markTestSkipped("Test skipped. $caCertFile not found.");
            }
        } else {
            $this->markTestSkipped("Test skipped. $configFile not found.");
        }
    }

    /**
    * This test creates an empty table.
    */
    public function testCreateTableWithPort()
    {
        $configuration = new Configuration(self::$logger, self::$configFile);
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
            'MysqlDatabaseTest mysqlDbConnection createTable successful return check'
        );

        #Check to see if the table was created as expected.
        $sql = "describe $name;";
        $result = $this->processMysqli($dbString, $sql);
        $this->assertNotNull(
            $result,
            'MysqlDatabaseTest mysqlDbConnection createTable check'
        );

        $expectedColumns = ['test_etl_table_id','record_id','full_name','weight'];
        $i = 0;
        while ($row = $result->fetch_assoc()) {
            $this->assertEquals(
                $expectedColumns[$i],
                $row['Field'],
                "MysqlDatabaseTest mysqlDbConnection createTable $expectedColumns[$i] column check"
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
            'MysqlDataabaseTest mysqlsDbConnection expected error for table already exists exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'mysqlsDbConnection expected error for table already exists error code check'
        );

        $this->assertEquals(
            $expectedMessage4,
            substr($exception->getMessage(), -1*strlen($expectedMessage4)),
            'mysqlsDbConnection expected error for table already exists error message check'
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
            'MysqlDataabaseTest mysqlsDbConnection createTable If Exists check'
        );

        #drop the table
        $this->processMysqli($dbString, "drop table $name;");
    }

    public function testInsertRow()
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
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $foreignKey = null;
        $suffix = null;
        $data = [
            'record_id' => 1001,
            'full_name' => 'Ima Tester'
        ];
        $rootTable->createRow($data, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new Configuration(self::$logger, self::$configFile);

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
        $expectedCount = 1;
        $rows = $rootTable->getRows();
        $this->assertEquals($expectedCount, sizeof($rows), 'MysqlDatabaseTest mysqlDBConnection insertRow row count');
        $row = $rows[0];
        $result = $mysqlDbConnection->insertRow($row);
        $expected = 0;
        $this->assertEquals($expected, $result, 'MysqlDatabaseTest mysqlDbConnection insertRow return check');

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
                'MysqlDatabaseTest mysqlDbConnection insertRow content check'
            );
            $i++;
        }
  
        #Check to see if an error will be generated as expected by trying to create a table
        #that already exists.
        $exceptionCaught6 = false;
        $expectedMessage6 = "[1050]: Table '$name' already exists";

        try {
            $mysqlDbConnection->createTable($rootTable, false);
        } catch (EtlException $exception) {
            $exceptionCaught6 = true;
        }

         $this->assertTrue(
             $exceptionCaught6,
             'MysqlDatabaseTest mysqlsDbConnection insertRow expected error table already exists exception caught'
         );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'MysqlDatabaseTest mysqlsDbConnection insertRow expected error table already exists error code check'
        );

        $this->assertEquals(
            $expectedMessage6,
            substr($exception->getMessage(), -1*strlen($expectedMessage6)),
            'MysqlDatabaseTest mysqlsDbConnection insertRow expected error table already exists error message check'
        );

        #drop the table
        $this->processMysqli($dbString, "drop table $name;");
    }

    /**
    * Testing the processQueryFile method also tests the processQueries method.
    */
    public function testProcessQueryFile()
    {
        #Create the mysqlDbConnection object
        $configuration = new Configuration(self::$logger, self::$configFile);
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

        #####Test file with valid queries that create two tables
        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processMysqli($dbString, "drop table queryTest01;");
        $this->processMysqli($dbString, "drop table queryTest02;");

        # create the file that contains the queries
        $sql = 'create table queryTest01 (rec_id int, title varchar(255), start date, primary key (rec_id));';
        $sql .= 'create table queryTest02 (p_id int, descr text, end date, primary key (p_id));';
        $qFile = './tests/output/queryTestFile.sql';
        $fh = fopen($qFile, 'w');
        fwrite($fh, $sql);
        fclose($fh);

        $result = $mysqlDbConnection->processQueryFile($qFile);
        $expected = 0;
        $this->assertEquals($expected, $result, 'MysqlDatabaseTest mysqlDbConnection processQueries return check');

        #Check to see if the tables were created as expected.
        $sql1 = "describe queryTest01;";
        $result1 = $this->processMysqli($dbString, $sql1);
        $this->assertNotNull(
            $result1,
            'MysqlDatabaseTest mysqlDbConnection processQueries sql command query1 check'
        );

        $expectedColumns1 = ['rec_id', 'title', 'start'];
        $i = 0;
        while ($row = $result1->fetch_assoc()) {
            $this->assertEquals(
                $expectedColumns1[$i],
                $row['Field'],
                "MysqlDatabaseTest mysqlDbConnection processQueries query1 column check"
            );
            $i++;
        }

        $sql2 = "describe queryTest02;";
        $result2 = $this->processMysqli($dbString, $sql2);
        $this->assertNotNull(
            $result2,
            'MysqlDatabaseTest mysqlDbConnection processQueries sql command query2 check'
        );

        $expectedColumns2 = ['p_id', 'descr', 'end'];
        $i = 0;
        while ($row = $result2->fetch_assoc()) {
            $this->assertEquals(
                $expectedColumns2[$i],
                $row['Field'],
                "MysqlDatabaseTest mysqlDbConnection processQueries query2 column check"
            );
            $i++;
        }

        #####Test with invalid file name
        $badFile = 'imaBadFile.abc';
        $exceptionCaught7 = false;
        $expectedMessage7 = 'Could not access query file';

        try {
            $mysqlDbConnection->processQueryFile($badFile);
        } catch (EtlException $exception) {
            $exceptionCaught7 = true;
        }

        $this->assertTrue(
            $exceptionCaught7,
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile bad file name exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile bad file name error code check'
        );

        $this->assertEquals(
            $expectedMessage7,
            substr($exception->getMessage(), 0, 27),
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile bad file name error message check'
        );

        #####Test with an error condition in the 1st query by testing an empty file
        $emptyFile = 'emptyFile.sql';
        $fh = fopen($emptyFile, 'w');
        fwrite($fh, '');
        fclose($fh);

        $exceptionCaught8 = false;
        $expectedMessage8 = 'Post-processing query 1 failed:';

        try {
            $mysqlDbConnection->processQueryFile($emptyFile);
        } catch (EtlException $exception) {
            $exceptionCaught8 = true;
        }

        $this->assertTrue(
            $exceptionCaught8,
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile file exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile file error code check'
        );

        $this->assertEquals(
            $expectedMessage8,
            substr($exception->getMessage(), 0, 31),
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile file error message check'
        );

        #####Test with an error condition in a query other than the first on by sending a bad third query
        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processMysqli($dbString, "drop table queryTest03;");
        $this->processMysqli($dbString, "drop table queryTest04;");

        # create the file that contains the queries, with the third query misspelling 'table'
        $sql3 = 'create table queryTest03 (rec_id int, title varchar(255), start date, primary key (rec_id));';
        $sql3 .= 'create table queryTest04 (p_id int, descr text, end date, primary key (p_id));';
        $sql3 .= 'create tabl queryTest05 (r_id int, fname, primary key (r_id));';
        $qFile3 = './tests/output/badThirdQuery.sql';
        $fh = fopen($qFile3, 'w');
        fwrite($fh, $sql3);
        fclose($fh);

        $exceptionCaught9 = false;
        $expectedMessage9 = 'Post-processing query 2 failed:';

        try {
            $mysqlDbConnection->processQueryFile($qFile3);
        } catch (EtlException $exception) {
            $exceptionCaught9 = true;
        }

        $this->assertTrue(
            $exceptionCaught9,
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile query error exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile query error code check'
        );

        $this->assertEquals(
            $expectedMessage9,
            substr($exception->getMessage(), 0, 31),
            'MysqlDatabaseTest mysqlsDbConnection processQueryFile query error message check'
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
  
    public function testReplaceLookupViewWithoutLookup()
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
            'record_id' => 1001,
            'full_name' => 'Ima Tester'
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
            'record_id' => 1002,
            'full_name' => 'Spider Webb'
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        #############################################################
        # create the table in the database
        #############################################################
        $configuration = new Configuration(self::$logger, self::$configFile);
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
            'MysqlDatabaseTest mysqlsDbConnection replaceLookupView return check'
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
                    "MysqlDatabaseTest mysqlDbConnection replaceLookupView column check"
                );
            }

           #Make sure the rows are as expected
            $rowAsString = implode(',', $row);
            $this->assertEquals(
                $expectedRows[$i],
                $rowAsString,
                'MysqlDatabaseTest mysqlDbConnection replaceLookupView rows check'
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
            'MysqlDatabaseTest mysqlsDbConnection replaceLookupView exception caught'
        );

        $this->assertEquals(
            self::$expectedCode,
            $exception->getCode(),
            'MysqlDatabaseTest mysqlsDbConnection replaceLookupView exception error code check'
        );

        $this->assertEquals(
            $expectedMessage1,
            substr($exception->getMessage(), 0, 20),
            'MysqlDatabaseTest mysqlsDbConnection replaceLookupView exception error maessage check'
        );

        #drop the table and view
        $this->processMysqli($dbString, "drop table $name;");
        $this->processMysqli($dbString, "drop view  $name$labelViewSuffix;");
    }
  
    public function testReplaceLookupViewWithLookup()
    {
        #Create the MysqlDbConnection
        $configuration = new Configuration(self::$logger, self::$configFile);
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
        $field2->usesLookup = 'marital_status';
        $rootTable->addField($field2);

        $foreignKey = null;
        $suffix = null;
        $data1 = [
            'record_id' => 1001,
            'full_name' => 'Ima Tester',
            'marital_status' => 0
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);

        $data2 = [
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
        $lookupTable = new LookupTable($lookupChoices, $tablePrefix, $keyType);

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
            'MysqlDatabaseTest mysqlDbConnection replaceLookupView with Lookup return check'
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
                    "MysqlDatabaseTest mysqlDbConnection replaceLookupViewWithLookup column check"
                );
            }

           #Make sure the rows are as expected
            $rowAsString = implode(',', $row);
            $this->assertEquals(
                $expectedRows[$i],
                $rowAsString,
                'MysqlDatabaseTest mysqlDbConnection replaceLookupViewWithLookup rows check'
            );
            $i++;
        }

        #drop the table
        $this->processMysqli($dbString, "drop table $name;");
    }

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

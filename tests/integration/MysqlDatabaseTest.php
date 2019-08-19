<?php

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;
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
    protected $expectedCode = EtlException::DATABASE_ERROR;

    protected $rootTable;
    protected $ssl = null;
    protected $labelViewSuffix = null;
    protected $tablePrefix = null;
    protected $suffixes = '';
    protected $rowsType = RowsType::ROOT;
    protected $recordIdFieldName = 'record_id';
    private   $caCertFile = null;

    public function testConstructorNoSsslWithPort()
    {
        $sslFlg = false;
        $portFlg = true;
        $dbString = $this->getDbString($sslFlg, $portFlg);
        
        # Create the MysqlDbConnection
        $sslVerify = false;
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $this->caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );

        # verify object was created
        $this->assertNotNull(
            $mysqlDbConnection,
            'mysqlsDbConnection object created, non-ssl db user check'
        );
    }
 
    public function testCreateTableNotExistsWithSslAndNoPort()
    {
        $sslFlg = true;
        $portFlg = false;
        $dbString = $this->getDbString($sslFlg, $portFlg);

        # Create the MysqlDbConnection
        $sslVerify = true;
        $mysqlDbConnection = new MysqlDbConnection(
            $dbString,
            $this->ssl,
            $sslVerify,
            $this->caCertFile,
            $this->tablePrefix,
            $this->labelViewSuffix
        );


        #############################################################
        # create the table object
        #############################################################
        $name = 'test';
        $parent = 'test_id';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);

        $this->rootTable = new Table(
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
        $this->rootTable->addField($field0);

        $field1 = new Field(
            'full_name',
            FieldType::STRING,
            null
        );
        $this->rootTable->addField($field1);

        $field2 = new Field(
            'weight',
            FieldType::INT,
            null
        );
        $this->rootTable->addField($field2);

        #############################################################
        # Execute the tests for this method
        #############################################################
        #first, drop the table, in case it wasn't dropped from a prior test.
        $this->processMysqli('drop table ' . $name . ';');

        # run the createTable method to create the table in the MySQL database
        $createResult = $mysqlDbConnection->createTable($this->rootTable, false);
        $expected = 1;
        $this->assertEquals(
            $createResult,
            $expected,
            'MysqlDatabaseTest mysqlDbConnection createTable successful return check'
        );

        #Check to see if the table was created as expected.
        $sql = "describe $name;";
        $result = $this->processMysqli($sql);
        $this->assertNotNull(
            $result,
            'MysqlDatabaseTest mysqlDbConnection createTable check');

        $expectedColumns = ['test_id','record_id','full_name','weight'];
        $i = 0;
        while($row = $result->fetch_assoc()) {
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
        $expectedMessage4 = "[1050]: Table 'test' already exists";

        try {
            $mysqlDbConnection->createTable($this->rootTable, false);
        } catch (EtlException $exception) {
            $exceptionCaught4 = true;
        }

        $this->assertTrue(
            $exceptionCaught4,
            'MysqlDataabaseTest mysqlsDbConnection expected error for table already exists exception caught'
        );

        $this->assertEquals(
            $this->expectedCode,
            $exception->getCode(),
            'mysqlsDbConnection expected error for table already exists error code check'
        );

        $this->assertEquals(
            $expectedMessage4,
            substr($exception->getMessage(), -35),
            'mysqlsDbConnection expected error for table already exists error message check'
        );

        # try to create the table by setting $ifNotExists and make sure an error is not gewnerated
        $ifExists = true;
        $exceptionCaught5 = false;
        try {
            $mysqlDbConnection->createTable($this->rootTable, $ifExists);
        } catch (EtlException $exception) {
            $exceptionCaught5 = true;
        }

        $this->assertFalse(
            $exceptionCaught5,
            'MysqlDataabaseTest mysqlsDbConnection createTable If Exists check'
        );


    }

    private function processMysqli($sql) {
        $dbString = $this->getDbString(false, false);
        list($host, $user, $pw, $database) = explode(":", $dbString); 

        $mysqli = mysqli_init();
        $mysqli->real_connect($host, $user, $pw, $database);

        return $mysqli->query($sql);
    }

    private function getDbString($sslFlg, $port) {
        $dbString = 'sasrcapmg01.uits.iupui.edu:etltester1:C7pJ_pLUNe*K3:etlTest';
        $this->caCertFile = null;

        if ($sslFlg) {
            $dbString = 'sasrcapmg01.uits.iupui.edu:etltesterssl:C7pJ_pLUNe*K3:etlTest';
            $this->caCertFile = './tests/config/interim.crt';
        }
        if ($port) {
            $dbString .= ':3306'; 
        }
        return $dbString;
    }
}

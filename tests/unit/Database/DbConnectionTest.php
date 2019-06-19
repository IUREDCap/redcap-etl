<?php

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the DbConnection class.
 */
class DbConnectionTest extends TestCase
{
    public function testConnectionString()
    {
        $host     = 'localhost';
        $username = 'etl_user';
        $password = 'This:is:a\:test: \\test\abc\\';
        $dbname   = 'etl_test';
        $port     = '1234';
        
        $values = [$host, $username, $password, $dbname, $port];
        
        $connectionString = DbConnection::createConnectionString($values);
        
        $this->assertNotNull($connectionString, 'Not null connection string');
        $this->assertTrue(is_string($connectionString), 'Connection string is string');
        

        #---------------------------------------------------------------
        # Check that the parsed values from the connection string
        # match the original values.
        #---------------------------------------------------------------
        list($parsedHost, $parsedUsername, $parsedPassword, $parsedDbname, $parsedPort)
            = DbConnection::parseConnectionString($connectionString);
            
        $this->assertEquals($host, $parsedHost, 'Host check');
        $this->assertEquals($username, $parsedUsername, 'Username check');
        $this->assertEquals($password, $parsedPassword, 'Password check');
        $this->assertEquals($dbname, $parsedDbname, 'Dbname check');
        $this->assertEquals($port, $parsedPort, 'Port check');
        
        #----------------------------------------------------------------
        # Check that a connection string created from the parsed values
        # matches the original connection string.
        #----------------------------------------------------------------
        $createdConnectionString = DbConnection::createConnectionString(
            [$parsedHost, $parsedUsername, $parsedPassword, $parsedDbname, $parsedPort]
        );
        $this->assertEquals($connectionString, $createdConnectionString, 'Connection string equals check');
    }
}

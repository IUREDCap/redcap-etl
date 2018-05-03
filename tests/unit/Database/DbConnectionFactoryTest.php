<?php

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the DbConnectionFactory class.
 */
class DbConnectionFactoryTest extends TestCase
{
    public function testParseConnectionString()
    {
        $expectedDbType   = 'CSV';
        $expectedDbString = '/tmp/';
        $connectionString = $expectedDbType.':'.$expectedDbString;
        
        list($dbType, $dbString) = DbConnectionFactory::parseConnectionString($connectionString);
        
        $this->assertEquals($expectedDbType, $dbType);
        $this->assertEquals($expectedDbString, $dbString);
    }
}

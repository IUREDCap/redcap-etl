<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\EtlException;

/**
 * PHPUnit tests for the DbConnection class.
 * This is an abstract class, so only its static methods can be tested.
 */
class DbConnectionTest extends TestCase
{
    public function testConnectionStrings()
    {
        $connectionString = "MySQL:127.0.0.1:etl_user:etlPassword:etl_test";
        $result = DbConnection::parseConnectionString($connectionString);
        $expectedResult =  ['MySQL', '127.0.0.1', 'etl_user', 'etlPassword', 'etl_test'];
        $this->assertEquals($expectedResult, $result, 'Parse test without escapes');

        $newConnectionString = DbConnection::createConnectionString($result);
        $this->assertEquals($connectionString, $newConnectionString, 'Create test without escape');

        $connectionString = "MySQL:127.0.0.1:etl_user:etl\:Pass\word\\\:etl_test";
        $result = DbConnection::parseConnectionString($connectionString);
        $expectedResult =  ['MySQL', '127.0.0.1', 'etl_user', 'etl:Pass\\word\\', 'etl_test'];
        $this->assertEquals($expectedResult, $result, 'Parse test with escapes');


        $values =  ['MySQL', '127.0.0.1', 'etl_user', 'etl:Pass\word', 'etl_test'];
        $connectionString = DbConnection::createConnectionString($values);
        $this->assertEquals(
            "MySQL:127.0.0.1:etl_user:etl\\:Pass\\\\word:etl_test",
            $connectionString,
            'Create test with escapes'
        );
    }


    public function testParseSqlQueriesWithSingleQuery()
    {
        $queries = 'select * from test;';
        $expectedResult = [rtrim($queries, ';')];

        $result = DbConnection::parseSqlQueries($queries);
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseSqlQueriesWithSingleQueryWithoutSemiColon()
    {
        $queries = 'drop table test_db.test';
        $expectedResult = [$queries];

        $result = DbConnection::parseSqlQueries($queries);
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseSqlQueriesWithTwoQueriesOnSameLine()
    {
        $expectedResult = ['select * from test', 'select * from test2'];
        $queries = $expectedResult[0] . ';' . $expectedResult[1] . ';';

        $result = DbConnection::parseSqlQueries($queries);
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseSqlQueriesWithTwoQueriesOnSameLineWithoutEndingSemicolon()
    {
        $expectedResult = ['select * from test', 'select * from test2'];
        $queries = $expectedResult[0] . ';' . $expectedResult[1];

        $result = DbConnection::parseSqlQueries($queries);
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseSqlQueriesWithTwoQueriesAndComments()
    {
        $expectedResult = ['select * from test', 'select * from test2'];
        $queries = "-- This is a comment\n" . $expectedResult[0] . ";\n"
            . "-- This is another comment\n"
            . $expectedResult[1] . ';';

        $result = DbConnection::parseSqlQueries($queries);
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseSqlQueriesWithTwoQueriesAndCommentsWithoutEndingSemicolon()
    {
        $expectedResult = ['select * from test', 'select * from test2'];
        $queries = "-- This is a comment\n" . $expectedResult[0] . ";\n"
            . "-- This is another comment\n"
            . $expectedResult[1];

        $result = DbConnection::parseSqlQueries($queries);
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseSqlQueriesWithSingleQueryWithQuotedValue()
    {
        $expectedResult = ["insert into test (record_id, bmi) values ('12345678', 22.0)"];
        $queries = $expectedResult[0] . ";";

        $result = DbConnection::parseSqlQueries($queries);
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseSqlQueriesWithComplexQueries()
    {
        $expectedResult = [
            "insert into test (record_id, bmi) values ('12345678', 22.0)"
            , "insert into test (record_id, last_name) values ('O''Brien')"
        ];
        $queries = "-- Complex query\n"
            .$expectedResult[0] . ";\n"
            ."-- Another comment\n"
            .$expectedResult[1] . "-- ending end-of-line comment";
            ;

        $result = DbConnection::parseSqlQueries($queries);
        $this->assertEquals($expectedResult, $result);
    }
}

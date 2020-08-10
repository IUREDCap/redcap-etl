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
}

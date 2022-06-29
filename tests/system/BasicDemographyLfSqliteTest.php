<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Database\SqliteDbConnection;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "basic demography" tests using SQLite as the database.
 */
class BasicDemographyLfSqliteTest extends BasicDemographyLfSystemTest
{
    const CONFIG_FILE = __DIR__.'/../config/basic-demography-lf-sqlite.ini';
}

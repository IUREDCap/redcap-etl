<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating forms" tests using SQL Server as the database.
 */
class RepeatingFormsSqlServerTest extends RepeatingFormsSystemTest
{
    const CONFIG_FILE = __DIR__.'/../config/repeating-forms-sqlserver.ini';
}

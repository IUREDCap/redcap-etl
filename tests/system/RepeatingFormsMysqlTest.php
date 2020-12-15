<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating forms" tests using MySQL as the database.
 */
class RepeatingFormsMysqlTest extends RepeatingFormsSystemTest
{
    const CONFIG_FILE = __DIR__.'/../config/repeating-forms-mysql.ini';
}

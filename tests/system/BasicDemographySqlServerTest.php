<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "basic demography" tests using SQL Server as the database.
 */
class BasicDemographySqlServerTest extends BasicDemographySystemTest
{
    const CONFIG_FILE = __DIR__.'/../config/basic-demography-sqlserver.ini';
}

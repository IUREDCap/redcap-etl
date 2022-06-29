<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "basic demography" label field tests using MySQL as the database.
 */
class BasicDemographyLfMysqlTest extends BasicDemographyLfSystemTest
{
    const CONFIG_FILE = __DIR__.'/../config/basic-demography-lf-mysql.ini';
}

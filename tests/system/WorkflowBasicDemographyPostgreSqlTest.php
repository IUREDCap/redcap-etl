<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the basic demography workflow tests using PostgreSQLL as the database.
 */
class WorkflowBasicDemographyPostgreSqlTest extends WorkflowBasicDemographySystemTest
{
    const CONFIG_FILE = __DIR__.'/../config/workflow-basic-demography-postgresql.ini';
}

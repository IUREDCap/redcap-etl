<?php
#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class WorkflowFileIncludeTest extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $csvLabelFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/workflow-file-include.ini';

    public static function setUpBeforeClass(): void
    {
    }


    public function testConstructor()
    {
        $app = basename(__FILE__, '.php');
        self::$logger = new Logger($app);

        $redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
        $this->assertNotNull($redCapEtl, 'redCapEtl not null');

        $config = $redCapEtl->getTaskConfig(0);
        $this->assertNotNull($config, "redCapEtl configuration not null");

        $dbConnection = $config->getDbConnection();
        $this->assertNotNull($dbConnection, "DB connection not null check");
        $this->assertRegExp('/^CSV:/', $dbConnection, "DB connection pattern check");
    }
}

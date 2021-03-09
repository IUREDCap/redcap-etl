<?php
#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class WorkflowBasicDemographyTest extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $csvLabelFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/workflow-basic-demography.ini';

    public static function setUpBeforeClass(): void
    {
    }


    public function testConfigs()
    {
        try {
            $app = basename(__FILE__, '.php');
            self::$logger = new Logger($app);

            $redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            for ($i = 0; $i <= 2; $i++) {
                $config = $redCapEtl->getTaskConfig($i);
                $this->assertNotNull($config, "redCapEtl configuration {$i} not null");

                $dbConnection = $config->getDbConnection();
                $this->assertNotNull($dbConnection, "DB connection {$i} not null check");
                $this->assertMatchesRegularExpression('/^CSV:/', $dbConnection, "DB connection {$i} pattern check");
            }

            $redCapEtl->run();

            $matches = array();
            preg_match('/^CSV:(.*)/', $dbConnection, $matches);
            $dbDirectory = $matches[1];

            $this->assertFileExists($dbDirectory.'/basic_demography.csv', 'basic_demography.csv file check');
            $this->assertFileExists(
                $dbDirectory.'/basic_demography_label_view.csv',
                'basic_demography_label_view.csv file check'
            );

            $demographyLines = count(file($dbDirectory.'/basic_demography.csv'));
            $this->assertEquals(601, $demographyLines, 'Basic demography number of lines check');
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class Workflow1Test extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $csvLabelFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/workflow1.ini';

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

            $config0 = $redCapEtl->getTaskConfig(0);
            $this->assertNotNull($config0, 'redCapEtl configuration 0 not null');

            $config1 = $redCapEtl->getTaskConfig(1);
            $this->assertNotNull($config1, 'redCapEtl configuration 1 not null');

#            print "\n".$config1->getDbConnection()."\n";
            
            $redCapEtl->run();
        } catch (EtlException $exception) {
            print "\n*** ERROR: {$exception->getMessage()}\n";
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }

    public function testArrayConfig()
    {
        $configurationArray = [
            'workflow_name' => "workflow1",
            'ssl_verify'    => 1,
            'db_connection' => 'CSV:../output/workflow1/',
            'log_file'      => '../logs/workflow1.log',
            'print_logging' => false,
            'transform_rules_source' => 3,
            'batch_size'    => 10,

            'basic-demography' => [
                'redcap_api_url'        => 'http://localhost/redcap/api/',
                'data_source_api_token' => '34D499569034F206F4A97E45AB424A4B'
            ],

            'repeating-events' => [
                'redcap_api_url'        => 'http://localhost/redcap/api/',
                'data_source_api_token' => '1F574895CEFC6495798962F2B30D9F77'
            ],

            'repeating-forms' => [
                'redcap_api_url'        => 'http://localhost/redcap/api/',
                'data_source_api_token' => '2C94D35E42823B388AD2D9618D1F9D36',
                'table_prefix'          => 'rf_'
            ]
        ];

        try {
            $app = 'array_test';
            self::$logger = new Logger($app);

            $baseDir = __DIR__;
            $redCapEtl = new RedCapEtl(self::$logger, $configurationArray, null, $baseDir);

            $workflowConfig = $redCapEtl->getWorkflowConfig();

            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            $config0 = $redCapEtl->getTaskConfig(0);
            $this->assertNotNull($config0, 'redCapEtl configuration 0 not null');

            $config1 = $redCapEtl->getTaskConfig(1);
            $this->assertNotNull($config1, 'redCapEtl configuration 1 not null');

            $redCapEtl->run();
        } catch (EtlException $exception) {
            print "\n*** ERROR: {$exception->getMessage()}\n";
            print "\n".$exception->getTraceAsString()."\n";
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }
}

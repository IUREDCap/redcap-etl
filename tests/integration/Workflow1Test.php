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

    const DATA_DIR   = __DIR__.'/../data/workflow1/';
    const OUTPUT_DIR = __DIR__.'/../output/workflow1/';

    public static function setUpBeforeClass(): void
    {
    }

    public function removeOutputFiles()
    {
        # Remove existing CSV output files if any
        foreach (glob(self::OUTPUT_DIR . '*.csv') as $filePath) {
            unlink($filePath);
        }
    }

    public function checkOutputFiles()
    {
        # Check that the lookup file was created
        $this->assertFileExists(self::OUTPUT_DIR . 'etl_lookup.csv', 'Lookup file exists');

        # Check that the lookup file has the expected contents
        $expectedFile = self::DATA_DIR . 'etl_lookup.csv';
        $actualFile = self::OUTPUT_DIR . 'etl_lookup.csv';
        $this->assertFileEquals($expectedFile, $actualFile, 'Lookup file content check');

        # Check that the redcap metadata file has the expected contents
        $expectedFile = self::DATA_DIR . 'redcap_metadata.csv';
        $actualFile = self::OUTPUT_DIR . 'redcap_metadata.csv';
        $this->assertFileEquals($expectedFile, $actualFile, 'REDCap metadata file content check');

        # Check that the redcap project info file has the expected contents
        $expectedFile = self::DATA_DIR . 'redcap_project_info.csv';
        $actualFile = self::OUTPUT_DIR . 'redcap_project_info.csv';
        $this->assertFileEquals($expectedFile, $actualFile, 'REDCap project info file content check');

        # Check that the cardiovascular file has the expected contents
        $expectedFile = self::DATA_DIR . 'cardiovascular.csv';
        $actualFile = self::OUTPUT_DIR . 'cardiovascular.csv';
        $this->assertFileEquals($expectedFile, $actualFile, 'REDCap cardiovascular file content check');
    }

    public function testConfigAndSomeMethods()
    {
        try {
            $this->removeOutputFiles();

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

            $workflow = $redCapEtl->getWorkflow();

            $isStandaloneTask = $workflow->isStandaloneTask();
            $this->assertFalse($isStandaloneTask, 'Is standalone task check');

            $dbSchemas = $workflow->getDbSchemas();
            $this->assertNotNull($dbSchemas, 'Db Schemas not null check');

            $task0 = $workflow->getTask(0);
            $this->assertNotNull($task0, 'Task 0 not null');

            $dbId = $task0->getDbId();
            $this->assertNotEmpty($dbId);

            $dbSchema = $workflow->getDbSchema($dbId);
            $this->assertNotNull($dbSchema);
            $this->assertInstanceOf(Schema\Schema::class, $dbSchema, 'Db Schema instance check');

            # Check the output files
            $this->checkOutputFiles();


            #---------------------------------------
            # Test workflow timing methods
            #---------------------------------------
            $preProcessingTime = $workflow->getPreProcessingTime();
            $this->assertTrue(is_float($preProcessingTime), 'Pre-processing time check');

            $extractTime = $workflow->getExtractTime();
            $this->assertTrue(is_float($extractTime), 'Extract time check');

            $transformTime = $workflow->getTransformTime();
            $this->assertTrue(is_float($transformTime), 'Transform time check');

            $loadTime = $workflow->getLoadTime();
            $this->assertTrue(is_float($loadTime), 'Load time check');

            $postProcessingTime = $workflow->getPostProcessingTime();
            $this->assertTrue(is_float($postProcessingTime), 'Post-processing time check');

            $overheadTime = $workflow->getOverheadTime();
            $this->assertTrue(is_float($overheadTime), 'Overhead time check');

            $totalTime = $workflow->getTotalTime();
            $this->assertTrue(is_float($totalTime), 'Total time check');
        } catch (EtlException $exception) {
            print "\n*** ERROR: {$exception->getMessage()}\n";
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }

    public function testArrayConfig()
    {
        $this->removeOutputFiles();

        $processSections = true;
        $properties = parse_ini_file(self::CONFIG_FILE, $processSections);
        
        $configurationArray = [
            'workflow_name' => "workflow1",
            'ssl_verify'    => 1,
            'db_connection' => 'CSV:../output/workflow1/',
            'log_file'      => '../logs/workflow1.log',
            'transform_rules_source' => 3,

            'batch_size'    => 10,
            'create_lookup_table' => 1,

            'print_logging' => false,

            'basic-demography' => [
                'create_lookup_table' => 1,
                'redcap_api_url'        => $properties['basic-demography']['redcap_api_url'],
                'data_source_api_token' => $properties['basic-demography']['data_source_api_token']
            ],

            'repeating-events' => [
                'redcap_api_url'        => $properties['repeating-events']['redcap_api_url'],
                'data_source_api_token' => $properties['repeating-events']['data_source_api_token']
            ],

            'repeating-forms' => [
                'redcap_api_url'        => $properties['repeating-forms']['redcap_api_url'],
                'data_source_api_token' => $properties['repeating-forms']['data_source_api_token'],
                'table_prefix'          => 'rf_'
            ]
        ];

        # print_r($configurationArray);

        try {
            $app = 'array_test';
            self::$logger = new Logger($app);

            $baseDir = __DIR__;
            $redCapEtl = new RedCapEtl(self::$logger, $configurationArray, null, $baseDir);

            $this->assertNotNull($redCapEtl, 'redCapEtl not null');

            $config0 = $redCapEtl->getTaskConfig(0);
            $this->assertNotNull($config0, 'redCapEtl configuration 0 not null');

            $config1 = $redCapEtl->getTaskConfig(1);
            $this->assertNotNull($config1, 'redCapEtl configuration 1 not null');

            $redCapEtl->run();

            $this->checkOutputFiles();
        } catch (EtlException $exception) {
            print $exception."\n";
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }
    }
}

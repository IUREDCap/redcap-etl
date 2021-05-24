<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for the WorkflowConfig class.
 */
class WorkflowConfigTest extends TestCase
{
    public function setUp(): void
    {
    }


    public function testWorkflowIniConfig()
    {
        $propertiesFile = __DIR__.'/../data/config-testconfiguration.ini';
        $logger = new Logger('test-app');

        $workflowConfig = new WorkflowConfig();
        $this->assertNotNull($workflowConfig, 'Workflow config not null check');
    }

    public function testWorkflowJsonConfig()
    {
        $propertiesFile = __DIR__.'/../data/config-test.json';
        $logger = new Logger('test-app');

        $workflowConfig = new WorkflowConfig();
        $this->assertNotNull($workflowConfig, 'Workflow config not null check');

        $workflowConfig->set($logger, $propertiesFile);

        $taskConfigs = $workflowConfig->getTaskConfigs();
        $this->assertNotNull($taskConfigs, 'Task configs not null check');
        $this->assertEquals(1, count($taskConfigs), 'Task configs count check');

        $taskConfig = $taskConfigs[0];   // Get the first (and only) tsk configuration
        $this->assertNotNull($taskConfigs, 'Task config not null check');

        #---------------------------------------
        # Check task configuration properties
        #---------------------------------------
        $redcapApiUrl = $taskConfig->getProperty(ConfigProperties::REDCAP_API_URL);
        $this->assertEquals('http://localhost/redcap/api/', $redcapApiUrl, 'REDCap_API_URL check');

        $apiToken = $taskConfig->getProperty(ConfigProperties::DATA_SOURCE_API_TOKEN);
        $this->assertEquals('1749009812EE912C129A1294B2192C99', $apiToken, 'DATA_SOURCE_API_TOKEN check');

        $apiToken = $taskConfig->getProperty(ConfigProperties::SSL_VERIFY);
        $this->assertEquals('true', $apiToken, 'SSL_VERIFY check');

        $rulesFile = $taskConfig->getProperty(ConfigProperties::TRANSFORM_RULES_FILE);
        $this->assertEquals('', $rulesFile, 'TRANSFORM_RULES_FILE check');

        $expectedRules =
            "TABLE,demographics,demographics_id,ROOT\n"
            . "FIELD,first_name,string\n"
            . "FIELD,last_name,string\n"
            . "FIELD,address,string\n"
            . "FIELD,telephone,string\n"
            . "FIELD,email,string\n"
            . "FIELD,dob,date\n"
            . "FIELD,ethnicity,varchar(255)\n"
            . "FIELD,race,checkbox\n"
            . "FIELD,sex,varchar(255)\n"
            . "FIELD,height,float\n"
            . "FIELD,weight,int\n"
            . "FIELD,bmi,string\n"
            . "FIELD,comments,string"
            ;

        $rules = $taskConfig->getProperty(ConfigProperties::TRANSFORM_RULES_TEXT);
        $this->assertEquals($expectedRules, $rules, 'TRANSFORM_RULES_TEXT check');

        $rulesSource = $taskConfig->getProperty(ConfigProperties::TRANSFORM_RULES_SOURCE);
        $this->assertEquals('1', $rulesSource, 'TRANSFORM_RULES_SOURCE check');

        $dbSsl = $taskConfig->getProperty(ConfigProperties::DB_SSL);
        $this->assertEquals('false', $dbSsl, 'DB_SSL check');

        $dbSslVerify = $taskConfig->getProperty(ConfigProperties::DB_SSL_VERIFY);
        $this->assertEquals('false', $dbSslVerify, 'DB_SSL_VERIFY check');

        $dbPrimaryKeys = $taskConfig->getProperty(ConfigProperties::DB_PRIMARY_KEYS);
        $this->assertEquals('true', $dbPrimaryKeys, 'DB_PRIMARY_KEYS check');

        $dbForeignKeys = $taskConfig->getProperty(ConfigProperties::DB_FOREIGN_KEYS);
        $this->assertEquals('true', $dbForeignKeys, 'DB_FOREIGN_KEYS check');
    }


    public function testWorkflowArrayConfig()
    {
        $propertiesArray = [
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

        $logger = new Logger('test-app');

        $workflowConfig = new WorkflowConfig();
        $this->assertNotNull($workflowConfig, 'Workflow config not null check');

        $baseDir = __DIR__;
        $workflowConfig->set($logger, $propertiesArray, $baseDir);

        $workflowName = $workflowConfig->getWorkflowName();
        $this->assertEquals($propertiesArray['workflow_name'], $workflowName, 'Workflow name check');

        $taskConfigs = $workflowConfig->getTaskConfigs();

        $this->assertEquals(3, count($taskConfigs), 'Task configs count check');

        #----------------------------------------------------------------
        # Basic demography task
        #----------------------------------------------------------------
        $basicDemographyTaskConfig = $taskConfigs[0];
        $this->assertNotNull($basicDemographyTaskConfig, 'Basic demography task config not null check');

        $logFile = $basicDemographyTaskConfig->getLogFile();
        $expectedLogFile = realpath($baseDir . '/'. $propertiesArray['log_file']);
        $this->assertEquals($expectedLogFile, $logFile, 'Basic demography log file check');

        $rulesSource = $basicDemographyTaskConfig->getTransformRulesSource();
        $this->assertEquals(3, $rulesSource, 'Basic demography transformation rules source check');

        $token = $basicDemographyTaskConfig->getDataSourceApiToken();
        $this->assertEquals(
            $propertiesArray['basic-demography']['data_source_api_token'],
            $token,
            'Basic demography data source API token check'
        );

        $taskName = $basicDemographyTaskConfig->getTaskName();
        $this->assertEquals('basic-demography', $taskName, 'Basic demography task config name check');

        #----------------------------------------------------------------
        # Repeating events task
        #----------------------------------------------------------------
        $repeatingEventsTaskConfig = $taskConfigs[1];
        $this->assertNotNull($repeatingEventsTaskConfig, 'Repeating events task config not null check');

        $logFile = $repeatingEventsTaskConfig->getLogFile();
        $expectedLogFile = realpath($baseDir . '/'. $propertiesArray['log_file']);
        $this->assertEquals($expectedLogFile, $logFile, 'Repeating events log file check');

        $rulesSource = $repeatingEventsTaskConfig->getTransformRulesSource();
        $this->assertEquals(3, $rulesSource, 'Repeating events transformation rules source check');

        $token = $repeatingEventsTaskConfig->getDataSourceApiToken();
        $this->assertEquals(
            $propertiesArray['repeating-events']['data_source_api_token'],
            $token,
            'Repeating events data source API token check'
        );

        $taskName = $repeatingEventsTaskConfig->getTaskName();
        $this->assertEquals('repeating-events', $taskName, 'Repeating events task config name check');

        #----------------------------------------------------------------
        # Repeating forms task
        #----------------------------------------------------------------
        $repeatingFormsTaskConfig = $taskConfigs[2];
        $this->assertNotNull($repeatingFormsTaskConfig, 'Repeating forms task config not null check');

        $logFile = $repeatingFormsTaskConfig->getLogFile();
        $expectedLogFile = realpath($baseDir . '/'. $propertiesArray['log_file']);
        $this->assertEquals($expectedLogFile, $logFile, 'Repeating forms log file check');

        $rulesSource = $repeatingFormsTaskConfig->getTransformRulesSource();
        $this->assertEquals(3, $rulesSource, 'Repeating forms transformation rules source check');

        $token = $repeatingFormsTaskConfig->getDataSourceApiToken();
        $this->assertEquals(
            $propertiesArray['repeating-forms']['data_source_api_token'],
            $token,
            'Repeating forms data source API token check'
        );

        $taskName = $repeatingFormsTaskConfig->getTaskName();
        $this->assertEquals('repeating-forms', $taskName, 'Repeating forms task config name check');
    }

    public function testWorkflowSingleTaskArrayConfig()
    {
        $propertiesArray = [
            'ssl_verify'    => 1,
            'db_connection' => 'CSV:../output/workflow1/',
            'log_file'      => '../logs/workflow1.log',
            'print_logging' => false,
            'transform_rules_source' => 3,
            'batch_size'    => 10,
            'redcap_api_url'        => 'http://localhost/redcap/api/',
            'data_source_api_token' => '34D499569034F206F4A97E45AB424A4B'
        ];

        $logger = new Logger('test-app');

        $workflowConfig = new WorkflowConfig();
        $this->assertNotNull($workflowConfig, 'Workflow config not null check');

        $baseDir = __DIR__;
        $workflowConfig->set($logger, $propertiesArray, $baseDir);

        $taskConfigs = $workflowConfig->getTaskConfigs();

        $this->assertEquals(1, count($taskConfigs), 'Task configs count check');

        #----------------------------------------------------------------
        # Basic demography task
        #----------------------------------------------------------------
        $taskConfig = $taskConfigs[0];
        $this->assertNotNull($taskConfig, 'Task config not null check');

        $logFile = $taskConfig->getLogFile();
        $expectedLogFile = realpath($baseDir . '/'. $propertiesArray['log_file']);
        $this->assertEquals($expectedLogFile, $logFile, 'Log file check');

        $rulesSource = $taskConfig->getTransformRulesSource();
        $this->assertEquals(3, $rulesSource, 'Transformation rules source check');

        $token = $taskConfig->getDataSourceApiToken();
        $this->assertEquals(
            $propertiesArray['data_source_api_token'],
            $token,
            'Data source API token check'
        );

        $taskName = $taskConfig->getTaskName();
        $this->assertEquals('', $taskName, 'Task config name check');
    }


    public function testWorkflowsArrayConfigWithTaskConfig()
    {
        $propertiesArray = [
            'workflow_name' => "workflow_with_task_config",
            'db_connection' => 'CSV:../output/workflow1/',
            'log_file'      => '../logs/workflow1.log',
            'print_logging' => false,
            'transform_rules_source' => 3,
            'batch_size'    => 10,
            'redcap_api_url'        => 'http://localhost/redcap/api/',
            'data_source_api_token' => '34D499569034F206F4A97E45AB424A4B',
            'task1' => [
                'batch_size'    => 20,
                'task_config'   => [
                    'batch_size' => 30
                ]
             ],
            'task2' => [
                'task_config'   => [
                    'batch_size' => 30,
                    'email_to_list' => 'user@someplace.edu'
                ]
             ]
        ];

        $logger = new Logger('test-app');

        $workflowConfig = new WorkflowConfig();
        $this->assertNotNull($workflowConfig, 'Workflow config not null check');

        $baseDir = __DIR__;
        $workflowConfig->set($logger, $propertiesArray, $baseDir);

        $this->taskConfigsCheck($workflowConfig);
    }

    public function testWorkflowsJsonConfigWithTaskConfig()
    {
        $configFile = __DIR__.'/../data/workflow1.json';

        $logger = new Logger('test-app');

        $workflowConfig = new WorkflowConfig();
        $this->assertNotNull($workflowConfig, 'Workflow config not null check');

        $baseDir = __DIR__;
        $workflowConfig->set($logger, $configFile, $baseDir);

        $this->taskConfigsCheck($workflowConfig);
    }

    /**
     * Common code for tests for test_config property.
     */
    public function taskConfigsCheck($workflowConfig)
    {
        $taskConfigs = $workflowConfig->getTaskConfigs();

        $this->assertEquals(2, count($taskConfigs), 'Task configs count check');

        #----------------------------------------------------------------
        # Task 1
        #----------------------------------------------------------------
        $taskConfig = $taskConfigs[0];
        $this->assertNotNull($taskConfig, 'Task 1 config not null check');

        $taskName = $taskConfig->getTaskName();
        $this->assertEquals('task1', $taskName, 'Task 1 config name check');

        $batchSize = $taskConfig->getBatchSize();
        $this->assertEquals(20, $batchSize, 'Task 1 config batch size check');

        #----------------------------------------------------------------
        # Task 2
        #----------------------------------------------------------------
        $taskConfig = $taskConfigs[1];
        $this->assertNotNull($taskConfig, 'Task 2 config not null check');

        $taskName = $taskConfig->getTaskName();
        $this->assertEquals('task2', $taskName, 'Task 2 config name check');

        $batchSize = $taskConfig->getBatchSize();
        $this->assertEquals(10, $batchSize, 'Task 2 config batch size check');

        $emailToList = $taskConfig->getEmailToList();
        $this->assertEquals('user@someplace.edu', $emailToList, 'Task 2 email to-list check');

        $apiUrl = $taskConfig->getRedCapApiUrl();
        $this->assertEquals('http://localhost/redcap/api/', $apiUrl, 'Task 2 API URL check');
    }
}

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
}

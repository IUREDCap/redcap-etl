<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class RulesGenerationTest extends TestCase
{
    private static $logger;
    private static $redCapEtl;

    const CONFIG_FILE = __DIR__.'/../config/basic-demography.ini';

    public static function setUpBeforeClass()
    {
        $app = basename(__FILE__, '.php');
        self::$logger = new Logger($app);
        self::$redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
    }


    public function testDefaultRulesGeneration()
    {
        $addFormCompleteFields = false;
        $addDagFields = false;
        $addFileFields = false;

        $rules = self::$redCapEtl->autoGenerateRules($addFormCompleteFields, $addDagFields, $addFileFields);
        $this->assertNotNull($rules, 'rules not null');
        $this->assertContains('TABLE,demographics,demographics_id,ROOT', $rules, 'table statement check');
        $this->assertNotContains('redcap_data_access_group', $rules, 'DAG field check');
        $this->assertNotContains('demographics_complete', $rules, 'complete field check');
    }

    public function testRulesGenerationWithDagFields()
    {
        $addFormCompleteFields = false;
        $addDagFields = true;
        $addFileFields = false;

        $rules = self::$redCapEtl->autoGenerateRules($addFormCompleteFields, $addDagFields, $addFileFields);
        $this->assertNotNull($rules, 'rules not null');
        $this->assertContains('TABLE,demographics,demographics_id,ROOT', $rules, 'table statement check');
        $this->assertContains('redcap_data_access_group', $rules, 'DAG field check');
        $this->assertNotContains('demographics_complete', $rules, 'complete field check');
    }

    public function testRulesGenerationWithCompleteFields()
    {
        $addFormCompleteFields = true;
        $addDagFields = false;
        $addFileFields = false;

        $rules = self::$redCapEtl->autoGenerateRules($addFormCompleteFields, $addDagFields, $addFileFields);
        $this->assertNotNull($rules, 'rules not null');
        $this->assertContains('TABLE,demographics,demographics_id,ROOT', $rules, 'table statement check');
        $this->assertNotContains('redcap_data_access_group', $rules, 'DAG field check');
        $this->assertContains('demographics_complete', $rules, 'complete field check');
    }
}

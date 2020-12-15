<?php
#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class WorkflowErrorsTest extends TestCase
{
    private static $csvDir;
    private static $csvFile;
    private static $csvLabelFile;
    private static $logger;
    private static $properties;

    const CONFIG_FILE = __DIR__.'/../config/workflow-error1.json';

    public static function setUpBeforeClass()
    {
    }


    public function testConfigs()
    {
        $exceptionCaught = false;
        try {
            $app = basename(__FILE__, '.php');
            self::$logger = new Logger($app);
            $redCapEtl = new RedCapEtl(self::$logger, self::CONFIG_FILE);
        } catch (EtlException $exception) {
            $code = $exception->getCode();
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught check');
        $this->assertEquals(EtlException::INPUT_ERROR, $code, 'Exception code check');
    }
}

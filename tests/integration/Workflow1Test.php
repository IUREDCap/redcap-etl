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

    public static function setUpBeforeClass()
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
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Logger class.
 */
class ConfigPropertiesTest extends TestCase
{
    public function setUp()
    {
    }

    public function testIsValid()
    {
        $isValid = ConfigProperties::isValid('Not a valid property');
        $this->assertFalse($isValid, 'Invalid property test');

        $isValid = ConfigProperties::isValid(ConfigProperties::BATCH_SIZE);
        $this->assertTrue($isValid, 'Valid property test');
    }
}

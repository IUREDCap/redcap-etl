<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the EtlException class.
 */
class EtlExceptionTest extends TestCase
{

    public function setUp()
    {
    }
    
    public function testConstructor()
    {
        $exception = new EtlException('Test exception', 1, null);
        $this->assertNotNull($exception, 'exception not null check');
    }
}

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
class FileUtilTest extends TestCase
{
    public function setUp()
    {
    }

    public function testIsAbsolutePath()
    {
        $absolutePath = '/foo/bar/bang';
        $relativePath = 'foo/bar/bang';

        $isAbsolutePath = FileUtil::isAbsolutePath($absolutePath);
        $this->assertTrue($isAbsolutePath, 'IsAbsolutePath true check');

        $isAbsolutePath = FileUtil::isAbsolutePath($relativePath);
        $this->assertFalse($isAbsolutePath, 'IsAbsolutePath false check');
    }
}

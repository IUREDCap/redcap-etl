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
    public function setUp(): void
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

    public function testGetAbsoluteDir()
    {
        #--------------------------------
        # Current directory test
        #--------------------------------
        $dir  = '.';

        $absoluteDir = FileUtil::getAbsoluteDir($dir);
        $this->assertEquals(getcwd(), $absoluteDir, 'Get absoulte directory check');

        #--------------------------------
        # Null directory test
        #--------------------------------
        $dir  = null;

        $absoluteDir = FileUtil::getAbsoluteDir($dir);
        $this->assertEquals(getcwd(), $absoluteDir, 'Get absoulte directory check');

        #--------------------------------
        # Non-existant directory test
        #--------------------------------
        $dir = 'not/a/real/directory';
        $exceptionCaught = false;
        try {
            $absoluteDir = FileUtil::getAbsoluteDir($dir);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
        }
        $this->assertTrue($exceptionCaught, 'Exception caught for non-existent directory');

        $this->assertEquals(EtlException::INPUT_ERROR, $code, 'Exception code check');
    }

    public function testGetAbsolutePath()
    {
        #--------------------------------
        # Current file path test
        #--------------------------------
        $filePath = __FILE__;
        $fileName = basename($filePath);
        $baseDir  = __DIR__;

        $absolutePath = FileUtil::getAbsolutePath($fileName, $baseDir);

        $this->assertEquals($filePath, $absolutePath, 'Absolute file path check');


        #--------------------------------
        # Null path test
        #--------------------------------
        $absolutePath = FileUtil::getAbsolutePath(null, $baseDir);

        $this->assertEquals($baseDir, $absolutePath, 'Absolute file path check');

        #--------------------------------
        # Non-existant path test
        #--------------------------------
        $path = 'not/a/real/file.txt';
        $exceptionCaught = false;
        try {
            $absolutePath = FileUtil::getAbsolutePath($path);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
        }
        $this->assertTrue($exceptionCaught, 'Exception caught for non-existent path');

        $this->assertEquals(EtlException::INPUT_ERROR, $code, 'Exception code check');
    }
}

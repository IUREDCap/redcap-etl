<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\SystemFunctions;

/**
 * PHPUnit tests for external files for the FileUtil class.
 */
class FileUtilTest extends TestCase
{
    const DATA_DIR = __DIR__.'/../data/';

    
    public function testFileReadAndWrite()
    {
        $content = FileUtil::fileToString(self::DATA_DIR.'file.txt');
        $this->assertEquals($content, "Test data file.", 'file.txt content match.');
        
        $outputFile = self::DATA_DIR.'output.txt';
        $text1 = "This is a test.";
        FileUtil::writeStringToFile($text1, $outputFile);
        $content = FileUtil::fileToString($outputFile);
        $this->assertEquals($content, $text1, 'String write check.');
        
        $text2 = " Another test.";
        FileUtil::appendStringToFile($text2, $outputFile);
        $content = FileUtil::fileToString($outputFile);
        $this->assertEquals($content, $text1 . $text2, 'String append check.');
    }

    public function testFileUtilSetErrorHandler()
    {
        $myMessage = 'error handler test';
        $myCode    = 123;
        
        $myErrorHandler = new ErrorHandler();
        
        FileUtil::setErrorHandler($myErrorHandler);
        $errorHandler = FileUtil::getErrorHandler();
        
        $this->assertSame($myErrorHandler, $errorHandler, 'Error handler check.');
    }
 
    public function testFileUtilSetNullErrorHandler()
    {
        $exceptionCaught = false;
        try {
            FileUtil::setErrorHandler(null);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testFileToStringWithNonExistantFile()
    {
        $exceptionCaught = false;
        try {
            $content = FileUtil::fileToString(self::DATA_DIR.uniqid().'.txt');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INPUT_FILE_NOT_FOUND, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testFileToStringWithUnreadableFile()
    {
        $exceptionCaught = false;
        SystemFunctions::setIsReadableToFail();
        try {
            $content = FileUtil::fileToString(self::DATA_DIR.'file.txt');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INPUT_FILE_UNREADABLE, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetIsReadable();
    }
    
    public function testFileToStringWithSystemFileError()
    {
        $exceptionCaught = false;
        SystemFunctions::setFileGetContentsToFail();
        $error = ['message' => 'System file error.'];
        SystemFunctions::setErrorGetLast($error);
        try {
            $content = FileUtil::fileToString(self::DATA_DIR.'file.txt');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INPUT_FILE_ERROR, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetFileGetContents();
        SystemFunctions::resetErrorGetLast();
    }
    
    
    public function testFileToStringWithUnkownSystemFileError()
    {
        $exceptionCaught = false;
        SystemFunctions::setFileGetContentsToFail();
        try {
            $content = FileUtil::fileToString(self::DATA_DIR.'file.txt');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INPUT_FILE_ERROR, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetFileGetContents();
    }
    

    public function testWriteStringToFileWithSystemFileError()
    {
        $exceptionCaught = false;
        SystemFunctions::setFilePutContentsToFail();
        $error = ['message' => 'System file error.'];
        SystemFunctions::setErrorGetLast($error);
        try {
            FileUtil::writeStringToFile('test', self::DATA_DIR.'output.txt');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::OUTPUT_FILE_ERROR, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetFilePutContents();
        SystemFunctions::resetErrorGetLast();
    }

    public function testWriteStringToFileWithUnkownSystemFileError()
    {
        $exceptionCaught = false;
        SystemFunctions::setFilePutContentsToFail();
        try {
            FileUtil::writeStringToFile('test', self::DATA_DIR.'output.txt');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::OUTPUT_FILE_ERROR, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetFilePutContents();
    }
}

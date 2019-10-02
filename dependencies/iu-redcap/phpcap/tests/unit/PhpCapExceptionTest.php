<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class PhpCapExceptionTest extends TestCase
{
    
    public function testInvalidArgument()
    {
        $message = 'Argument has wrong type.';
        $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
        
        $exceptionCaught = false;
        
        try {
            throw new PhpCapException($message, $code);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getMessage(), $message, 'Message matches.');
            $this->assertEquals($exception->getCode(), $code, 'Code matches.');
        }
        
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testConnectionError()
    {
        $message = 'Unsupported protocol';
        $code    = ErrorHandlerInterface::CONNECTION_ERROR;
        
        $connectionErrorNumber = 1;

        $exceptionCaught = false;
        
        try {
            throw new PhpCapException($message, $code, $connectionErrorNumber);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getMessage(), $message, 'Message matches.');
            $this->assertEquals($exception->getCode(), $code, 'Code matches.');
            $this->assertEquals(
                $exception->getConnectionErrorNumber(),
                $connectionErrorNumber,
                'Connection error number matches.'
            );
        }
        
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testHttpError()
    {
        $message        = "Invalid URL.";
        $code           = ErrorHandlerInterface::INVALID_URL;
        $httpStatusCode = 404;
        
        $exceptionCaught = false;
        
        try {
            $connectionErrorNumber = null;
            throw new PhpCapException($message, $code, $connectionErrorNumber, $httpStatusCode);
        } catch (PHPCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getMessage(), $message, 'Message matches.');
            $this->assertEquals($exception->getCode(), $code, 'Code matches.');
            $this->assertNull($exception->getConnectionErrorNumber(), 'Connection error check.');
            $this->assertEquals($exception->getHttpStatusCode(), $httpStatusCode, 'HTTP status code matches.');
        }

        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
}

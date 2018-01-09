<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for EtlErrorHandler class.
 */
class EtlErrorHandlerTest extends TestCase
{

    public function setUp()
    {
    }
    
    public function testThrowException()
    {
        $errorHandler = new EtlErrorHandler();

        $this->assertNotNull($errorHandler, 'error handler not null');

        $errorMessage = 'Test error';
        $errorCode    = 123;

        $exceptionCaught = false;

        try {
            $errorHandler->throwException($errorMessage, $errorCode);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'exception caught');
    }
}

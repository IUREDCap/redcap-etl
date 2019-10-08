<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class RedCapApiConnectionTest extends TestCase
{
    private $apiUrl;
    
    public function setUp()
    {
        $this->apiUrl = 'https://redcap.someplace.edu/api/';
        $this->connection = new RedCapApiConnection($this->apiUrl);
    }
    
    public function testConnectionCreation()
    {
        $sslVerify = true;
        $caCertificateFile = __DIR__.'/../data/file.txt'; # file needs to exist
        $errorHandler = new ErrorHandler();
        
        $connection = new RedCapApiConnection(
            $this->apiUrl,
            $sslVerify,
            $caCertificateFile,
            $errorHandler
        );
        
        $this->assertEquals($this->apiUrl, $connection->getUrl(), 'URL check.');
        $this->assertEquals($sslVerify, $connection->getSslVerify(), 'SSL verify check.');
        $this->assertEquals(
            $caCertificateFile,
            $connection->getCaCertificateFile(),
            'CA certificate file check.'
        );
        $this->assertSame(
            $errorHandler,
            $connection->getErrorHandler(),
            'Error handler check.'
        );
    }
    
    public function testCall()
    {
        $expectedResponse = 'call test';
        SystemFunctions::setCurlExecResponse($expectedResponse);
        
        $response = $this->connection->call('data');
        $this->assertEquals($expectedResponse, $response, 'Response check.');
        SystemFunctions::setCurlExecResponse('');
    }
    
    public function testConnectionSetErrorHandler()
    {
        $errorHandler = new ErrorHandler();
        $errorHandlerFromGet = $this->connection->getErrorHandler();
        $this->assertNotSame($errorHandler, $errorHandlerFromGet, 'Error handler get check.');
        
        $this->connection->setErrorHandler($errorHandler);
        $errorHandlerFromGet = $this->connection->getErrorHandler();
        $this->assertSame($errorHandler, $errorHandlerFromGet, 'Error handler set check.');
    }
    
    public function testSetUrl()
    {
        $this->assertEquals($this->apiUrl, $this->connection->getUrl(), 'Get URL check.');
        
        $newUrl = 'https://redcap.somewhere.edu/api/';
        $this->connection->setUrl($newUrl);
        
        $this->assertEquals($newUrl, $this->connection->getUrl(), 'Set URL check.');
    }
    
    public function testSetSslVerify()
    {
        $this->assertEquals(false, $this->connection->getSslVerify(), 'Get SSL verify check.');
        
        $this->connection->setSslVerify(true);
        $this->assertEquals(true, $this->connection->getSslVerify(), 'Set SSL verify check.');
    }
    
    
    public function testSetCaCertificateFile()
    {
        $this->assertEquals(
            '',
            $this->connection->getCaCertificateFile(),
            'Get CA certificate file check.'
        );
        
        $caFile = 'ca.crt';
        $this->connection->setCaCertificateFile($caFile);
        $this->assertEquals(
            $caFile,
            $this->connection->getCaCertificateFile(),
            'Set CA certificate file check.'
        );
    }
    
    public function testSetTimeoutInSeconds()
    {
        $this->assertEquals(
            RedCapApiConnection::DEFAULT_TIMEOUT_IN_SECONDS,
            $this->connection->getTimeOutInSeconds(),
            'Get timeout in seconds check.'
        );
        
        $timeout = RedCapApiConnection::DEFAULT_TIMEOUT_IN_SECONDS + 600;
        $this->connection->setTimeOutInSeconds($timeout);
        $this->assertEquals(
            $timeout,
            $this->connection->getTimeoutInSeconds(),
            'Set timeout in seconds check.'
        );
    }
    
    
    public function testSetConnectionTimeoutInSeconds()
    {
        $this->assertEquals(
            RedCapApiConnection::DEFAULT_CONNECTION_TIMEOUT_IN_SECONDS,
            $this->connection->getConnectionTimeOutInSeconds(),
            'Get connection timeout in seconds check.'
        );
        
        $timeout = RedCapApiConnection::DEFAULT_CONNECTION_TIMEOUT_IN_SECONDS + 120;
        $this->connection->setConnectionTimeOutInSeconds($timeout);
        $this->assertEquals(
            $timeout,
            $this->connection->getConnectionTimeoutInSeconds(),
            'Set connection timeout in seconds check.'
        );
    }
    
    public function testCaCertificateFileNotFound()
    {
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection($this->apiUrl, true, uniqid().".txt");
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                $exception->getCode(),
                ErrorHandlerInterface::CA_CERTIFICATE_FILE_NOT_FOUND,
                'CA cert file not found.'
            );
        }
        $this->assertTrue($caughtException, 'Caught CA cert file not found exception.');
    }
    
    public function testCaCertificateFileUnreadable()
    {
        SystemFunctions::setIsReadableToFail();
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection($this->apiUrl, true, __FILE__);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                $exception->getCode(),
                ErrorHandlerInterface::CA_CERTIFICATE_FILE_UNREADABLE,
                'CA cert file is unreadable.'
            );
        }
        $this->assertTrue($caughtException, 'Caught CA cert file unreadable exception.');
        SystemFunctions::resetIsReadable();
    }
    
    
    public function testCurlErrorWithNoMessage()
    {
        $stringError = 'Peer certificate cannot be authenticated with given CA certificates 6';
        
        SystemFunctions::setCurlErrorInfo($number  = 60, $message = '', $stringError);
        
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection($this->apiUrl);
            $apiConnection->call('data');
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                $exception->getCode(),
                ErrorHandlerInterface::CONNECTION_ERROR,
                'Exception code check.'
            );
            $this->assertEquals($stringError, $exception->getMessage(), 'Message check.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
        SystemFunctions::setCurlErrorInfo(0, '', '');
    }
    
    public function testCurlErrorWithNoMessageOrMessageString()
    {
        SystemFunctions::setCurlErrorInfo($number = 60, $message = '', $stringError = null);
        
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection($this->apiUrl);
            $apiConnection->call('data');
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(
                ErrorHandlerInterface::CONNECTION_ERROR,
                $code,
                'Exception code check.'
            );
            # The error code should be contained in the error message
            $this->assertContains(strval($code), $exception->getMessage(), 'Message check.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
        SystemFunctions::setCurlErrorInfo(0, '', '');
    }
    
    public function testCallWithCurlError()
    {
        $data = array(
                'token' => '12345678901234567890123456789012',
                'content' => 'project',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        
        SystemFunctions::setCurlExecResponse('OK');
        SystemFunctions::$curlErrorNumber = 3;
        SystemFunctions::$curlErrorMessage = 'The URL was not properly formatted.';
        $exceptionCaught = false;
        try {
            $result = $this->connection->callWithArray($data);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::CONNECTION_ERROR,
                $exception->getCode(),
                'Exception code check.'
            );
            $this->assertEquals(
                SystemFunctions::$curlErrorNumber,
                $exception->getConnectionErrorNumber(),
                'Connection error number check.'
            );
            $this->assertEquals(
                SystemFunctions::$curlErrorMessage,
                $exception->getMessage(),
                'Exception message check.'
            );
        }
        $this->assertTrue($exceptionCaught);
        SystemFunctions::setCurlExecResponse('');
        SystemFunctions::$curlErrorNumber = 0;
        SystemFunctions::$curlErrorMessage = '';
    }
    
    public function testCallWithHttpCode301()
    {
        SystemFunctions::setCurlExecResponse('OK');
        SystemFunctions::$httpCode = 301;
        $exceptionCaught = false;
        try {
            $result = $this->connection->call('data');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_URL,
                $exception->getCode(),
                'Exception code check.'
            );
            $this->assertEquals(
                SystemFunctions::$httpCode,
                $exception->getHttpStatusCode(),
                'HTTP status code check.'
            );
        }
        $this->assertTrue($exceptionCaught);
        SystemFunctions::setCurlExecResponse('');
        SystemFunctions::$httpCode = null;
    }

    public function testCallWithHttpCode404()
    {
        SystemFunctions::setCurlExecResponse('OK');
        SystemFunctions::$httpCode = 404;
        $exceptionCaught = false;
        try {
            $result = $this->connection->call('data');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                $exception->getCode(),
                ErrorHandlerInterface::INVALID_URL,
                'Exception code check.'
            );
            $this->assertEquals(
                $exception->getHttpStatusCode(),
                SystemFunctions::$httpCode,
                'HTTP status code check.'
            );
        }
        $this->assertTrue($exceptionCaught);
        SystemFunctions::setCurlExecResponse('');
        SystemFunctions::$httpCode = null;
    }
    
    public function testCallWithInvalidData()
    {
        $exceptionCaught = false;
        try {
            # data should be a string
            $this->connection->call(123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
}

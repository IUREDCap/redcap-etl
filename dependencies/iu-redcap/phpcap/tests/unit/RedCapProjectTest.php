<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class RedCapProjectTest extends TestCase
{
    private $apiUrl;
    private $apiToken;
    private $connection;
    private $redCapProject;
    
    public function setUp()
    {
        $this->apiUrl   = 'https://redcap.somplace.edu/api/';
        $this->apiToken = '12345678901234567890123456789012';
        $this->connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')
            ->getMock();
        
        $this->redCapProject = new RedCapProject(
            $this->apiUrl,
            $this->apiToken,
            null,
            null,
            null,
            $this->connection
        );
    }
    
    
    public function testCreateProjectWithNullApiUrl()
    {
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(null, $this->apiToken);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    
    public function testCreateProjectWithNonStringApiUrl()
    {
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(123, $this->apiToken);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
            $this->assertContains('integer', $exception->getMessage(), 'Message content check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    
    public function testCreateProjectWithNullApiToken()
    {
        #----------------------------------
        # Null API token
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject($this->apiUrl, null);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Null API token exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Null API token exception caught.');
    }
    
    public function testCreateProjectWithApiTokenWithInvalidType()
    {
        #----------------------------------
        # API token with invalid type
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject($this->apiUrl, 123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'API token with wrong type.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateProjectwithApiTokenWithInvalidCharacter()
    {
        #----------------------------------
        # API token with invalid character
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject($this->apiUrl, '1234567890123456789012345678901G');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'API token with invalid character exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'API token with invalid character exception caught.');
    }
    
    
    public function testCreateProjectWithApiTokenWithIncorrectLength()
    {
        #----------------------------------
        # API token with incorrect length
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject($this->apiUrl, '1234567890123456789012345678901');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'API token with incorrect length exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'API token with incorrect length exception caught.');
    }
    
    public function testCreateProjectWithSslVerifyWithInvalidType()
    {
        #----------------------------------
        # SSL verify with invalid type
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject($this->apiUrl, '12345678901234567890123456789012', 123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'SSL verify with wrong type exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'SSL verify with wrong type exception caught.');
    }
    
    public function testCreateProjectWithCaCertificateFileWithInvalidType()
    {
        #--------------------------------------
        # CA certificate file with invalid type
        #--------------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject($this->apiUrl, '12345678901234567890123456789012', true, 123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'CA certificate file with wrong type exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'CA certificate file with wrong type exception caught.');
    }
    
    
    public function testSetErrorHandler()
    {
        $errorHandler = $this->redCapProject->getErrorHandler();
        $this->assertNotNull($errorHandler, 'Error handler not null check.');
        
        $this->assertTrue($errorHandler instanceof ErrorHandlerInterface, 'Error handler class check.');
        
        $myErrorHandler = new ErrorHandler();
        
        $this->redCapProject->setErrorHandler($myErrorHandler);
        
        # Test that error handler retrieved is the same one that was set
        $retrievedErrorHandler = $this->redCapProject->getErrorHandler();
        $this->assertSame($myErrorHandler, $retrievedErrorHandler, 'Error handler get check.');
    }
    
    
    public function testSetConnection()
    {
        $connection = $this->redCapProject->getConnection();
        $this->assertNotNull($connection, 'Connection not null check.');
        
        $this->assertTrue($connection instanceof RedCapApiConnectionInterface, 'Connection class check.');
        
        $url = $connection->getUrl();
        
        $myConnection = new RedCapApiConnection($url);
        
        $this->redCapProject->setConnection($myConnection);
        
        # Test that connection retrieved is the same one that was set
        $retrievedConnection = $this->redCapProject->getConnection();
        $this->assertSame($myConnection, $retrievedConnection, 'Connection get check.');
    }
}

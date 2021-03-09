<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\PhpCapException;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Logger class.
 */
class LoggerTest extends TestCase
{
    private $project;
    private static $customLog;

    public function setUp(): void
    {
        $apiUrl   = 'https://someplace.edu/api/';
        $apiToken = '11111111112222222222333333333344';

        $this->project = new TestProject($apiUrl, $apiToken);
        $this->project->setApp('LoggerTest');

        self::$customLog = array();
    }
    
    public function testConstructor()
    {
        $logger = new Logger('test-app');
        $this->assertNotNull($logger, 'logger not null check');
    }

    public function testLog()
    {
        $logMessages = ['Test 1', 'Test 2', 'Test 3'];
        $logApps     = array_fill(0, count($logMessages), $this->project->getApp());

        $logger = new Logger($this->project->getApp());
        $logger->setPrintLogging(false);

        $logFile = __DIR__.'/../logs/logger-test-log.txt';
        $logger->setLogFile($logFile);
        $getLogFile = $logger->getLogFile();
        $this->assertEquals($logFile, $getLogFile, 'Log file set/get test');

        file_put_contents($logFile, '');  // Clear any existing file contents
        foreach ($logMessages as $logMessage) {
            $logger->log($logMessage);
        }

        $contents = file_get_contents($logFile);
        $this->assertMatchesRegularExpression('/Test 1/', $contents, 'Test1 log contents test');
        $this->assertMatchesRegularExpression('/Test 2/', $contents, 'Test2 log contents test');
        $this->assertMatchesRegularExpression('/Test 3/', $contents, 'Test3 log contents test');
    }
    
    public function testGetApp()
    {
        $logger = new Logger($this->project->getApp());

        $app = $logger->getApp();
        $this->assertEquals($this->project->getApp(), $app, 'get app check');
    }

    public function testGetLogId()
    {
        $logger = new Logger($this->project->getApp());

        $logId = $logger->getLogId();
        $this->assertNotNull($logId, 'Log ID not null check');
        $this->assertMatchesRegularExpression('/^[a-fA-F0-9]+\.[0-9]+$/', $logId, 'Log ID pattern match');
    }

    public function testLogEmail()
    {
        $logger = new Logger($this->project->getApp());
        $logger->setPrintLogging(false);

        $from    = 'redcap-etl@iu.edu';
        $to      = 'admin1@iu.edu,admin2@iu.edu';
        $subject = 'REDCap-ETL Notification';

        $logger->setLogEmail($from, $to, $subject);

        $getFrom = $logger->getLogFromEmail();
        $this->assertEquals($from, $getFrom, 'From e-mail check');

        $getTo  = $logger->getLogToEmail();
        $this->assertEquals($to, $getTo, 'To e-mail check');

        $getSubject  = $logger->getLogEmailSubject();
        $this->assertEquals($subject, $getSubject, 'E-mail subject check');

        # Try to reset the from e-mail
        $newFrom = 'redcap@iu.edu';
        $logger->setLogFromEmail($newFrom);
        $getNewFrom = $logger->getLogFromEmail();
        $this->assertEquals($newFrom, $getNewFrom, 'From e-mail set/get check');

        # Try to reset the to e-mail
        $newTo = 'admin@iu.edu';
        $logger->setLogToEmail($newTo);
        $getNewTo = $logger->getLogToEmail();
        $this->assertEquals($newTo, $getNewTo, 'To e-mail set/get check');

        #-------------------------------------------
        # Test sending e-mails
        #-------------------------------------------
        SystemFunctions::setOverrideMail(true);
        $errorMessage = 'Test error';
        $logger->logEmailSummary($errorMessage);
        $mailArguments = SystemFunctions::getMailArguments();
        list($to, $mailSubject, $message, $additionalHeaders, $addtionalParameters) = array_pop($mailArguments);
        $this->assertEquals($newTo, $to, 'To e-mail send check');
        $this->assertMatchesRegularExpression('/'.$errorMessage.'/', $message, 'Message send check');
        $this->assertEquals($subject, $mailSubject, 'Message send check');

        # Test array of to e-mails
        $newTo = array('admin1@iu.edu', 'admin2@iu.edu', 'admin3@iu.edu');
        $logger->setLogToEmail($newTo);
        $logger->logEmailSummary($errorMessage);
        $mailArguments = SystemFunctions::getMailArguments();
        for ($i = count($newTo) - 1; $i >= 0; $i--) {
            list($to, $mailSubject, $message, $additionalHeaders, $addtionalParameters) = array_pop($mailArguments);
            $this->assertEquals($newTo[$i], $to, 'Array to e-mails send check '.$i);
        }

        # Test multiple to e-mails
        $multTo = 'admin1@iu.edu,admin2@iu.edu,admin3@iu.edu';
        $logger->setLogToEmail($multTo);
        $logger->logEmailSummary($errorMessage);
        $mailArguments = SystemFunctions::getMailArguments();
        for ($i = count($newTo) - 1; $i >= 0; $i--) {
            list($to, $mailSubject, $message, $additionalHeaders, $addtionalParameters) = array_pop($mailArguments);
            $this->assertEquals($newTo[$i], $to, 'Multiple to e-mails send check '.$i);
        }

        SystemFunctions::setOverrideMail(false);
    }

    public function testPrintLogging()
    {
        $logger = new Logger($this->project->getApp());

        $printLogging = $logger->getPrintLogging();
        $this->assertTrue($printLogging, 'Default print logging test');

        $logger->setPrintLogging(false);
        $printLogging = $logger->getPrintLogging();
        $this->assertFalse($printLogging, 'Set print logging false test');

        $logger->setPrintLogging(true);
        $printLogging = $logger->getPrintLogging();
        $this->assertTrue($printLogging, 'Set print logging true test');
    }
    
    public function testEmailSummary()
    {
        $logger = new Logger($this->project->getApp());

        $emailSummary = $logger->getEmailSummary();
        $this->assertFalse($emailSummary, 'Default email summary test');

        $logger->setEmailSummary(true);
        $emailSummary = $logger->getEmailSummary();
        $this->assertTrue($emailSummary, 'Set email summary true test');

        $logger->setEmailSummary(false);
        $emailSummary = $logger->getEmailSummary();
        $this->assertFalse($emailSummary, 'Set email summary false test');
    }

    public function testEmailErrors()
    {
        $logger = new Logger($this->project->getApp());

        $emailErrors = $logger->getEmailErrors();
        $this->assertTrue($emailErrors, 'Default email errors test');

        $logger->setEmailErrors(false);
        $emailErrors = $logger->getEmailErrors();
        $this->assertFalse($emailErrors, 'Set email errors false test');

        $logger->setEmailErrors(true);
        $emailErrors = $logger->getEmailErrors();
        $this->assertTrue($emailErrors, 'Set email errors true test');
    }
    
    public function testLoggingCallback()
    {
        $loggingCallback = array($this, 'loggingCallback');  // object method callback

        $logger = new Logger($this->project->getApp());
        $this->assertNotNull($logger);

        $logger->setPrintLogging(false);
        $logger->setLoggingCallback($loggingCallback);

        $logValue = 'This is a test';
        $logger->log($logValue);
        $loggedValue = self::$customLog[0];
        $this->assertEquals($logValue, $loggedValue, 'log method for callback test');

        $logValue = 'Test of callback logging method';
        $logger->logToCallback($logValue);
        $loggedValue = self::$customLog[1];
        $this->assertEquals($logValue, $loggedValue, 'logToCallback method test');

        # Test turning logger off (new message should NOT be logged)
        $logger->setOn(false);
        $logValue = 'On test';
        $logger->log($logValue);
        $numLogMessages = count(self::$customLog);
        $this->assertEquals(2, $numLogMessages, 'Set off log messages count');

        # Test turning logger back on (new message should be logged)
        $logger->setOn(true);
        $logger->log($logValue);
        $numLogMessages = count(self::$customLog);
        $this->assertEquals(3, $numLogMessages, 'Set on log messages count');


        #$logValue = 'Logging error test';
        #$exception = new \Exception($logValue);
        #$logger->logLoggingError($exception);
        #$loggedValue = self::$customLog[2];
        #print "\n\n{$loggedValue}\n\n";
        #$this->assertEquals($logValue, $loggedValue, 'logToCallback method test');
    }

    public function loggingCallback($message)
    {
        array_push(self::$customLog, $message);
    }
    
    public function testLogException()
    {
        $logger = new Logger($this->project->getApp());
        $logger->setPrintLogging(false);
        
        $logFile = __DIR__.'/../logs/test-log-exception.txt';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        $logger->setLogFile($logFile);
        
        $message = 'This is an exception log test.';
        $code = EtlException::INPUT_ERROR;
        $exception = new EtlException($message, $code);
        $logger->logException($exception);
        
        # Get only the first line of the file, since it will have the
        # error messages. The other lines will contain a stack trace.
        $fh = fopen($logFile, 'r');
        $logEntry = fgets($fh);
        fclose($fh);
        
        # Remove the timestamp and trailing newline
        list($date, $time, $logId, $fileMessage) = explode(' ', $logEntry, 4);
        $fileMessage = trim($fileMessage);

        $this->assertEquals(
            $message,
            $fileMessage,
            'Log exception check'
        );
        
        #----------------------------------------------
        # Test with error_log
        #----------------------------------------------
        $logger->setLogFile(null);   # turn off file logging
        $logFile = __DIR__.'/../logs/test-log-exception-to-error-log.txt';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        #ini_set("log_errors", 1);
        #ini_set("error_log", $logFile);
        $logger->setLogFile($logFile);
                
        $logger->logException($exception);
                
        # Get only the first line of the file, since it will have the
        # error messages. The other lines will contain a stack trace.
        $fh = fopen($logFile, 'r');
        $logEntry = fgets($fh);
        fclose($fh);
        
        list($date, $time, $logId, $fileMessage) = explode(' ', $logEntry, 4);
        $fileMessage = preg_replace('/\s+$/', '', $fileMessage);

        # Remove the timestamp and trailing newline
        #list($timestamp, $fileMessage) = explode('] ', $logEntry);
        #$fileMessage = trim($fileMessage);

        $this->assertEquals(
            $message,
            $fileMessage,
            'Log exception to error_log check'
        );
    }
    
    public function testLogPhpCapException()
    {
        $logger = new Logger($this->project->getApp());
        $logger->setPrintLogging(false);
        
        $logFile = __DIR__.'/../logs/test-log-phpcap-exception.txt';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        $logger->setLogFile($logFile);
        
        $phpcapMessage = 'REDCap API error.';
        $code = ErrorHandlerInterface::REDCAP_API_ERROR;
        $phpcapException = new PhpCapException($phpcapMessage, $code);
       
        $message = 'PHPCap error.';
        $code = EtlException::PHPCAP_ERROR;
        $exception = new EtlException($message, $code, $phpcapException);
        $logger->logException($exception);
        
        $combinedMessage = $message.' - Caused by PHPCap exception: '.$phpcapMessage;
        
        # Get only the first line of the file, since it will have the
        # error messages. The other lines will contain a stack trace.
        $fh = fopen($logFile, 'r');
        $logEntry = fgets($fh);
        fclose($fh);
        
        # Remove the timestamp and trailing newline
        list($date, $time, $logId, $fileMessage) = explode(' ', $logEntry, 4);
        $fileMessage = trim($fileMessage);

        $this->assertEquals(
            $combinedMessage,
            $fileMessage,
            'Log exception check'
        );
    }
}

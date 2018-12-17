<?php

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

    public function setUp()
    {
        $apiUrl   = 'https://someplace.edu/api/';
        $apiToken = '11111111112222222222333333333344';

        $this->project = new TestProject($apiUrl, $apiToken);
        $this->project->setApp('LoggerTest');
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
        $logger->setLogProject($this->project);
        $logger->setPrintLogging(false);

        $logFile = __DIR__.'/../logs/logger-test-log.txt';
        $logger->setLogFile($logFile);
        $getLogFile = $logger->getLogFile();
        $this->assertEquals($logFile, $getLogFile, 'Log file set/get test');

        foreach ($logMessages as $logMessage) {
            $logger->log($logMessage);
        }

        # Get the records from the project and check them
        $logRecords = $this->project->getAllRecords();
        $logRecordMessages = array_column($logRecords, 'message');
        $logRecordApps     = array_column($logRecords, 'app');

        $this->assertEquals($logMessages, $logRecordMessages, 'Log message check');

        $this->assertEquals($logApps, $logRecordApps, 'Log app check');

        /*
        SystemFunctions::setOverrideErrorLog(true);
        $this->project->setImportGeneratesException(true);
        $logger->log('This is a test.');
        $lastErrorLogMessage = SystemFunctions::getLastErrorLogMessage();
        $this->assertEquals(
            'Logging to project failed: data import error',
            $lastErrorLogMessage,
            'Import exception check'
        );
        SystemFunctions::setOverrideErrorLog(false);
        */
    }
    
    public function testGetApp()
    {
        $logger = new Logger($this->project->getApp());

        $app = $logger->getApp();
        $this->assertEquals($this->project->getApp(), $app, 'get app check');
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
        $logger->logToEmail($errorMessage);
        $mailArguments = SystemFunctions::getMailArguments();
        list($to, $mailSubject, $message, $additionalHeaders, $addtionalParameters) = array_pop($mailArguments);
        $this->assertEquals($newTo, $to, 'To e-mail send check');
        $this->assertEquals($message, $errorMessage, 'Message send check');
        $this->assertEquals($subject, $mailSubject, 'Message send check');

        # Test array of to e-mails
        $newTo = array('admin1@iu.edu', 'admin2@iu.edu', 'admin3@iu.edu');
        $logger->setLogToEmail($newTo);
        $logger->logToEmail($errorMessage);
        $mailArguments = SystemFunctions::getMailArguments();
        for ($i = count($newTo) - 1; $i >= 0; $i--) {
            list($to, $mailSubject, $message, $additionalHeaders, $addtionalParameters) = array_pop($mailArguments);
            $this->assertEquals($newTo[$i], $to, 'Array to e-mails send check '.$i);
        }

        # Test multiple to e-mails
        $multTo = 'admin1@iu.edu,admin2@iu.edu,admin3@iu.edu';
        $logger->setLogToEmail($multTo);
        $logger->logToEmail($errorMessage);
        $mailArguments = SystemFunctions::getMailArguments();
        for ($i = count($newTo) - 1; $i >= 0; $i--) {
            list($to, $mailSubject, $message, $additionalHeaders, $addtionalParameters) = array_pop($mailArguments);
            $this->assertEquals($newTo[$i], $to, 'Multiple to e-mails send check '.$i);
        }


        SystemFunctions::setOverrideMail(false);
    }
    
    /*
    public function testLogError()
    {
        $logger = new Logger($this->project->getApp());
        $logger->setPrintLogging(false);

        SystemFunctions::setOverrideErrorLog(true);
        $message = 'This is an error log test.';
        $logger->logError($message);
        $lastErrorLogMessage = SystemFunctions::getLastErrorLogMessage();
        $this->assertEquals(
            $message,
            $lastErrorLogMessage,
            'Log error check'
        );
        SystemFunctions::setOverrideErrorLog(false);
    }
    */
    
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
        list($timestamp, $fileMessage) = explode(': ', $logEntry);
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
        
        list($date, $time, $fileMessage) = explode(' ', $logEntry, 3);
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
        list($timestamp, $fileMessage) = explode(': ', $logEntry, 2);
        $fileMessage = trim($fileMessage);

        $this->assertEquals(
            $combinedMessage,
            $fileMessage,
            'Log exception check'
        );
    }
}

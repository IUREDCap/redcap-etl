<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\RedCapEtl\Database\CsvDbConnection;

/**
 * Class for logging.
 *
 * Info - logged to file (if file set), logged to project (if set), printed (unless unset)
 * Exception/Error - logged to file (if set), logged to project (if set),
 *         sent by e-mail (if from and to addresses set)
 *
 * If a message is not logged (either because no logging was specified, or specified
 * logging failed), then an attempt is made to log the message to the PHP system log.
 */
class Logger
{
    // Error message to pass to logEmailSummary when you want to log an error, but don't
    // want to add an error message the output.
    const NO_PRINT_ERROR_MESSAGE = 'no-print-error-message';

    private $workflowLogger;  // For a logger for a task, the logger for the workflow that the task belongs to

    private $logId;
    
    private $isOn;
    
    /** @var string the name of the log file (if any) */
    private $logFile;

    /** @var boolean Whether messages logged should be printed to standard output */
    private $printLogging;

    /** @var string For project logging, the app name to use. */
    private $app;

    /** @var string For project logging, the record ID base. */
    private $projectIdBase;
    private $projectIndex;
    private $projectDate;


    private $emailErrors;
    
    /** @var boolean indicates if an e-mail logging summary should be sent to
                     log to e-mail list; defaults to false. */
    private $emailSummary;
    
    /** @var string The from e-mail address for e-mail log messages. */
    private $logFromEmail;
    
    private $logToEmail;
    private $logEmailSubject;
    


    /** @var array Array of log messages (in effect an in-memory log). */
    private $logArray;

    /** @var DbConnection the database connection; used for database logging. */
    private $dbConnection;
    
    /** @var boolean indicates if database logging should be done. */
    private $dbLogging;
    
    /** @var EtlLogTable the main database logging table. */
    private $dbLogTable;
    
    /** @var EtlEventLogTable the database event logging table. */
    private $dbEventLogTable;
    
    /** @var integer the ID for the row in the main database logging table. */
    private $dbLogTableId;
    
    /** @var TaskConfig the task configuration for the ETL run; this will not be set
                        until the configuration information has been processed. */
    private $taskConfig;
    
    # Logging error flags that are used to only print the first error
    # for a specific type of logging errors
    private $printLoggingErrorLogged;
    private $fileLoggingErrorLogged;
    private $dbLoggingErrorLogged;
    private $projectLoggingErrorLogged;

    /** @var callable callback for custom logging. */
    private $loggingCallback;
        
    /**
     * Creates a logger.
     *
     * @param string app the name of the application that is running the ETL process.
     *    This is used for logging purposes.
     */
    public function __construct($app)
    {
        $this->workflowLogger = null;

        $this->logId = uniqid('', true);
        
        $this->app = $app;
        $this->projectIndex = 0;
        
        $this->isOn = true;
        
        $this->printLogging = true;

        $this->logArray      = array();

        $this->logFile    = null;

        $this->logFromEmail = null;
        $this->logToEmail   = null;
        $this->logEmailSubject = '';

        $this->emailErrors  = true;
        $this->emailSummary = false;
        
        $this->taskConfig = null;
        
        $this->printLoggingErrorLogged   = false;
        $this->fileLoggingErrorLogged    = false;
        $this->dbLoggingErrorLogged      = false;
        $this->projectLoggingErrorLogged = false;
    }
    
    public function setOn($isOn)
    {
        $this->isOn = $isOn;
    }


    /**
     * Sets the log file.
     *
     * @param string $logFile the file (including path) to use for logging.
     */
    public function setLogFile($logFile)
    {
        $this->logFile = $logFile;
    }

    public function getLogFile()
    {
        return $this->logFile;
    }

    public function getEmailErrors()
    {
        return $this->emailErrors;
    }
    
    public function setEmailErrors($emailErrors)
    {
        $this->emailErrors = $emailErrors;
    }
    
    /**
     * Set the e-mail values for logging to e-mail.
     *
     * @param string $from the from e-mail address for logging e-mails.
     * @param string $to the to e-mail address for logging e-mails.
     * @param string $subject the e-mail subject for logging e-mails.
     */
    public function setLogEmail($from, $to, $subject)
    {
        $this->logFromEmail    = $from;
        $this->logToEmail      = $to;
        $this->logEmailSubject = $subject;
    }

    public function getLogFromEmail()
    {
        return $this->logFromEmail;
    }

    public function getLogToEmail()
    {
        return $this->logToEmail;
    }

    public function getLogEmailSubject()
    {
        return $this->logEmailSubject;
    }


    /**
     * Sets the from e-mail for logging
     *
     * @param string $from the from e-mail address to used for logging.
     */
    public function setLogFromEmail($from)
    {
        $this->logFromEmail = $from;
    }
    
    public function setLogToEmail($to)
    {
        $this->logToEmail = $to;
    }

    /**
     * Sets the logging callback that is used for custom logging.
     *
     * @param callable $loggingCallback callback used for custom logging. The callback
     *     will be passed a single string argument that is the message to log.
     */
    public function setLoggingCallback($loggingCallback)
    {
        $this->loggingCallback = $loggingCallback;
    }

    /**
     * Prepends the task name (if any) to the mesage and returns it. Generally only
     * loggers associated with a task in a workflow will have a task name set.
     * Prepending the task name to the log message enables one to see which log
     * messages are associated with which tasks in a workflow.
     */
    private function prependTaskName($message)
    {
        if (isset($this->taskConfig)) {
            $taskName = $this->taskConfig->getTaskName();
            if (!empty($taskName)) {
                $message = '[' . $taskName . '] ' . $message;
            }
        }
        return $message;
    }

    /**
     * Log the specified information to all logging sources that
     * have been enabled.
     *
     * @param string $message the message to log.
     */
    public function log($message)
    {
        $message = $this->prependTaskName($message);

        if (isset($this->workflowLogger)) {
            # Log each of a workflow's task's messages to the workflow's logging array to
            # make it possible to e-mail a summary of logging messages for the entire workflow
            $this->workflowLogger->logToArray($message);
        }

        static $printLoggingErrorLogged    = false;
        static $fileLoggingErrorLogged     = false;
        static $dbLoggingErrorLogged       = false;
        static $projectLoggingErrorLogged  = false;
        static $callbackLoggingErrorLogged = false;
        
        #------------------------------------------
        # In-memory loggin to an array
        #------------------------------------------
        $this->logToArray($message);
        
        try {
            $this->logWithPrint($message);
        } catch (\Exception $exception) {
            # Only log the first print logging error
            if (!$this->printLoggingErrorLogged) {
                $this->logLoggingError($exception);
                $this->printLoggingErrorLogged = true;
            }
        }
        
        try {
            $this->logToFile($message);
        } catch (\Exception $exception) {
            # Only log the first file logging error
            if (!$this->fileLoggingErrorLogged) {
                $this->logLoggingError($exception);
                $this->fileLoggingErrorLogged = true;
            }
        }

        try {
            $this->logEventToDatabase($message);
        } catch (\Exception $exception) {
            # Only log the first database logging error
            if (!$this->dbLoggingErrorLogged) {
                $this->logLoggingError($exception);
                $this->dbLoggingErrorLogged = true;
            }
        }

        try {
            $this->logToCallback($message);
        } catch (\Exception $exception) {
            if (!$this->callbackLoggingErrorLogged) {
                $this->logLoggingError($exception);
                $this->callbackLoggingErrorLogged = true;
            }
        }
    }


    /**
     * Logs a logging error (ignoring any errors that occur in trying to
     * log the log error). The hope is that there are multiple logging
     * methods enabled, and at least one of them works.
     */
    private function logLoggingError($exception)
    {
        $message = 'LOGGING ERROR: '.$exception->getMessage();
        
        try {
            $this->logToArray($message);
        } catch (\Exception $exception) {
            ; // ignore logging errors
        }
        
        try {
            $this->logWithPrint($message);
        } catch (\Exception $exception) {
            ; // ignore logging errors
        }
        
        try {
            $this->logToFile($message);
        } catch (\Exception $exception) {
            ; // ignore logging errors
        }
        
        try {
            $this->logEventToDatabase($message);
        } catch (\Exception $exception) {
            ; // ignore logging errors
        }

        try {
            $this->logToCallback($message);
        } catch (\Exception $exception) {
            ; // ignore logging errors
        }
    }


    /**
     * Logs the specified exception.
     *
     * @param \Exception $exception the exception to log.
     */
    public function logException($exception)
    {
        $message = $exception->getMessage();
        $message = preg_replace('/\s+$/', '', $message);
        $message = $this->prependTaskName($message);

        #--------------------------------------------------------------------
        # if this was an error caused by PHPCap, then include information
        # about the original PHPCap error.
        #--------------------------------------------------------------------
        if ($exception->getCode() === EtlException::PHPCAP_ERROR) {
            $previousException = $exception->getPrevious();
            if (isset($previousException)) {
                $message .= ' - Caused by PHPCap exception: '.$previousException->getMessage();
            }
        }

        if (isset($this->workflowLogger)) {
            # Log each of a workflow's task's messages to the workflow's logging array to
            # make it possible to e-mail a summary of logging messages for the entire workflow
            $this->workflowLogger->logToArray($message);
        }

        $this->logToArray($message);
        $this->logWithPrint($message);
        $this->logEventToDatabase($message);
        
        #--------------------------------------------------
        # Add the stack trace for file logging and e-mail
        #--------------------------------------------------
        $stackTrace = $exception->getTraceAsString();
        $message .= PHP_EOL.$stackTrace;

        $this->logToFile($message);

        $this->logToCallback($message);
        
        # The exception message should already get included in the
        # e-mail summary from the logging array, so just send the
        # stack trace
        //$this->logEmailSummary($stackTrace);
        $this->logEmailSummary(self::NO_PRINT_ERROR_MESSAGE);
    }


    /**
     * Log to internal memory (specifially an array).
     */
    private function logToArray($message)
    {
        array_push($this->logArray, $message);
    }
        
    private function logWithPrint($message)
    {
        if ($this->printLogging === true && $this->isOn) {
            if (isset($this->taskConfig)) {
                $taskName = $this->taskConfig->getTaskName();
                if (!empty($taskName)) {
                    $message = '[' . $taskName . '] ' . $message;
                }
            }
            print $message."\n";
        }
    }

    /**
     * Logs main entry to database (one per ETL run) that is used
     * as the parent record for the log message records for the
     * ETL run. This method should be called only once per ETL run,
     * and should be called before any messages are logged.
     */
    public function logToDatabase()
    {
        if ($this->dbLogging === true && !empty($this->dbLogTable) && $this->isOn) {
            if (!($this->dbConnection instanceof CsvDbConnection)) {
                try {
                    $tablePrefix = '';
                    if (isset($this->taskConfig)) {
                        $tablePrefix = $this->taskConfig->getTablePrefix();
                        if (!isset($tablePrefix)) {
                            $tablePrefix = '';
                        }
                    }
                    $batchSize = $this->taskConfig->getBatchSize();
                    $row = $this->dbLogTable->createLogDataRow($this->app, $tablePrefix, $batchSize);
                    $id = $this->dbConnection->insertRow($row);
                    # Save inserted ID for use as foreign key in
                    # log events table
                    $this->dbLogTableId = $id;
                } catch (\Exception $exception) {
                    if (!$this->dbLoggingErrorLogged) {
                        $this->logLoggingError($exception);
                    } else {
                        $this->dbLoggingErrorLogged = true;
                    }
                }
            }
        }
    }

    /**
     * Log an event to the database.
     *
     * @param string $message the message describing the event (or
     *     information) to log to the database.
     */
    public function logEventToDatabase($message)
    {
        $logged = false;
        if ($this->dbLogging === true && !empty($this->dbEventLogTable) && $this->isOn) {
            if (!($this->dbConnection instanceof CsvDbConnection)) {
                $logId = $this->dbLogTableId;
                $row = $this->dbEventLogTable->createEventLogDataRow($logId, $message);
                $this->dbConnection->insertRow($row);
                $logged = true;
            }
        }
        return $logged;
    }


    /**
     * Logs the specified message to the log file, if one was configured.
     *
     * @param string $message the message to log.
     */
    public function logToFile($message)
    {
        static $firstMessage = true;
        $logged = false;
        
        if (!empty($this->logFile) && $this->isOn) {
            #$configInfo = '';
            #if (!empty($this->taskConfig)) {
            #    $redcapApiUrl = $this->taskConfig->getRedCapApiUrl();
            #    $projectId    = $this->taskConfig->getProjectId();
            #    $cronJob      = $this->taskConfig->getCronJob();
            #    $configName   = $this->taskConfig->getConfigName();
            #    $configInfo = "url={$redcapApiUrl} pid={$projectId} cron={$cronJob}"
            #         ." config={$configName} ";
            #}
            
            #list($microseconds, $seconds) = explode(" ", microtime());
            #$timestamp = date("Y-m-d H:i:s", $seconds).substr($microseconds, 1, 7);
            #$message = $timestamp.': '.'['.$this->logId.'] '.$message."\n";
            #$logged = error_log($message, 3, $this->logFile);
            
            $logged = $this->formatAndLogFileMessage($message);
            if ($logged === false) {
                $user = $this->getProcessUser();

                $message = 'Logging to file "'.($this->logFile).'"';
                if (!empty($user)) {
                    $message .= ' as user "'.$user.'"';
                }
                $message .= ' failed.';

                $code = EtlException::LOGGING_ERROR;
                throw new EtlException($message, $code);
            }
            
                        
            if ($firstMessage) {
                $firstMessage = false;

                #$this->formatAndLogFileMessage($configInfo);
                
                $timezone         = date('e');
                $utcOffsetSeconds = date('Z'); # seconds offset from UTC (Coordinated Universal Time)
                $initMessage = 'timezone='.$timezone.' '.'utc-offset='.$utcOffsetSeconds;
                $this->formatAndLogFileMessage($initMessage);
            }
        }
        return $logged;
    }
    
    private function formatAndLogFileMessage($message)
    {
        list($microseconds, $seconds) = explode(" ", microtime());
        $timestamp = date("Y-m-d H:i:s", $seconds).substr($microseconds, 1, 7);
        $formattedMessage = $timestamp.': '.'['.$this->logId.'] '.$message."\n";
        $logged = error_log($formattedMessage, 3, $this->logFile);
        return $logged;
    }

    /**
     * Logs events that have occurred so far to e-mail if settings indicate to do so.
     * This method would generally be called at the completion of an ETL process.
     * If no exception is passed, then a successful completion is assumed.
     *
     * @param string $errorMessage final additional error message (to what has
     *      already been logged) if any.
     */
    public function logEmailSummary($errorMessage = null)
    {
        $logged = false;
        $eol = "\n";

        $message = implode($eol, $this->logArray);
        
        if ($this->canLogEmailSummary($errorMessage)) {
            if (isset($errorMessage)) {
                if ($errorMessage !== self::NO_PRINT_ERROR_MESSAGE) {
                    $message .= $eol.$errorMessage.$eol.'Processing failed'.$eol;
                }
            }
                
            try {
                $failedSendTos = $this->sendMail(
                    $this->logToEmail,
                    $attachments = array(),
                    $message,
                    $this->logEmailSubject,
                    $this->logFromEmail
                );

                if ($failedSendTos !== null && count($failedSendTos) > 0) {
                    $message = 'Logging to e-mail failed for the following e-mail addreses: '
                            .(implode(', ', $failedSendTos));
                    $logged = false;
                    throw new \Exception($message);
                } else {
                    $logged = true;
                }
            } catch (Exception $exception) {
                $this->logLoggingError($exception);
                $logged = false;
            }
        }
        return $logged;
    }

    public function canLogEmailSummary($errorMessage = null)
    {
        $canLog = false;
        if (!empty($this->logFromEmail) && !empty($this->logToEmail) && $this->isOn) {
            if (($this->emailErrors && isset($errorMessage)) || ($this->emailSummary && empty($errorMessage))) {
                $canLog = true;
            }
        }
        return $canLog;
    }

    /**
     * Sends an email.
     *
     * Attachments have been disabled. For info about how to send
     * attachemnts in emails sent from PHP:
     * http://webcheatsheet.com/php/send_email_text_html_attachment.php#attachment
     *
     * @param mixed $mailToAddress string or array to e-mail addresses.
     * @param array $attachments array of attachments. NOTE: attachements
     *     are not currently supported. There is code for supporting them
     *     below, but it has been commented out.
     * @param string $message the e-mail message to send.
     * @param string $subjectString the e-mail subject.
     * @param string $fromAddress e-mail from address.
     *
     * @return array list of send to e-mails for which the send failed (if any).
     */
    protected function sendMail(
        $mailToAddress,
        $attachments,
        $message,
        $subjectString,
        $fromAddress
    ) {
        // If the address is not an array, and there's a comma in the
        // address, it must really be a string holding a list of addresses,
        // so convert split it into an array.
        if (is_array($mailToAddress)) {
            $mailToAddresses = $mailToAddress;
        } elseif (preg_match("/,/", $mailToAddress)) {
            $mailToAddresses = preg_split("/,/", $mailToAddress);
        } else {
            $mailToAddresses = array($mailToAddress);
        }

        // It MIGHT be useful to validate the email address syntax here,
        // or somewhere else, possibly using
        // filter_var( $email, FILTER_VALIDATE_EMAIL)
  
        // We MIGHT also like to validate the existence of the email
        // target, perhaps by using smtp_validateEmail.class.php.
  
        // Foreach mailto address
        $headers =
            "From: ".$fromAddress."\r\n" .
            "X-Mailer: php";
        $sendmailOpts = '-f '.$fromAddress;

        /* If attachments are needed, the following code should be
         * uncommented and tested:
        // Check if any attachments are being sent
        if (count($attachments) > 0) {
            // Create a boundary string. It must be unique so we use the MD5
            // algorithm to generate a random hash.
            $randomHash = md5(date('r', time()));

            // Add boundary string and mime type specification to headers
            $headers .= "\r\nContent-Type: multipart/mixed; ".
                "boundary=\"PHP-mixed-".$randomHash."\"";

            // Start the body of the message.
            $textHeader = "--PHP-mixed-".$randomHash."\n".
                "Content-Type: text/plain; charset=\"iso-8859-1\"\n".
                "Content-Transfer-Encoding: 7bit\n\n";
            $textFooter = "--PHP-mixed-".$randomHash."\n\n";

            $message = $textHeader.$message."\n".$textFooter;

            // Attach each file
            foreach ($attachments as $attachment) {
                $attachHeader = "--PHP-mixed-".$randomHash."\n".
                    "Content-Type: ".$attachment['content_type']."\n".
                    "Content-Transfer-Encoding: base64\n".
                    "Content-Disposition: attachment\n\n";

                $message .= $attachHeader.$attachment['data'];
            }

            $message .= "--PHP-mixed-".$randomHash."--\n\n";
        }
        */

        $failedSendTos = array();
        foreach ($mailToAddresses as $mailTo) {
            $sent = mail($mailTo, $subjectString, $message, $headers, $sendmailOpts);
            if ($sent === false) {
                array_push($failedSendTos, $mailTo);
            }
        }

        return $failedSendTos;
    }

    public function emailLogArray()
    {
        $message = implode("\r\n", $this->logArray);
        $failedSendTos = $this->sendMail(
            $this->logToEmail,
            $attachments = array(),
            $message,
            $this->logEmailSubject,
            $this->logFromEmail
        );
    }
    
    /**
     * Logs to the optional user-specified logging callback.
     *
     * @param string $message the message to log.
     */
    public function logToCallback($message)
    {
        if (is_callable($this->loggingCallback) && $this->isOn) {
            call_user_func($this->loggingCallback, $message);
        }
    }


    public function hasLoggingError()
    {
        return $this->printLoggingErrorLogged
            || $this->fileLoggingErrorLogged
            || $this->dbLoggingErrorLogged
            || $this->projectLoggingErrorLogged
            ;
    }

    /**
     * Gets the username associated with the current process, if this
     * functionality is supported by the system, or returns null
     * otherwise
     *
     * @return string the username associated with the current process,
     *     or null if the system does not support getting this information.
     */
    public function getProcessUser()
    {
        $user = null;

        if (function_exists('posix_getuid') && function_exists('posix_getpwuid')) {
            $userInfo = posix_getpwuid(posix_getuid());
            $user = $userInfo['name'];
        }

        return $user;
    }

    public function getLogArray()
    {
        return $this->logArray;
    }
    
    public function getPrintLogging()
    {
        return $this->printLogging;
    }
    
    public function setPrintLogging($printLogging)
    {
        $this->printLogging = $printLogging;
    }
    
    public function getEmailSummary()
    {
        return $this->emailSummary;
    }
    
    public function setEmailSummary($emailSummary)
    {
        $this->emailSummary = $emailSummary;
    }
    
    public function getDbConnection()
    {
        return $this->dbConnection;
    }
    
    public function setDbConnection($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
        
    public function getDbLogging()
    {
        return $this->dbLogging;
    }
    
    public function setDbLogging($dbLogging)
    {
        $this->dbLogging = $dbLogging;
    }
    
    public function getDbLogTable()
    {
        return $this->dbLogTable;
    }
    
    public function setDbLogTable($dbLogTable)
    {
        $this->dbLogTable = $dbLogTable;
    }
    
    public function getDbEventLogTable()
    {
        return $this->dbEventLogTable;
    }
    
    public function setDbEventLogTable($dbEventLogTable)
    {
        $this->dbEventLogTable = $dbEventLogTable;
    }
    
    public function getTaskConfig()
    {
        return $this->taskConfig;
    }
    
    public function setTaskConfig($taskConfig)
    {
        $this->taskConfig = $taskConfig;
    }
    
    public function getLogId()
    {
        return $this->logId;
    }


    /**
     * Gets the name of the application that is running the ETL process.
     */
    public function getApp()
    {
        return $this->app;
    }

    public function getWorkflowLogger()
    {
        return $this->workflowLogger;
    }

    public function setWorkflowLogger($workflowLogger)
    {
        $this->workflowLogger = $workflowLogger;
    }
}

<?php

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
    private $logId;
    
    private $isOn;
    
    /** @var string the name of the log file (if any) */
    private $logFile;

    private $logProject;

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
    
    /** @var Configuration the configuration for the ETL run; this will not be set
                           until the configuration information has been processed. */
    private $configuration;
    
    # Logging error flags that are used to only print the first error
    # for a specific type of logging errors
    private $printLoggingErrorLogged;
    private $fileLoggingErrorLogged;
    private $dbLoggingErrorLogged;
    private $projectLoggingErrorLogged;
        
    /**
     * Creates a logger.
     *
     * @param string app the name of the application that is running the ETL process.
     *    This is used for logging purposes.
     */
    public function __construct($app)
    {
        $this->logId = uniqid('', true);
        
        $this->app = $app;
        $this->projectIndex = 0;
        
        $this->isOn = true;
        
        $this->printLogging = true;

        $this->logArray      = array();

        $this->logFile    = null;
        $this->logProject = null;

        $this->logFromEmail = null;
        $this->logToEmail   = null;
        $this->logEmailSubject = '';

        $this->emailErrors  = true;
        $this->emailSummary = false;
        
        $this->configuration = null;
        
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
     * Sets the REDCap project for logging.
     *
     * @param EtlRedCapProject $project the REDCap logging project.
     */
    public function setLogProject($project)
    {
        $this->logProject = $project;

        // REDCap must have a record_id when importing a new record. It does
        // not auto generate a new record_id on API Imports (or regular imports?),
        // even when the project is set to auto generate new record_ids.
        // Because multiple people may be using this application simultaneously,
        // it's not sufficient to simply use a timestamp. There is a risk that
        // even with the timestamp and a random number, logs might overwrite each
        // other, but I haven't found a better solution.
        $this->projectIdBase = time().'-'.rand(1, 9999).'-';
        $this->projectIndex  = 0;
        $this->projectDate   = date('g:i:s a d-M-Y T');
    }


    /**
     * Log the specified information to all logging sources that
     * have been enabled.
     *
     * @param string $message the message to log.
     */
    public function log($message)
    {
        static $printLoggingErrorLogged   = false;
        static $fileLoggingErrorLogged    = false;
        static $dbLoggingErrorLogged      = false;
        static $projectLoggingErrorLogged = false;
        
        $this->logToArray($message);
        
        try {
            $this->logWithPrint($message);
        } catch (\Exception $exception) {
            # Only log the first print logging error
            if (!$this->printLoggingErrorLogged) {
                $this->logLoggingError($exception);
            } else {
                $this->printLoggingErrorLogged = true;
            }
        }
        
        try {
            $this->logToFile($message);
        } catch (\Exception $exception) {
            # Only log the first file logging error
            if (!$this->fileLoggingErrorLogged) {
                $this->logLoggingError($exception);
            } else {
                $this->fileLoggingErrorLogged = true;
            }
        }

        try {
            $this->logEventToDatabase($message);
        } catch (\Exception $exception) {
            # Only log the first database logging error
            if (!$this->dbLoggingErrorLogged) {
                $this->logLoggingError($exception);
            } else {
                 $this->dbLoggingErrorLogged = true;
            }
        }
            
        try {
            $this->logToProject($message);
        } catch (\Exception $exception) {
            # Only log the first database logging error
            if (!$this->projectLoggingErrorLogged) {
                $this->logLoggingError($exception);
            } else {
                $this->projectLoggingErrorLogged = true;
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
            $this->logToProject($message);
        } catch (\Exception $exception) {
            ; // ignore logging errors
        }

        try {
            $this->logEventToDatabase($message);
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

        $this->logToArray($message);
        $this->logWithPrint($message);
        $this->logToProject($message);
        $this->logEventToDatabase($message);
        
        #--------------------------------------------------
        # Add the stack trace for file logging and e-mail
        #--------------------------------------------------
        $stackTrace = $exception->getTraceAsString();
        $message .= PHP_EOL.$stackTrace;

        $this->logToFile($message);
        
        # The exception message should already get included in the
        # e-mail summary from the logging array, so just send the
        # stack trace
        $this->logEmailSummary($stackTrace);
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
                    if (isset($this->configuration)) {
                        $tablePrefix = $this->configuration->getTablePrefix();
                        if (!isset($tablePrefix)) {
                            $tablePrefix = '';
                        }
                    }
                    $batchSize = $this->configuration->getBatchSize();
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
            $configInfo = '';
            if (!empty($this->configuration)) {
                $redcapApiUrl = $this->configuration->getRedCapApiUrl();
                $projectId    = $this->configuration->getProjectId();
                $cronJob      = $this->configuration->getCronJob();
                $configOwner  = $this->configuration->getConfigOwner();
                $configName   = $this->configuration->getConfigName();
                $configInfo = "url={$redcapApiUrl} pid={$projectId} cron={$cronJob}"
                     ." user={$configOwner} config={$configName} ";
            }
            
            #list($microseconds, $seconds) = explode(" ", microtime());
            #$timestamp = date("Y-m-d H:i:s", $seconds).substr($microseconds, 1, 7);
            #$message = $timestamp.': '.'['.$this->logId.'] '.$message."\n";
            #$logged = error_log($message, 3, $this->logFile);
            
            $logged = $this->formatAndLogFileMessage($message);
            if ($logged === false) {
                $message = 'Logging to file "'.($this->logFile).'" as user "'.get_current_user().'" failed.';
                $code = EtlException::LOGGING_ERROR;
                throw new EtlException($message, $code);
            }
            
                        
            if ($firstMessage) {
                $firstMessage = false;

                $this->formatAndLogFileMessage($configInfo);
                
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
     * Logs the specified message to the REDCap logging project, if one was specified.
     *
     * @param string $message the message to log.
     *
     * @return boolean true if the log operated succeeded, and false otherwise.
     */
    public function logToProject($message)
    {
        $logged = false;

        if (isset($this->logProject) && $this->isOn) {
            # Prepare data to be imported
            $this->projectIndex = sprintf("%'.03d", $this->nextIndex());
            $records = array();
            $records[0] = array(
                'record_id' => ($this->projectIdBase).($this->projectIndex),
                'curdate'   => $this->projectDate,
                'app'       => $this->app,
                'message'   => $message
            );

            $this->logProject->importRecords($records);
            $logged = true;
        }

        return $logged;
    }

    protected function nextIndex()
    {
        $this->projectIndex++;
        return($this->projectIndex);
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
        
        if (!empty($this->logFromEmail) && !empty($this->logToEmail) && $this->isOn) {
            if (($this->emailErrors && isset($errorMessage)) || ($this->emailSummary && empty($errorMessage))) {
                if (isset($errorMessage)) {
                    $message .= $eol.$errorMessage.$eol.'Processing failed'.$eol;
                }
                
                try {
                    $failedSendTos = $this->sendMail(
                        $this->logToEmail,
                        $attachments = array(),
                        $message,
                        $this->logEmailSubject,
                        $this->logFromEmail
                    );

                    if (count($failedSendTos) > 0) {
                        $message = 'Logging to e-mail failed for the following e-mail addreses: '
                                .(implode(', ', $failedSendTos));
                        $this->logLoggingError($message);
                        $logged = false;
                    } else {
                        $logged = true;
                    }
                } catch (Exception $exception) {
                    $this->logLoggingError($exception);
                    $logged = false;
                }
            }
        }
        return $logged;
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

        $faliedSendTos = array();
        foreach ($mailToAddresses as $mailto) {
            $sent = mail($mailto, $subjectString, $message, $headers, $sendmailOpts);
            if ($sent === false) {
                array_push($failedSentTos, $mailTo);
            }
        }

        return $faliedSendTos;
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
    
    public function hasLoggingError()
    {
        return $this->printLoggingErrorLogged
            || $this->fileLoggingErrorLogged
            || $this->dbLoggingErrorLogged
            || $this->projectLoggingErrorLogged
            ;
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
    
    public function getConfiguration()
    {
        return $this->configuration;
    }
    
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }
    
    /**
     * Gets the name of the application that is running the ETL process.
     */
    public function getApp()
    {
        return $this->app;
    }
}

<?php

namespace IU\REDCapETL;

use Database\DbConnection;

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
    /** @var string the name of the log file (if any) */
    private $logFile;

    private $logProject;

    /** @var boolean Whether info that is logged should be printed to standard output */
    private $printInfo;
    
    /** @var boolean If true (the default) then REDCap-ETL  will attempt to log errors to PHP's
        system logger if no other logging of the errors is successful. */
    private $logToSystemLogger;

    /** @var string For project logging, the app name to use. */
    private $app;

    /** @var string For project logging, the record ID base. */
    private $projectIdBase;
    private $projectIndex;
    private $projectDate;

    /** @var string The from e-mail address for e-mail log messages. */
    private $logFromEmail;
    private $logToEmail;
    private $logEmailSubject;
    
    /** @var indicates if an e-mail logging summary should be sent to
        log to e-mail list; defaults to false. */
    private $sendEmailSummary;

    /** @var array Array of log messages (in effect an in-memory log). */
    private $logArray;
    
    /** @var DbConnection database connection (used for database logging) */
    private $dbConnection;



    /**
     * Creates a logger.
     *
     * @param string app the name of the application that is running the ETL process.
     *    This is used for logging purposes.
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->projectIndex = 0;
        
        $this->printInfo         = true;
        $this->logToSystemLogger = true;

        $this->logArray = array();

        $this->logFile    = null;
        $this->logProject = null;
                
        $this->logFromEmail = null;
        $this->logToEmail   = null;
        $this->logEmailSubject = '';
        
        $this->sendEmailSummary = false;
        
        $this->dbConnection;
    }
    
    /**
     * Sets all relevant settings so that no logging will occur.
     */
    public function turnOff()
    {
        $this->printInfo         = false;
        $this->logToSystemLogger = false;
        
        $this->logFile    = null;
        $this->logProject = null;
        
        $this->logFromEmail = null;
        $this->logToEmail   = null;
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
     * Sets whether or not info messages that are logged
     * are printed to stanadrd output or not.
     *
     * @param boolean $printInfo true indicates that informational logging messages
     *     should be printed, and false indicates that the should not.
     */
    public function setPrintInfo($printInfo)
    {
        $this->printInfo = $printInfo;
    }


    /**
     * Log the specified information to the log file (if specified)
     * and the REDCap logging project (if specified), or to PHP's
     * system log if no log file or project was specified, of if
     * writing to all specified logs fails.
     *
     * @param string $info the information to log.
     */
    public function logInfo($info)
    {
        $logged = false;
        $logFileOk = true;

        $this->logToArray($info);
        
        list($loggedToFile, $fileError) = $this->logToFile($info);

        list($loggedToProject, $projectError) = $this->logToProject($info);

        if ($this->printInfo === true) {
            print $info."\n";
        }
        
        #---------------------------------------
        # Log logging errors
        #---------------------------------------
        if (isset($fileError)) {
            $this->logError($fileError);
        }

        if (isset($projectError)) {
            $this->logError($projectError);
        }

        return $logged;
    }


    /**
     * Logs the specified exception.
     *
     * @param \Exception $exception the exception to log.
     */
    public function logException($exception)
    {
        $message = $exception->getMessage();

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

        list($loggedToProject, $projectError) = $this->logToProject($message);

        #--------------------------------------------------
        # Add the stack trace for file logging and e-mail
        #--------------------------------------------------
        $message .= PHP_EOL.$exception->getTraceAsString();

        list($loggedToFile, $fileError) = $this->logToFile($message);
        $loggedToEmail   = $this->logToEmail($message);

        if ($loggedToFile === false && $loggedToProject === false && $loggedToEmail === false) {
            if ($this->logToSystemLogger) {
                $logged = error_log($message, 0);
            }
        }
    }


    /**
     * Log the specified error (sends to e-mail list, if specified).
     *
     * @param string $error the error message to log.
     */
    public function logError($error)
    {
        $this->logToArray($error);

        list($loggedToFile, $fileError) = $this->logToFile($error);
        list($loggedToProject, $projectError) = $this->logToProject($error);

        $loggedToEmail   = $this->logToEmail($error);

        #--------------------------------------------------------
        # If the error didn't get logged, either becaues no
        # logging destinations were specified, or the specified
        # logging destination failed, log to PHP's sytem logger.
        #--------------------------------------------------------
        if ($loggedToFile === false && $loggedToProject === false && $loggedToEmail === false) {
            if ($this->logToSystemLogger) {
                $logged = error_log($error, 0);
            }
        }
    }

    public function logToArray($message)
    {
        array_push($this->logArray, $message);
    }

    /**
     * Logs the specified message to the log file, if one was configured.
     *
     * @param string $message the message to log.
     * @return array first element is true if the logging operation succeeded, or false otherwise.
     *               second element is unset if no error occurred, and a string with the error
     *               message if an error did occur.
     */
    public function logToFile($message)
    {
        $error  = null;
        $logged = false;

        if (!empty($this->logFile)) {
            $timestamp = date('Y-m-d H:i:s');
            $message = $timestamp.': '.$message."\n";

            $logged = error_log($message, 3, $this->logFile);
            #-----------------------------------------------------------
            # If logging to the specified file failed, log to PHP's
            # system log.
            #-----------------------------------------------------------
            if ($logged === false) {
                if ($this->logToSystemLogger) {
                    $error = 'Logging to file "'.($this->logFile).'" as user "'.get_current_user().'" failed.';
                    error_log($error);
                }
            }
        }
        return array($logged, $error);
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
        $error = null;
        $logged = false;

        if (isset($this->logProject)) {
            # Prepare data to be imported
            $this->projectIndex = sprintf("%'.03d", $this->nextIndex());
            $records = array();
            $records[0] = array(
                'record_id' => ($this->projectIdBase).($this->projectIndex),
                'curdate'   => $this->projectDate,
                'app'       => $this->app,
                'message'   => $message
            );

            try {
                $this->logProject->importRecords($records);
                $logged = true;
            } catch (\Exception $exception) {
                $logged = false;

                if ($this->logToSystemLogger) {
                    $error = 'Logging to project failed: '.($exception->getMessage());
                    error_log($error, 0);
                }
            }
        }

        return array($logged, $error);
    }

    protected function nextIndex()
    {
        $this->projectIndex++;
        return($this->projectIndex);
    }


    /**
     * Logs the specified error to e-mail, if e-mail logging has been set up.
     *
     * @param string $error the error message to log to e-mail.
     */
    public function logToEmail($error)
    {
        $logged = false;

        if (!empty($this->logFromEmail) && !empty($this->logToEmail)) {
            try {
                $failedSendTos = $this->sendMail(
                    $this->logToEmail,
                    $attachments = array(),
                    $message = $error,
                    $this->logEmailSubject,
                    $this->logFromEmail
                );

                if (count($failedSendTos) > 0) {
                    if ($this->logToSystemLogger) {
                        error_log('Logging to e-mail failed for the following e-mail addreses: '
                            .(implode(', ', $failedSendTos)), 0);
                    }
                    $logged = false;
                } else {
                    $logged = true;
                }
            } catch (Exception $exception) {
                $logged = false;
                
                if ($this->logToSystemLogger) {
                    $error = $exception->getMessage();
                    error_log('Logging to e-mail failed: '.$error, 0);
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

    public function getLogArray()
    {
        return $this->logArray;
    }
    
    public function getSendEmailSummary()
    {
        return $this->sendEmailSummary;
    }
    
    public function setSendEmailSummary($sendEmailSummary)
    {
        $this->sendEmailSummary = $sendEmailSummary;
    }
    
    public function processEmailSummary()
    {
        if (!empty($this->logFromEmail) && !empty($this->logToEmail) && $this->sendEmailSummary) {
            $this->emailLogArray();
        }
    }
    
    public function setDbConnection($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * Gets the name of the application that is running the ETL process.
     */
    public function getApp()
    {
        return $this->app;
    }
}

<?php

namespace IU\REDCapETL;

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
    private $logFile;
    private $logProject;
    private $notifier;
    private $printInfo = true; // whether info that is logged should
                                      // be printed to standard output

    private $app;           // For project logging, the app name to use
    private $projectIdBase; // For project logging, the record ID base
    private $projectIndex;
    private $projectDate;


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
        $this->logFile    = null;
        $this->logProject = null;
        $this->notifier   = null;
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

    /**
     * Set the e-mail values for logging to e-mail.
     *
     * @param string $from the from e-mail address for logging e-mails.
     * @param string $to the to e-mail address for logging e-mails.
     * @param string $subject the e-mail subject for logging e-mails.
     */
    public function setLogEmail($from, $to, $subject)
    {
        try {
            $this->notifier = new Notifier($from, $to, $subject);
        } catch (Exception $exception) {
            $message = 'Unable to create notifier: '.$exception.getMessage();
            error_log($message, 0);
            $this->notifier = null;
        }
    }

    /**
     * Sets the from e-mail for logging
     *
     * @param string $from the from e-mail address to used for logging.
     */
    public function setLogEmailFrom($from)
    {
        try {
            $this->notifier->setSender($from);
        } catch (Exception $exception) {
            $message = 'Unable to set log e-mail from address: '.$exception.getMessage();
            error_log($message, 0);
            $this->notifier = null;
        }
    }
    
    public function setLogEmailTo($to)
    {
        try {
            $this->notifier->setRecipients($to);
        } catch (Exception $exception) {
            $message = 'Unable to set log e-mail to list: '.$exception.getMessage();
            error_log($message, 0);
            $this->notifier = null;
        }
    }


    /**
     * Set the REDCap project for logging.
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

        list($loggedToFile, $fileError) = $this->logToFile($info);

        list($loggedToProject, $projectError) = $this->logToProject($info);

        if ($this->printInfo === true) {
            print $info."\n";
        }

        #-----------------------------------------------------
        # If the information wasn't logged to the log file or
        # project, and it wasn't printed, write it to PHP's
        # system log
        #-----------------------------------------------------
        if (!$loggedToFile && !$loggedToProject && $this->printInfo === false) {
            error_log($info, 0);
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
            $previouseException = $exception->getPrevious();
            if (isset($previousException)) {
                $message .= ' - Caused by PHPCap exception: '.$previousException->getMessage();
            }
        }

        list($loggedToProject, $projectError) = $this->logToProject($message);

        #--------------------------------------------------
        # Add the stack trace for file logging and e-mail
        #--------------------------------------------------
        $message .= PHP_EOL.$exception->getTraceAsString();

        list($loggedToFile, $fileError) = $this->logToFile($message);
        $loggedToEmail   = $this->logToEmail($message);

        if ($loggedToFile === false && $loggedToProject === false && $loggedToEmail === false) {
            $logged = error_log($message, 0);
        }
    }


    /**
     * Log the specified error (sends to e-mail list, if specified).
     *
     * @param string $error the error message to log.
     */
    public function logError($error)
    {
        list($loggedToFile, $fileError) = $this->logToFile($error);
        list($loggedToProject, $projectError) = $this->logToProject($error);
        $loggedToEmail   = $this->logToEmail($error);

        #--------------------------------------------------------
        # If the error didn't get logged, either becaues no
        # logging destinations were specified, or the specified
        # logging destination failed, log to PHP's sytem logger.
        #--------------------------------------------------------
        if ($loggedToFile === false && $loggedToProject === false && $loggedToEmail === false) {
            $logged = error_log($error, 0);
        }
    }

    /**
     * Logs the specified message to the log file, if one was specified.
     *
     * @param string $message the message to log.
     * @param string $error if
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
                $error = 'Logging to file "'.($this->logFile).'" as user "'.get_current_user().'" failed.';
                error_log($error);
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
            } catch (Exception $exception) {
                $logged = false;
                $error = 'Logging to project failed: '.($exception->getMessage());
                error_log($error, 0);
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

        if (isset($this->notifier)) {
            # NOTE: need to change notify to return a value??????? !!!!!!!!!!!!!!!!!!
            try {
                $failedSendTos = $this->notifier->notify($error);
                if (count($failedSendTos) > 0) {
                    error_log('Logging to e-mail failed for the following e-mail addreses: '
                        .(implode(', ', $failedSendTos)), 0);
                    $logged = false;
                } else {
                    $logged = true;
                }
            } catch (Exception $exception) {
                $logged = false;
                $error = $exception->getMessage();
                error_log('Logging to e-mail failed: '.$error, 0);
            }
        }
        return $logged;
    }

    /**
     * Gets the name of the application that is running the ETL process.
     */
    public function getApp()
    {
        return $this->app;
    }

    public function getNotifier()
    {
        return $this->notifier;
    }
}

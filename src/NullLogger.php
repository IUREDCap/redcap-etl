<?php

namespace IU\REDCapETL;

/**
 * Logging class that will do no logging irrespective of the configuration file settings
 * (for turning off logging completely).
 */
class NullLogger extends Logger
{

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
        
        $this->printInfo         = false;
        $this->logToSystemLogger = false;

        $this->logFile    = null;
        $this->logProject = null;
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
        $this->logFile = null;
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
        $this->logFromEmail    = null;
        $this->logToEmail      = null;
        $this->logEmailSubject = null;
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
        $this->logFromEmail = null;
    }
    
    public function setLogToEmail($to)
    {
        $this->logToEmail = null;
    }


    /**
     * Sets the REDCap project for logging.
     *
     * @param EtlRedCapProject $project the REDCap logging project.
     */
    public function setLogProject($project)
    {
        $this->logProject = null;

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
        $this->printInfo = false;
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
        return false;
    }


    /**
     * Logs the specified exception.
     *
     * @param \Exception $exception the exception to log.
     */
    public function logException($exception)
    {
    }


    /**
     * Log the specified error (sends to e-mail list, if specified).
     *
     * @param string $error the error message to log.
     */
    public function logError($error)
    {
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
        $faliedSendTos = array();
        return $faliedSendTos;
    }

    

    /**
     * Gets the name of the application that is running the ETL process.
     */
    public function getApp()
    {
        return $this->app;
    }
}

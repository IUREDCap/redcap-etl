<?php

namespace IU\REDCapETL;

/**
 * Class for sending notifications to a list of e-mail addresses.
 */
class Notifier
{
    protected $sender = '';     // Email address to be used as From: address
    protected $recipients = '';  // Email address list of recipients
    protected $subject = '';    // Subject of sent emails
    protected $file = null;     // Optional file used for logging

    public function __construct($from, $to, $subject, $file = null)
    {
        $this->sender    = $from;
        $this->recipients = $to;
        $this->subject   = $subject;
        if (isset($file) && is_string($file) && trim($file) != '') {
            $this->file = trim($file);
        }
    }

  
    /**
     * Sends an e-mail notification.
     *
     * @return array an array of strings that are the send to e-mail addresses
     *     for which the send failed (if any).
     */
    public function notify($message)
    {
        $fileLogOk = true;
        if (isset($this->file)) {
            $callerStackFrame = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            $fileLogOk = Logger::logErrorToFile($message, $this->file, $callerStackFrame);
        }

        #-----------------------------------------------------------------------
        # If a log file was specified and writing to it failed, append to the
        # nofitication message that will be e-mailed.
        #-----------------------------------------------------------------------
        if ($fileLogOk === false) {
            $message .= ' [Note: attempt to log this messsage to file "'.$this->file.'" failed.]';
        }

        $failedSendTos = $this->sendmail(
            $this->recipients,
            array(),
            $message,
            $this->subject,
            $this->sender
        );

        return $failedSendTos;
    }


    /**
     * Sends an email.
     * For info about how to send attachemnts in emails sent from PHP:
     * http://webcheatsheet.com/php/send_email_text_html_attachment.php#attachment
     *
     * $mailToAddress,       // string or array thereof
     * $attachments,          // array of $attachment
     * $message,              // string
     * $subjectString,       // string
     * $fromAddress          // string or array thereof
     *
     * @return array list of send to e-mails for which the send failed (if any).
     */
    protected function sendmail(
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
        // but that wouldn't be easy.  filter_var( $email,
        // FILTER_VALIDATE_EMAIL)) requires PHP 5.2 and up, and at the
        // present time we are running PHP 5.1.6.
  
        // We MIGHT also like to validate the existence of the email
        // target, perhaps by using smtp_validateEmail.class.php.
  
        // Foreach mailto address
        $headers =
            "From: ".$fromAddress."\r\n" .
            "X-Mailer: php";
        $sendmailOpts = '-f '.$fromAddress;

        // Check if any attachments are being sent
        if (0 < count($attachments)) {
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

        $faliedSendTos = array();
        foreach ($mailToAddresses as $mailto) {
            $sent = mail($mailto, $subjectString, $message, $headers, $sendmailOpts);
            if ($sent === false) {
                array_push($failedSentTos, $mailTo);
            }
        }

        return $faliedSendTos;
    }

    public function setSender($sender)
    {
        $this->sender = $sender;
    }
    
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;
    }
}

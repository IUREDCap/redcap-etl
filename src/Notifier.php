<?php

namespace IU\REDCapETL;

/**
 * Class for sending notifications to a list of e-mail addresses.
 */
class Notifier
{
    /** @var string from e-mail address. */
    protected $sender = '';
    
    /** @var string comma-separated list of recipient e-mail addresses. */
    protected $recipients = '';
    
    /** @var string subject used for e-mail notifications */
    protected $subject = '';

    /**
     * Creates a Notifier object.
     *
     * @param string $sender the e-mail address of the sender.
     * @param string $recipients comma-separated list of recipient
     *     e-mail addresses.
     * @param string $subject the subject to used for e-mail notifications.
     */
    public function __construct($sender, $recipients, $subject)
    {
        $this->sender     = $sender;
        $this->recipients = $recipients;
        $this->subject    = $subject;
    }

  
    /**
     * Sends an e-mail notification.
     *
     * @param string $message the message to send.
     *
     * @return array an array of strings that are the send to e-mail addresses
     *     for which the send failed (if any).
     */
    public function notify($message)
    {
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
     * @param mixed $mailToAddress string or array to e-mail addresses.
     * @param array $attachments array of attachments.
     * @param string $message the e-mail message to send.
     * @param string $subjectString the e-mail subject.
     * @param string $fromAddress e-mail from address.
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
        // or somewhere else, possibly using
        // filter_var( $email, FILTER_VALIDATE_EMAIL)
  
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

    
    public function getRecipients()
    {
        return $this->recipients;
    }
    
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;
    }
    
    public function getSender()
    {
        return $this->sender;
    }
    
    public function setSender($sender)
    {
        $this->sender = $sender;
    }
    
    public function getSubject()
    {
        return $this->subject;
    }
}

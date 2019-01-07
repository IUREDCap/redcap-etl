<?php

namespace IU\REDCapETL;

/**
 * Exception class for REDCap-ETL.
 *
 * @see http://php.net/manual/en/class.exception.php
 *         Information on additional methods provided by parent class Exception.
 */
class EtlException extends \Exception
{
    const FILE_ERROR     = 1;
    const PHPCAP_ERROR   = 2;
    const INPUT_ERROR    = 3;
    const DATABASE_ERROR = 4;
    const DET_ERROR      = 5;  # REDCap Data Entry Trigger error
    const LOGGING_ERROR  = 6;

    /**
     * Constructor.
     *
     * @param string $message the error message.
     * @param integer $code the error code.
     * @param \Exception $previous the previous exception.
     */
    public function __construct(
        $message,
        $code,
        $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

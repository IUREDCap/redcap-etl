<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/**
 * This file contains the PHPCapException class.
 */

namespace IU\PHPCap;

/**
 * Exception class for PHPCap exceptions. This is the exception that PHPCap will
 * throw when it encounters an error.
 *
 * Example usage:
 *
 * <pre>
 * <code class="phpdocumentor-code">
 * try {
 *     $projectInfo = $project->exportProjectInfo();
 * }
 * catch (PhpCapException $exception) {
 *     print "The following error occurred: {$exception->getMessage()}\n";
 *     print "Error code: {$exception->getCode()}\n";
 *     $connectionErrorNumber = $exception->getConnectionErrorNumber();
 *     if (isset($connectionErrorNumber)) {
 *         print "A connection error occurred.\n";
 *         print "Connection error number: {$connectionErrorNumber}\n";
 *     }
 *     print "Stack trace:\n{$exception->getTraceAsString()}\n";
 * }
 * </code>
 * </pre>
 *
 * @see http://php.net/manual/en/class.exception.php
 *         Information on additional methods provided by parent class Exception.
 */
class PhpCapException extends \Exception
{

    /** @var integer|null connection error number */
    private $connectionErrorNumber;
    
    /** @var integer|null HTTP status code */
    private $httpStatusCode;
    
    
    /**
     * Constructor.
     *
     * @param string $message the error message.
     * @param integer $code the error code.
     * @param integer $connectionErrorNumber the connection error number
     *     (set to null if no connection error occurred).
     * @param integer $httpStatusCode the HTTP status code (set to null if no HTTP status code was returned).
     * @param \Exception $previous the previous exception.
     */
    public function __construct(
        $message,
        $code,
        $connectionErrorNumber = null,
        $httpStatusCode = null,
        $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->connectionErrorNumber  = $connectionErrorNumber;
        $this->httpStatusCode = $httpStatusCode;
    }
    
    
    /**
     * Returns the connection error number, or null if there was no
     * connection error. The possible numbers returned will depend
     * on the type of connection class being used. For example, if
     * cURL is being used, then the cURL error number would be
     * returned.
     *
     * @return integer|null connection error number, or null if there was no connection error.
     */
    public function getConnectionErrorNumber()
    {
        return $this->connectionErrorNumber;
    }
    

    /**
     * Returns the HTTP status code, or null if this was not set.
     *
     * @return integer|null HTTP status code, or null if this was not set.
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}

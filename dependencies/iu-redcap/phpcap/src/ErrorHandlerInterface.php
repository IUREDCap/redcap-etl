<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

/**
 * Interface for error handlers for PHPCap.
 */
interface ErrorHandlerInterface
{
    // Error codes
    
    /** Invalid argument passed to a PHPCap method. */
    const INVALID_ARGUMENT = 1;
    
    /** Too many arguments were passed to the method. */
    const TOO_MANY_ARGUMENTS = 2;

    /** An invalid URL was used. */
    const INVALID_URL = 3;
    
    /** A CA certificate file was specified, but it could not be found. */
    const CA_CERTIFICATE_FILE_NOT_FOUND = 4;
    
    /** The CA certificate file could not be read. */
    const CA_CERTIFICATE_FILE_UNREADABLE = 5;

    /** A connection error occurred. */
    const CONNECTION_ERROR = 6;
    
    /** The REDCap API generated an error. */
    const REDCAP_API_ERROR = 7;
    
    /** A JSON error occurred. This would typically happen when PHPCap is expecting
     * the REDCap API to return data in JSON format, but the result returned is not valid JSON.
     */
    const JSON_ERROR = 8;
    
    /** The output file could not be found, or was found and could not be written */
    const OUTPUT_FILE_ERROR     = 9;

    /** The input file could not be found. */
    const INPUT_FILE_NOT_FOUND  = 10;
    
    /** The input file was found, but is unreadable. */
    const INPUT_FILE_UNREADABLE = 11;
    
    /** The input file contents are invalid. */
    const INPUT_FILE_ERROR      = 12;
    
    /**
     * Throw an exception for the specified values.
     *
     * @param string $message message describing the error that occurred.
     * @param integer $code the error code.
     * @param integer $connectionErrorNumber the error number from the underlying connection used,
     *     of null if no connection error occurred.
     *     For example, if cURL is being used (the default) this will be the cURL error number
     *     if a connection error occurs.
     * @param integer $httpStatusCode https status code, which would typcially be set if
     *     an error occurs with the http response from the REDCap API.
     * @param \Throwable $previousException the previous exception that occurred that
     *     caused this exception, if any.
     */
    public function throwException($message, $code, $connectionErrorNumber, $httpStatusCode, $previousException);
}

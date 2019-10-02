<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/**
 * Contains interface for classes implementing a connection to a REDCap API.
 */

namespace IU\PHPCap;

/**
 * Interface for connection to the API of a REDCap instance.
 * Classes implementing this interface are used to provide low-level
 * access to the REDCap API.
 */
interface RedCapApiConnectionInterface
{

    /**
     * Constructor that creates a REDCap API connection for the specified URL, with the
     * specified settings.
     *
     * @param string $url
     *            the URL for the API of the REDCap site that you want to connect to.
     * @param boolean $sslVerify indicates if verification should be done for the SSL
     *            connection to REDCap. Setting this to false is not secure.
     * @param string $caCertificateFile
     *            the CA (Certificate Authority) certificate file used for veriying the REDCap site's
     *            SSL certificate (i.e., for verifying that the REDCap site that is
     *            connected to is the one specified).
     * @param ErrorHandlerInterface $errorHandler the error handler for the connection.
     */
    public function __construct(
        $url,
        $sslVerify,
        $caCertificateFile,
        $errorHandler
    );

    /**
     * Destructor for this class.
     */
    public function __destruct();

    /**
     * Makes a call to REDCap's API and returns the results.
     *
     * @param mixed $data
     *         data for the call.
     * @throws PhpCapException
     * @return string the response returned by the REDCap API for the specified call data.
     *         See the REDCap API documentation for more information.
     */
    public function call($data);
    
    /**
     * Calls REDCap's API using a with a correctly formatted string version
     * of the specified array and returns the results.
     *
     * @param $dataArray array the array of data that is converted to a
     *         string and then passed to the REDCap API.
     * @throws PhpCapException
     * @return string the response returned by the REDCap API for the specified call data.
     *         See the REDCap API documentation for more information.
     */
    public function callWithArray($dataArray);
    
    /**
     *  Returns call information for the most recent call.
     *  The format of the call information will be connection dependent.
     */
    public function getCallInfo();
    
    /**
     * Gets the error handler for the connection.
     *
     * return ErrorHandlerInterface the error handler for the connection.
     */
    public function getErrorHandler();
    
    /**
     * Sets the error handler;
     *
     * @param ErrorHandlerInterface $errorHandler the error handler to use.
     */
    public function setErrorHandler($errorHandler);

    /**
     * Gets the URL of the connection.
     *
     * return string the URL of the connection.
     */
    public function getUrl();
    
    /**
     * Sets the URL of the connection.
     *
     * @param string $url the URL of the connection.
     */
    public function setUrl($url);
    
    /**
     * Gets the status of SSL verification for the connection.
     *
     * @return boolean true if SSL verification is enabled, and false otherwise.
     */
    public function getSslVerify();
    
    /**
     * Sets SSL verification for the connection.
     *
     * @param boolean $sslVerify if this is true, then the site being connected to will
     *     have its SSL certificate verified.
     */
    public function setSslVerify($sslVerify);
    
    
    /**
     * Gets the timeout in seconds for calls to the connection.
     *
     * @return integer timeout in seconds for calls to connection.
     */
    public function getTimeoutInSeconds();
    
    
    /**
     * Sets the timeout in seconds for calls to the connection.
     *
     * @param $timeoutInSeconds timeout in seconds for call to connection.
     */
    public function setTimeoutInSeconds($timeoutInSeconds);
    
    /**
     * Gets the timeout for time to make a connection in seconds.
     *
     * @return integer connection timeout in seconds.
     */
    public function getConnectionTimeoutInSeconds();
    
    /**
     * Sets the timeout for time to make a connection in seconds.
     *
     * @param integer connection timeout in seconds.
     */
    public function setConnectionTimeoutInSeconds($connectionTimeoutInSeconds);
}

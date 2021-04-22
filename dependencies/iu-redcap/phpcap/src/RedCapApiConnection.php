<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/**
 * Contains class for creating and using a connection to a REDCap API.
 */

namespace IU\PHPCap;

/**
 * A connection to the API of a REDCap instance. This class provides a low-level
 * interface to the REDCap API, and is primarily intended for internal use by PHPCap,
 * but could be used directly by a user to access REDCap functionality not provided
 * by PHPCap.
 */
class RedCapApiConnection implements RedCapApiConnectionInterface
{
    const DEFAULT_TIMEOUT_IN_SECONDS = 1200; // 1,200 seconds = 20 minutes
    const DEFAULT_CONNECTION_TIMEOUT_IN_SECONDS = 20;
    
    /** resource cURL handle. */
    private $curlHandle;
    
    /** array used to stored cURL option values. */
    private $curlOptions;

    /** the error handler for the connection. */
    protected $errorHandler;
    
    /**
     * {@inheritdoc}
     *
     * @throws PhpCapException if an error occurs and the default error handler is being used.
     */
    public function __construct(
        $url,
        $sslVerify = false,
        $caCertificateFile = '',
        $errorHandler = null
    ) {
        # If an error handler was specified, use it,
        # otherwise, use the default PHPCap error handler
        if (isset($errorHandler)) {
            $this->errorHandler = $errorHandler;
        } else {
            $this->errorHandler = new ErrorHandler();
        }
        
        $this->curlOptions = array();
        
        $this->curlHandle = curl_init();
        
        $this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $sslVerify);
        $this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 2);
        
        if ($sslVerify && $caCertificateFile != null && trim($caCertificateFile) != '') {
            if (! file_exists($caCertificateFile)) {
                $message = 'The cert file "'. $caCertificateFile.'" does not exist.';
                $code    = ErrorHandlerInterface::CA_CERTIFICATE_FILE_NOT_FOUND;
                $this->errorHandler->throwException($message, $code);
            } elseif (! is_readable($caCertificateFile)) {
                $message = 'The cert file "'. $caCertificateFile.'" exists, but cannot be read.';
                $code    = ErrorHandlerInterface::CA_CERTIFICATE_FILE_UNREADABLE;
                $this->errorHandler->throwException($message, $code);
            } // @codeCoverageIgnore

            $this->setCurlOption(CURLOPT_CAINFO, $caCertificateFile);
        }
        
        $this->setCurlOption(CURLOPT_TIMEOUT, self::DEFAULT_TIMEOUT_IN_SECONDS);
        $this->setCurlOption(CURLOPT_CONNECTTIMEOUT, self::DEFAULT_CONNECTION_TIMEOUT_IN_SECONDS);
        $this->setCurlOption(CURLOPT_URL, $url);
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOption(CURLOPT_HTTPHEADER, array ('Accept: text/xml'));
        $this->setCurlOption(CURLOPT_POST, 1);
    }

    /**
     * Closes the cURL handle (if it is set).
     */
    public function __destruct()
    {
        if (isset($this->curlHandle)) {
            curl_close($this->curlHandle);
            $this->curlHandle = null;
        }
    }


    public function call($data)
    {
        if (!is_string($data) && !is_array($data)) {
            $message = "Data passed to ".__METHOD__." has type ".gettype($data)
                .", but should be a string or an array.";
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        
        $errno = 0;
        $response = '';
        
        // Post specified data (and do NOT save this in the options array)
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($this->curlHandle);
        
        if ($errno = curl_errno($this->curlHandle)) {
            $message = curl_error($this->curlHandle);
            $code    = ErrorHandlerInterface::CONNECTION_ERROR;
            
            # Had one case where curl_error didn't return a message
            if ($message == null || $message == '') {
                $message = curl_strerror($errno);
                if ($message == null || $message == '') {
                    $message = 'Connection error '.$errno.' occurred.';
                }
            }
            $this->errorHandler->throwException($message, $code, $errno);
        } else { // @codeCoverageIgnore
            // Check for HTTP errors
            $httpCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
            if ($httpCode == 301) {
                $callInfo = curl_getinfo($this->curlHandle);
                $message =  "The page for the specified URL ("
                    .$this->getCurlOption(CURLOPT_URL). ") has moved to "
                    .$callInfo ['redirect_url'] . ". Please update your URL.";
                $code = ErrorHandlerInterface::INVALID_URL;
                $this->errorHandler->throwException($message, $code, null, $httpCode);
            } elseif ($httpCode == 404) {
                $message = 'The specified URL ('.$this->getCurlOption(CURLOPT_URL)
                    .') appears to be incorrect. Nothing was found at this URL.';
                $code = ErrorHandlerInterface::INVALID_URL;
                $this->errorHandler->throwException($message, $code, null, $httpCode);
            } // @codeCoverageIgnore
        }
        
        return ($response);
    }
    

    public function callWithArray($dataArray)
    {
        $data = http_build_query($dataArray, '', '&');
        return $this->call($data);
    }

    
    /**
     * Returns call information for the most recent call.
     *
     * @throws PhpCapException if an error occurs and the default error handler is being used.
     * @return array an associative array of values of call information for the most recent call made.
     *
     * See {@link href="http://php.net/manual/en/function.curl-getinfo.php
     *     http://php.net/manual/en/function.curl-getinfo.php}
     *     for information on what values are returned.
     */
    public function getCallInfo()
    {
        $callInfo = curl_getinfo($this->curlHandle);
        if ($errno = curl_errno($this->curlHandle)) {
            $message = curl_error($this->curlHandle);
            $code    = ErrorHandlerInterface::CONNECTION_ERROR;
            $this->errorHandler->throwException($message, $code, $errno);
        } // @codeCoverageIgnore
        
        return $callInfo;
    }


    /**
     * {@inheritdoc}
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }
    

    public function setErrorHandler($errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }
    

    public function getUrl()
    {
        return $this->getCurlOption(CURLOPT_URL);
    }
    
    public function setUrl($url)
    {
        return $this->setCurlOption(CURLOPT_URL, $url);
    }
    
    public function getSslVerify()
    {
        return $this->getCurlOption(CURLOPT_SSL_VERIFYPEER);
    }
    
    public function setSslVerify($sslVerify)
    {
        $this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $sslVerify);
    }
    
    
    public function getCaCertificateFile()
    {
        return $this->getCurlOption(CURLOPT_CAINFO);
    }
    
    public function setCaCertificateFile($caCertificateFile)
    {
        $this->setCurlOption(CURLOPT_CAINFO, $caCertificateFile);
    }
    
    public function getTimeoutInSeconds()
    {
        return $this->getCurlOption(CURLOPT_TIMEOUT);
    }
    
    public function setTimeoutInSeconds($timeoutInSeconds)
    {
        $this->setCurlOption(CURLOPT_TIMEOUT, $timeoutInSeconds);
    }

    public function getConnectionTimeoutInSeconds()
    {
        return $this->getCurlOption(CURLOPT_CONNECTTIMEOUT);
    }
    
    public function setConnectionTimeoutInSeconds($connectionTimeoutInSeconds)
    {
        $this->setCurlOption(CURLOPT_CONNECTTIMEOUT, $connectionTimeoutInSeconds);
    }
    
    
    /**
     * Sets the specified cURL option to the specified value.
     *
     * {@internal
     *     NOTE: this method is cURL specific and is NOT part
     *     of the connection interface, and therefore should
     *     NOT be used internally by PHPCap outside of this class.
     * }
     *
     * See {@link http://php.net/manual/en/function.curl-setopt.php
     *     http://php.net/manual/en/function.curl-setopt.php}
     *     for information on cURL options.
     *
     * @param integer $option the cURL option that is being set.
     * @param mixed $value the value that the cURL option is being set to.
     * @return boolean Returns true on success and false on failure.
     */
    public function setCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
        $result = curl_setopt($this->curlHandle, $option, $value);
        return $result;
    }

    /**
     * Gets the value for the specified cURL option number.
     *
     * {@internal
     *     NOTE: this method is cURL specific and is NOT part
     *     of the connection interface, and therefore should
     *     NOT be used internally by PHPCap outside of this class.
     * }
     *
     * See {@link http://php.net/manual/en/function.curl-setopt.php
     *     http://php.net/manual/en/function.curl-setopt.php}
     *     for information on cURL options.
     *
     * @param integer $option cURL option number.
     * @return mixed if the specified option has a value that has been set in the code,
     *     then the value is returned. If no value was set, then null is returned.
     *     Note that the cURL CURLOPT_POSTFIELDS option value is not saved,
     *     because it is reset with every call and can can be very large.
     *     As a result, null will always be returned for this cURL option.
     */
    public function getCurlOption($option)
    {
        $optionValue = null;
        if (array_key_exists($option, $this->curlOptions)) {
            $optionValue = $this->curlOptions[$option];
        }
        return $optionValue;
    }
    
    public function __clone()
    {
        # Reset the curlHandle so it will be a new handle
        $this->curlHandle = curl_init();
        
        # Reset all of the options (for the new handle)
        foreach ($this->curlOptions as $optionName => $optionValue) {
            $this->setCurlOption($optionName, $optionValue);
        }
        
        $this->errorHandler = clone $this->errorHandler;
    }
}

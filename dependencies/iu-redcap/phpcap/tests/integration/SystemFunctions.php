<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

class SystemFunctions
{
    public static $curlErrorNumber   = 0;
    public static $curlErrorMessage  = '';
    public static $curlStringError   = '';
    public static $curlExecResponse  = '';
    public static $httpCode          = null;
    
    private static $isReadable      = true;
    private static $fileGetContents = true;
    private static $filePutContents = true;
    private static $errorGetLast    = null;
    
    private static $jsonErrorNumber  = null;
    private static $jsonErrorMessage = null;
    
    private static $jsonDecode = null;
    
    
    public static function setCurlErrorInfo($number, $message, $stringError)
    {
        self::$curlErrorNumber   = $number;
        self::$curlErrorMessage  = $message;
        self::$curlStringError   = $stringError;
    }
    
    
    public static function setCurlExecResponse($response)
    {
        self::$curlExecResponse = $response;
    }
    
    public static function setIsReadableToFail()
    {
        self::$isReadable = false;
    }
    
    public static function getIsReadable()
    {
        return self::$isReadable;
    }
    
    public static function resetIsReadable()
    {
        self::$isReadable = true;
    }
    
    
    
    public static function setFileGetContentsToFail()
    {
        self::$fileGetContents = false;
    }
    
    public static function getFileGetContents()
    {
        return self::$fileGetContents;
    }
    
    public static function resetFileGetContents()
    {
        self::$fileGetContents = true;
    }


    public static function setFilePutContentsToFail()
    {
        self::$filePutContents = false;
    }
    
    public static function getFilePutContents()
    {
        return self::$filePutContents;
    }
    
    public static function resetFilePutContents()
    {
        self::$filePutContents = true;
    }
    
    /**
     * Sets the value that will be returned by the
     * error_get_last() function.
     *
     * @param mixed $error
     */
    public static function setErrorGetLast($error)
    {
        self::$errorGetLast = $error;
    }
    
    public static function getErrorGetLast()
    {
        return self::$errorGetLast;
    }
    
    public static function resetErrorGetLast()
    {
        self::$errorGetLast = null;
    }
    
    
    public static function setJsonError()
    {
        self::$jsonErrorNumber  = JSON_ERROR_STATE_MISMATCH;
        self::$jsonErrorMessage = 'Invalid or malformed JSON';
    }
    
    public static function clearJsonError()
    {
        self::$jsonErrorNumber  = null;
        self::$jsonErrorMessage = null;
    }
    
    public static function getJsonErrorNumber()
    {
        return self::$jsonErrorNumber;
    }
    
    public static function getJsonErrorMessage()
    {
        return self::$jsonErrorMessage;
    }
    
    public static function setJsonDecodeToError()
    {
        self::$jsonDecode = ['error' => 'REDCap could not process the request.'];
    }
    
    public static function getJsonDecode()
    {
        return self::$jsonDecode;
    }
    
    public static function resetJsonDecode()
    {
        self::$jsonDecode = null;
    }
}



#==============================================================================
# Overridden system functions
#==============================================================================

function curl_exec($curlHandle)
{
    if (SystemFunctions::$curlExecResponse !== '') {
        $response = SystemFunctions::$curlExecResponse;
    } else {
        $response = \curl_exec($curlHandle);
    }
    return $response;
}

function curl_errno($curlHandle)
{
    if (SystemFunctions::$curlErrorNumber != 0) {
        $errno = SystemFunctions::$curlErrorNumber;
    } else {
        $errno = \curl_errno($curlHandle);
    }
    return $errno;
}

function curl_error($curlHandle)
{
    if (SystemFunctions::$curlErrorNumber !== '') {
        $error = SystemFunctions::$curlErrorMessage;
    } else {
        $errno = \curl_error($curlHandle);
    }
    return $error;
}

function curl_strerror($errornum)
{
    if (SystemFunctions::$curlStringError !== '') {
        $strerror = SystemFunctions::$curlStringError;
    } else {
        $strerror = \curl_strerror($errornum);
    }
    return $strerror;
}


function is_readable($file)
{
    $isReadable = SystemFunctions::getIsReadable();
    
    if ($isReadable === true) {
        \is_readable($file);
    }
    
    return $isReadable;
}

function curl_getinfo($curlHandle, $curlOption = null)
{
    $result = 0;
    if ($curlOption === CURLINFO_HTTP_CODE && isset(SystemFunctions::$httpCode)) {
        $result = SystemFunctions::$httpCode;
    } else {
        if ($curlOption == null) {
            $result = \curl_getinfo($curlHandle);
        } else {
            $result = \curl_getinfo($curlHandle, $curlOption);
        }
    }
    return $result;
}

function file_get_contents($filename)
{
    $contents = SystemFunctions::getFileGetContents();

    if ($contents === true) {
        $contents = \file_get_contents($filename);
    }

    return $contents;
}

function file_put_contents($filename, $data, $flags = 0)
{
    $result = SystemFunctions::getFilePutContents();
    
    if ($result === true) {
        $result = \file_put_contents($filename, $data, $flags);
    }
    return $result;
}

function error_get_last()
{
    $error = SystemFunctions::getErrorGetLast();

    if (!isset($error)) {
        $error = \error_get_last();
    }
    
    return $error;
}

function json_last_error()
{
    $error = SystemFunctions::getJsonErrorNumber();
    if (!isset($error)) {
        $error = \json_last_error();
    }
    return $error;
}

function json_last_error_msg()
{
    $errorMessage = SystemFunctions::getJsonErrorMessage();
    if (!isset($errorMessage)) {
        $errorMessage = \json_last_error();
    }
    return $errorMessage;
}

function json_decode($json, $assoc = false, $depth = 512, $options = 0)
{
    $jsonDecode = SystemFunctions::getJsonDecode();
    if (!isset($jsonDecode)) {
        $jsonDecode = \json_decode($json, $assoc, $depth, $options);
    }
    
    return $jsonDecode;
}

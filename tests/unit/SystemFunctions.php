<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

class SystemFunctions
{
    private static $overrideErrorLog  = false;
    private static $lastErrorLogMessage = null;

    private static $overrideFileGetContents = false;
    private static $fileGetContentsResult = null;

    private static $mailArguments = array();
    private static $mailFails = false;
    private static $overrideMail = false;

    public static function errorLog($message, $messageType)
    {
        self::$lastErrorLogMessage = $message;
    }

    public static function getOverrideErrorLog()
    {
        return self::$overrideErrorLog;
    }

    public static function setOverrideErrorLog($override)
    {
        self::$overrideErrorLog = $override;
    }

    public static function getLastErrorLogMessage()
    {
        return self::$lastErrorLogMessage;
    }

    public static function setFileGetContentsResult($returnVal)
    {
        self::$fileGetContentsResult = $returnVal;
    }

    public static function fileGetContents($filename)
    {
        return self::$fileGetContentsResult;
    }

    public static function getOverrideFileGetContents()
    {
        return self::$overrideFileGetContents;
    }

    public static function setOverrideFileGetContents($override)
    {
        self::$overrideFileGetContents = $override;
    }

    public static function getOverrideMail()
    {
        return self::$overrideMail;
    }

    public static function setOverrideMail($override)
    {
        self::$overrideMail = $override;
    }

    public static function mail($to, $subject, $message, $additionalHeaders, $addtionalParameters)
    {
        $result = true;
        array_push(self::$mailArguments, array($to, $subject, $message, $additionalHeaders, $addtionalParameters));

        if (self::$mailFails == true) {
            $result = false;
        }
        return $result;
    }

    public static function getMailArguments()
    {
        return self::$mailArguments;
    }

    /**
     * param boolean $fails indicates if mail should fail or not.
     */
    public static function setMailFails($fails)
    {
        self::$mailFails = $fails;
    }
}

#------------------------------------------------------------------------------
# Overridden system functions
#------------------------------------------------------------------------------

function error_log($message, $messageType = 0, $messageFile = null)
{
    if (SystemFunctions::getOverrideErrorLog() === true) {
        $result = SystemFunctions::errorLog($message, $messageType);
    } else {
        $result = \error_log($message, $messageType, $messageFile);
    }
    return $result;
}

// ADA: Using full set of parameters led to unexpected errors. May need to
//      debug these if any calls to file_get_contents use more than the first
//      parameter.
//function file_get_contents($filename, $use_include_path = FALSE, $context = NULL, $offset = 0, $maxlen = NULL)
function file_get_contents($filename)
{
    if (SystemFunctions::getOverrideFileGetContents() === true) {
        $result = SystemFunctions::fileGetContents($filename);
    } else {
        $result = \file_get_contents($filename);
        //$result = \file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
    }
    return $result;
}

function mail($to, $subject, $message, $additionalHeaders, $addtionalParameters)
{
    if (SystemFunctions::getOverrideMail() === true) {
        $result = SystemFunctions::mail($to, $subject, $message, $additionalHeaders, $addtionalParameters);
    } else {
        $result = \mail($to, $subject, $message, $additionalHeaders, $addtionalParameters);
    }
    return $result;
}

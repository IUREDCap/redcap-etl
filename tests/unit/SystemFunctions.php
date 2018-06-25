<?php

namespace IU\REDCapETL;

class SystemFunctions
{
    private static $overrideErrorLog  = false;
    private static $lastErrorLogMessage = null;
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

function error_log($message, $messageType = 0)
{
    if (SystemFunctions::getOverrideErrorLog() === true) {
        $result = SystemFunctions::errorLog($message, $messageType);
    } else {
        $result = \error_log($message, $messageType);
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

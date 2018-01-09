<?php

namespace IU\REDCapETL;

/**
 * REDCap ETL error handler.
 */
class EtlErrorHandler
{
    public function throwException(
        $message,
        $code = 0,
        $previousException = null
    ) {
        throw new EtlException($message, $code, $previousException);
    }
}

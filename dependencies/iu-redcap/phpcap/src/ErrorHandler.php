<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

/**
 * Default error handler for PHPCap. PHPCap will call
 * the throwException method of this class when
 * an error occurs.
 */
class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * See {@link http://php.net/manual/en/function.debug-backtrace.php debug_backtrace}
     *     for information on how to get a stack trace within this method.
     */
    public function throwException(
        $message,
        $code,
        $connectionErrorNumber = null,
        $httpStatusCode = null,
        $previousException = null
    ) {
        throw new PhpCapException($message, $code, $connectionErrorNumber, $httpStatusCode, $previousException);
    }
}

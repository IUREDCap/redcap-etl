<?php

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
     * @see <a href="http://php.net/manual/en/function.debug-backtrace.php">debug_backtrace()</a>
     *     for information on how to get a stack trace within this method.
     */
    public function throwException(
        $message,
        $code,
        $connectionErrorNumber = null,
        $httpStatusCode = null,
        $previousException = null
    ) {
        throw new PhpCapException(
            $message,
            $code,
            $connectionErrorNumber,
            $httpStatusCode,
            $previousException
        );
    }
}

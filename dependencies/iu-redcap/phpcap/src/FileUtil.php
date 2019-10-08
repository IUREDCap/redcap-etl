<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/**
 * This file contains file utilities.
 */
namespace IU\PHPCap;

/**
 * File utility class for dealing with files.
 */
class FileUtil
{
    
    protected static $errorHandler = null;
    
    /**
     * Reads the contents of the specified file and returns it as a string.
     *
     * @param string $filename the name of the file that is to be read.
     *
     * @throws PhpCapException if an error occurs while trying to read the file.
     *
     * @return string the contents of the specified file.
     */
    public static function fileToString($filename)
    {
        $errorHandler = static::getErrorHandler();
        
        if (!file_exists($filename)) {
            $errorHandler->throwException(
                'The input file "'.$filename.'" could not be found.',
                ErrorHandlerInterface::INPUT_FILE_NOT_FOUND
            );
        } elseif (!is_readable($filename)) {
            $errorHandler->throwException(
                'The input file "'.$filename.'" was unreadable.',
                ErrorHandlerInterface::INPUT_FILE_UNREADABLE
            );
        } // @codeCoverageIgnore
        
        $contents = file_get_contents($filename);

        if ($contents === false) {
            $error = error_get_last();
            $errorMessage = null;
            if ($error != null && array_key_exists('message', $error)) {
                $errorMessage = $error['message'];
            }
            
            if (isset($errorMessage)) {
                $errorHandler->throwException(
                    'An error occurred in input file "'.$filename.'": '.$errorMessage,
                    ErrorHandlerInterface::INPUT_FILE_ERROR
                );
            } else { // @codeCoverageIgnore
                $errorHandler->throwException(
                    'An error occurred in input file "'.$filename.'"',
                    ErrorHandlerInterface::INPUT_FILE_ERROR
                );
            } // @codeCoverageIgnore
        } // @codeCoverageIgnore
        
        return $contents;
    }
 
    /**
     * Writes the specified string to the specified file.
     *
     * @param string $string the string to write to the file.
     * @param string $filename the name of the file to write the string.
     * @param boolean $append if true, the file is appended if it already exists. If false,
     *        the file is created if it doesn't exist, and overwritten if it does.
     *
     * @throws PhpCapException if an error occurs.
     *
     * @return mixed false on failure, and the number of bytes written on success.
     */
    public static function writeStringToFile($string, $filename, $append = false)
    {
        $errorHandler = static::getErrorHandler();
        
        $result = false;
        if ($append === true) {
            $result = file_put_contents($filename, $string, FILE_APPEND);
        } else {
            $result = file_put_contents($filename, $string);
        }
        
        if ($result === false) {
            $error = error_get_last();
            $errorMessage = null;
            if ($error != null && array_key_exists('message', $error)) {
                $errorMessage = $error['message'];
            }
            
            if (isset($errorMessage)) {
                $errorHandler->throwException(
                    'An error occurred in output file "'.$filename.'": '.$errorMessage,
                    ErrorHandlerInterface::OUTPUT_FILE_ERROR
                );
            } else { // @codeCoverageIgnore
                $errorHandler->throwException(
                    'An error occurred in output file "'.$filename.'"',
                    ErrorHandlerInterface::OUTPUT_FILE_ERROR
                );
            } // @codeCoverageIgnore
        } // @codeCoverageIgnore
            
        return $result;
    }
    
    /**
     * Appends the specified string to the specified file.
     *
     * @param string $string the string to append.
     * @param string $filename the name of the file that is appended.
     *
     * @throws PhpCapException if an error occurs.
     *
     * @return mixed false on failure, and the number of bytes appended on success.
     */
    public static function appendStringToFile($string, $filename)
    {
        $result = static::writeStringToFile($string, $filename, true);
        return $result;
    }
    
    /**
     * Gets the error handler for the class.
     *
     * @return ErrorHandlerInterface the error handler for the class.
     */
    public static function getErrorHandler()
    {
        if (!isset(self::$errorHandler)) {
            self::$errorHandler = new ErrorHandler();
        }
        return self::$errorHandler;
    }
    
    /**
     * Sets the error handler used for methods in this class.
     *
     * @param ErrorHandlerInterface $errorHandler
     */
    public static function setErrorHandler($errorHandler)
    {
        # Get the current error handler to make sure it's set,
        # since it will need to be used if the passed error
        # handler is invalid
        $currentErrorHandler = static::getErrorHandler();
        
        if (!($errorHandler instanceof ErrorHandlerInterface)) {
            $message = 'The error handler argument is not valid, because it doesn\'t implement '
                .ErrorHandlerInterface::class.'.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $currentErrorHandler->throwException($message, $code);
        } // @codeCoverageIgnore
        self::$errorHandler = $errorHandler;
    }
}

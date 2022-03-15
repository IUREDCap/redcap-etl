<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

/**
 * File utility class.
 */
class FileUtil
{
    /**
     * Indicates is the specified file path is an absolute path.
     *
     * @param string $path the file path to check.
     *
     * @return boolean returns true of the file path is an absoulte path,
     *     and false otherwise.
     */
    public static function isAbsolutePath($path)
    {
        $isAbsolute = false;
        $path = trim($path);

        if (DIRECTORY_SEPARATOR === '/') {
            if (preg_match('/^\/.*/', $path) === 1) {
                $isAbsolute = true;
            }
        } else {  // Windows
            if (preg_match('/^(\/|\\\|[a-zA-Z]:(\/|\\\)).*/', $path) === 1) {
                $isAbsolute = true;
            }
        }
        return $isAbsolute;
    }
    
    /**
     * Gets the absolute directory path for the specified path.
     * If the specified path includes a file, then the file
     * will be removed from the path that is returned.
     *
     * @param string path the file or directory path
     *
     * @param string $baseDir the optional base directory to use if a relative path
     *     is specified.
     *
     * @return string absolute path for the specified path.
     */
    public static function getAbsoluteDir($path)
    {
        if ($path == null) {
            $path = '';
        } else {
            $path = trim($path);
        }
        
        $dirName  = dirname($path);
        $realDir  = realpath($dirName);
        if ($realDir === false) {
            $message = 'Directory for "'.$path.'" not found.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        return $realDir;
    }
    
    /**
     * Gets the absolute path of a path to a file or direcory.
     * The directory part of the path must exist, but the file
     * part does not need to.
     *
     * @param string path the file or directory path
     *
     * @param string $baseDir the optional base directory to use if a relative path
     *     is specified.
     *
     * @return string absolute path for the specified path.
     */
    public static function getAbsolutePath($path, $baseDir = null)
    {
        if ($path == null) {
            $path = '';
        } else {
            $path = trim($path);
        }
        
        if (!self::isAbsolutePath($path) && !empty($baseDir)) {
            $path = $baseDir . '/' . $path;
        }
    
        $dirName  = dirname($path);
        $realDir  = realpath($dirName);
        if ($realDir === false) {
            $message = 'Directory for "'.$path.'" not found.';
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }
            
        $fileName = basename($path);
        if (!empty($fileName)) {
            $realFile = $realDir.'/'.$fileName;
        }

        return $realFile;
    }

    public static function getSafePath($path)
    {
        $path = realpath($path);
        return $path;
    }
}

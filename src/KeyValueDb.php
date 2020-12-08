<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

/**
 * Centralized class for getting the next primary key value for a database table.
 */
class KeyValueDb
{
    # array of key values by [database][table]
    private static $keyValues = array();

    public static function initialize()
    {
        self::$keyValues = array();
    }

    /**
     * Gets the next key value for the specified database and table.
     */
    public static function getNextKeyValue($db, $table)
    {
        if (!array_key_exists($db, self::$keyValues)) {
            self::$keyValues[$db] = array();
        }

        $dbKeys = self::$keyValues[$db];
        if (!array_key_exists($table, $dbKeys)) {
            self::$keyValues[$db][$table] = 1;
        } else {
            self::$keyValues[$db][$table]++;
        }
        $keyValue = self::$keyValues[$db][$table];
        return $keyValue;
    }

    public static function getKeyValues()
    {
        return self::$keyValues;
    }
}

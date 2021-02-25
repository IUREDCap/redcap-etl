<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the "repeating events" tests using MySQL as the database.
 */
class SystemTestsUtil
{
    /**
     * Convert CSV data into a map format that matches the output
     * of PDO's fetchall method.
     */
    public static function convertCsvToMap($csv)
    {
        $map = array();
        $header = $csv[0];
        for ($i = 1; $i < count($csv); $i++) {
            $row = array();
            for ($j = 0; $j < count($header); $j++) {
                $key   = $header[$j];
                $value = $csv[$i][$j];
                $row[$key] = $value;
            }
            array_push($map, $row);
        }
        return $map;
    }

    public static function convertMapValues(&$csv)
    {
        for ($i = 0; $i < count($csv); $i++) {
            foreach ($csv[$i] as $key => $value) {
                if (is_numeric($value)) {
                    $csv[$i][$key] = floatval($csv[$i][$key]);
                }
            }
        }
    }

    public static function convertCsvValues(&$csv)
    {
        $header = $csv[0];
        for ($i = 1; $i < count($csv); $i++) {
            for ($j = 0; $j < count($header); $j++) {
                if (is_numeric($csv[$i][$j])) {
                    $csv[$i][$j] = floatval($csv[$i][$j]);
                }
            }
        }
    }

    public static function convertCsvRowValues(&$csvRow)
    {
        foreach ($csvRow as $key => $value) {
            if (is_numeric($value)) {
                $csvRow[$key] = floatval($csvRow[$key]);
            }
        }
    }
}

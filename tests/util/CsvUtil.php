<?php
#-------------------------------------------------------
# Copyright (C) 2024 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * Utility class for CSV
 */
class CsvUtil
{
    /**
     * Converts the specified CSV file to a 2-dimensional array.
     *
     * @param string $csvfile the path to the CSV file to convert.
     */
    public static function csvFileToArray($csvFile)
    {
        $values = [];
        $file = fopen($csvFile, 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $values[] = $line;
        }
        fclose($file);

        return $values;
    }
}

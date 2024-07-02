<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

/**
 * Contains the valid rows types for Tables.
 */
class RowsType
{
    const ROOT                 = 0;
    const BY_EVENTS            = 1;
    const BY_SUFFIXES          = 2;

    const BY_EVENTS_SUFFIXES         = 3;
    const BY_REPEATING_INSTRUMENTS   = 4;
    const BY_REPEATING_EVENTS        = 5;

    const BY_REPEATING_INSTRUMENTS_SUFFIXES = 6;
    const BY_REPEATING_EVENTS_SUFFIXES      = 7;

    public static function isValid($rowsType)
    {
        $valid = false;

        switch ($rowsType) {
            case RowsType::ROOT:
            case RowsType::BY_EVENTS:
            case RowsType::BY_SUFFIXES:
            case RowsType::BY_EVENTS_SUFFIXES:
            case RowsType::BY_REPEATING_INSTRUMENTS:
            case RowsType::BY_REPEATING_EVENTS:
            case RowsType::BY_REPEATING_INSTRUMENTS_SUFFIXES:
            case RowsType::BY_REPEATING_EVENTS_SUFFIXES:
                $valid = true;
                break;

            default:
                break;
        }
        return($valid);
    }

    /**
     * Indicates if rows type includes suffixes (i.e., is the suffixes type
     * or a compound type that includes suffixes).
     *
     * @return boolean true if the type includes suffixes, false otherwise.
     */
    public static function hasSuffixes($rowsType)
    {
        $valid = false;

        switch ($rowsType) {
            case in_array(RowsType::BY_SUFFIXES, $rowsType, true):
            case in_array(RowsType::BY_EVENTS_SUFFIXES, $rowsType, true):
            case in_array(RowsType::BY_REPEATING_INSTRUMENTS_SUFFIXES, $rowsType, true):
            case in_array(RowsType::BY_REPEATING_EVENTS_SUFFIXES, $rowsType, true):
                $valid = true;
                break;

            default:
                break;
        }
        return($valid);
    }
}

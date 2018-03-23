<?php

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

    public static function isValid($rowsType)
    {
        $valid = false;

        switch ($rowsType) {
            case RowsType::ROOT:
            case RowsType::BY_EVENTS:
            case RowsType::BY_SUFFIXES:
            case RowsType::BY_EVENTS_SUFFIXES:
            case RowsType::BY_REPEATING_INSTRUMENTS:
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
    public static function hasSuffixes()
    {
        $valid = false;

        switch ($rowsType) {
            case RowsType::BY_SUFFIXES:
            case RowsType::BY_EVENTS_SUFFIXES:
                $valid = true;
                break;

            default:
                break;
        }
        return($valid);
    }
}

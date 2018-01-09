<?php

namespace IU\REDCapETL\Schema;

/**
 * Contains the valid field types.
 */
class FieldType
{
    const INT      = 'int';
    const FLOAT    = 'float';
    const STRING   = 'string';
    const DATE     = 'date';
    const CHECKBOX = 'checkbox';

    public static function isValid($fieldType)
    {
        $valid = false;

        switch ($fieldType) {
            case FieldType::INT:
            case FieldType::FLOAT:
            case FieldType::STRING:
            case FieldType::DATE:
            case FieldType::CHECKBOX:
                $valid = true;
                break;

            default:
                break;
        }
        return($valid);
    }
}

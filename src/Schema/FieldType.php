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
    const CHAR     = 'char';
    const VARCHAR  = 'varchar';
    const DATE     = 'date';
    const DATETIME = 'datetime';
    const CHECKBOX = 'checkbox';
    
    const AUTO_INCREMENT = 'auto_increment';

    public static function isValid($fieldType)
    {
        $valid = false;

        switch ($fieldType) {
            case FieldType::INT:
            case FieldType::FLOAT:
            case FieldType::STRING:
            case FieldType::CHAR:
            case FieldType::VARCHAR:
            case FieldType::DATE:
            case FieldType::DATETIME:
            case FieldType::CHECKBOX:
            case FieldType::AUTO_INCREMENT:
                $valid = true;
                break;

            default:
                break;
        }
        return($valid);
    }
}

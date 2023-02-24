<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

/**
 * Contains the valid field types.
 */
class FieldType
{
    const INT          = 'int';
    const FLOAT        = 'float';
    const STRING       = 'string';
    const CHAR         = 'char';
    const VARCHAR      = 'varchar';
    const DATE         = 'date';
    const DATETIME     = 'datetime';
    const CHECKBOX     = 'checkbox';
    const CHECKBOXLIST = 'checkboxlist';
    const DROPDOWN     = 'dropdown';
    const RADIO        = 'radio';
    
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
            case FieldType::CHECKBOXLIST:
            case FieldType::DROPDOWN:
            case FieldType::RADIO:
            case FieldType::AUTO_INCREMENT:
                $valid = true;
                break;

            default:
                break;
        }
        return($valid);
    }
}

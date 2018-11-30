<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Table;

/**
 * Table class for main logging table (used for database logging).
 */
class EtlEventLogTable extends Table
{
    const FIELD_PRIMARY_ID  = 'log_id';
    const FIELD_TIME        = 'time';
    
    public function __construct($lookupChoices, $tablePrefix, $keyType, $name)
    {
        parent::__construct(
            $tablePrefix . $name,
            self::FIELD_PRIMARY_ID,
            $keyType,
            array(RowsType::ROOT),
            array()
        );

        #-----------------------------------------------
        # Create and add fields for the lookup table
        #-----------------------------------------------
        $fieldPrimary   = new Field(self::FIELD_PRIMARY_ID, FieldType::INT);
        $fieldTime      = new Field(self::FIELD_TIME, FieldType::DATETIME);
        
        $this->addField($fieldPrimary);
        $this->addField($fieldTime);
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\FieldTypeSpecifier;
use IU\REDCapETL\Schema\Row;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Table;

/**
 * Table class for main logging table (used for database logging).
 */
class EtlEventLogTable extends Table
{
    const FIELD_PRIMARY_ID  = 'log_event_id';
    const FIELD_LOG_ID      = 'log_id';
    const FIELD_TIME        = 'time';
    const FIELD_MESSAGE     = 'message';
    
    public function __construct($name)
    {
        parent::__construct(
            $name,
            self::FIELD_PRIMARY_ID,
            new FieldTypeSpecifier(FieldType::AUTO_INCREMENT),
            array(RowsType::ROOT),
            array()
        );

        #-----------------------------------------------
        # Create and add fields for the lookup table
        #-----------------------------------------------
        $fieldPrimary   = new Field(self::FIELD_PRIMARY_ID, FieldType::AUTO_INCREMENT);
        $fieldLogId     = new Field(self::FIELD_LOG_ID, FieldType::INT);
        $fieldTime      = new Field(self::FIELD_TIME, FieldType::DATETIME, 6);
        $fieldMessage   = new Field(self::FIELD_MESSAGE, FieldType::STRING);
        
        $this->addField($fieldPrimary);
        $this->addField($fieldLogId);
        $this->addField($fieldTime);
        $this->addField($fieldMessage);
    }
    
    /**
     * Gets a row for saving in the database version of this table.
     *
     * @param integer $logId foreign key value that points to main database log table.
     * @param string $message the message to log.
     */
    public function createEventLogDataRow($logId, $message)
    {
        list($microseconds, $seconds) = explode(" ", microtime());
        $startTime = date("Y-m-d\TH:i:s", $seconds).substr($microseconds, 1, 4);
        
        $row = new Row($this);
        $row->addValue(self::FIELD_LOG_ID, $logId);
        $row->addValue(self::FIELD_TIME, $startTime);
        $row->addValue(self::FIELD_MESSAGE, $message);
        
        return $row;
    }
}

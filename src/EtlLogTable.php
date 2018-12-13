<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\FieldTypeSpecifier;
use IU\REDCapETL\Schema\Row;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Table;

/**
 * Table class for main logging table (used for database logging)
 * that records the ETL jobs that are run.
 */
class EtlLogTable extends Table
{
    const FIELD_PRIMARY_ID   = 'log_id';
    const FIELD_TIME         = 'start_time';
    
    #const FIELD_APP          = 'app';
    
    # Don't need prefix, because log table name will get prefix
    #const FIELD_TABLE_PREFIX = 'table_prefix';
    
    const FIELD_BATCH_SIZE   = 'batch_size';
    
    const FIELD_REDCAP_ETL_VERSION = 'redcap_etl_version';
    
    public function __construct($tablePrefix, $name)
    {
        parent::__construct(
            $tablePrefix . $name,
            self::FIELD_PRIMARY_ID,
            new FieldTypeSpecifier(FieldType::AUTO_INCREMENT),
            array(RowsType::ROOT),
            array()
        );

        #-----------------------------------------------
        # Create and add fields for the lookup table
        #-----------------------------------------------
        $fieldPrimary = new Field(self::FIELD_PRIMARY_ID, FieldType::AUTO_INCREMENT);
        $fieldTime    = new Field(self::FIELD_TIME, FieldType::DATETIME, 6);
        
        #$fieldApp         = new Field(self::FIELD_APP, FieldType::VARCHAR, 60);
        #$fieldTablePrefix = new Field(self::FIELD_TABLE_PREFIX, FieldType::VARCHAR, 60);
        $fieldBatchSize   = new Field(self::FIELD_BATCH_SIZE, FieldType::INT);
        
        $fieldRedcapEtlVersion = new Field(self::FIELD_REDCAP_ETL_VERSION, FieldType::CHAR, 10);
        
        $this->addField($fieldPrimary);
        $this->addField($fieldTime);
        #$this->addField($fieldApp);
        #$this->addField($fieldTablePrefix);
        $this->addField($fieldBatchSize);
        $this->addField($fieldRedcapEtlVersion);
    }
    
    /**
     * Gets a row for saving in the database version of this table.
     */
    public function createLogDataRow($app, $tablePrefix, $batchSize)
    {
        list($microseconds, $seconds) = explode(" ", microtime());
        $startTime = date("Y-m-d H:i:s", $seconds).substr($microseconds, 1, 7);
        
        $redCapEtlVersion = Version::RELEASE_NUMBER;
        
        $row = new Row($this);
        $row->addValue(self::FIELD_TIME, $startTime);
        #$row->addValue(self::FIELD_APP, $app);
        #$row->addValue(self::FIELD_TABLE_PREFIX, $tablePrefix);
        $row->addValue(self::FIELD_BATCH_SIZE, $batchSize);
        $row->addValue(self::FIELD_REDCAP_ETL_VERSION, $redCapEtlVersion);
        
        return $row;
    }
}

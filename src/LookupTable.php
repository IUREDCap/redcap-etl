<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Table;

/**
 * Table class for storing multiple choice field values and
 * and their corresponding labels.
 */
class LookupTable extends Table
{
    const DEFAULT_NAME      = 'etl_lookup';
    
    const FIELD_PRIMARY_ID  = 'lookup_id';
    const FIELD_TABLE_NAME  = 'table_name';
    const FIELD_FIELD_NAME  = 'field_name';
    const FIELD_VALUE       = 'value';
    const FIELD_LABEL       = 'label';
    
    /** @var array multi-dimensional array: [table-name][field-name][value] = label.
     *    Used internally for efficient retrieval of multiple choice labels.
     */
    private $map;

    private $lookupChoices;

    /**
     * @parameter array $lookupChoices map from REDCap field name to a map from multiple
     *     choice value to multiple choice label for that field.
     *
     * @parameter string $tablePrefix the prefix for the table name, if any.
     *
     * @parameter FieldTypeSpecifier $keyType the type of the primary key.
     *
     * @parameter string $bane the name of the table, not including the table prefix, if any.
     */
    public function __construct($lookupChoices, $tablePrefix, $keyType, $name = self::DEFAULT_NAME)
    {
        parent::__construct(
            $tablePrefix . $name,
            self::FIELD_PRIMARY_ID,
            $keyType,
            array(RowsType::ROOT),
            array()
        );

        $this->map = array();

        $this->lookupChoices = $lookupChoices;
        
        #-----------------------------------------------
        # Create and add fields for the lookup table
        #-----------------------------------------------
        $fieldPrimary   = new Field(self::FIELD_PRIMARY_ID, FieldType::STRING);
        $fieldFieldName = new Field(self::FIELD_TABLE_NAME, FieldType::STRING);
        $fieldTableName = new Field(self::FIELD_FIELD_NAME, FieldType::STRING);
        $fieldCategory  = new Field(self::FIELD_VALUE, FieldType::STRING);
        $fieldValue     = new Field(self::FIELD_LABEL, FieldType::STRING);
        
        $this->addField($fieldPrimary);
        $this->addField($fieldFieldName);
        $this->addField($fieldTableName);
        $this->addField($fieldCategory);
        $this->addField($fieldValue);
    }
    
    
    /**
     * Adds a multiple choice field's values and corresponding
     * labels into the Lookup table.
     *
     * @param string $tablename the name of the table that contains the
     *     field to be added.
     *
     * @param string $redcapFieldName the name of the field to be added.
     */
    public function addLookupField($tableName, $redcapFieldName, $dbFieldName = null)
    {
        if (empty($dbFieldName)) {
            $dbFieldName = $redcapFieldName;
        }

        if (!(array_key_exists($tableName, $this->map) && array_key_exists($dbFieldName, $this->map[$tableName]))) {
            foreach ($this->lookupChoices[$redcapFieldName] as $value => $label) {
                # REDCap apparently converts all field names to lower-case
                $value = strtolower($value);
                #--------------------------------------------------------
                # Set up the table/fieldcategory/label for this choice
                # The primary key will be set automatically
                #--------------------------------------------------------
                $data = array(
                    self::FIELD_TABLE_NAME => $tableName,
                    self::FIELD_FIELD_NAME => $dbFieldName,
                    self::FIELD_VALUE => $value,
                    self::FIELD_LABEL => $label
                );

                // Add the row, using no foreign key or suffix
                $this->createRow($data, '', '', $this->rowsType);

                #---------------------------------------------------
                # Update the map
                #---------------------------------------------------
                if (!array_key_exists($tableName, $this->map)) {
                    $this->map[$tableName] = array();
                }

                if (!array_key_exists($dbFieldName, $this->map[$tableName])) {
                    $this->map[$tableName][$dbFieldName] = array();
                }
                
                $this->map[$tableName][$dbFieldName][$value] = $label;
            }
        }
    }

    /**
     * Gets the label for the specified field's value.
     *
     * @param string $tableName the name of the database table for which
     *    the label is to be retrieved.
     * @param string $fieldName the field name for of the label to get.
     * @param string $value the (coded) value of the label to get.
     *
     * @return string the label for the specified field.
     */
    public function getLabel($tableName, $fieldName, $value)
    {
        $label = '';

        # if the value is not null and
        # (is not a string or is a non-blank string)
        if (isset($value)) {
            if (is_string($value)) {
                $value = trim($value);
                if ($value !== '') {
                    $value = strtolower($value);
                    $label = $this->map[$tableName][$fieldName][$value];
                }
            } else {
                $label = $this->map[$tableName][$fieldName][$value];
            }
        }
        return $label;
    }

    /**
     * Gets a map from values to labels for the specfied field.
     *
     * @param string $tableName the name of the database table for which
     *    the map is to be retrieved.
     * @param string $fieldName the map to get.
     *
     * @return array map from values to labels for the specified field.
     */
    public function getValueLabelMap($tableName, $fieldName)
    {
        $valueLabelMap = $this->map[$tableName][$fieldName];
        return $valueLabelMap;
    }

    /**
     * Returns the merge of this lookup table with the specified lookup table.
     *
     * @var LookupTable $table the lookup table to be merged.
     */
    public function merge($table, $mergeData = true)
    {
        if ($this->getName() !== $table->getName()) {
            $message = "Lookup table names \"{$this->getName()}\" and \"{$table->getName()}\" do not match.";
            throw new EtlException($message, EtlException::INPUT_ERROR);
        }

        $mergedLookup = parent::merge($table, $mergeData);

        $mergedLookup->lookupChoices = array_merge($this->lookupChoices, $table->lookupChoices);
        ksort($mergedLookup->lookupChoices);

        $mergedLookup->map = array_merge($this->map, $table->map);
        ksort($mergedLookup->map);

        #-----------------------------------------------------------------------
        # Add back the data without duplicates and in sorted order
        #-----------------------------------------------------------------------
        $mergedLookup->rows = array(); # remove existing rows
        $map = $mergedLookup->map;
        foreach ($map as $table => $tableInfo) {
            foreach ($tableInfo as $field => $valueInfo) {
                foreach ($valueInfo as $value => $label) {
                    $data = array(
                        self::FIELD_TABLE_NAME => $table,
                        self::FIELD_FIELD_NAME => $field,
                        self::FIELD_VALUE => $value,
                        self::FIELD_LABEL => $label
                    );

                    // Add the row, using no foreign key or suffix
                    $this->createRow($data, '', '', $this->rowsType);
                }
                $mergedLookup->addLookupField($table, $field);
            }
        }
        return $mergedLookup;
    }


    public function compare($mapRow1, $mapRow2)
    {
        $cmp = strcmp($mapRow1[0], $mapRow2[0]); // compare table name
        if ($cmp === 0) {
            $cmp = strcmp($mapRow1[1], $mapRow2[1]); // compare field name
            if ($cmp === 0) {
                $cmp = strcmp($mapRow1[2], $mapRow2[2]); // compare value
            }
        }
        return $cmp;
    }

    public function getLookupChoices()
    {
        return $this->lookupChoices;
    }

    public function getMap()
    {
        return $this->map;
    }
}

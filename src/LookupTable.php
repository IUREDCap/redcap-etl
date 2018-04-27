<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Table;
use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;

/**
 * Table class for storing multiple choice field values and
 * and their corresponding labels.
 */
class LookupTable extends Table
{
    const NAME              = 'Lookup';
    
    const FIELD_PRIMARY_ID  = 'lookup_id';
    const FIELD_TABLE_NAME  = 'table_name';
    const FIELD_FIELD_NAME  = 'field_name';
    const FIELD_CATEGORY    = 'category';
    const FIELD_LABEL       = 'label';
    
    private $map;  // map for efficient retrieval; used internally

    private $lookupChoices;
    private $lookupTableIn;  // For efficiently checking if field was already inserted
    
    public function __construct($lookupChoices, $tablePrefix, $keyType)
    {
        parent::__construct(
            $tablePrefix . self::NAME,
            self::FIELD_PRIMARY_ID,
            $keyType,
            RulesParser::ROOT,
            array()
        );

        $this->map = array();

        $this->lookupChoices = $lookupChoices;
        $this->lookupTableIn = array();
        
        #-----------------------------------------------
        # Create and add fields for the lookup table
        #-----------------------------------------------
        $fieldPrimary   = new Field(self::FIELD_PRIMARY_ID, FieldType::STRING);
        $fieldFieldName = new Field(self::FIELD_TABLE_NAME, FieldType::STRING);
        $fieldTableName = new Field(self::FIELD_FIELD_NAME, FieldType::STRING);
        $fieldCategory  = new Field(self::FIELD_CATEGORY, FieldType::STRING);
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
     * @param string $fieldName the name of the field to be added.
     */
    public function addLookupField($tableName, $fieldName)
    {
        if (empty($this->lookupTableIn[$tableName.':'.$fieldName])) {
            $this->lookupTableIn[$tableName.':'.$fieldName] = true;

            foreach ($this->lookupChoices[$fieldName] as $value => $label) {
                # REDCap apparently converts all field names to lower-case
                $value = strtolower($value);
                #--------------------------------------------------------
                # Set up the table/fieldcategory/label for this choice
                # The primary key will be set automatically
                #--------------------------------------------------------
                $data = array(
                    self::FIELD_TABLE_NAME => $tableName,
                    self::FIELD_FIELD_NAME => $fieldName,
                    self::FIELD_CATEGORY => $value,
                    self::FIELD_LABEL => $label
                );

                // Add the row, using no foreign key or suffix
                $this->createRow($data, '', '');

                #---------------------------------------------------
                # Update the map
                #---------------------------------------------------
                if (!array_key_exists($tableName, $this->map)) {
                    $this->map[$tableName] = array();
                }

                if (!array_key_exists($fieldName, $this->map[$tableName])) {
                    $this->map[$tableName][$fieldName] = array();
                }
                
                $this->map[$tableName][$fieldName][$value] = $label;
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
     */
    public function getValueLabelMap($tableName, $fieldName)
    {
        $valueLabelMap = $this->map[$tableName][$fieldName];
        return $valueLabelMap;
    }
}

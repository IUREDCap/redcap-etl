<?php

namespace IU\REDCapETL;

/**
 * Lookup class for looking up labels for categories.
 */
class Lookup
{
    private $rows;

    /**
     * Creates Lookup object for looking up labels for REDCap categories
     * (e.g., "yes" for 0 and "no" for 1).
     *
     * @param Table $lookupTable has information about values and labels
     *     for the multiple-choice REDCap fields.
     */
    public function __construct($lookupTable)
    {
        $this->rows = array();

        $rowObjects = $lookupTable->getRows();

        foreach ($rowObjects as $rowObject) {
            $rowData = $rowObject->getData();
            $tableName = $rowData['table_name'];
            $fieldName = $rowData['field_name'];
            $category  = $rowData['category'];
            $label     = $rowData['label'];

            if (!array_key_exists($tableName, $this->rows)) {
                $this->rows[$tableName] = array();
            }
            if (!array_key_exists($fieldName, $this->rows[$tableName])) {
                $this->rows[$tableName][$fieldName] = array();
            }

            $this->rows[$tableName][$fieldName][$category] = $label;
        }
    }

    /**
     * Gets the label for the specified input values.
     *
     * @param string $tableName the name of the database table for which
     *    the label is to be retrieved.
     * @param string $fieldName the field name for of the label to get.
     * @param string $category the category/value of the label to get.
     */
    public function getLabel($tableName, $fieldName, $category)
    {
        $label = $this->rows[$tableName][$fieldName][$category];
        return $label;
    }

    /**
     * Gets a map from category/value to label for the specfied field.
     * @param string $tableName the name of the database table for which
     *    the map is to be retrieved.
     * @param string $fieldName the map to get.
     */
    public function getCategoryLabelMap($tableName, $fieldName)
    {
        $categoryLabelMap = $this->rows[$tableName][$fieldName];
        return $categoryLabelMap;
    }
}

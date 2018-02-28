<?php

namespace IU\REDCapETL\Schema;

/**
 * Row is used to store field/value information for a row of a relational
 * table
 */
class Row
{
    public $table = '';  // The table that contains this row

    public $data = array();  // Map from field_name => value

    /**
     * Creates a row.
     *
     * @param Table $table the table that holds this row.
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    public function addValue($fieldName, $value)
    {
        $this->data[$fieldName] = $value;
    }

    public function getData()
    {
        return($this->data);
    }

    /**
     * Returns a string representation of thhe row (intended for debugging purposes.
     *
     * @param integer $indent the number of spaces to indent each line.
     */
    public function toString($indent)
    {
        $in = str_repeat(' ', $indent);
        $string = '';
        $string .= "{$in}(";
        $isFirst = true;

        foreach ($this->data as $key => $value) {
            if ($isFirst) {
                $isFirst = false;
            } else {
                $string .= ', ';
            }
            $string .= $key.": ".$value;
        }
        $string .= ")\n";
        return $string;
    }
}

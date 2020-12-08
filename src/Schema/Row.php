<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

/**
 * Row is used to store field/value information for a row of a relational
 * table
 */
class Row
{
    /** @var Table The table that contains this row. */
    public $table = null;

    /** @var array Map from field name to value
        for transferring a row from REDCap to a database */
    public $data = array();

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
    public function toString($indent = 0)
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

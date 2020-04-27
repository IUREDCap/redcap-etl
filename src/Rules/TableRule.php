<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Rules;

use IU\REDCapETL\Schema\RowsType;

class TableRule extends Rule
{
    /** @var string the name of the table as specified in the rule
     * (i.e., not including any table prefix that has been configured).
     **/
    public $tableName;

    public $parentTable;

    public $primaryKey;

    /** @var string the text part of the rule that specified the table rows type(s) */
    public $tableRowsType;

    /** @var array list of rows types defined in the rule for this table */
    public $rowsType;

    /** @var array list of suffixes (if any) defined for this table */
    public $suffixes;
    
    public function __construct($line, $lineNumber)
    {
        $this->suffixes = array();
        $this->rowsType = array();
        parent::__construct($line, $lineNumber);
    }
    
    public function isRootTable()
    {
        $isRoot = false;
        if (is_array($this->rowsType) && count($this->rowsType) === 1) {
            $isRoot = $this->rowsType[0] === RowsType::ROOT;
        }
        return $isRoot;
    }
    
    public function getTableName()
    {
        return $this->tableName;
    }
}

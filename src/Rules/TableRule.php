<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Rules;

use IU\REDCapETL\Schema\RowsType;

class TableRule extends Rule
{
    public $tableName;
    public $parentTable;
    public $primaryKey;
    public $rowsType;
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

<?php
#-------------------------------------------------------
# Copyright (C) 2022 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Rules;

class ExtractFilterRule extends Rule
{
    public $filterLogic;

    public function __construct($line, $lineNumber)
    {
        parent::__construct($line, $lineNumber);
    }
}

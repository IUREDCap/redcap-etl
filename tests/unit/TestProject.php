<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

/**
 * Test project class used for mocking the EtlRedCapProject class
 * for unit testing.
 */
class TestProject extends EtlRedCapProject
{
    private $records;

    private $importGeneratesException = false;

    public function importRecords(
        $records,
        $format = 'php',
        $type = 'flat',
        $overwriteBehavior = 'normal',
        $dateFormat = 'YMD',
        $returnContent = 'count',
        $forceAutoNumber = false,
        $csvDelimiter = ','
    ) {
        # This check added so that there is
        # an easy way to cause this method to
        # generate an exception
        if ($this->importGeneratesException) {
            $message = 'data import error';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }

        if (!isset($this->records)) {
            $record = array();
        }

        foreach ($records as $record) {
            $this->records[$record['record_id']] = $record;
        }
    }

    public function getAllRecords()
    {
        $allRecords = $this->records;
        ksort($allRecords);
        $allRecords = array_values($allRecords);
        return $allRecords;
    }

    public function setImportGeneratesException($generatesException)
    {
        $this->importGeneratesException = $generatesException;
    }
}

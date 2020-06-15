<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

/**
 * Basic Demography project mock class.
 */
class RepeatingEventsProjectMock extends EtlRedCapProjectMock
{
    protected $dataFile  = __DIR__.'/../data/projects/repeating-events.json';
    protected $xmlFile   = __DIR__.'/../data/projects/repeating-events.xml';


    public function __construct(
        $apiUrl,
        $apiToken,
        $sslVerify = false,
        $caCertificateFile = null,
        $errorHandler = null,
        $connection = null
    ) {
        parent::__construct(
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        );
    }
}

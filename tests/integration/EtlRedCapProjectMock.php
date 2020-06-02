<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

/**
 * Mock REDCap Project class for REDCap-ETL that extends ETL's RedCapProject class.
 */
class EtlRedCapProjectMock extends EtlRedCapProject
{
    # To use this class for mocking, create a class that extends this
    # class, and in it set $dataFile to a data file with JSON data for
    # the project you want to mock and set $xmlFile to the file with
    # XML data for the project you want to mock.
    protected $dataFile  = null;
    protected $xmlFile   = null;

    protected $metadata    = null;
    protected $projectInfo = null;
    protected $records     = null;
    protected $fieldNames  = null;

    private $instruments             = null;
    private $instrumentEventMappings = null;
    
    private $projectXml  = null;

    private $redCapVersion;

    public function __construct(
        $apiUrl,
        $apiToken,
        $sslVerify = false,
        $caCertificateFile = null,
        $errorHandler = null,
        $connection = null
    ) {
        /*
        parent::__construct(
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        );
         */

        if (!empty($this->dataFile)) {
            $jsonData = file_get_contents($this->dataFile);
            $data = json_decode($jsonData, true);

            $this->metadata    = $data ["metadata"];
            $this->projectInfo = $data ['projectInfo'];
            $this->fieldNames  = $data ['fieldNames'];

            $this->instruments             = $data['instruments'];
            if (array_key_exists('instrumentEventMappings', $data)) {
                $this->instrumentEventMappings = $data['instrumentEventMappings'];
            }

            $this->redCapVersion = $data['redCapVersion'];

            $this->records = $data['records'];
        }

        if (!empty($xmlFile)) {
            $this->projectXml = file_get_contents($xmlFile);
        }
    }

    public function exportMetadata($format = 'php', $fields = [], $forms = [])
    {
        return $this->metadata;
    }

    public function exportProjectInfo($format = 'php')
    {
        return $this->projectInfo;
    }

    public function exportFieldNames($format = 'php', $field = null)
    {
        return $this->fieldNames;
    }

    public function exportInstruments($format = 'php')
    {
        return $this->instruments;
    }

    public function exportInstrumentEventMappings($format = 'php', $arms = [])
    {
        return $this->instrumentEventMappings;
    }

    public function getRecordIdFieldName()
    {
        $recordIdFieldName = null;
        if (isset($this->metadata)) {
            $recordIdFieldName = $this->metadata[0]['field_name'];
        }
        return $recordIdFieldName;
    }

    public function getRecordIdBatches($batchSize = null, $filterLogic = null, $recordIdFieldName = null)
    {
        $recordIdBatches = array();

        #-----------------------------------
        # Check arguments
        #-----------------------------------
        if (!isset($batchSize) || !is_int($batchSize) || $batchSize < 1) {
            $message = 'Invalid batch size argument: "{$batchsize}" with type "'.gettype($batchSize).'"';
            throw new \Exception($messages);
        }

        $recordIdFieldName = $this->getRecordIdFieldName();

        if (isset($recordIdFieldName) && isset($this->records)) {
            $recordIds = array_column($this->records, $recordIdFieldName);
            $recordIds = array_unique($recordIds);  # Remove duplicate record IDs

            $numberOfRecordIds = count($recordIds);

            $position = 0;
            for ($position = 0; $position < $numberOfRecordIds; $position += $batchSize) {
                $recordIdBatch = array();
                $recordIdBatch = array_slice($recordIds, $position, $batchSize);
                array_push($recordIdBatches, $recordIdBatch);
            }
        }
        return $recordIdBatches;
    }

    public function exportRedcapVersion()
    {
        return $this->redCapVersion;
    }

    public function exportRecordsAp($arrayParameter = [])
    {
        $records = array();
        $recordIdFieldName = $this->getRecordIdFieldName();

        $recordIds = $arrayParameter['recordIds'];

        foreach ($this->records as $record) {
            if (in_array($record[$recordIdFieldName], $recordIds)) {
                array_push($records, $record);
            }
        }
        return $records;
    }
}

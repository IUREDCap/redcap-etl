<?php

namespace IU\REDCapETL;

/**
 * Project data class for retrieving project data from files
 * created with the project_data.php script.
 */
class ProjectData
{
    private $json;
    private $projectInfo;
    private $instruments;
    private $metadata;
    private $instrumentEventMappings;

    private $projectXml;

    private $rulesText;

    public function __construct($jsonFile, $xmlFile, $rulesTextFile)
    {
        $this->json = file_get_contents($jsonFile);
        if ($this->json === false) {
            throw new \Exception('Could not access file "'.$jsonFile.'"');
        }

        $this->projectXml = file_get_Contents($xmlFile);
        if ($this->projectXml === false) {
            throw new \Exception('Could not access file "'.$xmlFile.'"');
        }

        $this->rulesText = file_get_Contents($rulesTextFile);
        if ($this->rulesText === false) {
            throw new \Exception('Could not access file "'.$rulesTextFile.'"');
        }

        $data = json_decode($this->json, true);
        if ($data === null) {
            throw new \Exception('Could not parse JSON data in file "'.$jsonFile.'"');
        }

        if (!array_key_exists('projectInfo', $data)) {
            throw new \Exception('No "projectInfo" element found in file "'.$jsonFile.'"');
        }
        $this->projectInfo = $data['projectInfo'];

        if (!array_key_exists('instruments', $data)) {
            throw new \Exception('No "instruments" element found in file "'.$jsonFile.'"');
        }
        $this->instruments = $data['instruments'];

        if (!array_key_exists('metadata', $data)) {
            throw new \Exception('No "metadata" element found in file "'.$jsonFile.'"');
        }
        $this->metadata    = $data['metadata'];

        if (array_key_exists('instrumentEventMappings', $data)) {
            $this->instrumentEventMappings = $data['instrumentEventMappings'];
        } else {
            $this->instrumentEventMappings = '';
        }

        if (array_key_exists('recordIdFieldName', $data)) {
            $this->recordIdFieldName = $data['recordIdFieldName'];
        } else {
            $this->recordIdFieldName = '';
        }

        if (array_key_exists('fieldNames', $data)) {
            $this->fieldNames = $data['fieldNames'];
        } else {
            $this->fieldNames = '';
        }
    }


    public function getProjectInfo()
    {
        return $this->projectInfo;
    }

    public function getInstruments()
    {
        return $this->instruments;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getProjectXml()
    {
        return $this->projectXml;
    }

    public function getInstrumentEventMappings()
    {
        return $this->instrumentEventMappings;
    }

    public function getRulesText()
    {
        return $this->rulesText;
    }
}

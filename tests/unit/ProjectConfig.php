<?php

namespace IU\REDCapETL;

/**
 * Project data class for retrieving project data from files
 * created with the project_data.php script.
 */
class ProjectConfig
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
    }
}

?>
<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

/**
 * PHPUnit integration tests for the RedCap class.
 */
class RedCapIntegrationTest extends TestCase
{
    private static $config;
    private static $apiUrl;
    private static $superToken;
    private static $basicDemographyApiToken;
    private static $redCap;
    
    public static function setUpBeforeClass(): void
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$apiUrl     = self::$config['api.url'];
        
        if (array_key_exists('super.token', self::$config)) {
            self::$superToken = self::$config['super.token'];
            if (self::$superToken) {
                self::$redCap = new RedCap(self::$config['api.url'], self::$superToken);
            } else {
                self::$superToken = null;
            }
        }
        
        self::$basicDemographyApiToken = self::$config['basic.demography.api.token'];

        self::$redCap = new RedCap(self::$apiUrl, self::$superToken);
    }

    
    public function testCreateRedCap()
    {
        $redCap = self::$redCap = new RedCap(self::$apiUrl, self::$superToken);
        $this->assertNotNull($redCap, 'RedCap not null check.');
    }
    
    public function testCreateRedCapWithErrorHandler()
    {
        $errorHandler = new ErrorHandler();
        $redCap = new RedCap(self::$apiUrl, self::$superToken, null, null, $errorHandler);
        $this->assertNotNull($redCap, 'RedCap not null check.');
    }

    
    public function testCreateRedCapWithConnection()
    {
        $connection = new RedCapApiConnection(self::$apiUrl);
        
        $redCap = new RedCap(self::$apiUrl, self::$superToken, null, null, null, $connection);
        $this->assertNotNull($redCap, 'RedCap not null check.');
        
        # Try to get a project with the new RedCap
        $project = self::$redCap->getProject(self::$basicDemographyApiToken);
        $this->assertNotNull($project, 'Project not null check.');
    }
    
    public function testGetProjectConstructorCallback()
    {
        $constructor = self::$redCap->getProjectConstructorCallback();
        
        $this->assertNotNull($constructor, 'Constructor not null.');
        $this->assertTrue(is_callable($constructor), 'Constructor is callable.');
    }
    
    public function testSetProjectConstructorCallback()
    {
        $constructor = function (
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        ) {
                return 123;
        };

        $redCap = new RedCap(self::$apiUrl);
        $redCap->setProjectConstructorCallback($constructor);
        
        $value = $redCap->getProject('12345678901234567890123456789012');
        $this->assertEquals(123, $value, 'Project value check.');
    }
    
    /**
     * Note: there is no way for this test to delete the projects that
     * it creates.
     */
    public function testCreateProject()
    {
        if (isset(self::$superToken)) {
            $projectTitle = 'PHPCap Created Project Test';
            $purpose = 1;
            $purposeOther = 'PHPCap project creation test';
            $projectNotes = 'This is a test project using php data format.';
        
            $projectData = [
                'project_title' => $projectTitle,
                'purpose' => 1,
                'purpose_other' => $purposeOther,
                'project_notes' => $projectNotes,
                'is_longitudinal' => 1,
                'surveys_enabled' => 1,
                'record_autonumbering_enabled' => 1
            ];
            $project = self::$redCap->createProject($projectData);
        
            $projectInfo = $project->exportProjectInfo();
        
            $this->assertEquals($projectTitle, $projectInfo['project_title'], 'Project title check.');
            $this->assertEquals($purpose, $projectInfo['purpose'], 'Purpose check.');
            $this->assertEquals($purposeOther, $projectInfo['purpose_other'], 'Purpose other check.');
            $this->assertEquals($projectNotes, $projectInfo['project_notes'], 'Project notes check.');
            #$this->assertEquals(1, $projectInfo['is_longitudinal'], 'Is longitudinal check.');
            $this->assertEquals(1, $projectInfo['surveys_enabled'], 'Surveys enabled check.');
            $this->assertEquals(
                1,
                $projectInfo['record_autonumbering_enabled'],
                'Record autonumbering check.'
            );

            #-------------------------------------------------------
            # Test import and export of DAGs (Data Access Groups)
            #-------------------------------------------------------
            $dags = [
                'data_access_group_name'  => 'group1',
                'unique_group_name' => ""
            ];

            $dags = [$dags];
            $project->importDags($dags, $format='php');

            $dagInfo = $project->exportDags();
            $this->assertEquals(1, count($dagInfo), 'DAG count check');

            $dagId = $dagInfo[0]['data_access_group_id'];
            $this->assertNotNull($dagId, 'DAG ID not null check');

            $dagName = $dagInfo[0]['data_access_group_name'];
            $this->assertEquals('group1', $dagName, 'DAG name check');

            #-------------------------------------------------------
            # Test import and export of User Roles
            #-------------------------------------------------------
            $userRoles = [
                [
                    'role_label' => 'Project Manager',
                    'user_rights' => 0
                ]
            ];

            $project->importUserRoles($userRoles);

            $userRoles = $project->exportUserRoles();
            $uniqueRoleName = $userRoles[0]['unique_role_name'];

            $userRoleAssignments = $project->exportUserRoleAssignments();

            $userRoleLabel = $userRoles[0]['role_label'];
            $this->assertEquals('Project Manager', $userRoleLabel, 'User role label check');

            #-----------------------------------------------------------------------------
            # Test file repository methods
            #
            # Tested here because there is no way to delete a folder,
            # so instead of having folders build up in a fixed test project,
            # they will be in created test projects that already need to be
            # deleted manually anyway.
            #-----------------------------------------------------------------------------
            $name           = 'test';
            $parentFolderId = null;
            $dagId          = null;
            $roleId         = null;

            #--------------------
            # Create basic folder
            #--------------------
            $createFolderId = $project->createFileRepositoryFolder($name, $parentFolderId, $dagId, $roleId);
            $this->assertTrue(is_int($createFolderId), 'Int create folder ID check');

            $list = $project->exportFileRepositoryList();
            $this->assertEquals(1, count($list), 'File repository list check');

            $folderId = $list[0]['folder_id'];
            $this->assertNotNull($folderId, 'Folder ID not null check 1');
            $this->assertEquals($createFolderId, $folderId, 'Folder ID march check');

            #--------------
            # Import a file
            #--------------
            $filename = 'import-file.txt';
            $filepath = __DIR__ .'/../data/' . $filename;
            $fileContents = file_get_contents($filepath);
            $result = $project->importFileRepositoryFile($filepath);
            $list = $project->exportFileRepositoryList();
            $this->assertEquals(2, count($list), 'File import list check');

            $fileDocId      = $list[1]['doc_id'];
            $exportFilename = $list[1]['name'];
            $this->assertEquals($filename, $exportFilename, 'Imported file name check');

            #---------------------------------------
            # Export the file that was just imported
            #---------------------------------------
            $fileInfo = array();
            $exportFileContents = $project->exportFileRepositoryFile($fileDocId, $fileInfo);
            $this->assertEquals($fileContents, $exportFileContents, 'File export contents check');
            $this->assertEquals($filename, $fileInfo['name'], 'Export file name check');

            // This check won't work due to a bug in REDCap
            //$this->assertEquals('text/plain', $fileInfo['mime_type'], 'Export file mime-type check');

            #---------------------------------------------
            # Delete the file that was previously imported
            #---------------------------------------------
            $list = $project->exportFileRepositoryList();
            $fileNames = array_column($list, 'name');
            $this->assertTrue(in_array($filename, $fileNames));

            $project->deleteFileRepositoryFile($fileDocId);

            $list = $project->exportFileRepositoryList();
            $fileNames = array_column($list, 'name');
            $this->assertFalse(in_array($filename, $fileNames));

            #----------------------------------
            # Export a file with a unset doc ID
            #----------------------------------
            $docId = null;
            $exceptionCaught = false;
            try {
                $exportFileContents = $project->exportFileRepositoryFile($docId);
            } catch (\Exception $exception) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, 'Unset doc ID exception check');

            #-------------------------------------
            # Export a file with an invalid doc ID
            #-------------------------------------
            $docId = 'invalid';
            $exceptionCaught = false;
            try {
                $exportFileContents = $project->exportFileRepositoryFile($docId);
            } catch (\Exception $exception) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, 'Invalid doc ID exception check');

            #--------------------------------------------
            # Create folder with invalid parent folder ID
            #--------------------------------------------
            $parentFolderId = 'invalid';
            $exceptionCaught = false;
            try {
                $project->createFileRepositoryFolder($name, $parentFolderId, $dagId, $roleId);
            } catch (\Exception $exception) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, 'Invalid parent folder ID exception check');
            $parentFolderId = null;

            #----------------------------------
            # Create folder with invalid DAG ID
            #----------------------------------
            $dagId = 'invalid';
            $exceptionCaught = false;
            try {
                $project->createFileRepositoryFolder($name, $parentFolderId, $dagId, $roleId);
            } catch (\Exception $exception) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, 'Invalid DAG ID exception check');
            $dagId = null;

            #-----------------------------------
            # Create folder with invalid role ID
            #-----------------------------------
            $roleId = 'invalid';
            $exceptionCaught = false;
            try {
                $project->createFileRepositoryFolder($name, $parentFolderId, $dagId, $roleId);
            } catch (\Exception $exception) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, 'Invalid role ID exception check');
            $roleId = null;

            #---------------------------
            # Create folder with role ID
            #---------------------------
            $name   = 'test2';
            $roleId = $uniqueRoleName;
            $roleId = 48;
            $project->createFileRepositoryFolder($name, $parentFolderId, $dagId, $roleId);

            #-----------------
            # Create subfolder
            #-----------------
            $name = 'subfolder';
            $parentFolderId = $folderId;
            $project->createFileRepositoryFolder($name, $parentFolderId, $dagId, $roleId);
            $list = $project->exportFileRepositoryList('php', null);
            # $this->assertEquals(1, count($list), 'File repository list check');
        }
    }
    
   
    public function testCreateProjectWithOdm()
    {
        if (isset(self::$superToken)) {
            $projectTitle = 'PHPCap Created Project with ODM Test';
            $purpose = 1;
            $purposeOther = 'PHPCap project creation with ODM test';
            $projectData = '[{'
                .'"project_title": "'.$projectTitle.'",'
                .'"purpose": "1",'
                .'"purpose_other": "'.$purposeOther.'"'
                .'}]';
            $odmFile = __DIR__.'/../projects/PHPCapBasicDemography.REDCap.xml';
            $projectOdmData = FileUtil::fileToString($odmFile);
            
            $project = self::$redCap->createProject($projectData, 'json', $projectOdmData);
            $this->assertNotNull($project, 'Project not null check.');
            
            $projectInfo = $project->exportProjectInfo();
            $this->assertEquals($projectTitle, $projectInfo['project_title'], 'Project title check.');
        }
    }
    
    
    public function testCreateProjectWithNullProjectData()
    {
        $exceptionCaught = false;
        try {
            $projectData = null;
            $project = self::$redCap->createProject($projectData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode());
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
    
    
    public function testCreateProjectWithInvalidPhpProjectData()
    {
        $exceptionCaught = false;
        try {
            # project data should be an array for 'php' format, but it's a string
            $projectData = '[{"project_title": "Test project.", "purpose": "0"}]';
            $project = self::$redCap->createProject($projectData, $format = 'php');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode());
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
    
    public function testCreateProjectWithInvalidJsonProjectData()
    {
        $exceptionCaught = false;
        try {
            # project data should be a string for 'json' format, but it's an array
            $projectData = ["project_title" => "Test project.", "purpose" => "0"];
            $project = self::$redCap->createProject($projectData, $format = 'json');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode());
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
    
    public function testCreateProjectWithPhpToJsonError()
    {
        SystemFunctions::setJsonError();
        
        $exceptionCaught = false;
        try {
            $projectData = ["project_title" => "Test project.", "purpose" => "0"];
            $project = self::$redCap->createProject($projectData, $format = 'php');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::JSON_ERROR, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::clearJsonError();
    }
    
    public function testCreateProjectWithInvalidProjectDataFieldName()
    {
        if (isset(self::$superToken)) {
            $exceptionCaught = false;
            try {
                // project_caption below should be project_title
                $projectData = [
                    "project_caption" => "Test project.",
                    "purpose" => "0"
                ];
                $project = self::$redCap->createProject($projectData, $format = 'php');
            } catch (PhpCapException $exception) {
                $exceptionCaught = true;
                $code = $exception->getCode();
                $this->assertEquals(ErrorHandlerInterface::REDCAP_API_ERROR, $code, 'Exception code check.');
            }
            $this->assertTrue($exceptionCaught, 'Exception caught.');
        }
    }
    
    public function testCreateProjectWithInvalidFormatType()
    {
        $exceptionCaught = false;
        try {
            $projectData = [
                    'project_title' => 'PHPCap create project error',
                    'purpose' => 0
            ];
            $projectData = null;
            $project = self::$redCap->createProject($projectData, $format =  123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode());
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
    
    
    public function testCreateProjectWithInvalidFormatValue()
    {
        $exceptionCaught = false;
        try {
            $projectData = [
                    'project_title' => 'PHPCap create project error',
                    'purpose' => 0
            ];
            $projectData = null;
            $project = self::$redCap->createProject($projectData, $format = 'invalid');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode());
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
    
    
    public function testGetProject()
    {
        $project = self::$redCap->getProject(self::$basicDemographyApiToken);
        
        $this->assertNotNull($project, 'Project not null check.');
        
        $apiToken = $project->getApiToken();
        
        $this->assertEquals(self::$basicDemographyApiToken, $apiToken, 'API token check.');
    }
    
    
    public function testGetProjectWithNullApiToken()
    {
        $exceptionCaught = false;
        try {
            $project = self::$redCap->getProject(null);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
    
    public function testGetProjectWithApiTokenWithInvalidType()
    {
        $exceptionCaught = false;
        try {
            $project = self::$redCap->getProject(123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
    
    
    public function testGetProjectWithNonHexidecimalApiToken()
    {
        $exceptionCaught = false;
        try {
            $project = self::$redCap->getProject('ABCDEFG8901234567890123456789012');  // 'G' is invalid
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
    
    
    public function testGetProjectWithApiTokenWithInvalidLength()
    {
        $exceptionCaught = false;
        try {
            $project = self::$redCap->getProject('123456789012345678901234567890123');  // length = 33
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }

    public function testImportRepeatingIntrumentsAndEventsWithSuperToken()
    {
        if (isset(self::$superToken)) {
            #create a new project with no a repeatable forms
            $projectTitle = 'PHPCap Created Project for Repeating Forms Test';
            $purpose = 1;
            $purposeOther = 'PHPCap project creation for testing Import Repeating Instruments and Events';
            $projectData = '[{'
               .'"project_title": "'.$projectTitle.'",'
               .'"purpose": "1",'
               .'"purpose_other": "'.$purposeOther.'"'
               .'}]';
            $odmFile = __DIR__.'/../projects/PHPCapRepeatingForms.REDCap.xml';
            $projectOdmData = FileUtil::fileToString($odmFile);
            
            $project = self::$redCap->createProject($projectData, 'json', $projectOdmData);

            #use the Import Repeating Instruments and Events to make the
            #weight form repeatable
            $records = FileUtil::fileToString(__DIR__.'/../data/repeatable-survey-forms-events.csv');
            $count = $project->importRepeatingInstrumentsAndEvents(
                $records,
                $format = 'csv'
            );
            $this->assertEquals(
                1,
                $count,
                'Integration test, Import Repeating Instruments and Events record count.'
            );
        
            $result = $project->exportRepeatingInstrumentsAndEvents($format = 'csv');
            $expected = 'form_name,custom_form_label';
            $expected .= chr(10) . 'weight,[weight_date] [weight_time]' . chr(10);
            $this->assertEquals(
                $expected,
                $result,
                'Integration test, Import Repeating Instruments and Events form now repeatable check.'
            );
        }
    }
}

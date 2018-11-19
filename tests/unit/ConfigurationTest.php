<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Logger class.
 */
class ConfigurationTest extends TestCase
{
    public function setUp()
    {
    }

    public function testConfig()
    {
        $propertiesFile = __DIR__.'/../data/config-testconfiguration.ini';
        $logger = new Logger('test-app');

        $config = new Configuration($logger, $propertiesFile);
        $this->assertNotNull($config, 'logger not null check');

        $expectedDataSourceApiToken = '1111111122222222333333334444444';
        $dataSourceApiToken = $config->getDataSourceApiToken();
        $this->assertEquals($expectedDataSourceApiToken, $dataSourceApiToken, 'DataSourceApiToken check');

        $expectedTransformRulesSource = '3';
        $transformRulesSource = $config->getTransformRulesSource();
        $this->assertEquals($expectedTransformRulesSource, $transformRulesSource, 'TransformRulesSource check');

        $expectedBatchSize = '10';
        $batchSize = $config->getBatchSize();
        $this->assertEquals($expectedBatchSize, $batchSize, 'BatchSize check');

        $expectedCaCertFile = 'test_cacert_file_path';
        $caCertFile = $config->getCaCertFile();
        $this->assertEquals($expectedCaCertFile, $caCertFile,
                            'CaCertFile check');

        $expectedCalcFieldIgnorePattern = '/^0$/';
        $calcFieldIgnorePattern = $config->getCalcFieldIgnorePattern();
        $this->assertEquals($expectedCalcFieldIgnorePattern,
                            $calcFieldIgnorePattern,
                            'CalcFieldIgnorePattern check');

        $expectedEmailFromAddress = 'foo@bar.com';
        $emailFromAddress = $config->getEmailFromAddress();
        $this->assertEquals($expectedEmailFromAddress, $emailFromAddress,
                            'EmailFromAddress check');

        $expectedEmailSubject = 'email subject';
        $emailSubject = $config->getEmailSubject();
        $this->assertEquals($expectedEmailSubject, $emailSubject,
                            'EmailSubject check');

        $expectedEmailToList = 'bang@bucks.net,what@my.com';
        $emailToList = $config->getEmailToList();
        $this->assertEquals($expectedEmailToList, $emailToList,
                            'EmailToList check');

        $expectedExtractedRecordCountCheck = false;
        $extractedRecordCountCheck = $config->getExtractedRecordCountCheck();
        $this->assertEquals($expectedExtractedRecordCountCheck,
                            $extractedRecordCountCheck,
                            'ExtractedRecordCountCheck check');

        $expectedFieldType = Schema\FieldType::VARCHAR;
        $expectedFieldSize = 123;

        $generatedInstanceType = $config->getGeneratedInstanceType();
        $this->assertEquals($expectedFieldType,
                            $generatedInstanceType->getType(),
                            'GeneratedInstanceType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedInstanceType->getSize(),
                            'GeneratedInstanceType size check');

        $generatedKeyType = $config->getGeneratedKeyType();
        $this->assertEquals($expectedFieldType,
                            $generatedKeyType->getType(),
                            'GeneratedKeyType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedKeyType->getSize(),
                            'GeneratedKeyType size check');

        $generatedLabelType = $config->getGeneratedLabelType();
        $this->assertEquals($expectedFieldType,
                            $generatedLabelType->getType(),
                            'GeneratedLabelType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedLabelType->getSize(),
                            'GeneratedLabelType size check');

        $generatedNameType = $config->getGeneratedNameType();
        $this->assertEquals($expectedFieldType,
                            $generatedNameType->getType(),
                            'GeneratedNameType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedNameType->getSize(),
                            'GeneratedNameType size check');

        $generatedRecordIdType = $config->getGeneratedRecordIdType();
        $this->assertEquals($expectedFieldType,
                            $generatedRecordIdType->getType(),
                            'GeneratedRecordIdType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedRecordIdType->getSize(),
                            'GeneratedRecordIdType size check');

        $generatedSuffixType = $config->getGeneratedSuffixType();
        $this->assertEquals($expectedFieldType,
                            $generatedSuffixType->getType(),
                            'GeneratedSuffixType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedSuffixType->getSize(),
                            'GeneratedSuffixType size check');

        $expectedCreateLookupTable = true;
        $createLookupTable = $config->getCreateLookupTable();
        $this->assertEquals($expectedCreateLookupTable,
                            $createLookupTable, 'CreateLookupTable check');

        $expectedTimeLimit= 3600;
        $timeLimit = $config->getTimeLimit();
        $this->assertEquals($expectedTimeLimit, $timeLimit, 'Time limit check');
        $expectedTimezone = 'America/Indiana/Indianapolis';
        $timezone = $config->getTimezone();
        $this->assertEquals($expectedTimezone, $timezone, 'Timezone check');
    }

    public function testNullPropertiesFile()
    {
        $propertiesFile = null;
        $logger = new Logger('test-app');

        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught');

        $expectedCode = EtlException::INPUT_ERROR;
        $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
    }

    public function testNonExistentPropertiesFile()
    {
        $propertiesFile = __DIR__.'/../data/non-existent-config-file.ini';
        $logger = new Logger('test-app');

        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught');

        $expectedCode = EtlException::INPUT_ERROR;
        $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
    }
}

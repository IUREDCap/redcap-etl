#!/usr/bin/env php
<?php

#-------------------------------------------------------------
# This is a test script for running REDCap-ETL that
# outputs time statistics for the process.
#
# Options:
#    -c <configuration-file> [-b <batch-size>]
#
#-------------------------------------------------------------

$startTime = microtime(true);

require(__DIR__.'/../dependencies/autoload.php');

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Version;
use IU\REDCapETL\Logger;

$app = basename(__FILE__, '.php');
$logger = new Logger($app);

#---------------------------
# Set default values
#---------------------------
$configFile = null;
$batchSize  = null;

#----------------------------------------------
# Process the command line options
#----------------------------------------------
$options = getopt('c:b:');

if (array_key_exists('c', $options)) {
    # configuration file
    $configFile = $options['c'];
} else {
    print "Usage: test.php -c <configuration_file> [-b <batch-size>]\n";
    exit(1);
}

if (array_key_exists('b', $options)) {
    $batchSize = $options['b'];
}


try {
    $currentDateTime = date('Y-m-d H:i:s');

    $redCapEtl = new RedCapEtl($logger, $configFile);
    if (isset($batchSize)) {
        $tasks = $redCapEtl->getTasks();
        $redcapVersion = ($tasks[0])->getDataProject()->exportRedCapVersion();

        $metadata    = ($tasks[0])->getDataProject()->exportMetadata();
        $projectInfo = ($tasks[0])->getDataProject()->exportProjectInfo();
        $projectTitle = $projectInfo['project_title'];

        $redcapEtlVersion = Version::RELEASE_NUMBER;
        $phpVersion = PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION.".".PHP_RELEASE_VERSION;
        foreach ($tasks as $task) {
            $task->getTaskConfig()->setBatchSize($batchSize);
        }
    }

    $count = $redCapEtl->run();

    $workflow = $redCapEtl->getWorkflow();

    $preProcessingTime  = $workflow->getPreProcessingTime();
    $extractTime        = $workflow->getExtractTime();
    $transformTime      = $workflow->getTransformTime();
    $loadTime           = $workflow->getLoadTime();
    $postProcessingTime = $workflow->getPostProcessingTime();
    $overheadTime       = $workflow->getOverheadTime();

    $totalTime     = $workflow->getTotalTime();
} catch (EtlException $exception) {
    $logger->logException($exception);
    $logger->log('Processing failed.');
    exit(1);
}

$endTime = microtime(true);

#$batchSize = $redCapEtl->getConfiguration()->getBatchSize();
$time = $endTime - $startTime;
$memoryUsed = memory_get_peak_usage();
print "{$currentDateTime},{$configFile},{$count},{$batchSize},"
    ."{$redcapVersion},{$redcapEtlVersion},"
    ."{$memoryUsed},{$totalTime},"
    ."{$preProcessingTime},{$extractTime},{$transformTime},{$loadTime},{$postProcessingTime},{$overheadTime}\n";

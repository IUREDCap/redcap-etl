#!/usr/bin/env php
<?php

#-------------------------------------------------------------
# Test script for REDCap-ETL logging to e-mail.
#-------------------------------------------------------------
require(__DIR__.'/../dependencies/autoload.php');

use IU\REDCapETL\Logger;

$usage = 'Usage: email_test.php <from-email> <to-email> [<subject>]';

$fromEmal = '';
$toEmail  = '';
$subject  = 'REDCap-ETL e-mail test';

#--------------------------------------
# Process the command line arguments
#--------------------------------------
switch (count($argv)) {
    case 4:
        $subject   = $argv[3];
        # Continue processing the from and to e-mail addresses:
    case 3:
        $fromEmail = $argv[1];
        $toEmail   = $argv[2];
        break;
    default:
        print "{$usage}\n";
        exit(1);
}

$logger = new Logger('e-mail-test');

$logger->setLogEmail($fromEmail, $toEmail, $subject);

$logger->logToEmail('This is a test of REDCap-ETL logging to e-mail.');

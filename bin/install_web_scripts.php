#!/usr/bin/env php
<?php

#-------------------------------------------------------------
# Installs web scripts for REDCap ETL
#
# Options:
#    -i <install-dir> -w <web-directory>
#-------------------------------------------------------------

$usage = 'usage: php install_web_scripts.php -w <web_directory>';

$replace = 'REPLACE_INSTALL_DIR';

$options = getopt('w:');

if (!isset($options['w'])) {
    print "{$usage}\n";
    exit(1);
} else {
    $webDir = trim($options['w']);
    print "webDir: $webDir\n";
    if (!is_writeable($webDir)) {
       print "Web directory $webDir is not writeable\n"; 
       exit(1);
    }
}

$installDir = __DIR__ . '/../';

$autoload = $installDir. '/dependencies/autoload.php';

$webScriptFile = $installDir . '/src/redcap_det_handler.php';

$configFilesPattern = __DIR__ . '/../config/*.ini';

# replicate directory structure under config, so that users
# can control where on the web server the files go???
# or - they could store to a staging directory and then
# create a custom script to install to actual web directory

$configFiles = glob($configFilesPattern);


$configDir = new RecursiveDirectoryIterator(__DIR__ . '/../config/');
$iterator = new RecursiveIteratorIterator($configDir);
$regex = new RegexIterator($iterator, '/^.+\.ini$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($regex as $iniFile => $object) {

    print ".ini file: $iniFile \n";

    $values = parse_ini_file($iniFile);
    if (array_key_exists('web_script', $values)) {
        $webScript = $values['web_script'];
        if ($webScript !== '') {
            print "Web script: $webScript \n";
        }
    }

    if (isset($webScript) && trim($webScript) !== '') {
        $webFile = $webDir . '/' . $webScript;

        $file_contents = file_get_contents($webScriptFile);
        $file_contents = str_replace($replace, $installDir, $file_contents);
        $file_contents = str_replace('propertiesFile = null;', 'propertiesFile = '.$iniFile, $file_contents);
        file_put_contents($webFile, $file_contents);
    }

}

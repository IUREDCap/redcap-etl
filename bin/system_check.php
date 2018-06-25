#!/usr/bin/env php
<?php

$minVersion = '5.6.0';

print "PHP version ".PHP_VERSION." ";

if (version_compare(PHP_VERSION, $minVersion) < 0) {
    print "- ERROR: you need to be running at least PHP version ".$minVersion;
} else {
    print "- OK";
}
print "\n";


if (in_array('curl', get_loaded_extensions())) {
    print "curl enabled\n";
} else {
    print "curl disabled - ERROR: curl needs to be enabled in PHP\n";
}

if (in_array('dom', get_loaded_extensions())) {
    print "dom enabled\n";
} else {
    print "dom disabled - ERROR: curl needs to be enabled in PHP\n";
}

if (in_array('openssl', get_loaded_extensions())) {
    print "openssl enabled\n";
} else {
    print "openssl disabled - ERROR: openssl needs to be enabled in PHP\n";
}

if (function_exists('mail')) {
    print "mail function exists\n";
}

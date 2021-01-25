#!/usr/bin/env php
<?php

#===============================================================================
# Script for get statistics for a CSV load database for REDCap-ETL.
#===============================================================================

# Default values
$labelViewSuffix   = '_label_view';
$recordIdFieldName = 'record_id';
$verboseMode       = false;

# Map from record ID to size of data
$recordIdToDataSizeMap = array();

# Map from record ID to number of database rows
$recordIdToDbRows = array();

$tableToNumberOfColumns = array();
$tableToNumberOfRows   = array();

if (count($argv) < 2) {
    print "Usage: php $argv[0] [-r <record-id-field-name] [-l <label-view-suffix>] [-v] <csv-dir>\n";
    exit(1);
} else {
    $csvDir = array_pop($argv);
    $options = getopt('r:l:v');

    if (array_key_exists('r', $options)) {
        $recordIdFieldName = $options['r'];
    }

    if (array_key_exists('l', $options)) {
        $labelViewSuffix = $options['l'];
    }

    if (array_key_exists('v', $options)) {
        $verboseMode = true;
    }
}

#------------------------------------------------
# Get the CSV data files (files that are not
# label views, log files, or metadata files).
#------------------------------------------------
$dataFiles = array();
$files = glob($csvDir.'/*.csv');

foreach ($files as $file) {
    if (!preg_match('/'.$labelViewSuffix.'.csv/', $file)
        && !preg_match('/etl_event_log.csv/', $file)
        && !preg_match('/etl_log.csv/', $file)
        && !preg_match('/redcap_metadata.csv/', $file)
        && !preg_match('/redcap_project_info.csv/', $file)
    ) {
        $dataFiles[] = $file;
    }
}

foreach ($dataFiles as $dataFile) {
    $fh = fopen($dataFile, 'r');
    $header = fgetcsv($fh);
    $recordIdKey = array_search($recordIdFieldName, $header);

    $tableName = basename($dataFile, '.csv');
    $tableToNumberOfColumns[$tableName] = count($header);
    $tableToNumberOfRows[$tableName]   = 0;

    while ($row = fgetcsv($fh)) {
        $tableToNumberOfRows[$tableName] += 1;
        $recordId = $row[$recordIdKey];
        $recordSize = 0;
        foreach ($row as $element) {
            $recordSize += strlen($element);
        }

        if (!array_key_exists($recordId, $recordIdToDataSizeMap)) {
            $recordIdToDataSizeMap[$recordId] = 0;
        }
        $recordIdToDataSizeMap[$recordId] += $recordSize;

        if (!array_key_exists($recordId, $recordIdToDbRows)) {
            $recordIdToDbRows[$recordId] = 0;
        }
        $recordIdToDbRows[$recordId] += 1;
    }
    fclose($fh);
}

if ($verboseMode) {
    foreach ($recordIdToDataSizeMap as $recordId => $dataSize) {
        print "Record ID {$recordId}: {$dataSize} bytes, {$recordIdToDbRows[$recordId]} database rows\n";
    }
}

$totalRows = 0;
$totalData = 0;
foreach ($recordIdToDataSizeMap as $recordId => $size) {
    $totalData += $size;
    $totalRows += $recordIdToDbRows[$recordId];
}

print "\n";
print "Number of data tables: ".count($dataFiles)."\n";

print "Table names: ";
$isFirst = true;
foreach ($dataFiles as $table) {
    if ($isFirst) {
        $isFirst = false;
    }
    else {
        print ", ";
    }
    print basename($table, '.csv');
}
print "\n";

$totalDataFields = 0;
foreach ($tableToNumberOfColumns as $table => $numberOfColumns) {
    $rows = $tableToNumberOfRows[$table];
    $numberOfDataFields = $rows * $numberOfColumns;
    print "Table {$table}: ".number_format($rows)." rows, {$numberOfColumns} columns, "
        .number_format($numberOfDataFields)." data fields\n";
    $totalDataFields += $numberOfDataFields;
}
print "\n";
print "Total table rows: ".number_format($totalRows)."\n";
print "Total table data fields: ".number_format($totalDataFields)."\n";

print "\n";
print "Number of record IDs: ".count($recordIdToDataSizeMap)."\n";
print "Total bytes data loaded: ".number_format($totalData)."\n";
print "Average bytes data loaded per record ID: ".number_format($totalData / count($recordIdToDataSizeMap), 2)."\n";
print "Average table data fields per record ID: ".number_format($totalDataFields / count($recordIdToDataSizeMap), 2)."\n";
print "Average database rows per record ID: ".number_format($totalRows / count($recordIdToDataSizeMap), 2)."\n";
print "\n";


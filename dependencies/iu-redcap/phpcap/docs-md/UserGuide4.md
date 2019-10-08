<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

User Guide 4 - Importing Data
=============================================

PHPCap's __RedCapProject__ class provides the following methods for importing data:
1. __importRecords__ - method for importing records. 
2. __importFile__ - method for importing a file (e.g., a document or image) into an existing REDCap record.


importRecords
---------------------------
Detailed documentation for the importRecords method can be found in
the PHPCap API documentation:
https://iuredcap.github.io/phpcap/api/class-IU.PHPCap.RedCapProject.html

Since this method corresponds very closely to the REDCap API Import Records method, the
REDCap API documentation can also be checked for more information. And the REDCap
API Playground can be used to get a sense of the functionality provided by this method.

Here is example PHPCap code that uses the importRecords method to import records from a CSV (Comma-Separated Value) file:
```php
<?php
...
// import records from a CSV file
try {
    $records = FileUtil::fileToString('data.csv');
    $number = $project->importRecords($records, 'csv');
    print "{$number} records were imported.\n";
} catch (Exception $exception) {
    print "*** Import Error: ".$exception->getMessage()."\n";
}
```

The importRecords method imports CSV records from a string, so if your data is stored
in a file, you need to read the file into a string first, as is done above.
The importRecords method returns the number of records that were imported.
It is also possible to specify that the record IDs of the records that were
imported be returned instead. See the PHPCap API documentation for more details.


### Batch processing of CSV imports

PHPCap currently provides no direct support for batch imports.
To import a large CSV file in batches, you need to either:
* break up the file into multiple files, and import each one separately
* read the file into a string one batch of records at a time, and import the string after
  each read

One thing you need to be careful about is that each batch of rows needs to have
the header row with column names as the first row. If you simply read a large CSV files
100 rows at a time, the first import would succeed, because it would have the header row,
but the subsequent imports would fail.


importFile
---------------------------
The importFile method is used for importing a file, such as a consent form for a patient,
into an existing REDCap record.

Detailed documentation for the importRecords method can be found in
the PHPCap API documentation:
https://iuredcap.github.io/phpcap/api/class-IU.PHPCap.RedCapProject.html 

Below is example code for importing a consent form file for a patient into the patient's record:
```php
project->importFile('consent1001.pdf','1001','patient_consent');
```
Or, using variables to indicate what the arguments represent:
```php
$file = 'consent1001.pdf';
$recordId = '1001';
$field = 'patient_consent';
project->importFile($file, $recordId, $field);
```
Both of the above examples are importing the file "consent1001.pdf" into field "patient_consent" in the record with an ID of 1001.

A similar example for a longitudinal study is as follows:
```php
$file = 'consent.pdf';
$recordId = '1001';
$field = 'patient_consent';
$event = 'enrollment_arm_1';
project->importFile($file, $recordId, $field, $event); 
```
In this case, the event needs to be specified also.

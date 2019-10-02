<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

User Guide 3 - Exporting Records
=============================================

PHPCap's __RedCapProject__ class provides the following 3 methods for exporting records:
1. __exportRecords__ - standard method for exporting records. 
2. __exportRecordsAp__ - "array parameter" method for exporting records.
3. __exportReports__ - method that exports the records produced by a report that
                       is defined for the project in REDCap.

__Batch Processing__. The methods above return all of their records at once, but
the method __getRecordIdBatches__ can be used with the first 2 methods above
to export records in batches. This will cut down on the memory requirements of the export, which
can be useful for exporting the records from very large projects.

exportRecords
---------------------------
The exportRecords method is a standard PHP method that has 12 parameters that can
be set to modify the records that are exported and their format.

The complete documentation for this method can be found in the PHPCap API documentation:
https://iuredcap.github.io/phpcap/api/class-IU.PHPCap.RedCapProject.html

Since this method corresponds very closely to the REDCap API Export Records method, the
REDCap API documentation can also be checked for more information. And the REDCap
API Playground can be used to get a sense of the functionality provided by this method.

All of the parameters for exportRecords have default values assigned. So the following example
can be used to export all records from a project with default formats:
```php
$records = $project->exportRecords();
```
Other examples:
```php
// export all records in CSV format
$records = $project->exportRecords('csv');

// export records with IDs 1001 and 1002 in XML format
// with one record per XML item ('flat')
$records = $project->exportRecords('xml', 'flat', ['1001', '1002']);

// export only the 'age' and 'bmi' fields for all records in
// CSV format (note that null can be used for arguments
// where you want to use the default value)
$records = $project->exportRecords('csv', null, null, ['age', 'bmi']);
```

exportRecordsAp
---------------------------
The exportRecordsAp method supports the same functionality as the exportRecords method,
but with different parameters. The exportRecordsAp method has a single array parameter
where the keys of the array correspond the the parameter names in the exportRecords
method definition, and the value for each key is the argument value. For example, the
following exportRecordsAp method call would export the records from
the project in XML format for events 'enrollment_arm_1' and 'enrollment_arm_2'.
```php
$records = $project->exportRecordsAp(
    ['format' => 'xml', 'events' => ['enrollment_arm_1', 'enrollment_arm_2']]
);
```

As compared with the exportRecords method, exportRecordsAp lets you specify values
only for the parameters where you want non-default values, and you can
specify them in any order.

For example, if you wanted to export the records from your project in CSV format
with data access group information included, you would use something like the following
with the exportRecords method:
```php
$records = $project->exportRecords('csv', null, null, null, null, null,
    null, null, null, null, null, true);
```
In this case, the order of the arguments has to match exactly with the
order of the parameters in the method definition. And since an argument
for the the last parameter ($exportDataAccessGroups) is being provided, arguments for all
parameters before it need to be included.

The same export could be specified with the exportRecordsAp method as follows:
```php 
$records = $project->exportRecordsAp(['format' => 'csv', 'exportDataAccessGroups' => true]);
```
In this case, only the arguments with non-default values need to be specified. And, the order
doesn't matter, so the above export could also be specified as:
```php
$records = $project->exportRecordsAp(['exportDataAccessGroups' => true, 'format' => 'csv']);
```

exportReports
----------------------------
To use the exportReports method, you first need to define one or more reports in REDCap
for the project you are using.

For example, if you had previously defined a report in REDCap that had an ID of 18999,
you could export the records for that report in CSV format using the following:
```php
$records = $project->exportReports('18999', 'csv');
```

getRecordIdBatches
---------------------------
The getRecordIdBatches method retrieves batches of record IDs from a project that can then
be used as input to the exportRecords and exportRecordsAp methods to export records in batches,
for example:
```php
...
# Get all the record IDs of the project using a batch size of 10
$recordIdBatches = $project->getRecordIdBatches(10);
foreach ($recordIdBatches as $recordIdBatch) {
    $records = $project->exportRecordsAp(['recordIds' => $recordIdBatch]);
    ...
}
...
```
the getRecordIdBatches method returns an array of arrays of record IDs. So the call shown
above might return, for example:
```php
[
    [1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010],
    [1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020],
    [1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030],
    [1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040],
    [1041, 1042, 1043, 1044, 1045, 1046, 1047, 1048, 1049, 1050],
    [1051, 1052, 1053, 1054, 1055, 1056, 1057, 1058, 1059, 1060],
    [1061, 1062, 1063, 1064, 1065, 1066, 1067, 1068, 1069, 1070],
    [1071, 1072, 1073, 1074, 1075, 1076, 1077, 1078, 1079, 1080],
    [1081, 1082, 1083, 1084, 1085, 1086, 1087, 1088, 1089, 1090],
    [1091, 1092, 1093, 1094, 1095, 1096, 1097, 1098, 1099, 1100]
]
```

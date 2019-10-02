<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

PHPCap
==========================================================================

PHPCap is a PHP API (Application Programming Interface) for REDCap, that lets you:
* export/import/delete data in REDCap
* export/import/delete project information (e.g., field names and types) in REDCap

PHPCap makes accessing REDCap from a PHP program easier by providing:
* a high-level interface
* improved error checking

REDCap is a web application for building and managing online surveys and databases. For information about REDCap, please see http://www.project-redcap.org.

Developers: [Jim Mullen](https://github.com/mullen2); [Andy Arenson](https://github.com/aarenson), aarenson@iu.edu

[![Packagist](https://img.shields.io/github/v/release/iuredcap/PHPCap.svg)](https://packagist.org/packages/iu-redcap/phpcap)
[![PHP 5.6+](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![BSD 3-Clause](https://img.shields.io/github/license/iuredcap/PHPCap.svg)](https://opensource.org/licenses/BSD-3-Clause)



Requirements
--------------------------
To use PHPCap, you need to have:
* A computer with PHP 5.6 or later installed, and PHP needs to have cURL and OpenSSL enabled.
* An account on a REDCap site.
* API token(s) for the project(s) you want to access. API tokens need to be requested within the REDCap system.


Example
--------------------------

```php
<?php
require_once('PHPCap/autoloader.php');

use IU\PHPCap\RedCapProject;

$apiUrl = 'https://redcap.someplace.edu/api/';
$apiToken  = '273424CC67263B849E41CCD2134F37C3';

$project = new RedCapProject($apiUrl, $apiToken);

# Print the project title
$projectInfo = $project->exportProjectInfo();
print "project title: ".$projectInfo['project_title']."\n";

# Print the first and last names for all records
$records = $project->exportRecords();
foreach ($records as $record) {
    print $record['first_name']." ".$record['last_name']."\n";
}
?>
```


Documentation
----------------------------
For more information, see the PHPCap documentation:
[https://iuredcap.github.io/phpcap](https://iuredcap.github.io/phpcap/)




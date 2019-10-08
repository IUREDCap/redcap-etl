<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

User Guide 2 - API Overview
=============================================

The four main classes provided by PHPCap for users are:

<table>
<thead>
  <tr>
    <th>Class</th><th>Description</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>
      <a href="https://iuredcap.github.io/phpcap/api/class-IU.PHPCap.RedCapProject.html">RedCapProject</a>
    </td>
    <td>
      Used to retrieve data from, and modify, a project in REDCap.
    </td>
  </tr>
  <tr>
    <td>
      <a href="https://iuredcap.github.io/phpcap/api/class-IU.PHPCap.RedCap.html">RedCap</a> 
    </td>
    <td>
      Represents a REDCap instance/site. This class is only required for creating new REDCap
      projects using PHPCap, but it also may be helpful if your program needs
      to access multiple projects, especially if you are doing a lot of customization
      of PHPCap.
    </td>
  </tr>  
  <tr>
    <td>
      <a href="https://iuredcap.github.io/phpcap/api/class-IU.PHPCap.FileUtil.html">FileUtil</a> 
    </td>
    <td>
      Used to read from, and write to, files. FileUtil is
      set up to throw a PhpCapException if an error occurs, so it can
      make error handling more consistent and easier.
    </td>
  </tr>
  <tr>
    <td>
      <a href="https://iuredcap.github.io/phpcap/api/class-IU.PHPCap.PhpCapException.html">PhpCapException</a>
    </td>
    <td>
      Exception class used by PHPCap when an error occurs. 
    </td>
  </tr>
</tbody>
</table>
 
 Here is a complete example that uses three of these classes to export the
 records in a REDCap project to a file in CSV (Comma-Separated Values) format:
 ```php
 <?php 

# use only one of the following and
# change the path to the file as needed
require('vendor/autoload.php');   # For Composer
require('PHPCap/autoloader.php'); # For Git 

use IU\PHPCap\RedCapProject;
use IU\PHPCap\FileUtil;
use IU\PHPCap\PhpCapException;

$apiUrl = 'https://redcap.xxxxx.edu/api/';  # replace this URL with your institution's
                                            # REDCap API URL.

$apiToken = '11111111112222222222333333333344';  # replace with your actual API token

$sslVerify = true;

# set the file path and name to the location of your
# CA certificate file
$caCertificateFile = 'USERTrustRSACertificationAuthority.crt';

try {
    $project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
    
    # Export the records of the project in CSV format
    # and store then in file 'data.csv'
    $records = $project->exportRecords('csv');
    FileUtil::writeStringToFile($records, 'data.csv');
    
} catch (PhpCapException $exception) {
    print $exception->getMessage();
}
?>
 ```
 __Notes:__
 
 The require statement includes the PHPCap autoloader which loads the PHPCap classes
 that are actually used, so there is no need to require or include the individual
 PHPCap classes. If you used Composer to download PHPCap, you should use its autoloader (in the
 vendor directory) in the require statement, instead of the one contained in the PHPCap project.
 
 The use statements allow you to refer to the PHPCap classes without having to specify
 their fully qualified names. For example, if you did not have a use statement for
 the FileUtil class, you would need to use:
 ```php
 IU\PHPCap\FileUtil::writeStringToFile($records, 'data.csv');
 ```
 
 Setting $sslVerify to true and specifying a CA certificate file are very important
 for security reasons. These settings enable PHPCap to verify that the REDCap site
 accessed is actually the one specified in the $apiUrl. If this verification is not
 done, it is possible that another site could impersonate your REDCap site and
 read the data you send and receive. 
 For information on how to create a CA certificate file, see [CA Certificate File](CACertificateFile.md)
 
 For writing the file, you could use PHP's file_put_contents function,
 but an advantage of using PHPCap's FileUtil method is that FileUtil is
 set up to throw a PhpCapException if an error occurs, so it can
 make error handling more consistent and easier.
 
 

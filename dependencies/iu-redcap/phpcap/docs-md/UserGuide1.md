<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

User Guide 1 - Getting Started
====================================

Prerequisites
----------------------
* __REDCap 7.0+.__ You need to have an account for a REDCap version 7.0 or greater site.
The version of your REDCap site should be listed on the bottom of the page after you log in.
* __Your REDCap API URL.__ You need to know the URL of your REDCap's API (Application Programming Interface). You can see this from within REDCap by using the following steps:
    1. Select a project.
    2. Click on the __API__ link on the left.
    3. On the API page, click on the __REDCap API documentation__ link
       toward the the top of the page.
    4. Select one of the methods in the left, such as __Export Arms__.
    5. Your REDCap API URL should be displayed in the page for the method
       under __URL__.
* __REDCap Project with API Token.__ You need to have an API token for a project in REDCap. You would typically get this
by creating a project in REDCap and then requesting an API token. To request an API
token, from your project's page in REDCap:
    1. Click on the __API__ link on the left.
    2. On the API page, click on the __Request API token__ button.
* __PHP 5.6+ with cURL and OpenSSL.__ You need to have a computer (with Internet access) that has PHP version 5.6 or greater installed. And you need to have the following PHP modules enabled:
    * cURL
    * OpenSSL
    
    If you use Composer to install PHPCap (see below), it will
    automatically check that the above modules are enabled,
    and generate an error message if they are not. If you don't
    use Composer, you can use the following command to check
    which modules are enabled:
    ```shell
    php -m
    ```

    Example PHP Setup on Ubuntu 16:
    ```shell
    sudo apt-get install php php-curl composer
    ```
    Information on installing PHP on Windows: 
    http://php.net/manual/en/install.windows.php


Creating a PHPCap Project
------------------------------

### Create a project directory

Create a directory for your project, and cd to that directory:

    mkdir phpcap-project
    cd phpcap-project

### Get PHPCap
  
If you have [Composer](https://getcomposer.org/) installed, you can get PHPCap using:

    composer require iu-redcap/phpcap
    
If you have [Git](https://git-scm.com/) installed, you can use the following:

    git clone https://github.com/iuredcap/phpcap 

If you don't have Composer or Git installed, you can get a Zip file of PHPCap from GitHub by clicking on this link:

https://github.com/iuredcap/phpcap/archive/master.zip

Or go to this page and click on the "Clone or download" button, and then click on "Download ZIP":

https://github.com/iuredcap/phpcap/

Then unzip the file to your project directory.
    
You should now have the following directory structure:

<table>
    <tr><th>Composer</th><th>Git</th></tr>
    <tr>
      <td style="vertical-align:top">
<pre>phpcap-project/
    composer.json
    composer.lock        
    vendor/
        <b>autoload.php</b>
        composer/
        phpcap/</pre>
     </td>
     <td style="vertical-align:top">
<pre>phpcap-project/
    PHPCap/
        ...
        <b>autoloader.php</b>
        ...
        docs/
        ...</pre>
      </td>
    </tr>
</table>
You will need to include the PHPCap autoloader (shown in bold above) in your code to access the PHPCap classes. And the location and name of the autoloader to use will depend on
whether you used Composer to download PHPCap, or downloaded it from GitHub (using Git or directly).

            
### Create your first test program

Create a file __test.php__ in your project directory:

<table>
    <tr><th>Composer</th><th>Git</th></tr>
    <tr>
      <td style="vertical-align:top">
<pre>phpcap-project/
    composer.json
    composer.lock
    <b>test.php</b>
    vendor/
        autoload.php
        composer/
        phpcap/</pre>        
     </td>
     <td style="vertical-align:top">
<pre>phpcap-project/
    PHPCap/
        ...
        autoloader.php
        ...
        docs/
        ...
    <b>test.php</b></pre>
      </td>
    </tr>
</table>

Enter the following into the __test.php__ file, modifying the API URL and token to match those for your REDCap project:

```php
<?php

# Use only one of the following requires:
require('vendor/autoload.php');   # For Composer
require('PHPCap/autoloader.php'); # For Git 


use IU\PHPCap\RedCapProject;

$apiUrl = 'https://redcap.xxxxx.edu/api/';  # replace this URL with your institution's
                                            # REDCap API URL.
                                                 
$apiToken = '11111111112222222222333333333344';    # replace with your actual API token

$project = new RedCapProject($apiUrl, $apiToken);
$projectInfo = $project->exportProjectInfo();

print_r($projectInfo);
```    

Run the test program using the following command in your project directory:

    php test.php
    
You should see output generated with information about your project.
It should look similar to the following, although some of the values will
probably be different:

```php
Array
(
    [project_id] => 9639
    [project_title] => PHPCap Basic Demography Test
    [creation_time] => 2017-03-31 13:40:53
    [production_time] => 
    [in_production] => 0
    [project_language] => English
    [purpose] => 1
    [purpose_other] => PHPCap testing
    [project_notes] => 
    [custom_record_label] => 
    [secondary_unique_field] => 
    [is_longitudinal] => 0
    [surveys_enabled] => 1
    [scheduling_enabled] => 0
    [record_autonumbering_enabled] => 0
    [randomization_enabled] => 0
    [ddp_enabled] => 0
    [project_irb_number] => 
    [project_grant_number] => 
    [project_pi_firstname] => 
    [project_pi_lastname] => 
    [display_today_now_button] => 1
)
```

### Making your test program secure

The program above is not secure, because it does not use SSL verification to verify that the
REDCap site accessed is the one actually intended. To make the program more secure, it
should use SSL verification. 

To do this you need to add the SSL verify flag and set it to true:

```php
...
$sslVerify = true;
$project = new RedCapProject($apiUrl, $apiToken, $sslVerify);
...
```

If you can successfully run the test program with the change above, then
it should mean that your system is set up to do SSL verification by default.
However, if the above change causes an SSL certificate error,
you will need take additional steps to get SSL verification to work.

One approach to getting SSL verification to work is to create a
certificate file for this, and add specify it in your program.
Information on creating the file
can be found here: [CA Certificate file](CACertificateFile.md)

Assuming the file was created with the name 'USERTrustRSACertificationAuthority.crt' and is in
you top-level project directory, the project creation would now be modified to the following:

```php
...
$sslVerify = true;
$caCertificateFile = 'USERTrustRSACertificationAuthority.crt';
$project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
...
```

So, at this point, your project directory should look as follows (without the .crt
file if it is not needed):

<table>
    <tr><th>Composer</th><th>Git</th></tr>
    <tr>
      <td style="vertical-align:top">
<pre>phpcap-project/
    composer.json
    composer.lock
    test.php
    <b>USERTrustRSACertificationAuthority.crt</b>
    vendor/
        autoload.php
        composer/
        phpcap/</pre>        
     </td>
     <td style="vertical-align:top">
<pre>phpcap-project/
    PHPCap/
        ...
        autoloader.php
        ...
        docs/
        ...
    test.php
    <b>USERTrustRSACertificationAuthority.crt</b></pre>
      </td>
    </tr>
</table>
        
And your test program should look similar to the following:


```php
<?php

# Use only one of the following requires:
require('vendor/autoload.php');   # For Composer
require('PHPCap/autoloader.php'); # For Git 

use IU\PHPCap\RedCapProject;

$apiUrl = 'https://redcap.xxxxx.edu/api/';  # replace this URL with your institution's
                                            # REDCap API URL.
                                                 
$apiToken = '11111111112222222222333333333344';    # replace with your actual API token

$sslVerify = true;
$caCertificateFile = 'USERTrustRSACertificationAuthority.crt';
$project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
$projectInfo = $project->exportProjectInfo();

print_r($projectInfo);
```    

If everything is working correctly, the test program should (still) output information about your project.

### Checking for errors

In general, when an error occurs in PHPCap, it throws a PhpCapException.
These exceptions can be checked and handled using "try" and "catch". For example,
to handle exceptions in the sample program, it could be modified as follows:
```php
<?php

# Use only one of the following requires:
require('vendor/autoload.php');   # For Composer
require('PHPCap/autoloader.php'); # For Git 

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

$apiUrl = 'https://redcap.xxxxx.edu/api/';  # replace this URL with your institution's
                                            # REDCap API URL.
                                                 
$apiToken = '11111111112222222222333333333344';    # replace with your actual API token

$sslVerify = true;
$caCertificateFile = 'USERTrustRSACertificationAuthority.crt';
try {
    $project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
    $projectInfo = $project->exportProjectInfo();
    print_r($projectInfo);
} catch (PhpCapException $exception) {
    print "The following error occurred: {$exception->getMessage()}\n";
    print "Here is a stack trace:\n";
    print $exception->getTraceAsString()."\n";
}


```    
Note that in addition to the "try" and "catch" that were added, an additional use statement was
added for the PhpCapException class: 
```php
use IU\PHPCap\PhpCapException;
```

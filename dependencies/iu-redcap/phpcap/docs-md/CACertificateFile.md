<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

CA Certificate File Info
=====================================================================

The CA (Certificate Authority) certificate file is needed so that PHPCap can verify that
the REDCap instance it is connecting to is actually the instance that
was specified.

In PHPCap, it is possible to set SSL verification to false, so that
a CA certificate file is not required, however, this is insecure, and
is not recommended. At most, setting SSL verification to false should
only be used for initial testing with non-critical data.

It is possible that your system may already be set up to use a correct CA certificate file.
This can be tested by trying to access a project with SSL verification set to true, but with
no CA certificate file specified, for example:

```php
<?php

require('PHPCap/autoloader.php');

use IU\PHPCap\RedCapProject;

$apiUrl = 'https://redcap.someplace.edu/api/'; # Replace with your REDCap's API URL
$apiToken = '1234567890A1234567890B1234567890';  # Replace with your API token

$sslVerify = true;
$project = RedCapProject($apiUrl, $apiToken, $sslVerify);
$project->exportProjectInfo();
```

If this works, then that would indicate that your system is already set up with a CA certificate
file. If it fails, and you get an error message about a security certificate, such as

> SSL certificate problem: self signed certificate in certificate chain

then your system is not already set up.


Creating a CA Certificate File with Firefox
--------------------------------------------------

To use the Firefox web browser to create a CA (Certificate Authority) certificate file for use with PHPCap, use the following steps:

1. Access your REDCap site with Firefox.
2. Click on the padlock icon to the left of the URL displayed in Firefox, and then the connection, and then "More Information".
    
    ![Page Information](resources/secure-connection.png)  
3. If the previous step succeeded, a "Page Info" window should open up. In this window, 
click on the "Security" tab, if it is not already selected.  
    
    ![Page Information](resources/page-info-security.png)  
4. Click on the "View Certificate" button
5. Click on the "Detail" tab of the "Certificate Viewer" dialog.  
    
    ![Certificate Viewer](resources/certificate-viewer.png)  
6. Select the top entry in the "Certificate Hierarchy" box.
7. Click the "Export..." button.
8. In the "Save Certificate to File" window that should appear:
    1. navigate to where you want to save the file
    2. change the name of the file if you don't want to use the default name
    3. set "Save as type" to "X.509 Certificate (PEM)"
    4. click on the "Save" button.  
    
        ![Save Certificate to File](resources/save-certificate-to-file.png)   


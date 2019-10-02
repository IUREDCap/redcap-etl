<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

User Guide 5 - Extending PHPCap
=============================================

If you need additional functionality to what is provided by PHPCap, you
can extend it.

Extending the RedCapProject class
-------------------------------------
You can create your own class that extends PHPCap's RedCapProject class
where you can define custom methods.

For example, if you wanted to have a method that returns the
title of the project, you could create a class
similar to the following that extends PHPCap's RedCapProject class:
```php
class MyRedCapProject extends IU\PHPCap\RedCapProject
{
    public function exportProjectTitle() {
       $projectInfo = $this->exportProjectInfo();
       $projectTitle = $projectInfo['project_title'];
       return $projectTitle;
    }
}
```
The new class would have all of the methods of RedCapProject as well as the
new method defined above, and you would then use this new class instead of
RedCapProject, for example:
```php
...
$project = new MyRedCapProject($apiUrl, $apiToken);
$projectTitle = $project->exportProjectTitle();
print("Project Title: $projectTitle\n");

$records = $project->exportRecords();
```

The RedCapProject class also has a connection property that gives you direct access to
the REDCap API. So within a method of your class extending RedCapProject, you
could use the following to send data to, and get the results from, your REDCap API:
```php
# Pass data to the REDCap API, and get back the result
$result = $this->connection->call($data);
```
This is useful for accessing methods provided by the REDCap API that
have not been implemented in PHPCap.

If you do define your own project class, and want to use it in conjunction with PHPCap's
RedCap class, you can use the __setProjectConstructorCallback__ method of the __RedCap__
class to get RedCap to use your project class for projects that it returns from its
methods to create and get projects. For example:
```php
...
$callback = function ($apiUrl, $apiToken, $sslVerify = false,
                      $caCertificateFile = null, $errorHandler = null,
                      $connection = null) {
        return new MyRedCapProject($apiUrl, $apiToken, $sslVerify,
                                   $caCertificateFile, $errorHandler, $connection);
    };
...        
$redCap = new RedCap($apiUrl);
$redCap->setProjectConstructorCallback($callback);
```

Extending the ErrorHandler class
----------------------------------------
The ErrorHandler class handles errors that occur in PHPCap,
and it handles them by throwing a PhpCapException. This
class can be extended, or you can implement a completely new class
that implements the ErrorHandlerInterface interface.

The constructors for the RedCap and RedCapProject classes have an ErrorHandler
parameter that can be used to set these classes to use your custom error handler class.
In addition, if a project is created with, or gotten from, a RedCap object where 
a custom error handler has been set, the returned project will be assigned a clone
of the custom error handler in the RedCap object.

Extending the RedCapApiConnection class
----------------------------------------------
The RedCapApiConnection class is used to represent the PHPCap's underlying
connection to a REDCap API.
This class can be extended, or you can implement a completely new connection class
that implements the RedCapApiConnectionInterface interface.

The constructors for the RedCap and RedCapProject classes have a connection
parameter that can be used to set these classes to use your custom connection class.
In addition, if a project is created with, or gotten from, a RedCap object where 
a custom connection has been set, the returned project will be assigned a clone
of the custom connection in the RedCap object.

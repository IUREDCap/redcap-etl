<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

Developer Guide
===================================================

This guide is for people interested in developing PHPCap (i.e., actually making changes to the PHPCap code). If you just want to use PHPCap to access REDCap from PHP, please see the
user guides.

Setup
--------------------------------------------------------
1. Install PHP 5.6 or greater with the following extensions:
    * cURL
    * DOM/XML
    * mbstring
    * OpenSSL
1. (Optional) Install XDebug. This is needed for PHPUnit code coverage analysis.
1. Install Git. The code for PHPCap is stored in GitHub, and Git is required to be able to download it for development.
   See: https://git-scm.com/downloads
2. Get PHPCap:
     
    ```shell
    git clone https://github.com/iuredcap/phpcap
    ```
    
3. Get Composer. Composer is needed to download the development dependencies for PHPCap.
   See: https://getcomposer.org/download/.
   You can either install the composer.phar file to the root directory of PHPCap (the .gitignore 
   file is set to ignore this file), or install it globally at the system or account level.
4. Install PHPCap's development dependencies:

    ```shell
    # If you installed the composer.phar file in PHPCap's root directory:
    php composer.phar install
    
    # If you installed composer globally:
    composer install
    
    # The dependencies should be installed into a "vendor" directory
    # (which will be ignored by Git).    
    ```

### Example Setup on Ubuntu 16
To set up PHPCap for development on Ubuntu 16, execute the following commands:
    
```shell
sudo apt-get install php php-curl php-xml php-mbstring
sudo apt-get install php-xdebug
sudo apt-get install git
git clone https://github.com/iuredcap/phpcap
sudo apt-get install composer
cd PHPCap
composer install
```

Development
-----------------------------------------

### Automated Tests

PHPCap uses PHPUnit for running automated tests. PHPUnit should get installed as a dependency for your PHPCap project when you run the "composer install" command.
PHPCap has the following types of automated tests:
1. __unit tests__
    * in directory __tests/unit__
    * don't require a REDCap instance to run
2. __integration tests__
    * in directory __tests/integration__
    * require a REDCap instance to run
    * require setup and configuration to run

You can test your PHPUnit installation by running the following in the root PHPCap directory, which will run the unit tests for PHPCap:

	    ./vendor/bin/phpunit --testsuite unit
    
If the above command succeeds, you should see an "OK" message with the number of tests and assertions that were run, and you should see no errors of failures.
    
To run _all_ the automated tests (the unit tests as well as the integration tests), setup steps needs to be completed. Some of the integration tests are considered optional. If 
you don't do the setup steps for these optional tests, they will not be run. But if you don't do the setup steps for the non-optional integration tests, phpunit will fail
when you try to run them.


#### Setup for Non-Optional Integration Tests
Running all the automated tests, or all the integration tests, will fail unless the
following setup steps are completed:
1. Log in to your REDCap site.
2. Create an empty project in REDCap.
3. Create a project in REDCap for the "Basic Demography" and "Longitudinal Data" test projects in directory __tests/projects/__, and import each of those test project files into the REDCap project created for it.
4. In REDCap, request API tokens for the empty project and the projects imported in the step above.
5. Once you have your tokens, copy the "config-example.ini" file to a file
   named "config.ini" and then set the URL in that file to be the
   URL for the API of your REDCap instance, and set the tokens to be
   the tokens requested in the previous step.
   
#### Setup for Optional CA Certificate File Integration Tests
To run the optional tests involving the CA certificate file, you will need to set up
a valid CA certificate file, and set the __ca.certificate.file__ property
in the __config.ini__ file to the path to that file. If this property
isn't set, the tests for this will be skipped.
See [CA Certificate File Info](CACertificateFile.md) for more information on how to
do this.

#### Setup for Optional Report Integration Tests
To run all of the optional report tests, you will need to manually set up a report for the
longitudinal data project and then set the 
__longitudinal.data.report.id__ property in your __tests/config.ini__ file
to the ID of the report. If the ID property is not set, then the tests
that use the report will not be run. You need to set up an "Exercise" report
as follows:
    * Include these fields in this order: study_id, age, ethnicity, race, sex, gym, aerobics
    * Filter (only) by the following events: "Enrollment (Arm 1: Drug A)", "Enrollment (Arm 2: Drug B)" 
    
#### Setup for Optional Survey Integration Tests
To run the survey tests, use the following steps:
1. In REDCap, create a project for the "Repeatable Survey" project test file in the __tests/projects__ directory, and import the project test file into that project.
2. In the "Project Setup" tab for the project created in the step above, click on the __Enable__ button for "User surveys for this project?"
3. In the "Project Setup" tab, click on the __Online Designer__ button
4. In the "Online Designer" tab, click on the __Enable__ button for instrument "Basic Information", and then:
    1. Select "Yes" for "Allow 'Save & Return Later' option for respondents?"
    2. Click on the __Save Changes__ button
5. In the "Online Designer" tab, click on the __Enable__ button for instrument "Weight", and then:
    1. Select "Yes" for "Allow 'Save & Return Later' option for respondents?"
    2. Check the box for "(Optional) Allow respondents to repeat the survey"
    3. Click on the __Save Changes__ button
6. In the "Online Designer" tab, click on the "Survey Queue" button
7. In the "Set up Survey Queue" dialog:
    1. Click on the __Activate__ button for "Weight"
    2. Check "When the following survey is completed:"
    3. In the selection below "When the following survey is completed:", select "Basic Information"
    4. Click on the __Save__ button
8. Click on the "Manage Survey Participants" link on the left
9. In the "Manage Survey Participants" panel, go to the "Participant List" tab
10. Click on the __Add participants__ button
11. Add an e-mail that you have access to and click on the __Add participants__ button
12. Click on the __Enable__ button for participant identifiers, and confirm this action
13. Enter an identifier for the participant for the e-mail that you entered above and then click on the __Save__ button
14. Click on the __Compose Survey Invitations__ button
15. In the "Send a Survey Invitation to Participants" dialog:
    1. Make sure that "Immediately" is selected for when the e-mail should be sent
    2. Make sure that the participant you create above is selected
    3. Click on the __Send Invitations__ button
16. Open the e-mail that should have been sent (you may need to wait a few minutes to receive it)
17. Click on the "Basic Information" link in the e-mail and fill out the survey, and then at least one "Weight" survey
18. In your project window in REDCap, click on the "API" link on the left and request (or create if you are an admin) an API token with at least API Export rights
19. Once you have your API token, use it and the e-mail and identifier entered earlier to set the following properties in the config.ini file in the tests directory:

        repeatable.survey.api.token
        survey.participant.email
        survey.participant.identifier
    

#### Setup for the Optional Repeating Forms Tests

To run the repeating forms tests, use the following steps:

1. In REDCap, create a project for the "Repeating Forms" project test file in the __tests/projects__ directory, and import the project test file into that project.
2. In your project window in REDCap, click on the "API" link on the left and request (or create if you are an admin) an API token with at least API Export rights
3. Once you have your API token, use it to set the following property in the config.ini file in the tests directory:

        repeating.forms.api.token


#### Setup for Optional Project Creation Tests
To set up the optional tests for project creation, you need to uncomment and set the
__super.token__ to a valid super token value. Note that the tests for project creation
have no way to delete the projects that are created, so they will need to be deleted
manually.


Note: the .gitignore file in PHPCap is set to ignore the __tests/config.ini__ file, so that your
personal API tokens will not be committed to Git. 


#### Running the Automated Tests
To run the automated tests, execute the following command in the top-level directory of your downloaded version of PHPCap:

    ./vendor/bin/phpunit

The above command will run all the unit tests, and all the integration tests, except for
the optional test that were not set up.
    
Note: PHPUnit uses the **phpunit.xml** configuration file in the root directory of PHPCap.
See this file for a list of the test suites and files. This file will need to be
modified to make changes to the test suites and/or files.

##### Running Selected Tests
You can run just the integration tests using:

    ./vendor/bin/phpunit --testsuite integration

You can run a specific test class by specifying the path to its file, for example:

    ./vendor/bin/phpunit tests/unit/PhpCapExceptionTest.php

You can use the **--filter** option to run specific test methods, for example, the following
would run only tests methods that contain 'Unreadable' in their name:

    ./vendor/bin/phpunit --filter 'Unreadable'

And you can combine class files and filters together. For example, the following command would only run
methods with 'Curl' in their name that belong to the PhpCapExceptionTest class:

    ./vendor/bin/phpunit tests/unit/PhpCapExceptionTest.php --filter 'Curl'
    
##### Code Coverage
If XDebug has been installed (and PHP is configured to use it), code coverage for the automated tests can
be calculated by running the following command in the root directory of PHPCap:

    ./vendor/bin/phpunit --coverage-html tests/coverage
    
To see the results, open the file **tests/coverage/index.html** with a web browser. The .gitignore file is set to
ignore the tests/coverage directory.

Note that when writing code, it is sometimes necessary to use the __@codeCoverageIgnore__ annotation
to reach 100% line coverage. The one problem that has come up is that the code coverage check will
mark the closing bracket after a method that throws an exception as a line that was not covered. To avoid this, you can add the @codeCoverageIgnore annotation as shown in the example below. 
```php
if ($required) {
    $message = 'No field was specified.';
    $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
} // @codeCoverageIgnore
```

### Local Tests
The directory __tests/local/__ has been set up so that all files in it, except for the README file, will be ignored by Git.
This directory is intended as a place for developers to places tests for changes they are working on.

### Coding Standard Compliance
PHPCap follows the PSR-1 and PSR-2 coding standards. See:
* http://www.php-fig.org/psr/psr-1/
* http://www.php-fig.org/psr/psr-2/

To check for compliance for the PHPCap source code, execute the following command in the root directory of PHPCap:

    ./vendor/bin/phpcs src

To check compliance for the automated tests, use:

    ./vendor/bin/phpcs tests/unit tests/integration

To check for compliance for the PHPCap source code and the tests, use the default settings
(defined in the top-level phpcs.xml file):

    ./vendor/bin/phpcs

You can check specific files for compliance by specifying the path to the file, for example:

    ./vendor/bin/phpcs src/RedCapProject.php  
     
Note that if you are working on Windows and have the git property __core.autocrlf__ set to true, you may see errors similar to the following:

    ----------------------------------------------------------------------
    FOUND 1 ERROR AFFECTING 1 LINE
    ----------------------------------------------------------------------
    1 | ERROR | [x] End of line character is invalid; expected "\n" but
      |       |     found "\r\n"
    ----------------------------------------------------------------------
    PHPCBF CAN FIX THE 1 MARKED SNIFF VIOLATIONS AUTOMATICALLY
    ----------------------------------------------------------------------
These errors are not important, because the issue should be fixed when you commit your code.

PHPCap also follows the PSR-4 (Autoloader) standard, see: http://www.php-fig.org/psr/psr-4/


### Documentation

Documentation consists of the following:
* Top-level README.md file
* Markdown documents that have been manually created in the __docs-md/__ directory
* HTML API documentation generated from the PHPDoc comments in the code, which are stored in the __docs/api/__ directory
* HTML versions of the Markdown documentation in the docs-md/ directory, which are generated programmatically, stored in the __docs/__ directory, and use the same style as the API documentation.


#### API Document Generation
To generate the API documentation (stored in **./docs/api**), execute the following command in PHPCap's root directory:

    ./vendor/bin/apigen generate
    
Note: ApiGen uses the **apigen.neon** configuration file in the root directory of PHPCap.

The API documentation is stored in Git to eliminate the need for non-developer users to install Composer and the developer dependencies.

#### HTML Document Generation
To generate an HTML version for the Markdown documents in the __docs-md/__ directory, execute the following command in PHPCap's root directory:

    php generate-html-docs.php

### Tagging Releases

Releases should be tagged in accordance with semantic versioning: 
http://semver.org/


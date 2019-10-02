<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

PHPCap Automated Tests
=====================================

### Pre-requisties
1. REDCap account - you need to have a REDCap account on your REDCap instance
2. Install the Composer developer dependencies


### Setup Steps
1. Log in to your REDCap site
2. Import the test REDCap project files in directory __tests/projects__.
3. In REDCap, request API tokens for the projects imported in the step above
4. Once you have your token, copy the "config-example.ini" file to a file
   named "config.ini" and then set the URL in that file to be the
   URL for the API of your REDCap instance, and set the tokens to be
   the tokens for your projects that were requested in the previous step
   
### Running the Tests
To run the tests, from the PHPCap root directory enter:
    
    ./vendor/bin/phpunit
    
for Windows shells use:

    .\vendor\bin\phpunit
    
PHPUnit uses the phpunit.xml configuration file in the top-level PHPCap directory.


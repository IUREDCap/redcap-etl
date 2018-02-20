REDCap ETL Developer Guide
======================================

Development Environment Setup
-------------------------------------

This is a list of the steps for setting up a REDCap ETL development environment. Example commands are shown for Ubuntu 16.

1. Install PHP

        sudo apt install php php-curl php-mbstring php-mysql php-xml
        sudo phpenmod mysqli   # enable mysqli extension
        sudo phpenmod pdo_mysql # enable PDO extension for PHP 
        sudo apt install php-xdebug  # Install XDebug to be able to see phpunit test code coverage

2. Install Composer (needed to get PHPCap and development dependencies)

        sudo apt install composer

3. Install sendmail (needed for the Notifier class, which sends e-mail notifications of errors)

        sudo apt install sendmail

4. Install MySQL

        sudo apt install mysql-server
        sudo mysql_secure_installation
        systemctl status mysql.service   # check status

5. Create a database and database user that will be used as the place to store the REDCap data, for example, in MySQL use:

        CREATE DATABASE `etl_test`;
        CREATE USER 'etl_user'@'localhost' IDENTIFIED BY 'etlPassword';
        GRANT ALL ON `etl_test`.* TO 'etl_user'@'localhost';

6. Install Apache (used for DET (Data Entry Trigger) web scripts)

        sudo apt install apache2 libapache2-mod-php

7. Install Git

        sudo apt install git
        # Add e-mail and name information, for example:
        git config --global user.email "jsmith@someuniversity.edu"
        git config --global user.name "J Smith"

8. Get the code. Execute the following command in the directory where
   you want to put REDCap ETL:

        git clone https://github.iu.edu/ABITC/redcap-etl

9. Install Composer dependencies. In the top-level directory where the code was downloaded, run:

        composer install



Automated Tests
------------------------------

There are 3 types of automated tests:

1. __Unit__ - each test focuses on a single class
2. __Integration__ - tests focus on the integration of multiple classes
3. __System__ - tests focus on testing the system as a whole (multiple classes + scripts)

The test types above are listed in order of least to most setup effort.

|                                       | Unit |Integration | System    |
|---------------------------------------|------|:----------:|:---------:|
| __Configuration file setup required__ |      | &#10003;   | &#10003;  |
| __REDCap project setup required__     |      | &#10003;   | &#10003;  |
| __MySQL database setup required__     |      |            | &#10003;  |
| __Web server setup required__         |      |            | &#10003;  |
| __Webs script setup required__        |      |            | &#10003;  |
| __REDCap DET setup required__         |      |            | &#10003;  |



### Unit Tests
You should be able to run the unit tests at this point if you have
completed the previous steps.
To run the unit tests, enter the following in a command shell
at the top-level directory of the REDCap ETL installation:

    ./vendor/bin/phpunit --testsuite unit
    
If this command runs successfully, you should see an "OK" message that
indicates the number of tests and assertions that were successful.

### Integration and System Tests
Setting up the integration and system tests requires having access
to a REDCap instance, and the ability to get API tokens for projects
on that instance. Setting these tests up is not required, but
the tests have much better code coverage when they are.

#### Integration tests
To set up the integration tests, you need to first set up
the Basic Demopraphy REDCap project that has the data for the
tests:

1. download the Basic Demography REDCap project: 

        tests/projects/BasicDemography.REDCap.xml

2. create a REDCap project using the
   "Upload a REDCap project XML file" option, and specify the file
   downloaded in the previous step
3. request an API token for the project you just created (or
   create a token if you are an admin)

The next thing you need to do is to create the configuration file
for the Basic Demography project:

1. Copy the basic demography configuration and transformation rules
   files from the tests/config-init directory to the tests/config
   directory, for example, from the top-level directory:
   
        cp tests/config-init/basic-demography.ini tests/config
        cp tests/config-init/basic-demography-rules.txt tests/config

2. Edit the file tests/config/basic-demography.ini and set the 
   following properties to appropriate values:
    1. redcap_api_url - set this to the URL for your REDCap's API
    2. data_source_api_token - set this to the REDCap API token for
       your REDCap Basic Demography project created above.
    3. db_connection =
    

#### System tests
To set up the system steps:
1. download the Visits REDCap project from the tests/projects
   directory: Visits.REDCap.xml


### Running the Tests

To run all of the automated test, in the top-level directory run:

    ./vendor/bin/phpunit

The system tests use the REDCap ETL scripts, so the DET web script
will need to be set up.


#### Generating test coverage

To see test coverage information, you need to have XDebug installed, and
then run the following command from the root directory of the project:

    ./vendor/bin/phpunit --coverage-html tests/coverage

Then with a browser, open the file:

    tests/coverage/index.html

The output
could be stored in a different directory, but directory tests/coverage
has been set up to be ignored by Git.


Coding Standards Compliance
------------------------------------

REDCap ETL follows these PHP coding standards:

* [PSR-1: Basic Coding Standard](http://www.php-fig.org/psr/psr-1/)
* [PSR-2: Coding Style Guide](http://www.php-fig.org/psr/psr-2/)
* [PSR-4: Autoloader](http://www.php-fig.org/psr/psr-4/)

From the top-level directory of your REDCap ETL installation,
the following command can be used
to check for coding standards compliance:

    ./vendor/bin/phpcs --standard=PSR1,PSR2 src tests/unit tests/integration tests/system bin

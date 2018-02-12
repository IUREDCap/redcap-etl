REDCap ETL Developer Guide
======================================

Development Environment Setup
-------------------------------------

This is a list of the steps for setting up a REDCap ETL development environment. Example commands are shown for Ubuntu 16.

1. Install PHP

        sudo apt install php php-curl php-mysql
        sudo phpenmod mysqli   # enable mysqli extension
        sudo phpenmod pdo_mysql # enable PDO extension for PHP 
        sudo apt install php-mbstring # needed for phpunit
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

6. Install Apache (used to test the handler script)

        sudo apt install apache2 libapache2-mod-php

7. Install Git

        sudo apt install git
        # Add e-mail and name information, for example:
        git config --global user.email "jsmith@someuniversity.edu"
        git config --global user.name "J Smith"

8. Get the code.

        git clone https://github.iu.edu/ABITC/redcap-etl

9. Install Composer dependencies. In the top-level directory where the code was downloaded, run:

        composer install

10. Copy REDCap projects for OPTIMISTIC regression to new projects:
    1. Don’t copy any records for the LOG project
    2. Get API Tokens for each of these new projects
    3. In the configuration project:
        1. Set your e-mail address
        2. Set the API token for the data (input) project
        3. Set the API token for the log project
        4. Configure the MySQL database connection



Automated Tests
------------------------------

There are 3 types of automated tests:

1. Unit
2. Integration
3. System

The test tyoes above are listed in order of lest to most setup effort.

<table>
  <thead>
    <tr> <th> &nbsp; </th> <th> Unit </th> <th> Integration </th> <th> System </th> </tr>
  </thead>
  <tbody>
    <tr>
      <th style="text-align: left"> Test Focus </th>
      <td> Single class </td>
      <td> Multiple classes </td>
      <td> Multiple classes + scripts </td>
    </tr>
    <tr>
      <th style="text-align: left"> REDCap instance required? </th>
      <td> &nbsp </td>
      <td style="text-align: center"> Yes </td>
      <td style="text-align: center"> Yes </td>
    </tr>
    <tr>
      <th style="text-align: left"> REDCap project and configuration file setup required? </th>
      <td> &nbsp </td>
      <td style="text-align: center"> Yes </td>
      <td style="text-align: center"> Yes </td>
    </tr>    
    <tr>
      <th style="text-align: left"> Web server, web server script and
          REDCap DET (Data Entry Trigger) setup required? </th>
      <td> &nbsp </td>
      <td> &nbsp; </td>
      <td style="text-align: center"> Yes </td>
    </tr>    
  </tbody>
</table>

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

1. download the Basic Demography REDCap project from the
   tests/projects directory: BasicDemography.REDCap.xml
2. create a REDCap project using the the
   "Upload a REDCap project XML file" option, and specify the file
   downloaded in the previous step
3. request an API token for the project you just created (or
   create a token if you are an admin)

The next thing you need to do is to create the configuration file
for the Basic Demography project:

#### System tests
To set up the system steps:
1. download the Visits REDCap project from the tests/projects
   directory: Tests.REDCap.xml


### Running the Tests

To run all of the automated test, in the top-level directory run:

    ./vendor/bin/phpunit

The system tests use the installed versions of the scripts, so you need to run an install before these automated tests can be run. Also, if you update the scripts or the classes they use, you will need to run an install so that these automated tests will be using the latest version of the code.

By default, the system tests will use the REDCap ETL batch script to load records into the database. However, it is also possible to run the tests using the handler script that would normally be activated by a REDCap DET (Data Entry Trigger).

#### Generating test coverage

To see test coverage information, you need to have XDebug installed, and then run the following command from the root directory of the project:

    ./vendor/bin/phpunit --coverage-html tests/coverage

Then open the file tests/coverage/index.html with a browser. The directory tests/coverage has been set up to be ignored by Git.





Coding Standards Compliance
------------------------------------

REDCap ETL follows these PHP coding standards:

* [PSR-1: Basic Coding Standard](http://www.php-fig.org/psr/psr-1/)
* [PSR-2: Coding Style Guide](http://www.php-fig.org/psr/psr-2/)
* [PSR-4: Autoloader](http://www.php-fig.org/psr/psr-4/)

From the top-level directory of your REDCap ETL installation, the following command can be used
to check for coding standards compliance:

    ./vendor/bin/phpcs --standard=PSR1,PSR2 src tests/unit tests/integration tests/system bin

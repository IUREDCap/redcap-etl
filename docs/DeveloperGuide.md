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
        GRANT ALL ON `etl_user`.* TO 'etl_test'@'localhost';

6. Install Apache (used to test the handler script)

        sudo apt install apache2 libapache2-mod-php

7. Install Git

        sudo apt install git
        # Add e-mail and name information, for example:
        git config --global user.email "jsmith@someuniversity.edu"
        git config --global user.name "J Smith"

8. Get the code (currently the new-phpcap-test branch is being used for REDCap ETL)

        git clone https://github.iu.edu/ABITC/opt2etl
        cd opt2etl
        git checkout --track origin/new-phpcap-test

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


Installing REDCap ETL
-----------------------------------------
You need to run the ./extras/install.pl command to install REDCap ETL. Here is an example install command:

    sudo ./extras/install.pl -install_dir /opt/redcap-etl -web_dir /var/www/html

The above command will install the handler script where it can be accessed using the following URL:

    http://localhost/opt2etl_handler.php

Before the install command is run for the first time, you need to create the install directory, and in that directory place a properties file for each instance of REDCap ETL you want on your system. For more information, see the following example file that should be in the top-level directory of your workspace directory (i.e., the directory where you cloned the REDCap ETL code from GitHub):

    redcap_etl.properties.example

All .properties files in the top-level install directory will be scanned by the install script, and a web script will be generated for each one that has a web script name specified.



Automated Tests
------------------------------
To set up the automated tests, you need copies of the regression test config, data and log projects. And you need copies of the basic demography config, data and log projects. 

You need to copy the file **config-example.ini** to **config.ini** in the **tests/** directory. Then edit the **config.ini** file and enter appropriate values for all of the properties.

To run all of the automated test, in the top-level directory run:

    ./vendor/bin/phpunit

The system tests use the installed versions of the scripts, so you need to run an install before these automated tests can be run. Also, if you update the scripts or the classes they use, you will need to run an install so that these automated tests will be using the latest version of the code.

By default, the system tests will use the REDCap ETL batch script to load records into the database. However, it is also possible to run the tests using the handler script that would normally be activated by a REDCap DET (Data Entry Trigger).

To see test coverage information, you need to have XDebug installed, and then run the following command from the root directory of the project:

    ./vendor/bin/phpunit --coverage-html tests/coverage

Then open the file tests/coverage/index.html with a browser. The directory tests/coverage has been set up to be ignored by Git.


To have the system tests run using the handler script, use the following steps:
1. In the REDCap configuration project, set the option to run ETL when the record is saved. This needs to be done each time the tests are run, because REDCap ETL will automatically reset this value to not run ETL each time the test is run.
2. Set the REDCAP_ETL_DATA_TEST_SCRIPT environment variable to 'handler' when running phpunit, for example:

    REDCAP_ETL_DATA_TEST_SCRIPT=handler ./vendor/bin/phpunit



This document describes how to set SQL Server using Ubuntu 18 (our default development environment). SQL Server is needed to run the automated SQL Server tests, but these tests are used for the development process and are not needed to just use REDCap-ETL.


1. Prerequisites
-------------------------------

You must have an Ubuntu machine with at least 2 GB of memory.

If you do not have Ubuntu 18.04 LTS installed, check the ubuntu wiki to see if 18.04 is the LTS version: https://wiki.ubuntu.com/Releases

If 18.04 is the listed as the LTS version, then update/upgrade Ubuntu Server in your home directory:

    sudo apt-get update
    sudo apt-get upgrade

Reboot the server, if necessary:

    sudo reboot now


2. Install SQL Server
---------------------------

In your home directory, import the public repository GPG keys:

    wget -qO- https://packages.microsoft.com/keys/microsoft.asc | sudo apt-key add -

Then register the Microsoft SQL Server Ubuntu repository:

    sudo add-apt-repository "$(wget -qO- https://packages.microsoft.com/config/ubuntu/16.04/mssql-server-2017.list)"

(Packages for Ubuntu 16.04 will be used for this. There are no packages at the time of this document for Ubuntu 18.04.)


Run the following commands to install SQL Server:

    sudo apt-get update
    sudo apt-get install -y mssql-server


3. Configure MS SQL server
----------------------------------------

Run the setup command shown below. You will be prompted to enter your MQL SERVER system administrator password and to choose your edition.

    sudo /opt/mssql/bin/mssql-conf setup

* The password has a minimum length of 8 characters and includes uppercase and lowercase letters, base 10 digits, and non-alphanumeric symbols.
* You should choose the Developer edition, which is free and has no production-use rights.



Once the configuration is done, verify that the service is running using the status command:

    systemctl status mssql-server --no-pager


Note: If you plan to connect remotely, you might also need to open the SQL Server TCP port (default 1433) on your firewall.


4. Install the SQL Server command-line tools
------------------------------------------------------

Install curl if needed:

    sudo apt-get install curl -y

Import the public repository GPG keys:

    curl https://packages.microsoft.com/keys/microsoft.asc | sudo apt-key add -

Register the Microsoft Ubuntu repository:

    curl https://packages.microsoft.com/config/ubuntu/16.04/prod.list | sudo tee /etc/apt/sources.list.d/msprod.list

Update the sources list and run the installation command with the unixODBC developer package.

    sudo apt-get update
    sudo apt-get install mssql-tools unixodbc-dev

Add /opt/mssql-tools/bin/ to your PATH environment variable in a bash shell.

    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc
    source ~/.bashrc


5. Verify the SQL Server Installation
------------------------------------------

At the time of this document, there is an issue with TCP, but the issue might be resolved by the time you are doing the installation, so try logging in at the unix prompt:

    sqlcmd -S localhost -U SA

If you get the error “Sqlcmd: Error: Microsoft ODBC Driver 17 for SQL Server : TCP Provider: Error code 0x2746”, see the section 6. Troubleshooting below.

Once logged in, run the following simple select statement that shows a list of database:

    1> select @@version
    2> go

Enter 'quit' to exit the SQL Server command line.

The output should resemble:

    ----------------------------------------------------------------------------------------------
    Microsoft SQL Server 2017 (RTM-CU17) (KB4515579) - 14.0.3238.1 (X64)

        Copyright (C) 2017 Microsoft Corporation
        Developer Edition (64-bit) on Linux (Ubuntu 18.04.3 LTS)

    (1 rows affected)
    1>


If you don't have to troubleshoot, continue with section 7. Install the PHP Drivers for SQL Server.


6. Troubleshooting the SQL Server Installation
----------------------------------------------------

If you encounter the error code 0x2746 error:

Try using version 1.0 of the OpenSSL libraries to connect to SQL Server:

Stop SQL Server

    sudo systemctl stop mssql-server

Open the editor for the service configuration

    sudo systemctl edit mssql-server

In the editor, add the following lines to the file and save it:

    [Service]
    Environment="LD_LIBRARY_PATH=/opt/mssql/lib"

Create symbolic links to OpenSSL 1.0 for SQL Server to use:

    sudo ln -s /usr/lib/x86_64-linux-gnu/libssl.so.1.0.0 /opt/mssql/lib/libssl.so
    sudo ln -s /usr/lib/x86_64-linux-gnu/libcrypto.so.1.0.0 /opt/mssql/lib/libcrypto.so

Start SQL Server

    sudo systemctl start mssql-server


If you encounter the error “sqlcmd: command not found,” try reinstalling the mssql-tools:

    sudo apt-get –reinstall install mssql-tools unixodbc-dev


If you encounter the error “Sqlcmd: Error: Microsoft ODBC Driver 17 for SQL Server : Can't open lib '/opt/microsoft/msodbcsql17/lib64/libmsodbcsql-17.4.so.2.1' : file not found,” try reinstalling only the ODBC driver:

    sudo apt-get update
    sudo ACCEPT_EULA=Y apt-get --reinstall install msodbcsql17


7. Install the PHP drivers for SQL Server
--------------------------------------------

See if PECL and phpize modules are already included in your PHP environment:

    php -m


If you need to install either one, you will need to run apt-file to find out which package provides the module. If you need to install apt-file first, run the following two commands:

    sudo apt install apt-file
    sudo apt update


To install PECL, run the apt-file to find the package that provides it:

    sudo apt-file search /usr/bin/pecl

and then Install the package that is displayed. For example if the output from the above command was:   

    php-pear: /usr/bin/pecl

then run the command to install php-pear:

    sudo apt install php-pear


To install phpize, run the apt-file to find the package that provides it:

    sudo apt-file search /usr/bin/phpize

and then Install the package that is displayed. For example if the output from the above command was:   

    php7.2-dev: /usr/bin/phpize7.2

then run the command to install php-pear:

    sudo apt-get install php7.2-dev


After phpize and PECL are available, install the PHP drivers for SQL server:

    sudo pecl install sqlsrv
    sudo pecl install pdo_sqlsrv

Next, update the ini files. In order to do that, you will need to know what version of PHP you are running:

    php -version

(Or you can get it from the version of the PHP package you installed for phpize, if you had to install it.)

Run the following commands, replacing the version number in the /etc/php directory with the version you are running. For example, if the output for the above command indicated you are running PHP 7.2.24, then execute the following commands:

    sudo su
    printf "; priority=20\nextension=sqlsrv.so\n" > /etc/php/7.2/mods-available/sqlsrv.ini
    printf "; priority=30\nextension=pdo_sqlsrv.so\n" > /etc/php/7.2/mods-available/pdo_sqlsrv.ini
    exit
    sudo phpenmod -v 7.2 sqlsrv pdo_sqlsrv


8. Create the database objects
----------------------------------------

Login into SQL Server:

    sqlcmd -S localhost -U SA

Create a database and verify it was created:

    1> create database etl_test
    2> GO

    1> select name from sys.databases
    2> GO


Create a login and verify it was created:

    1> CREATE LOGIN etl_user WITH PASSWORD=N'etlPassword123', DEFAULT_DATABASE=etl_test
    2> GO

    1> select name from sys.sql_logins
    2> GO


Create a database user and verify the username was created:

    1> CREATE USER etl FOR LOGIN etl_user WITH DEFAULT_SCHEMA=[DBO]
    2> GO

    1> select name, principal_id from sys.database_principals
    2> GO


Add the database user to the db_owner role:

    1> EXEC sp_addrolemember N'db_owner', N'etl'
    2> GO
    1> exit



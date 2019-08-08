REDCap-ETL Installation Guide
==================================


REDCap-ETL System Components
-------------------------------------------------------
The main components of an installed REDCap-ETL system
are shown in the diagram below, and described in the text below.

![ETL Process](etl-process.png)

* **Configuration File.** This file contains configuration information for a REDCap-ETL
process. A single REDCap-ETL instance can have multiple configuration files that describe
different ETL processes.
The configuration file either needs to define all needed configuration information, or
have a REDCap API token for a configuration project that contains the remaining necessary
configuration information. In both cases, this file must contain the URL for your
REDCap API. When the ETL process runs, this is the first file that will be accessed.
* **REDCap Projects**
    * **Configuration Project.** This optional (and now deprecated)
project contains configuration
information for the ETL process.  The advantage of using this project is that
it allows users who have access to REDCap, but not the REDCap-ETL server,
to be able to change
certain configuration properties, and to start the ETL process. The disadvantage of
using this project is that it increases the complexity of the installation.
It is expected that the configuration project will be removed in a future release
of REDCap-ETL, and that the configuration project's functionality will be provided
by a REDCap external module.
    * **Data Project.** This is the REDCap project that contains the data to be extracted.
    * **Logging Project.** This optional (and now deprecated) project is used
for logging. The advantage of using the logging project over the logging file is that
users who have access to REDCap, but not the REDCap-ETL server, can access the log information.
The disadvantages of
using this project are that it increases the complexity of the installation and slows down performance. It is expected that the logging project will be removed from REDCap-ETL in
a future release. REDCap-ETL now supports logging to the database, which provides more
flexible viewing options. REDCap-ETL also now supports e-mailing of a summary of the logging
information for an ETL process.
* **REDCap-ETL.** The software that actually does the Extract Transform and Load.
* **Database.** There needs to be some kind of database where the extracted and transformed data
can be loaded, such as a MySQL database, although this could be as simple as a directory for CSV files.
* **Web Server.** A web server, such as Apache, that supports PHP is necessary if you want to
  use REDCap's data entry triggers (DETs)
  to check the transformation rules and/or start
the ETL (Extract Transform Load) process from REDCap.
This will allow users to run the process by editing and saving a form in the REDCap
configuration project,
so that they do not need access to the server. If you only want to run the ETL process
on a regularly scheduled basis, or by manually running a command on the server,
then you do not need to have a web server.
* **E-mail Server.** This is optional, and if set up, is used for e-mailing error
notifications and logging summaries to designated users.


Installation Steps
-------------------------------------

### Step 1 - Set up the Server

**Install the System Requirements:**

* PHP 5.6+ or 7+, with:
    * curl extension
    * openssl extension
* Subversion or Git, for retrieving the REDCap-ETL code
* In addition, you need to be using REDCap version 7+


Example commands for setting up an Ubuntu 16 system:

    sudo apt install php php-curl
    sudo apt install git

### Step 2 (Optional) - Set up a MySQL Database

This needs to be set up if you want to load your extracted and transformed data into a
MySQL database.

Example commands for setting up MySQL on Ubuntu 16:

    sudo apt install php-mysql
    sudo phpenmod mysqli    # enable mysqli extension
    sudo phpenmod pdo_mysql # enable PDO extension for PHP 

    sudo apt install mysql-server
    sudo mysql_secure_installation

    systemctl status mysql.service   # check status

Create a database and database user that will be used as the place to store the REDCap data, for example, in MySQL use:

    CREATE DATABASE `etl`;
    CREATE USER 'etl_user'@'localhost' IDENTIFIED BY 'etlPassword';
    GRANT ALL ON `etl`.* TO 'etl_user'@'localhost';

### Step 3 (Optional) - Set up SQLite

This needs to be set up if you want to load your extracted data into a SQLite database.

Example commands for setting up SQLite on Ubuntu 16:

    sudo apt install php-sqlite3  # add PHP support for SQLite
    sudo apt install sqlite3
    sudo apt install sqlitebrowser   # optional

To create a database, in the directory where you want the database, execute, for example:

    sqlite3 etl-data.db
    # then enter ".q" to exit SQLite


### Step 4 - Get the REDCap-ETL Software

The basic Git command to get the code is:

    git clone https://github.com/IUREDCap/redcap-etl

To install the code to the **/opt/redcap-etl** directory, the following command could be used:

    sudo git clone https://github.com/IUREDCap/redcap-etl /opt/redcap-etl

Set the permissions for the new directory as desired. For example, if the process
was being run as user etl in group etl, the following command might be used on a Linux
system:

    sudo chown -R etl:etl /opt/redcap-etl


### Step 5 (Optional/Deprecated) - Set up a Configuration Project

Setting up a REDCap configuration project is optional. Configuration
can be specified using only the configuration file (see below) instead.

Using a configuration project adds complexity to the setup, and will
require an API token for REDCap-ETL to access the configuration
project. The possible advantages of using a configuration project are:

* It allows users who do not have access to the REDCap-ETL server
  to modify configuration information
* It allows users who do not have access to the server to run the
  REDCap-ETL process using REDCap's DET (Data Entry Trigger) feature
  



In REDCap, create a new project using the
"Upload a REDCap project XML file " option using the file
**projects/redcap-etl-config.xml** from REDCap-ETL downloaded
in the previous step.

Set at least all of the required fields for this project, and get a REDCap API token for the project. This token will need to be placed in your configuration
file that you will also need to set up.

To be able to set up the configuration project, you will need a data project and an API token for that project.

See [Configuration Guide](ConfigurationGuide.md) for more information.

### Step 6 (Optional/Deprecated) - Set up a Logging Project

In REDCap, create a new project using the "Upload a REDCap project XML file" option using the file **projects/redcap-etl-log.xml** from REDCap-ETL downloaded previously. Then get an API token for this project, and then set the field for this in the configuration project.



### Step 7 - Create a Configuration File

The configuration file can be used to specify your entire configuration, or it can
be used in conjunction with a configuration project.

If you are using a configuration project, your configuration file will
still need to specify at least your REDCap API's URL, and the REDCap API token
of your configuration project, so that REDCap-ETL will be able to locate your
configuration project.

The standard place to store configuration files is in the **config/**
directory of the REDCap-ETL installation. This is the default directory
that the web script installation script (see below) will search for configuration
files. However, they could be stored in any directory where REDCap-ETL
has read access.

To create a new configuration file, the file **config/config-example.ini**
can be copied and then modified.
See this file, and the [Configuration Guide](ConfigurationGuide.md)
for more information about the configuration properties.


### Step 8 (Optional) - Set up an E-mail Server

You can optionally set up an e-mail server that will be used for logging
errors using e-mail to a specified list of users.

On Ubuntu 16, for example, you can set up an e-mail server using the following command:

        sudo apt install sendmail

The script **bin/email_test.php** can be used to test if e-mail logging
works.

### Step 9 (Optional) - Set up a Data Entry Trigger

Setting up a Data Entry Trigger (DET) will allow you to run the ETL
process from REDCap. Once set up, when you save the "Run" form in the
configuration project, with the option to run the ETL process, a DET
will be generated that will execute a web script on the REDCap-ETL
server that will start the ETL process. This can be useful if there are
users who need to manually start the ETL process, but do not have
access to the REDCap-ETL server.

#### Web server setup
You need to set up a web server that supports PHP to run web scripts that will process DETs.

For example, to install the Apache web server on Ubuntu 16, use:

        sudo apt install apache2 libapache2-mod-php

#### Web script installation
To set up the Data Entry Trigger (DET) web script, that will process the
DET, use the following command:

        bin/install_web_scripts.php
    
You need to specify the directory where you want the web scripts
installed. And, if you placed you configuration file(s) in a directory
other that REDCap-ETL's config directory, you will also need to specify
a configuration directory. When this web script is run, it will recurse
through the config directory, and install the web scripts (if any)
specified in the configuration (.ini) files that it finds to the
specified web directory. For example:

        php install_web_scripts.php -w /var/www/html
    
would install all web scripts specified in configuration (.ini) files in
REDCap-ETL's config directory to the /var/www/html directory.

#### Configure the DET in REDCap
You need to configure the DET in REDCap. To do this:

1. Go to **Project Setup** for your configuration project in REDCap.
2. In the **Enable optional modules and customization** section, click
   on the **Additional customizations** button.
3. In the customizations dialog, check the **Data Entry Trigger** box,
   and enter the URL for your installed web script.
4. Click on the **Save** button


Running the ETL Process
-------------------------------------

There are 3 ways to run REDCap-ETL:

1. __Manual.__ Execute the **bin/redcap_etl.php** command manually on the server
2. __DET.__ Generate a DET (Data Entry Trigger) by saving the "Run" form of
   the configuration project, with the option to run ETL set
3. __Scheduled.__ Set up a cron job to run the ETL process at specific recurring times


### Running ETL Manually

To run the ETL process manually, you need to run the redcap_etl.php script
and specify the configuration file to use, for example:

        /opt/redcap-etl/bin/redcap_etl.php -c /opt/redcap-etl/config/visits.ini
In the example above:

* `/opt/redcap-etl` is the directory where REDCap-ETL was installed on
  the server
* `/opt/redcap-etl/config/visits.ini` is a configuration file set up
  by a user that specifies an ETL process
  
Depending on how your server is set up, you may need to use
`php /opt/redcap-etl/bin/redcap_etl.php ...` to run
the command.

### Running ETL Using REDCap's Data Entry Triggers

To use REDCap's DET (Data Entry Trigger) feature to run the ETL process,
you need to first set this up as described above. Once the setup has
been completed, in REDCap:

1. Open the configuration project for the ETL process that you want to run
2. Edit the "Run" form, and for the "Run on Save" field, select the
   option that specifies that the ETL process should be run
3. Save the "Run" form


### Running ETL at Regularly Scheduled Times

On Linux systems, you should be able to set up a cron job to run
ETL processes on a regularly scheduled basis. 

Here is an example crontab entry for a cron job to run the ETL process:

    0 2 * * * cd /opt/redcap-etl/bin; php ./redcap_etl.php \
        -c /opt/redcap-etl/config/visits.ini

For this example:

* `0 2 * * *` indicates that the ETL process will be run at 2:00am
  every day. See cron documentation for more details.
* `/opt/redcap-etl` is the directory where REDCap-ETL has been installed
  in this case
* `redcap_etl.php` is the standard REDCap-ETL script for running the
  ETL process
* `/opt/redcap-etl/config/visits.ini` is the configuration file
  for the ETL process that has been set up by the user



REDCap ETL Installation
==================================


REDCap ETL System Components
-------------------------------------------------------
The main componets of an installed REDCap ETL system
are shown in the diagram below, and described in the text below.

![alt text](etl-process.png "ETL Process")

* **Properties File.** This file contains the REDCap API URL and REDCap API token for the configuration project, so that it can be accessed by REDCap ETL. In addition, this file contains some server properties, such as the location for writing REDCap ETL log files.
When the ETL process runs, this is the first file that will be accessed.
* **REDCap Projects**
    * **Configuration Project.** This project contains configuration         information for the ETL process.
    * **Data Project.** This is the REDCap project that contains the data to be extracted.
    * **Logging Project.** This optional project is used for logging. The advatage of using this project is that
users who have access to REDCap, but not the REDCap ETL server, can access the log information. This disadvantages of
using this project are that it increases the complexity of the installation and slows down performace.
* **REDCap ETL.** The software that actually does the ETL.
* **Database.** There needs to be some kind of database where the extracted and transformed data
can be loaded, such as a MySQL database, although this could be as simple as a directory for CSV files.
* **Apache Web Server.** The Apache web server is necessary if you want to use REDCap's date entry triggers to check the transformation rules and/or start
the ETL (Extract Transform Load) process from REDCap.
This will allow users to run the process by editing and saving the REDCap configuration project,
so that they do not need access to the server. If you only want to run the ETL process overnight
or by manually running a command on the server, then you do not need to have a web server.
* **E-mail Server.** This is optional, and if set up, is used for e-mailing error notifications
to designated users.


Installation Steps
-------------------------------------

### Step 1 - Set up the Server

**Install the System Requirements:**
* PHP 5.6+ or 7+, with:
    * curl extension
    * openssl extension
* Subversion or Git, for retrieving the code
* MySQL (if you want to store the extracted data in a database)
* Apache web server (if you want to allow REDCap data entry triggers for starting the ETL process)
* E-mail server (if you want to support e-mail error notifications)
* In addition, you need to be using REDCap version 7+


Example commands for setting up an Ubuntu 16 system:

    sudo apt install php php-curl
    sudo apt install git
    sudo apt install subversion

### Step 2 (Optional) - Set up a MySQL Database

    sudo apt install php-mysql
    sudo phpenmod mysqli    # enable mysqli extension
    sudo phpenmod pdo_mysql # enable PDO extension for PHP 

    sudo apt install mysql-server
    sudo mysql_secure_installation

    systemctl status mysql.service   # check status

Create a database and database user that will be used as the place to store the REDCap data, for example, in MySQL use:

    CREATE DATABASE `etl`;
    CREATE USER 'etl_user'@'localhost' IDENTIFIED BY 'etlPassword';
    GRANT ALL ON `etl_user`.* TO 'etl'@'localhost';


### Step 3 - Get the REDCap ETL

Get the code:
    git clone https://github.iu.edu/ABITC/redcap-etl

    git clone https://github.iu.edu/ABITC/redcap-etl /opt/redcap-etl
    sudo svn export https://github.iu.edu/ABITC/redcap-etl/trunk /opt/redcap-etl

Set the permissions for the new directory as desired. For example, if the process
was being run as user etl in group etl, the following command might be used on a Linux
system:

    sudo chown -R etl:etl /opt/redcap-etl


### Step 4 - Set up a Configuration Project

In REDCap, create a new project using the "Upload a REDCap project XML file " option using the file **projects/redcap-etl-config.xml** from REDCap ETL downloaded in the previous step.

Set the required fields for this project, and get a REDCap API token for the project.

To be able to set up the configuration project, you will need a data project and an API token for that project.

### Step 5 (Optional) - Set up a Logging Project

In REDCap, create a new project using the "Upload a REDCap project XML file " option using the file **projects/redcap-etl-log.xml** from REDCap ETL downloaded previously. Then get an API token for this project, and then set the field for this in the configuration project.



### Step 6 - Create a Configuration File

Configuration files need to be stored in the config directory of the REDCap ETL installation.

### Step 7 (Optional) - Set up an E-mail Server

On Ubuntu 16, for example, this is all you need to do:

        sudo apt install sendmail

### Step 8 (Optional) - Set up a Web Server and Data Entry Trigger Web Script

For example, to install the Apache web server on Ubuntu 16, use:

        sudo apt install apache2 libapache2-mod-php

### Step 9 (Optional) - Set up Scheduled Runs of the ETL Process

There are 3 ways to run REDCap ETL:
1. Execute the reacap-etl.php command on the server
2. Save the "Run" form of the configuration project, with the option to run ETL set
3. Set up a cron job to run the ETL process at specific recurring times

This section discuss how to set up the third option.




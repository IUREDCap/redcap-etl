REDCap ETL Configuration
========================

REDCap ETL requires a configuration file. In addition a REDCap
configuration project can also be used. Using a configuration project
allows users who do not have access to the REDCap ETL server to set
some configuration properties and to start the ETL process.

If a configuration project is used, then REDCap ETL will use the
configuration file to locate the configuration project, so in this case
the file needs to contain:

* the URL for your REDCap API
* the API token for your REDCap configuration project

The 3 main things that need to be specified in the configuration
are:

1. The data source - where the data is extracted from.
2. The transformation rules - how to transform the data in the data source
to the target for the data load.
3. The database - where the extracted and transformed data is loaded

Most properties can be specified in both the configuration file
and configuration project. For these properties, a non-blank value in the 
configuration project will replace the value in the configuration file
(which is read first).


Data Source
-----------------------------------
The data source is specified as the REDCap API token for the data project,
i.e., the REDCap project that has the data to be extracted.


Database
-----------------------------------

The database where the data is loaded is specified as a
database connection string. This string has the following format:

        <database-connection-type> : <database-connection-values>

Currently, the supported database connection types are

* **MySQL**
* **CSV** (comma-separated values).


### MySQL
For MySQL, the format of the database connnection string is:

        MySQL:<host>:<username>:<password>:<database>

Example MySQL database connection strings:

        MySQL:localhost:etl_user:etl_password:etl_test_db

        MySQL:someplace.edu:admin:admin_password_123:etl_prod_db

### CSV
For CSV, the database connnection string format is:

        CSV:<output-directory>

For example:

        CSV:/home/redcap-etl/csv/project1


Transformation Rules
---------------------------------------------------
The transformation rules specify how the records in REDCap should be transformed into records in your database. You can enter the map in the transformation rules 
text box, or upload a file containing the rules.


### Transformation Rules syntax

Transformation rules consists of one or more TABLE statements, where each TABLE statement is followed by one or more FIELD statements.

    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>
    FIELD, <field_name>, <field_type>
    ...
    FIELD, <field_name>, <field_type>


    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>
    FIELD, <field_name>, <field_type>
    ...


#### TABLE Statements

    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>

Note: if the table is a root table, it has no parent table, and the field after the table name should be the name to used for the table's (synthetic) primary key.

<code>
_rules_ = <br/>
    rules
    test
</code>

* __rows_type__ is one of:

        ROOT
        EVENTS
        EVENTS:<suffixes>
        <suffixes>
        REPEATING_INSTRUMENTS

* __suffixes__ is in the format

        suffix1; suffix2; ...

* Root tables currently need a synthetic primary key (usually <table_name\>_id) in the place of &lt;parent_table&gt;

#### FIELD Statements

    FIELD, < field_name>, < field_type>

* field_type is one of: int, float, string, date, datetime, checkbox

NOTE: TABLE, FIELD, < rows_type>, and < field_type> are all case sensitive. TABLE, FIELD, ROOT, and EVENTS must be uppercase. Int, float, string, date, and checkbox must be lowercase.


### Transformation Rules Example

#### REDCap Data

| Event   | Variable | Record1    | Record2  | Record3 |
|---------|----------|------------|----------|---------|
| Initial | record   | 1          | 2        | 3       |
| Initial | var1     | Joe        | Jane     | Rob     |
| Initial | var2     | Smith      | Doe      | Smith   |
|         |          |            |          |         |
| evA     | var3     | 10         | 11       | 12      |
| evA     | var4     | 20         | 21       | 22      |
| evA     | var5a    | 1001       | 1021     | 1031    |
| evA     | var6a    | 2001       | 2021     | 2031    |
| evA     | var5b    | 1002       | 1022     | 1032    |
| evA     | var6b    | 2002       | 2022     | 2032    |
| evA     | var7     | 10,000     | 10,001   | 10,002  |
| evA     | var8a    | red1       | red2     | red3    |
| evA     | var8b    | green1     | green2   | green3  |
|         |          |            |          |         |
| evB     | var3     | 101        | 102      | 103     |
| evB     | var4     | 201        | 202      | 203     |
| evB     | var5a    | 3001       | 3021     | 3031    |
| evB     | var6a    | 4001       | 4021     | 4031    |
| evB     | var5b    | 3002       | 3022     | 3032    |
| evB     | var6b    | 4002       | 4022     | 4032    |
| evB     | var7     | 20,000     | 20,001   | 20,002  |
| evB     | var8a    | blue1      | blue2    | blue3   |
| evB     | var8b    | yellow1    | yellow2  | yellow3 |


#### MySQL Data

NOTE: This only shows data transformed from REDCap record 1


__Table Main__

| record_id | var1 | var2  |
|-----------|------|-------|
|         1 | Joe  | Smith |

__Table Second__

| second_id | record_id | redcap_event | var3 | var4 |
|-----------|-----------|--------------|------|------|
|         1 |         1 | evA          |   10 |   20 |
|         2 |         1 | evB          |  101 |  201 |


__Table Third__

| third_id | record_id | redcap_event | var7    |
|----------|-----------|--------------|---------|
|        1 |         1 | evA          |  10,000 |
|        2 |         1 | evB          |  20,000 |


__Table Fourth__

| fourth_id | third_id | redcap_suffix | var5  | var6  |
|-----------|----------|---------------|-------|-------|
|         1 |        1 |             a |  1001 |  2001 |
|         2 |        1 |             b |  1002 |  2002 |
|         3 |        2 |             a |  3001 |  4001 |
|         4 |        2 |             b |  3002 |  4002 |


__Table Fifth__

| fifth_id | record_id | redcap_event | redcap_suffix | var8     |
|----------|-----------|--------------|---------------|----------|
|        1 |         1 | evA          | a             | red1     |
|        2 |         1 | evA          | b             | green1   |
|        3 |         1 | evB          | a             | blue1    |
|        4 |         1 | evB          | b             | yellow1  |


* NOTE: In this example, var3/var4 need to be put in one table while var7 needs to be put in a different table, despite the fact that all three variables have the same 1-many relationship with var1/var2.

* NOTE: This syntax assumes that Events will always be used to define 1-many relationships to the root parent table. Although we might envision a more complex situation in which events are named such that some events are considered children of other events, in practice that has not been done.

* NOTE: This example does not include a situation in which a child table that uses suffixes has a parent table that also uses suffixes, but the transforming code can handle that situation.


Mapping


    TABLE,  Main, Main_id, ROOT
    FIELD,  record, int
    FIELD,  var1, string
    FIELD,  var2, string

    TABLE,  Second, Main, EVENTS
    FIELD,  var3, int
    FIELD,  var4, int

    TABLE,  Third, Main, EVENTS
    FIELD,  var7, int

    TABLE,  Fourth, Third, a;b
    FIELD,  var5, int
    FIELD,  var6, int

    TABLE,  Fifth, Main, EVENTS:a;b
    FIELD,  var8, st


REDCap ETL Configuration Properties
--------------------------------------
<table>

<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>

<tbody>

<tr>
<td>ca_cert_file</td>
<td> X </td> <td> &nbsp; </td>
<td>Certificate authority certificate file. This can be used to support
SSL verification of the connection to REDCap if your system does not
provide support for it by default</td>
</tr>

<tr>
<td>config_api_token</td>
<td> X </td> <td> &nbsp; </td>
<td>The REDCap API token for the configuration project</td>
</tr>


<tr>
<td>email_from_address</td>
<td> X </td> <td> X </td>
<td>The from address for e-mail notifications sent by REDCap ETL</td>
</tr>


<tr>
<td>email_subject</td>
<td> X </td> <td> X </td>
<td>The subject for e-mail notifications sent by REDCap ETL</td>
</tr>

<tr>
<td>email_to_list</td>
<td> X </td> <td> X </td>

<td>The to address list for e-mail notifications sent by REDCap ETL</td>
</tr>

<tr>
<td>log_file</td>
<td> X </td> <td> &nbsp; </td>
<td>File to use for logging</td>
</tr>

<tr>
<td>log_project_api_token</td>
<td> X </td> <td> X </td>
<td>REDCap API token of logging project</td>
</tr>

<tr>
<td>redcap_api_url</td>
<td> X </td> <td> &nbsp; </td>
<td>The URL for your REDCap API.
Not ending the URL with a slash (/) may cause an error.</td> 
</tr>

<tr>
<td>ssl_verify</td>
<td> X </td> <td> &nbsp; </td>
<td>Indicates is SSL verification is used for the connection to REDCap.
This defaults to true. Setting it to false is insecure.</td> 
</tr>

<tr>
<td>time_limit</td>
<td> X </td> <td> &nbsp; </td>
<td>
Maximum number of seconds the ETL process is allowed to run.
A value of zero (the default value) means there is no limit.
</td> 
</tr>

<tr>
<td>time_zone</td>
<td> X </td> <td> &nbsp; </td>
<td>
Timezone to use; see the URL below for valid values:
https://secure.php.net/manual/en/timezones.php
By default, the default PHP timezone on the system is used.
</td> 
</tr>


<tr>
<td>web_script</td>
<td> X </td> <td> &nbsp; </td>
<td>The file name to use for the installed web script used to handle
REDCap DETs (Data Entry Triggers). The file name 
should normally end with .php. This is used to allow the ETL
process to be started from REDCap. You can leave this blank if you
are not using DETs.</td> 
</tr>

</tbody>
</table>

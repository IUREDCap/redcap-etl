REDCap ETL Configuration Project Information
============================================

REDCap ETL requires a configuration project that has information including the following:

* an API token for the REDCap project that contains the actual data
* an API token for a logging project that is used by REDCap ETL to log information
* database connection information for the the database where the extracted data are to be stored
* a schema map that indicates how data should be mapped from REDCap to the databases


Database connection string
-----------------------------------
The database connection string has the following format:

*database-connection-type* : *database-connection-values*

Currently, the only supported database connection types are MySQL and CSV.


### MySSQL
For MySQL, the *database-connnection-values* format is

    <host>:<username>:<password>:<database>

Example MySQL database connection strings:

    MySQL:localhost:etl_user:etl_password:etl_test_db

    MySQL:someplace.edu:admin:admin_password_123:etl_prod_db

### CSV
For CSV, the *database-connnection-values* format is

    <output-directory>

For example:

    CSV:/home/redcapetl/csv/project1


Schema Map
---------------------------------------------------
The schema map specifies how the records in REDCap should be transformed into records in your database. You can enter the map in the Schema Map text box provided, or upload a file containing the map. An uploaded file takes precedence over the Schema Map text box.


### Relational Schema Map syntax

Schema maps consists of one or more TABLE statements, where each TABLE statement is followed by one or more FIELD statements.

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

* __rows_type__ is one of:

    ROOT
    EVENTS
    EVENTS:<suffixes>
    <suffixes>
    REPEATING_INSTRUMENTS

    where suffixes is in the format suffix1; suffix2; ...

* Root tables currently need a synthetic primary key (usually <table_name\>_id) in the place of &lt;parent_table&gt;

#### FIELD Statements

    FIELD, < field_name>, < field_type>

* field_type is one of: int, float, string, date, checkbox

NOTE: TABLE, FIELD, < rows_type>, and < field_type> are all case sensitive. TABLE, FIELD, ROOT, and EVENTS must be uppercase. Int, float, string, date, and checkbox must be lowercase.


### Schema Map Example

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


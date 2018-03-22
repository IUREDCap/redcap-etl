Transformation Rules
===================================================
The transformation rules specify how the records in REDCap should be transformed
into records in your database.


Transformation Rules Syntax
-----------------------------------------

Transformation rules consists of one or more TABLE statements, where each TABLE statement is followed by one or more FIELD statements.

    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>
    FIELD, <field_name>, <field_type>[, <database_field_name>]
    ...
    FIELD, <field_name>, <field_type>[, <database_field_name>]


    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>
    FIELD, <field_name>, <field_type>[, <database_field_name>]
    ...


#### TABLE Statements

Table statements specify the tables that should be generated in your databse.

    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>

Note: if the table is a root table, it has no parent table, and the field after the table name should be the name to used for the table's (synthetic) primary key.

* `<rows_type>` is one of:

        ROOT
        EVENTS
        EVENTS:<suffixes>
        <suffixes>
        REPEATING_INSTRUMENTS

* `<suffixes>` is in the format

        suffix1; suffix2; ...

* Root tables currently need a synthetic primary key (usually <table_name\>_id) in the place of &lt;parent_table&gt;

#### FIELD Statements

Field statements specify the fields that are generated in the tables in your database.

    FIELD, <field_name>, <field_type>[, <database_field_name>]

__`<field_name>`__ is the name of the field in REDCap.

* If __`<database_field_name>`__ is not set, __`<field_name>`__ will also be the name
  of the field in the database where the extracted data is loaded
* If __`<database_field_name>`__ is set, then it will be uses as
  the name of the field in the database where the extracted data is loaded


__`<field_type>`__ can be one of the REDCap ETL types in the table below that shows
the database types used to store the different REDCap ETL types.

| REDCap ETL Type | MySQL Type      | CSV (Spreadsheet) Type | 
| --------------- | --------------- | ---------------------- |
| int             | int             | number                 |
| float           | float           | number                 |
| char(_size_)    | char(_size_)    | text                   |
| varchar(_size_) | varchar(_size_) | text                   |
| string          | text            | text                   |
| date            | date            | datetime               |
| datetime        | datetime        | datetime               |
| checkbox        | int             | number                 |

NOTE: TABLE, FIELD, &lt;rows_type&gt;, and &lt;field_type&gt; are all case sensitive. TABLE, FIELD, ROOT, and EVENTS must be uppercase. Int, float, string, date, and checkbox must be lowercase.


Transformation Rules Examples
----------------------------------------------

## Root Table Example

This is a simple example with a single ROOT table. For this example, the project is non-longitudinal and
has one form called "Registration". The data entered are as follows:


| record_id | first_name | last_name | dob        | registration_complete  |
| --------- | ---------- | --------- | ---------- | ---------------------: |
|      1001 | Anahi	     | Gislason	 | 08-27-1973 | 2                      |
|      1002 | Marianne   | Crona     | 06-18-1958 | 2                      |
|      1003 | Ryann	     | Tillman	 | 08-28-1967 | 2                      |

The transformation rules used are:

    TABLE,registration,registration_id,ROOT
    FIELD,first_name,string
    FIELD,last_name,string
    FIELD,dob,date,birthdate

The data that get loaded into the target database after REDCap ETL is run are:
 
| registration_id | record_id | first_name | last_name | birthdate  |
| --------------- | --------- | ---------- | --------- | ---------- |
| 1               | 1001      | Anahi      | Gislason  | 1973-08-27 |
| 2               | 1002      | Marianne   | Crona     | 1958-06-18 |
| 3               | 1003      | Ryann      | Tillman   | 1967-08-28 |

In this example:

* The database field __registration_id__ (specified in the TABLE command)
  is created as an auto-incremented synthetic key
* The database fields __record_id__, __first_name__ and __last_name_
  match the REDCap fields
* The REDCap field __dob__ was renamed to __birthdate__ in the database
* The __birthdate__ database field has Y-M-D format, which is what REDCap
  returns (even though the field was defined as having M-D-Y format in REDCap)
* No transformation rule was defined for the REDCap __registration_complete__ field,
  so it does not show up in the database. You are not required to specify a
  rule for every fields, so you can specify rules for only those fields that
  you are interested in.


## Complex Example

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



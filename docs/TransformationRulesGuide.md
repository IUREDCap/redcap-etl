Transformation Rules
===================================================
The transformation rules specify how the records in REDCap are transformed
into records in your database.


Simple Transformation Rules Example
----------------------------------------
This is a simple example with a single database table. 
For this example, the project is non-longitudinal and
has one form called "Registration".

**REDCap Data**


| record_id | first_name | last_name | dob        | registration_complete |
| ---------:| ---------- | --------- | ---------- |----------------------:|
|      1001 | Anahi	     | Gislason	 | 08-27-1973 | 2                     |
|      1002 | Marianne   | Crona     | 06-18-1958 | 2                     |
|      1003 | Ryann	     | Tillman	 | 08-28-1967 | 2                     |

**Transformation Rules**

    TABLE,registration,registration_id,ROOT
    FIELD,first_name,string
    FIELD,last_name,string
    FIELD,dob,date,birthdate

**Database Table**

**registration**

| registration_id | record_id | first_name | last_name | birthdate  |
| ---------------:| --------- | ---------- | --------- | ---------- |
| 1               | 1001      | Anahi      | Gislason  | 1973-08-27 |
| 2               | 1002      | Marianne   | Crona     | 1958-06-18 |
| 3               | 1003      | Ryann      | Tillman   | 1967-08-28 |

In this example:

* The database table name is specified as **registration**
* The database table is specified with a "rows type" of ROOT, because the rows of data have
  a one-to-one mapping to record IDs, i.e., each study participant has
  one first name, one last name, and one birthdate.
* The database field **registration_id** (specified in the TABLE command)
  is created automatically as an auto-incremented synthetic key
* The database field __record_id__ represents the REDCap record ID, and is
  created automatically in the database for all tables by REDCap-ETL
* The database fields __record_id__, __first_name__ and __last_name__
  match the REDCap fields.
* The REDCap field __dob__ with type __date__, was renamed to __birthdate__ in the database
* The __birthdate__ database field has Y-M-D format, which is what REDCap
  returns (even though the field was defined as having M-D-Y format in REDCap)
* No transformation rule was defined for the REDCap **registration_complete** field,
  so it does not show up in the database. You are not required to specify a
  rule for every field, so you can specify rules for only those fields that
  you are interested in.


Transformation Rules Syntax
-----------------------------------------

Transformation rules consists of one or more TABLE statements, where each TABLE statement is followed by zero or more FIELD statements that specify what REDCap fields are
stored in the table. Each statement needs to be on its own line.

    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>
    FIELD, <field_name>, <field_type>[, <database_field_name>]
    ...
    FIELD, <field_name>, <field_type>[, <database_field_name>]


    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>
    FIELD, <field_name>, <field_type>[, <database_field_name>]
    ...

A table statement that has no fields following it will generate a table that contains only 
a synthetic primary key field and a record ID field.

The transformation rules language is line-oriented, and each line has a 
comma-separated value (CSV) syntax. This allows the transformation rules to be
edited as a spreadsheet, as long as it is saved in CSV format. Editing this way
eliminates the need to enter field separators (commas), and automatically aligns field mappings horizontally, which makes them easier to read.


#### TABLE Statements

Table statements specify the tables that should be generated in your database.

    TABLE, <table_name>, <parent_table|primary_key_name>, <rows_type>

Note: if the table is a root table, it has no parent table, and the field after the table name is the name to use for the table's (synthetic) primary key:

    TABLE, <table_name>, <primary_key_name>, ROOT
    
For non-root tables, the field after the table name is the name of its parent table.

    TABLE, <table_name>, <parent_table>, <rows_type>


The `<rows_type>` indicates the following things about the table:

1. If the table is a root table or a child table of another table.
2. What data rows from REDCap will be stored in the table.
3. What identifier/key fields the rows of the database table will contain.


The possible `<rows_type>` values are shown in the table below:


<table>
    <thead>
    <tr>
      <th>Rows Type</th><th>Description</th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td>ROOT</td>
      <td>
      This indicates that the table is a root table (it has no parent table) and
      is typically used for a table that stores REDCap fields that have
      a one-to-one relationship with
      the REDCap record ID, for example: first name, last name, birthdate.
      </td>
    </tr>
    <tr>
      <td>EVENTS</td>
      <td>
      If this rows type is specified, only REDCap values that are in rows that are "standard"
      events (i.e., from non-repeating forms that are in non-repeating events) will
      be stored in the database table. Since the same form can be in multiple (non-repeating)
      events, in general, the rows in the database table will have a many-to-one
      relationship with the record ID. For example, you might have a field
      "total_cholesterol" in events "Initial Visit" and "Final Visit", so there would
      be 2 "total_cholesterol" values per record ID.
      </td>
    </tr>
    <tr>
      <td>REPEATING_EVENTS</td>
      <td>
      This rows type indicates that only REDCap values from rows that are in
      repeating events will be stored in the database table.
      </td>
    </tr>
    <tr>
      <td>REPEATING_INSTRUMENTS</td>
      <td>
      This rows type indicates that only REDCap values that are in
      repeating instruments will be stored in the database table.
      </td>
    </tr>
    <tr>
      <td>&lt;suffixes&gt;</td>
      <td>
      This is typically used for a table that store related REDCap fields that have
      the same prefix, but different suffixes. For example, you might specify
      suffixes of "1;2;3" for fields "heart_rate1",
      "heart_rate2" and "heart_rate3" that represent different heart rate
      measurements of a participant, and would represent a many-to-one relationship
      of heart rate to the the record ID field.
      </td>
    </tr>
    <tr>
      <td>EVENTS:&lt;suffixes&gt;</td>
      <td>
      This is typically used for a table that stores related REDCap fields that have
      the same prefix, but different suffixes, that occur in multiple events in
      a longitudinal study. For example, you might specify
      suffixes of "1;2;3" for fields "heart_rate1",
      "heart_rate2" and "heart_rate3" that represent different heart rate
      measurements of a participant, and are in events "Initial Visit" and "Final Visit".
      The events would represent a many-to-one relationship with the record ID, and
      heart rate field would represent a many-to-one relationship with
      the event that contained them.
      </td>
    </tr>
    </tbody>
</table>

**&amp; operator for rows type**

For longitudinal studies, the three rows type `EVENTS`, `REPEATING_INSTRUMENTS` and `REPEATING_EVENTS` can be combined together using the `&` operator, for example:

    TABLE, visits, enrollment, EVENTS & REPEATING_EVENTS

**Suffixes**

`<suffixes>` is in the format

    <suffix1>; <suffix2>; ...


__Identifier/key fields__

REDCap-ETL will automatically create various identifier and key fields in
each database table:

* **`<primary_key>`** - a numeric synthetic key
* **`<foreign_key>`** - a numeric foreign key that references a primary key of
    the table's parent table.
    This field is created for all tables with a rows type other than `ROOT`. 
* **`<record_id>`** - the record ID from REDCap
* **`redcap_event_name`** - the REDCap unique event name for the data record in REDCap. This is
    only created if the REDCap study is longitudinal, and the table's rows type is one
    of the following: `EVENTS`, `REPEATING_EVENTS`, `REPEATING_INSTRUMENTS`,
    `EVENTS:<suffixes>`
* **`redcap_repeat_instrument`** - the REDCap instrument name for the data record in REDCap. This
    is only created for tables with the rows type `REPEATING_INSTRUMENTS`
* **`redcap_repeat_instance`** - the REDCap instance value for the data record in REDCap.
    This field is only created for tables with rows types `REPEATING_EVENTS` or
    `REPEATING_INSTRUMENTS`.
* **`redcap_suffix`** - this field contains the suffix value for the record. This field is
    only created for tables that have a rows type of `<suffixes>` or `EVENTS:<suffixes>`.





#### FIELD Statements

Field statements specify the fields that are generated in the tables in your database.

    FIELD, <field_name>, <field_type>[, <database_field_name>]

__`<field_name>`__ is the name of the field in REDCap.

* If __`<database_field_name>`__ is not set, __`<field_name>`__ will also be the name
  of the field in the database where the extracted data is loaded
* If __`<database_field_name>`__ is set, then it will be uses as
  the name of the field in the database where the extracted data is loaded


__`<field_type>`__ can be one of the REDCap-ETL types in the table below that shows
the database types used to store the different REDCap-ETL types.

| REDCap-ETL Type | MySQL Type      | CSV (Spreadsheet) Type |
| --------------- | --------------- | ---------------------- |
| int             | int             | number                 |
| float           | float           | number                 |
| char(_size_)    | char(_size_)    | text                   |
| varchar(_size_) | varchar(_size_) | text                   |
| string          | text            | text                   |
| date            | date            | datetime               |
| datetime        | datetime        | datetime               |
| checkbox        | int             | number                 |

NOTE: `TABLE`, `FIELD`, `<rows_type>`, and `<field_type>`; are all case sensitive. So, for example, `TABLE`, `FIELD`, `ROOT`, and `EVENTS` must be uppercase, and `int`, `float`, `string`, `date`, and `checkbox` must be lowercase.

---


Transformation Rules Examples
----------------------------------------------

### Events Example

In this example, the REDCap project is a longitudinal project with a registration form, and a visit form.
The visit form is used by 3 events: Visit1, Visit2 and Visit3.

**REDCap Data**

| record_id | redcap_event_name  | first_name | last_name | dob       | registration_complete | weight | height | visit_complete |
|-----------|--------------------|------------|-----------|-----------|----------------------:|-------:|-------:|---------------:|
| 1001	    | registration_arm_1 | Anahi      | Gislason  | 8/27/1973 | 2                     |        |        |                |
| 1001      | visit1_arm_1       |            |           |           |                       | 90     | 1.7    | 2              |
| 1001      | visit2_arm_1       |            |           |           |                       | 91     | 1.7    | 2              |
| 1001      | visit3_arm_1       |            |           |           |                       | 92     | 1.7    | 2              |
| 1002      | registration_arm_1 | Marianne   | Crona     | 6/18/1958 | 2                     |        |        |                |
| 1002      | visit1_arm_1       |            |           |           |                       | 88     | 1.8    | 2              |
| 1002      | visit2_arm_1       |            |           |           |                       | 88     | 1.8    | 2              |
| 1002      | visit3_arm_1       |            |           |           |                       | 87     | 1.8    | 2              |
| 1003      | registration_arm_1 | Ryann      | Tillman   | 8/28/1967 | 2                     |        |        |                |
| 1003      | visit1_arm_1       |            |           |           |                       | 100    | 1.9    | 2              |
| 1003      | visit2_arm_1       |            |           |           |                       | 102    | 1.9    | 2              |
| 1003      | visit3_arm_1       |            |           |           |                       | 105    | 1.9    | 2              |


**Transformation Rules**

    TABLE,registration,registration_id,ROOT
    FIELD,record_id,string
    FIELD,first_name,string
    FIELD,last_name,string
    FIELD,dob,date

    TABLE,visit,registration,EVENTS
    FIELD,weight,string
    FIELD,height,string

**Database Tables**

**registration**

| registration_id | record_id | first_name | last_name | birthdate  |
|----------------:|-----------|------------|-----------|------------|
| 1               | 1001      | Anahi      | Gislason  | 1973-08-27 |
| 2               | 1002      | Marianne   | Crona     | 1958-06-18 |
| 3               | 1003      | Ryann      | Tillman   | 1967-08-28 |

**visits**

| visit_id | registration_id | record_id | redcap_event_name | weight | height |
|---------:|----------------:|-----------|-------------------|-------:|-------:|
| 1        | 1               | 1001      | visit1_arm_1      | 90     | 1.7    |
| 2        | 1               | 1001      | visit2_arm_1      | 91     | 1.7    |
| 3        | 1               | 1001      | visit3_arm_1      | 92     | 1.7    |
| 4        | 2               | 1002      | visit1_arm_1      | 88     | 1.8    |
| 5        | 2               | 1002      | visit2_arm_1      | 88     | 1.8    |
| 6        | 2               | 1002      | visit3_arm_1      | 87     | 1.8    |
| 7        | 3               | 1003      | visit1_arm_1      | 100    | 1.9    |
| 8        | 3               | 1003      | visit2_arm_1      | 102    | 1.9    |
| 9        | 3               | 1003      | visit3_arm_1      | 105    | 1.9    |

For the **visits** table:

* **visit_id** is the synthetic primary key automatically generated by REDCap-ETL
* **registration_id** is the foreign key automatically generated by REDCap-ETL that points
    to the parent table **registration**

---


### Complex Example

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



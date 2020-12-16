REDCap-ETL Software Architecture
=========================================

REDCap-ETL Main Classes
------------------------------

**RedCapEtl** is the main class and starting point for the ETL (Extract
Transform Load) process. An instance of it is created by the
redcap_etl.php script that is used to run the ETL process manually
or from a cron job. 
<br />

![Main REDCap-ETL Classes](redcap-etl-classes.png)


**Task.** Each Task:

* accesses one REDCap project which is represented by an **EtlRedCapProject** object
* has one **Schema** object that describes
    the database schema that the task will create in the database where data are loaded
* loads data to one database, which is represented by a **DbConnection** object
* has one **TaskConfig** object that represents the task's configuration, which specifies:
    * the REDCap project from which data are extracted (used to create is **EtlRedCapProject** object
    * the transformation rules that specify how the extracted data are transformed (used to create its
        **Schema** object)
    * the database connection information for the database where the extracted and transformed
        data are loaded (used to create its **DbConnection** object)
* has a **Logger** object used for logging


**Workflow.** Each Workflow:

* has one or more **Task** objects, that are executed in the order defined in the workflow's configuration
* has one **Schema** object for each different database where its tasks load data.
    This schema represents the merged database schema of the schemas for each of the workflow's tasks
    that load data to that database.
* has one **WorkflowConfig** object, which consists of one or more task configurations and represents
    the configuration for the workflow

---

Database Connection Classes
------------------------------------------

REDCap-ETL has a database connection class for each type of database that it supports.
All database connection classes are subclasses of the abstract **DbConnection** class, and database
connection classes that use [PDO](https://www.php.net/manual/en/book.pdo.php) (PHP Data Objects)
are also subclasses of the
abstract **PdoDbConnection** class.

![REDCap-ETL Database Connection Classes](redcap-etl-db-connections.png)


---

Transformation Rules Processing Classes
------------------------------------------

One of the more complicated parts of the ETL process is the translation of the
transformation rules into a target database schema with mapping information.
The transformation rules text is retrieved from either:

* a file on the REDCap-ETL server
* the **RulesGenerator** class (for automatically generated rules)

The **SchemaGenerator** class uses the **RulesParser** class to translate
the transformation rules text into a parse tree representation.
The **SchemaGenerator** class then uses the parse tree representation to
generate a **Schema** object that contains the target database schema
with mapping information. The **Schema** object also contains table rows
that are used to store the transformed data from REDCap before it is
loaded into the target database.
<br />

![REDCap-ETL Schema Generation](redcap-etl-schema-generation.png)

<br />

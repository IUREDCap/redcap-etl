Longitudinal REDCap Study Transformation
===================================================
The following example illustrates use of REDCap-ETL for a longitudinal REDCap study.  

#### Study Structure

The longitudinal study in our example has five instruments:
1. Enrollment
2. Contact Information
3. Emergency Contacts
4. Weight
5. Cardiovascular

The instruments above are designated to four events defined in the study as follows:

| Data Collection Instrument | Enrollment | Baseline | Visit | Home Visit |
|----------------------------|------------|----------|-------|------------|
| Enrollment          | X |   |   |   |
| Contact Information | X |   |   |   |
| Emergency Contacts  | X |   |   |   |
| Weight              |   | X | X | X |
| Cardiovascular      |   | X | X | X |

The study has 3 arms with identical instrument designation, but this does not affect the process of REDCap-ETL in any way.

Events **Enrollment** and **Baseline** are non-repeating events. 

**Visit** repeats entire event with both _Weight_ and _Cardiovascular_ instruments. 
**Home Visit** repeats _Weight_ and _Cardiovascular_ instruments independently of each other.

A new record starts with entering data in Enrollment; it is considered ROOT table in our target database. Transformation of data stored in Enrollment is straightforward:

    TABLE,enrollment,enrollment_id,ROOT
    FIELD,registration_date,date
    FIELD,first_name,string
    FIELD,last_name,string
    FIELD,birthdate,date
    ...
    


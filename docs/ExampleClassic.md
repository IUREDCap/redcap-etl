Classic REDCap Study Transformation
===================================================
The following examples illustrate use of REDCap-ETL for a classic REDCap study.  

### Example 1 - Non-repeating forms

This classic study has three forms-
1. Demography
2. Levels
3. Bloodpressure

| record_id | firstname | lastname | demography_complete | cholesterol | glucose | platelets | levels_complete | systolic_bp | diastolic_bp | bloodpressure_complete| 
|-----------|-----------|----------|---------------------|-------------|---------|-----------|-----------------|-------------|--------------|-----------------------|
| 1         | Jonathan  | Smith    | Incomplete (0)      | 198         | 75      | 330       | Complete (2)    | 127         | 82           | Complete (2)          | 
| 2         | Mark      | Briggs   | Incomplete (0)      | 202         | 84      | 363       | Complete (2)    | 114         | 85           | Incomplete (0)        |

 
Following transformation rules will move data per record into three tables-

    TABLE,demography,demography_id,ROOT
    FIELD,firstname,string
    FIELD,lastname,string

    TABLE,levels,levels_id,ROOT
    FIELD,cholesterol,int
    FIELD,glucose,int
    FIELD,platelets,int

    TABLE,bloodpressure,bloodpressure_id,ROOT
    FIELD,systolicbp,int
    FIELD,diastolicbp,int


#### MySQL Data


__Table demography__

| record_id | firstname | lastname |
|-----------|-----------|----------|
|         1 | Jonathan  | Smith    |
|         2 | Mark      | Briggs   |

__Table levels__

| levels_id | record_id | cholesterol | glucose | platelets |
|-----------|-----------|-------------|---------|-----------|
|         1 |         1 | 198         | 75      | 330       |
|         2 |         2 | 202         | 84      | 363       |


__Table bloodpressure__

| bloodpressure_id | record_id | systolic_bp | diastolic_bp |
|------------------|-----------|-------------|--------------|
| 1 | 1 | 127 | 82 |
| 2 | 1 | 114 | 85 |


### Example 2 - Classic study with repeating forms

This classic study has three forms-
1. Demography
2. Levels
3. Bloodpressure

Levels and Bloodpressure are repeating forms.


| record_id | redcap_repeat_instrument | redcap_repeat_instance | firstname | lastname | demography_complete | cholesterol | glucose | platelets | levels_complete | systolicbp | diastolicbp | bloodpressure_complete |
|-----------| ------------------------ | ---------------------- | --------- | -------- | ------------------- | ----------- | ------- | --------- | --------------- |  ---------- | ----------- | ----------------- |
| 1 |          |   | Jonathan | Smith  | Incomplete (0) |     |    |     |                |    |     |                |
| 1 | levels   | 1 |          |        |                | 198 | 75 | 330 | Incomplete (0) |    |     |                |
| 1 | levels   | 2 |          |        |                | 210 | 78 | 222 | Incomplete (0) |    |     |                |
| 1 | physical | 1 |          |        |                |     |    |     |                | 82 | 127 | Incomplete (0) |
| 1 | physical | 2 |          |        |                |     |    |     |                | 87 | 132 | Complete (2)   |
| 2 |          |   | Mark     | Briggs | Incomplete (0) |     |    |     |                |    |     |                |
| 2 | levels   | 1 |          |        |                | 202 | 84 | 363 | Incomplete (0) |    |     |                |
| 2 | physical | 1 |          |        |                |     |    |     |                | 78 | 120 | Incomplete (0) |

 
Following transformation rules will move data per record into three tables-

    TABLE,demography,demography_id,ROOT
    FIELD,firstname,string
    FIELD,lastname,string

    TABLE,levels,demography,REPEATING_INSTRUMENTS
    FIELD,cholesterol,int
    FIELD,glucose,int
    FIELD,platelets,int

    TABLE,bloodpressure,demography,REPEATING_INSTRUMENTS
    FIELD,systolicbp,int
    FIELD,diastolicbp,int

NOTE: To establish relationship between ROOT table and REPEATING_INSTRUMENTS, the transformation rules have 'demography' listed as the parent table for levels and bloodpressure.

#### MySQL Data


__Table demography__

| record_id | firstname | lastname |
|-----------|-----------|----------|
|         1 | Jonathan  | Smith    |
|         2 | Mark      | Briggs   |

__Table levels__

| levels_id | record_id | redcap_repeat_instrument | redcap_repeat_instance | cholesterol | glucose | platelets |
|-----------|-----------|-----------|-----------|-------------|---------|-----------|
|         1 |         1 | levels   | 1 |198 | 75 | 330 | 
|         2 |         1 | levels   | 2 |210 | 78 | 222 | 
|         3 |         2 | levels   | 1 |202 | 84 | 363 |

__Table bloodpressure__

| bloodpressure_id | record_id | redcap_repeat_instrument | redcap_repeat_instance | systolic_bp | diastolic_bp |
|----------|-----------|-------------|--------------|--------------|--------------|
|        1 |         1 | physical | 1 | 82 | 127 |
|        2 |         1 | physical | 2 | 87 | 132 |
|        3 |         2 | physical | 1 | 78 | 120 |
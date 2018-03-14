<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\Schema;
use IU\REDCapETL\Schema\Table;

use IU\REDCapETL\Database\DBConnectFactory;

/**
 * Transformation rules used for transforming data from
 * the extracted format to the load format used in the
 * target database.
 */
class TransformationRules
{
    # Separators; example: "TABLE,  Fifth, Main, EVENTS:a;b"
    const ELEMENTS_SEPARATOR  = ',';
    const ROWS_DEF_SEPARATOR   = ':';   # row type separator
    const SUFFIXES_SEPARATOR   = ';';

    # Suffix for the REDCap field indicated the form has been completed
    const FORM_COMPLETE_SUFFIX = '_complete';


    const ELEMENT_TABLE       = 'TABLE';
    const ELEMENT_FIELD       = 'FIELD';
    
    const RULE_TYPE_POS         = 0;
    const TABLE_NAME_POS      = 1;
    const TABLE_PARENT_POS    = 2;
    const TABLE_ROWSTYPE_POS  = 3;
    const FIELD_NAME_POS      = 1;
    const FIELD_TYPE_POS      = 2;

    # Table types (non-ROOT types represent 1:many relationships)
    const ROOT                  = 'ROOT';
    const EVENTS                = 'EVENTS';
    const SUFFIXES              = 'SUFFIXES';
    const REPEATING_INSTRUMENTS = 'REPEATING_INSTRUMENTS';

    # Parse status
    const PARSE_VALID = 'valid';
    const PARSE_ERROR = 'error';
    const PARSE_WARN  = 'warn';


    private $rules;

    private $lookupChoices;
    private $lookupTable;
    private $lookupTableIn;

    private $logger;
    private $tablePrefix;

    /**
     * Constructor.
     *
     * @param string $rules the transformation rules
     */
    public function __construct($rules)
    {
        $this->rules = $rules;
    }


    /**
     * Parses the tranformation rules and returns a schema
     *
     * @return string if successful, return PARSE_VALID, if not successful,
     *    return a string with feedback about problems in parsing the transformationRules
     */
    public function parseOld($dataProject, $tablePrefix, $logger)
    {
        $this->tablePrefix = $tablePrefix;
        $this->logger      = $logger;
        $recordIdFieldName = $dataProject->getRecordIdFieldName();

        $fieldNames        = $dataProject->getFieldNames();

        $redCapFields      = $dataProject->getFieldNames();
        // Remove each instrument's completion field from the field list
        foreach ($redCapFields as $fieldName => $val) {
            if (preg_match('/'.self::FORM_COMPLETE_SUFFIX.'$/', $fieldName)) {
                unset($redCapFields[$fieldName]);
            }
        }


        $this->lookupChoices = $dataProject->getLookupChoices();

        $this->createLookupTable();

        $parsedLineCount = 0;
        $info = '';
        $warnings = '';
        $errors = '';

        $schema = new Schema();

        // Log how many fields in REDCap could be parsed
        $msg = "Found ".count($redCapFields)." fields in REDCap.";
        $logger->logInfo($msg);
        $info .= $msg."\n";

        // Break the transformationRules into multiple lines
        $lines = preg_split('/\r\n|\r|\n/', $this->rules);

        // Foreach line in the Schema Map
        foreach ($lines as $line) {
            #print "line: $line\n"; // Jim

            // If line is nothing but whitespace and commas, skip it
            if (preg_match('/^[\s,]*$/', $line)) {
                continue;
            }

            // Get elements of the line, trimmed
            // trim removes leading and trailing whitespace
            $elements = array_map('trim', explode(self::ELEMENTS_SEPARATOR, $line));

            #print 'LINE: '.$line."\n";

            // Check first element
            switch ($elements[self::RULE_TYPE_POS]) {
                // Start with Table?
                case self::ELEMENT_TABLE:
                    // Retrieve Table parameters
                    $parentTableName = $tablePrefix
                        . $this->cleanTableName($elements[self::TABLE_PARENT_POS]);

                    $tableName = $tablePrefix
                        . $this->cleanTableName($elements[self::TABLE_NAME_POS]);

                    $rowsDef = $this->cleanRowsDef($elements[self::TABLE_ROWSTYPE_POS]);

                    // Validate all Table parameters found
                    if (($this->isEmptyString($parentTableName)) ||
                            ($this->isEmptyString($tableName)) ||
                            ($this->isEmptyString($rowsDef))) {
                        $msg = "Missing one or more parameters in line: '".$line."'";
                        $logger->logInfo($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }

                    list($rowsType, $suffixes) = $this->parseRowsDef($rowsDef);

                    // Validate the given rows type
                    if (false === $rowsType) {
                        $msg = "Invalid rows type in line: '".$line."'";
                        $logger->logInfo($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }

                    // Find parent Table
                    $parentTable = $schema->getTable($parentTableName);

                    // Create a new Table
                    $table = new Table(
                        $tableName,
                        $parentTable,
                        $rowsType,
                        $suffixes,
                        $recordIdFieldName
                    );

                    #---------------------------------------------------------
                    # Add the record ID field as a field for all tables
                    # (unless the primary key or foreign key has the same
                    # name).
                    #
                    # Note that it looks like this really needs to be added
                    # as a string type, because even if it is specified as
                    # an Integer in REDCap, there will be no length
                    # restriction (unless a min and max are explicitly
                    # specified), so a value can be entered that the
                    # database will not be able to handle.
                    #---------------------------------------------------------
                    if ($table->primary !== $recordIdFieldName) {
                        $field = new Field($recordIdFieldName, FieldType::STRING);
                        $table->addField($field);
                    }

                    // Depending on type of table, add output fields to represent
                    // which iteration of a field's value is stored in a row of
                    // the table
                    switch ($rowsType) {
                        case RedCapEtl::BY_EVENTS:
                            $field = new Field(RedCapEtl::COLUMN_EVENT, RedCapEtl::COLUMN_EVENT_TYPE);
                            $table->addField($field);
                            break;

                        case RedCapEtl::BY_REPEATING_INSTRUMENTS:
                            $field = new Field(
                                RedCapEtl::COLUMN_REPEATING_INSTRUMENT,
                                RedCapEtl::COLUMN_REPEATING_INSTRUMENT_TYPE
                            );
                            $table->addField($field);
                            $field = new Field(
                                RedCapEtl::COLUMN_REPEATING_INSTANCE,
                                RedCapEtl::COLUMN_REPEATING_INSTANCE_TYPE
                            );
                            $table->addField($field);
                            break;

                        case RedCapEtl::BY_SUFFIXES:
                            $field = new Field(RedCapEtl::COLUMN_SUFFIXES, RedCapEtl::COLUMN_SUFFIXES_TYPE);
                            $table->addField($field);
                            break;

                        case RedCapEtl::BY_EVENTS_SUFFIXES:
                            $field = new Field(RedCapEtl::COLUMN_EVENT, RedCapEtl::COLUMN_EVENT_TYPE);
                            $table->addField($field);
                            $field = new Field(RedCapEtl::COLUMN_SUFFIXES, RedCapEtl::COLUMN_SUFFIXES_TYPE);
                            $table->addField($field);
                            break;

                        default:
                            break;
                    }

                    // Add Table to Schema
                    $schema->addTable($table);

                    // If parent_table exists
                    #OLD: if (is_a($parentTable, 'Table')) {
                    if (is_a($parentTable, Table::class)) {
                        // Add a foreign key
                        $table->setForeign($parentTable);

                        // Add as a child of parent table
                        $parentTable->addChild($table);
                    }
                    break;

                // Start with Field?
                case self::ELEMENT_FIELD:
                    //------------------------------------------------------------
                    // Retrieve Field parameters
                    $fieldName = $this->cleanFieldName($elements[self::FIELD_NAME_POS]);
                    $fieldType = $this->cleanFieldType($elements[self::FIELD_TYPE_POS]);

                    //------------------------------------------------------------
                    // Validate all Field parameters found
                    if (($this->isEmptyString($fieldName)) || ($this->isEmptyString($fieldType))) {
                        $msg = "Missing one or more parameters in line: '".$line."'";
                        $this->log($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }


                    // Validate the given field type
                    if (! FieldType::isValid($fieldType)) {
                        $msg = "Invalid field_type in line: '".$line."'";
                        $this->log($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }

                    //------------------------------------------------------------
                    // Determine which fields need to be made
                    //------------------------------------------------------------

                    // If this is a checkbox field
                    $fields = array();
                    if ($fieldType === FieldType::CHECKBOX) {
                        // For a checkbox in a Suffix table
                        if ((RedCapEtl::BY_SUFFIXES === $table->rowsType)
                                || (RedCapEtl::BY_EVENTS_SUFFIXES === $table->rowsType)) {
                            // Lookup the choices using any one of the valid suffixes
                            $suffixes = $table->getPossibleSuffixes();
                            $lookupFieldName = $fieldName.$suffixes[0];
                        } else {
                            $lookupFieldName = $fieldName;
                        }

                        // Foreach category of the checkbox field
                        foreach ($this->lookupChoices[$lookupFieldName] as $cat => $label) {
                            // Form the variable name for this category
                            $fields[$fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$cat]
                                = FieldType::INT;
                        }
                    } else {
                        // Process a single field
                        $fields[$fieldName] = $fieldType;
                    }

                    //------------------------------------------------------------
                    // Process each field
                    //------------------------------------------------------------
                    # OLD:
                    # $fieldNames = $this->dataProject->get_fieldnames();
                    # Set above now...

                    foreach ($fields as $fname => $ftype) {
                        //-------------------------------------------------------------
                        // !SUFFIXES: Prep for and warn that map field is not in REDCap
                        //-------------------------------------------------------------
                        if ((RedCapEtl::BY_SUFFIXES !== $table->rowsType) &&
                                (RedCapEtl::BY_EVENTS_SUFFIXES !== $table->rowsType) &&
                                $fname !== 'redcap_data_access_group' &&
                                (empty($fieldNames[$fname]))) {
                            $msg = "Field not found in REDCap: '".$fname."'";
                            $logger->logInfo($msg);
                            $warnings .= $msg."\n";
                            continue 3;
                        }


                        //------------------------------------------------------------
                        // SUFFIXES: Prep for warning that map field is not in REDCap
                        //           Prep for warning that REDCap field is not in Map
                        //------------------------------------------------------------

                        // For fields in a SUFFIXES table, use the possible suffixes,
                        // including looking up the tree of parent tables, to look
                        // for at least one matching field in the exportfieldnames
                        if ((RedCapEtl::BY_SUFFIXES === $table->rowsType)
                                || (RedCapEtl::BY_EVENTS_SUFFIXES === $table->rowsType)) {
                            $possibles = $table->getPossibleSuffixes();

                            $fieldFound = false;

                            // Foreach possible suffix, is the field found?
                            foreach ($possibles as $sx) {
                                // In case this is a checkbox field
                                if ($fieldType === FieldType::CHECKBOX) {
                                    // Separate root from category
                                    list($rootName, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                    // Form the exported field name
                                    $exportFname = $rootName.$sx.RedCapEtl::CHECKBOX_SEPARATOR.$cat;

                                    // Form the metadata field name
                                    // Checkbox fields have a single metadata field name, but
                                    // (usually) multiple exported field names
                                    $metaFname = $rootName.$sx;
                                } else {
                                    // Otherwise, just append suffix
                                    $exportFname = $fname.$sx;
                                    $metaFname = $fname.$sx;
                                }


                                //--------------------------------------------------------------
                                // SUFFIXES: Remove from warning that REDCap field is not in Map
                                //--------------------------------------------------------------
                                if (!empty($fieldNames[$exportFname])) {
                                    $fieldFound = true;

                                    // Remove this field from the list of fields to be mapped
                                    unset($redCapFields[$exportFname]);
                                }
                            } // Foreach possible suffix

                            //------------------------------------------------------------
                            // SUFFIXES: Warn that map field is not in REDCap
                            //------------------------------------------------------------
                            if (false === $fieldFound) {
                                $msg = "Suffix field not found in REDCap: '".$fname."'";
                                $this->log($msg);
                                $warnings .= $msg."\n";
                                continue 2;
                            }
                        } else {
                            //------------------------------------------------------------
                            // !SUFFIXES: Prep for warning that REDCap field is not in Map
                            //------------------------------------------------------------

                            // Not BY_SUFFIXES, and field was found

                            // In case this is a checkbox field
                            if ($fieldType === FieldType::CHECKBOX) {
                                // Separate root from category
                                list($rootName, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                // Form the metadata field name
                                // Checkbox fields have a single metadata field name, but
                                // (usually) multiple exported field names
                                $metaFname = $rootName;
                            } else {
                                // $metaFname is redundant here, but used later when
                                // deciding whether or not to create rows in Lookup
                                $metaFname = $fname;
                            }

                            //---------------------------------------------------------------
                            // !SUFFIXES: Remove from warning that REDCap field is not in Map
                            //---------------------------------------------------------------

                            // Remove this field from the list of fields to be mapped
                            unset($redCapFields[$fname]);
                        }
        
                        if ($fname !== $recordIdFieldName) {
                            // Create a new Field
                            $field = new Field($fname, $ftype);

                            // Add Field to current Table (error if no current table)
                            $table->addField($field);

                            // If this field has category/label choices
                            if (array_key_exists($metaFname, $this->lookupChoices)) {
                                $this->makeLookupTable($table->name, $metaFname);
                                $field->usesLookup = $metaFname;
                                $table->usesLookup = true;
                            }
                        }
                    } // End foreach field to be created
                    break;

                // If empty or something else, skip to next line
                default:
                    $msg = "Don't recognize line type in Schema Map: '".$line."'";
                    $this->log($msg);
                    $errors .= $msg."\n";
                    break;
            } // End switch

            // If we've reached this point, there was nothing wrong with the line
            $parsedLineCount++;
        } // End foreach
        if (1 > $parsedLineCount) {
            $msg = "Found no lines in Schema Map";
            $this->log($msg);
            $errors .= $msg."\n";
        }

        // Log how many fields in REDCap could be parsed
        $msg = "Found ".count($redCapFields)." unmapped fields in REDCap.";
        $logger->logInfo($msg);

        // Set warning if count of remaining redcap fields is above zero
        if (0 < count($redCapFields)) {
            $warnings .= $msg."\n";

            // List fields, if count is five or less
            if (10 > count($redCapFields)) {
                $msg = "Unmapped fields: ".  implode(', ', array_keys($redCapFields));
                $logger->logInfo($msg);
                $warnings .= $msg;
            }
        }

        $messages = array();
        if ('' !== $errors) {
            $messages = array(self::PARSE_ERROR,$errors.$info.$warnings);
        } elseif ('' !== $warnings) {
            $messages = array(self::PARSE_WARN,$info.$warnings);
        } else {
            $messages = array(self::PARSE_VALID,$info);
        }

        return array($schema, $this->lookupTable, $messages);
    }



    public function parse($dataProject, $tablePrefix, $logger)
    {
        $this->tablePrefix = $tablePrefix;
        $this->logger      = $logger;
        $recordIdFieldName = $dataProject->getRecordIdFieldName();

        $fieldNames        = $dataProject->getFieldNames();

        $redCapFields      = $dataProject->getFieldNames();
        // Remove each instrument's completion field from the field list
        foreach ($redCapFields as $fieldName => $val) {
            if (preg_match('/'.self::FORM_COMPLETE_SUFFIX.'$/', $fieldName)) {
                unset($redCapFields[$fieldName]);
            }
        }


        $this->lookupChoices = $dataProject->getLookupChoices();

        $this->createLookupTable();

        $parsedLineCount = 0;
        $info = '';
        $warnings = '';
        $errors = '';

        $schema = new Schema();

        // Log how many fields in REDCap could be parsed
        $msg = "Found ".count($redCapFields)." fields in REDCap.";
        $logger->logInfo($msg);
        $info .= $msg."\n";

        $table = null;
        
        $parsedRules = $this->parseRules($this->rules);
        
        # Add errors from parsing to errors string
        foreach ($parsedRules as $rule) {
            foreach ($rule->getErrors() as $error) {
                $errors .= $error."\n";
            }
        }
            
        // Process each rule from first to last
        foreach ($parsedRules as $rule) {
            if ($rule->hasErrors()) {
                ; // log here ????????
            } elseif ($rule instanceof TableRule) {
                // CHANGE THIS CODE TO $table = createTableFromRule($rule, $tablePrefix, $recordIdFieldName); ???
              
                // Retrieve Table parameters
                $parentTableName = $tablePrefix . $rule->parentTable;
                $tableName       = $tablePrefix . $rule->tableName;
                $rowsType        = $rule->rowsType;
                $suffixes        = $rule->suffixes;

                // Find parent Table
                $parentTable = $schema->getTable($parentTableName);

                // Create a new Table
                $table = new Table(
                    $tableName,
                    $parentTable,
                    $rowsType,
                    $suffixes,
                    $recordIdFieldName
                );

                #---------------------------------------------------------
                # Add the record ID field as a field for all tables
                # (unless the primary key or foreign key has the same
                # name).
                #
                # Note that it looks like this really needs to be added
                # as a string type, because even if it is specified as
                # an Integer in REDCap, there will be no length
                # restriction (unless a min and max are explicitly
                # specified), so a value can be entered that the
                # database will not be able to handle.
                #---------------------------------------------------------
                if ($table->primary !== $recordIdFieldName) {
                    $field = new Field($recordIdFieldName, FieldType::STRING);
                    $table->addField($field);
                }

                // Depending on type of table, add output fields to represent
                // which iteration of a field's value is stored in a row of
                // the table
                switch ($rowsType) {
                    case RedCapEtl::BY_EVENTS:
                        $field = new Field(RedCapEtl::COLUMN_EVENT, RedCapEtl::COLUMN_EVENT_TYPE);
                        $table->addField($field);
                        break;

                    case RedCapEtl::BY_REPEATING_INSTRUMENTS:
                        $field = new Field(
                            RedCapEtl::COLUMN_REPEATING_INSTRUMENT,
                            RedCapEtl::COLUMN_REPEATING_INSTRUMENT_TYPE
                        );
                        $table->addField($field);
                        $field = new Field(
                            RedCapEtl::COLUMN_REPEATING_INSTANCE,
                            RedCapEtl::COLUMN_REPEATING_INSTANCE_TYPE
                        );
                        $table->addField($field);
                        break;

                    case RedCapEtl::BY_SUFFIXES:
                        $field = new Field(RedCapEtl::COLUMN_SUFFIXES, RedCapEtl::COLUMN_SUFFIXES_TYPE);
                        $table->addField($field);
                        break;

                    case RedCapEtl::BY_EVENTS_SUFFIXES:
                        $field = new Field(RedCapEtl::COLUMN_EVENT, RedCapEtl::COLUMN_EVENT_TYPE);
                        $table->addField($field);
                        $field = new Field(RedCapEtl::COLUMN_SUFFIXES, RedCapEtl::COLUMN_SUFFIXES_TYPE);
                        $table->addField($field);
                        break;

                    default:
                        break;
                }

                # Add Table to Schema
                $schema->addTable($table);

                # If parent_table exists
                if (is_a($parentTable, Table::class)) {
                    $table->setForeign($parentTable);  # Add a foreign key
                    $parentTable->addChild($table);    # Add as a child of parent table
                }
            } elseif ($rule instanceof FieldRule) {
                if ($table == null) {
                    break; // table not set, probably error with table rule
                }
                
                //------------------------------------------------------------
                // Determine which fields need to be made
                //------------------------------------------------------------

                $fieldName = $rule->redCapFieldName;
                $fieldType = $rule->dbFieldType;
                
                $fields = array();
                
                // If this is a checkbox field
                if ($fieldType === FieldType::CHECKBOX) {
                    // For a checkbox in a Suffix table
                    if ((RedCapEtl::BY_SUFFIXES === $table->rowsType)
                            || (RedCapEtl::BY_EVENTS_SUFFIXES === $table->rowsType)) {
                        // Lookup the choices using any one of the valid suffixes
                        $suffixes = $table->getPossibleSuffixes();
                        $lookupFieldName = $fieldName.$suffixes[0];
                    } else {
                        $lookupFieldName = $fieldName;
                    }

                    // Foreach category of the checkbox field
                    foreach ($this->lookupChoices[$lookupFieldName] as $cat => $label) {
                        // Form the variable name for this category
                        $fields[$fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$cat] = FieldType::INT;
                    }
                } else {
                    // Process a single field
                    $fields[$fieldName] = $fieldType;
                }

                //------------------------------------------------------------
                // Process each field
                //------------------------------------------------------------
                # OLD:
                # $fieldNames = $this->dataProject->get_fieldnames();
                # Set above now...

                foreach ($fields as $fname => $ftype) {
                    //-------------------------------------------------------------
                    // !SUFFIXES: Prep for and warn that map field is not in REDCap
                    //-------------------------------------------------------------
                    if ((RedCapEtl::BY_SUFFIXES !== $table->rowsType) &&
                            (RedCapEtl::BY_EVENTS_SUFFIXES !== $table->rowsType) &&
                            $fname !== 'redcap_data_access_group' &&
                            (empty($fieldNames[$fname]))) {
                        $msg = "Field not found in REDCap: '".$fname."'";
                        $logger->logInfo($msg);
                        $warnings .= $msg."\n";
                        continue 2; //continue 3;
                    }


                    //------------------------------------------------------------
                    // SUFFIXES: Prep for warning that map field is not in REDCap
                    //           Prep for warning that REDCap field is not in Map
                    //------------------------------------------------------------

                    // For fields in a SUFFIXES table, use the possible suffixes,
                    // including looking up the tree of parent tables, to look
                    // for at least one matching field in the exportfieldnames
                    if ((RedCapEtl::BY_SUFFIXES === $table->rowsType)
                             || (RedCapEtl::BY_EVENTS_SUFFIXES === $table->rowsType)) {
                        $possibles = $table->getPossibleSuffixes();

                        $fieldFound = false;

                        // Foreach possible suffix, is the field found?
                        foreach ($possibles as $sx) {
                            // In case this is a checkbox field
                            if ($fieldType === FieldType::CHECKBOX) {
                                // Separate root from category
                                list($rootName, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                // Form the exported field name
                                $exportFname = $rootName.$sx.RedCapEtl::CHECKBOX_SEPARATOR.$cat;

                                // Form the metadata field name
                                // Checkbox fields have a single metadata field name, but
                                // (usually) multiple exported field names
                                $metaFname = $rootName.$sx;
                            } else {
                                // Otherwise, just append suffix
                                $exportFname = $fname.$sx;
                                $metaFname = $fname.$sx;
                            }

                            //--------------------------------------------------------------
                            // SUFFIXES: Remove from warning that REDCap field is not in Map
                            //--------------------------------------------------------------
                            if (!empty($fieldNames[$exportFname])) {
                                $fieldFound = true;
                                 // Remove this field from the list of fields to be mapped
                                unset($redCapFields[$exportFname]);
                            }
                        } // Foreach possible suffix

                        //------------------------------------------------------------
                        // SUFFIXES: Warn that map field is not in REDCap
                        //------------------------------------------------------------
                        if (false === $fieldFound) {
                            $msg = "Suffix field not found in REDCap: '".$fname."'";
                            $this->log($msg);
                            $warnings .= $msg."\n";
                            break; // continue 2;
                        }
                    } else {
                        //------------------------------------------------------------
                        // !SUFFIXES: Prep for warning that REDCap field is not in Map
                        //------------------------------------------------------------

                        // Not BY_SUFFIXES, and field was found

                        // In case this is a checkbox field
                        if ($fieldType === FieldType::CHECKBOX) {
                            // Separate root from category
                            list($rootName, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                            // Form the metadata field name
                            // Checkbox fields have a single metadata field name, but
                            // (usually) multiple exported field names
                            $metaFname = $rootName;
                        } else {
                            // $metaFname is redundant here, but used later when
                            // deciding whether or not to create rows in Lookup
                            $metaFname = $fname;
                        }

                        //---------------------------------------------------------------
                        // !SUFFIXES: Remove from warning that REDCap field is not in Map
                        //---------------------------------------------------------------

                        // Remove this field from the list of fields to be mapped
                        unset($redCapFields[$fname]);
                    }
        
                    if ($fname !== $recordIdFieldName) {
                        // Create a new Field
                        $field = new Field($fname, $ftype);

                        // Add Field to current Table (error if no current table)
                        $table->addField($field);

                        // If this field has category/label choices
                        if (array_key_exists($metaFname, $this->lookupChoices)) {
                            $this->makeLookupTable($table->name, $metaFname);
                            $field->usesLookup = $metaFname;
                            $table->usesLookup = true;
                        }
                    }
                } // End foreach field to be created
            } // End if for rule types


            if (!$rule->hasErrors()) {
                $parsedLineCount++;
            }
        } // End foreach
        
        
        if (1 > $parsedLineCount) {
            $msg = "Found no lines in Schema Map";
            $this->log($msg);
            $errors .= $msg."\n";
        }

        // Log how many fields in REDCap could be parsed
        $msg = "Found ".count($redCapFields)." unmapped fields in REDCap.";
        $logger->logInfo($msg);

        // Set warning if count of remaining redcap fields is above zero
        if (0 < count($redCapFields)) {
            $warnings .= $msg."\n";

            // List fields, if count is five or less
            if (10 > count($redCapFields)) {
                $msg = "Unmapped fields: ".  implode(', ', array_keys($redCapFields));
                $logger->logInfo($msg);
                $warnings .= $msg;
            }
        }

        $messages = array();
        if ('' !== $errors) {
            $messages = array(self::PARSE_ERROR,$errors.$info.$warnings);
        } elseif ('' !== $warnings) {
            $messages = array(self::PARSE_WARN,$info.$warnings);
        } else {
            $messages = array(self::PARSE_VALID,$info);
        }

        return array($schema, $this->lookupTable, $messages);
    }


    protected function parseRules($rulesString)
    {
        $rules = array();
        
        //------------------------------------------------------
        // The rules language is a line-based language, so
        // start by breaking up the rules string into
        // separate lines (handle Windows and Linux end of
        // line conventions).
        //------------------------------------------------------
        $lines = preg_split('/\r\n|\r|\n/', $rulesString);
        
        // Process line by line
        $lineNumber = 1;
        $tableRulesCount = 0;
        foreach ($lines as $line) {
            // If line is nothing but whitespace and commas, skip it
            if (preg_match('/^[\s,]*$/', $line) === 1) {
                ; // don't do anything
            } else {
                // Get (comma-separated) values of the line, trimmed
                // trim removes leading and trailing whitespace
                $values = array_map('trim', explode(self::ELEMENTS_SEPARATOR, $line));
                $ruleType = $values[self::RULE_TYPE_POS];
                switch ($ruleType) {
                    case self::ELEMENT_TABLE:
                        $rule = $this->parseTableRule($values, $line, $lineNumber);
                        $tableRulesCount++;
                        break;
                    case self::ELEMENT_FIELD:
                        $rule = $this->parseFieldRule($values, $line, $lineNumber);
                        if ($tableRulesCount === 0) {
                            $rule->addError('Field rule specified before any Table rule on line '
                                . $lineNumber . ': "'.$line.'"');
                        }
                        break;
                    default:
                        $msg = 'Unrecognized rule type "'.$ruleType.'" on line '.$lineNumber.': "'
                                .$line.'"';
                        $rule = new Rule($line, $lineNumber);
                        $rule->addError($msg);
                        break;
                }
               
                array_push($rules, $rule);
                
                // print_r($rule); // Jim
               
                foreach ($rule->getErrors() as $error) {
                    $this->log($error);
                }
            }
            
            $lineNumber++;
        }
        
        return $rules;
    }
    
    
    private function parseTableRule($values, $line, $lineNumber)
    {
        $tableRule = new TableRule($line, $lineNumber);
        
        if (count($values) < 4) {
            $error = 'Not enough values (less than 4) on line '.$lineNumber.': "'
                    .$line.'"';
            $this->log($error);
            $tableRule->addError($error);
        } else {
            $tableRule->tableName     = $this->cleanTableName($values[self::TABLE_NAME_POS]);
            $tableRule->parentTable   = $this->cleanTableName($values[self::TABLE_PARENT_POS]);
            $tableRule->tableRowsType = $this->cleanRowsDef($values[self::TABLE_ROWSTYPE_POS]);

            if (empty($tableRule->tableName)) {
                $error = 'Missing table name on line '.$lineNumber.': "'
                    .$line.'"';
                $this->log($error);
                $tableRule->addError($error);
            } elseif (empty($tableRule->parentTable)) {
                $error = 'Missing table parent/primary key on line '.$lineNumber.': "'
                    .$line.'"';
                $this->log($error);
                $tableRule->addError($error);
            } elseif (empty($tableRule->tableRowsType)) {
                $error = 'Missing table rows type on line '.$lineNumber.': "'
                    .$line.'"';
                $this->log($error);
                $tableRule->addError($error);
            } else {
                list($rowsType, $suffixes) = $this->parseRowsDef($tableRule->tableRowsType);
                if ($rowsType === false) {
                    $tableRule->addError('Unrecognized rows type on line '.$lineNumber.': '.$line);
                } else {
                    $tableRule->rowsType = $rowsType;
                    $tableRule->suffixes = $suffixes;
                }
            }
        }
        
        return $tableRule;
    }
    
    
        
    private function parseFieldRule($values, $line, $lineNumber)
    {
        $fieldRule = new FieldRule($line, $lineNumber);
                
        if (count($values) < 3) {
            $error = 'Not enough values (less than 3) on line '.$lineNumber.': "'
                    .$line.'"';
            $this->log($error);
            $fieldRule->addError($error);
        } else {
            $fieldRule->redCapFieldName = $this->cleanFieldName($values[self::FIELD_NAME_POS]);
            $fieldRule->dbFieldType     = $this->cleanFieldType($values[self::FIELD_TYPE_POS]);
     
            // Validate all Field parameters found
            if ($this->isEmptyString($fieldRule->redCapFieldName)) {
                $msg = "Missing field name on line: '".$line."'";
                $this->log($msg);
                $fieldRule->addError($msg);
            }
            
            // Check that the field type exists and is valid
            if ($this->isEmptyString($fieldRule->dbFieldType)) {
                $msg = "Missing one or more parameters in line: '".$line."'";
                $this->log($msg);
                $fieldRule->addError($msg);
            } elseif (!FieldType::isValid($fieldRule->dbFieldType)) {
                $msg = 'Invalid field type "'.$fieldRule->dbFieldType.'" in line: "'.$line.'"';
                $this->log($msg);
                $fieldRule->addError($msg);
            }
        }
        
        return $fieldRule;
    }


    protected function cleanTableName($tableName)
    {
        return $this->generalSqlClean($tableName);
    }


    protected function cleanRowsDef($rowsDef)
    {
        return $this->generalSqlClean($rowsDef);
    }


    protected function cleanFieldName($fieldName)
    {
        return $this->generalSqlClean($fieldName);
    }


    protected function cleanFieldType($fieldType)
    {
        return $this->generalSqlClean($fieldType);
    }


    protected function generalSqlClean($input)
    {
        $cleaned = preg_replace("/[^a-zA-Z0-9_;:]+/i", "", $input);
        return $cleaned;
    }


    protected function isEmptyString($str)
    {
        return !(isset($str) && (strlen(trim($str)) > 0));
    }


    protected function parseRowsDef($rowsDef)
    {
        $rowsDef = trim($rowsDef);

        $regex = '/'.self::SUFFIXES_SEPARATOR.'/';

        $rowsType = '';
        $suffixes = array();

        list($rowsEncode, $suffixesDef) = array_pad(explode(self::ROWS_DEF_SEPARATOR, $rowsDef), 2, null);

        switch ($rowsEncode) {
            case self::ROOT:
                $rowsType = RedCapEtl::ROOT;
                break;

            case self::EVENTS:
                $suffixes = explode(self::SUFFIXES_SEPARATOR, $suffixesDef);
                $rowsType = (empty($suffixes[0])) ? RedCapEtl::BY_EVENTS : RedCapEtl::BY_EVENTS_SUFFIXES;
                break;

            case self::REPEATING_INSTRUMENTS:
                $rowsType = RedCapEtl::BY_REPEATING_INSTRUMENTS;
                break;

            case self::SUFFIXES:
                $suffixes = explode(self::SUFFIXES_SEPARATOR, $suffixesDef);
                $rowsType = (empty($suffixes[0])) ? false : RedCapEtl::BY_SUFFIXES;
                break;

            case (preg_match($regex, $rowsEncode) ? true : false):
                $suffixes = explode(self::SUFFIXES_SEPARATOR, $rowsEncode);
                $rowsType = (empty($suffixes[0])) ? false : RedCapEtl::BY_SUFFIXES;
                break;

            default:
                $rowsType = false;
        }

        return (array($rowsType,$suffixes));
    }


    protected function createLookupTable()
    {
        $this->lookupTable = new Table(
            $this->tablePrefix . RedCapEtl::LOOKUP_TABLE_NAME,
            RedCapEtl::LOOKUP_TABLE_PRIMARY_ID,
            self::ROOT,
            array()
        );

        #-----------------------------------------------
        # Create and add fields for the lookup table
        #-----------------------------------------------
        $fieldPrimary   = new Field(RedCapEtl::LOOKUP_TABLE_PRIMARY_ID, FieldType::INT);
        $fieldFieldName = new Field(RedCapEtl::LOOKUP_FIELD_TABLE_NAME, FieldType::STRING);
        $fieldTableName = new Field(RedCapEtl::LOOKUP_FIELD_FIELD_NAME, FieldType::STRING);
        $fieldCategory = new Field(RedCapEtl::LOOKUP_FIELD_CATEGORY, FieldType::STRING);
        $fieldValue    = new Field(RedCapEtl::LOOKUP_FIELD_LABEL, FieldType::STRING);
        
        $this->lookupTable->addField($fieldPrimary);
        $this->lookupTable->addField($fieldFieldName);
        $this->lookupTable->addField($fieldTableName);
        $this->lookupTable->addField($fieldCategory);
        $this->lookupTable->addField($fieldValue);
    }


    /**
     * Create a table that holds the categories and labels for a field
     * with multiple choices.
     */
    protected function makeLookupTable($tableName, $fieldName)
    {

        if (empty($this->lookupTableIn[$tableName.':'.$fieldName])) {
            $this->lookupTableIn[$tableName.':'.$fieldName] = true;

            // Foreach choice, add a row
            $currentId = 1;
            foreach ($this->lookupChoices[$fieldName] as $category => $label) {
                // Set up the table/fieldcategory/label for this choice
                $data = array(
                    RedCapEtl::LOOKUP_TABLE_PRIMARY_ID => $currentId++,
                    RedCapEtl::LOOKUP_FIELD_TABLE_NAME => $tableName,
                    RedCapEtl::LOOKUP_FIELD_FIELD_NAME => $fieldName,
                    RedCapEtl::LOOKUP_FIELD_CATEGORY => $category,
                    RedCapEtl::LOOKUP_FIELD_LABEL => $label
                );

                // Add the row, using no foreign key or suffix
                $this->lookupTable->createRow($data, '', '');
            }
        }

        return true;
    }

    protected function log($message)
    {
        $this->logger->logInfo($message);
    }

    
    /**
     * Generates default transformation rules for the
     * specified data project, sets the rules for the
     * object to the generated ones, and then returns
     * the rules.
     *
     * @return string the generated transformation rules
     */
    public function generateDefaultRules($dataProject)
    {
        $rules = '';

        $projectInfo = $dataProject->exportProjectInfo();
        $instruments = $dataProject->exportInstruments();
        $metadata    = $dataProject->exportMetadata();

        $recordId = $metadata[0]['field_name'];

        foreach ($instruments as $formName => $formLabel) {
            $primaryKey = strtolower($formName) . '_id';
            $rules .= "TABLE,".$formName.",$primaryKey,".'ROOT'."\n";

            foreach ($metadata as $field) {
                if ($field['form_name'] == $formName) {
                    $type = FieldType::STRING;
                
                    $validationType = $field['text_validation_type_or_show_slider_number'];
                    $fieldType      = $field['field_type'];

                    #print "{$validationType}\n";

                    if ($fieldType === FieldType::CHECKBOX) {
                        $type = FieldType::CHECKBOX;
                    } elseif ($validationType === FieldType::INT) { # value may be too large for db int
                        $type = FieldType::STRING;
                    } elseif ($fieldType === 'dropdown' || $fieldType === 'radio') {
                        $type = FieldType::INT;
                    } elseif (substr($validationType, 0, 5) === 'date_') {
                        # starts with 'date_'
                        $type = FieldType::DATE;
                    } elseif (substr($validationType, 0, 9) === 'datetime_') {
                        # starts with 'datetime_'
                        $type = FieldType::DATETIME;
                    }

                
                    if ($fieldType === 'descriptive' || $fieldType === 'file') {
                        ; // Don't do anything
                    } else {
                        $rules .= "FIELD,".$field['field_name'].",".$type."\n";
                    }
                }
            }
            $rules .= "\n";
        }
        $this->rules = $rules;
        return $rules;
    }
}

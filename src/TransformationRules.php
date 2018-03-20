<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\FieldTypeAndSize;
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
    # Suffix for the REDCap field indicated the form has been completed
    const FORM_COMPLETE_SUFFIX = '_complete';

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

        ###print_r($this->lookupChoices);
        
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
        
        $rulesParser = new RulesParser();
        $parsedRules = $rulesParser->parse($this->rules); // $this->parseRules($this->rules);
        
        # Add errors from parsing to errors string
        foreach ($parsedRules as $rule) {
            foreach ($rule->getErrors() as $error) {
                $errors .= $error."\n";
            }
        }
            
        // Process each rule from first to last
        foreach ($parsedRules as $rule) {
            if ($rule->hasErrors()) {
                // log the parse errors for this rule
                foreach ($rule->getErrors() as $error) {
                    $this->log($error);
                }
            } elseif ($rule instanceof TableRule) {
                // CHANGE THIS CODE TO $table = generateTableFromRule($rule, $tablePrefix, $recordIdFieldName); ???
              
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
                $fieldSize = $rule->dbFieldSize;

                $fieldTypeAndSize = new FieldTypeAndSize($fieldType, $fieldSize);
                
                $fields = array();
                
                // If this is a checkbox field
                if ($fieldType === FieldType::CHECKBOX) {
                    // For a checkbox in a Suffix table
                    if ((RedCapEtl::BY_SUFFIXES === $table->rowsType)
                            || (RedCapEtl::BY_EVENTS_SUFFIXES === $table->rowsType)) {
                        // Lookup the choices using any one of the valid suffixes
                        $suffixes = $table->getPossibleSuffixes();
                        $lookupFieldName = $fieldName.$suffixes[0];
                        ###print "\n\n";
                        ###print "lookupFieldName = {$lookupFieldName}\n";
                        ###print_r($suffixes);
                    } else {
                        $lookupFieldName = $fieldName;
                    }

                    // Foreach category of the checkbox field
                    foreach ($this->lookupChoices[$lookupFieldName] as $category => $label) {
                        // Form the variable name for this category
                        $fields[$fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$category]
                            = new FieldTypeAndSize(FieldType::INT, null);
                    }
                } else {
                    // Process a single field
                    $fields[$fieldName] = $fieldTypeAndSize;
                }

                //------------------------------------------------------------
                // Process each field
                //------------------------------------------------------------
                # OLD:
                # $fieldNames = $this->dataProject->get_fieldnames();
                # Set above now...

                foreach ($fields as $fname => $ftypeAndSize) {
                    $ftype = $ftypeAndSize->type;
                    $fsize = $ftypeAndSize->size;

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
                                list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                // Form the exported field name
                                $exportFname = $rootName.$sx.RedCapEtl::CHECKBOX_SEPARATOR.$category;

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
                            list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

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
        
                    #-----------------------------------------------------------------
                    # If the field name is the record ID field name, don't process
                    # it, because it should have already been automatically added
                    #-----------------------------------------------------------------
                    if ($fname !== $recordIdFieldName) {
                        // Create a new Field
                        $field = new Field($fname, $ftype, $fsize);

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


    protected function isEmptyString($str)
    {
        return !(isset($str) && (strlen(trim($str)) > 0));
    }


    protected function createLookupTable()
    {
        $this->lookupTable = new Table(
            $this->tablePrefix . RedCapEtl::LOOKUP_TABLE_NAME,
            RedCapEtl::LOOKUP_TABLE_PRIMARY_ID,
            RulesParser::ROOT,
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
            ##$currentId = 1;
            foreach ($this->lookupChoices[$fieldName] as $category => $label) {
                // Set up the table/fieldcategory/label for this choice
                ######print "\n\n***** currentId : {$currentId} for {$tableName}.{$fieldName}\n";
                
                $data = array(
                    ##RedCapEtl::LOOKUP_TABLE_PRIMARY_ID => $currentId++,
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
            $rules .= "TABLE,".$formName.",$primaryKey,".RulesParser::ROOT."\n";

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

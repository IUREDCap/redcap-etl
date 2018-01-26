<?php


namespace IU\REDCapETL;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\Schema;
use IU\REDCapETL\Schema\Table;

use IU\REDCapETL\Database\DBConnectFactory;

/**
 * Class for REDCap ETL (Extract, Transform, Load).
 *
 * This class has several data dependencies:
 *
 * * configuration file - that contains the initial e-mail address to use for notifications
 *   the URL for the REDCap instance being used, the API token of the REDCap configuration
 *   project to use
 * * REDCap configuration project - provides information on the data and logging projects
 *   and the data store
 * * REDCap data project - the source for data extraction
 * * REDCap logging project - the place where messages are logged
 * * Database - the data store where the extracted data is loaded
 *
 */
class TransformationRules
{
    # Separators; example: "TABLE,  Fifth, Main, EVENTS:a;b"
    const ELEMENTS_SEPARATOR  = ',';
    const ROWS_DEF_SEPARATOR   = ':';   # row type separator
    const SUFFIXES_SEPARATOR   = ';';

    # Suffix for the REDCap field indicated the form has been completed
    const FORM_COMPLETE_SUFFIX = '_complete';

    const ELEMENT_POS         = 0;
    const ELEMENT_TABLE       = 'TABLE';
    const ELEMENT_FIELD       = 'FIELD';
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
    public function __construct($rules) {
        $this->rules = $rules;
    }


    /**
     * Parses the tranformation rules and returns a schema
     *
     * @return string if successful, return PARSE_VALID, if not successful,
     *    return a string with feedback about problems in parsing the transformationRules
     */
    public function parse($dataProject, $tablePrefix, $logger)
    {
        $this->tablePrefix = $tablePrefix;
        $this->logger      = $logger;
        $recordIdFieldName = $dataProject->getRecordIdFieldName();

        $fieldNames        = $dataProject->getFieldNames();

        $redCapFields      = $dataProject->getFieldNames();
        // Remove each instrument's completion field from the field list
        foreach ($redCapFields as $field_name => $val) {
            if (preg_match('/'.self::FORM_COMPLETE_SUFFIX.'$/', $field_name)) {
                unset($redCapFields[$field_name]);
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
            switch ($elements[self::ELEMENT_POS]) {
                // Start with Table?
                case self::ELEMENT_TABLE:
                    // Retrieve Table parameters
                    $parent_table_name = $tablePrefix
                        . $this->cleanTableName($elements[self::TABLE_PARENT_POS]);

                    $table_name = $tablePrefix
                        . $this->cleanTableName($elements[self::TABLE_NAME_POS]);

                    $rows_def = $this->cleanRowsDef($elements[self::TABLE_ROWSTYPE_POS]);

                    // Validate all Table parameters found
                    if (($this->isEmptyString($parent_table_name)) ||
                            ($this->isEmptyString($table_name)) ||
                            ($this->isEmptyString($rows_def))) {
                        $msg = "Missing one or more parameters in line: '".$line."'";
                        $logger->logInfo($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }

                    list($rows_type, $suffixes) = $this->parseRowsDef($rows_def);

                    // Validate the given rows type
                    if (false === $rows_type) {
                        $msg = "Invalid rows_type in line: '".$line."'";
                        $logger->logInfo($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }

                    // Find parent Table
                    $parent_table = $schema->getTable($parent_table_name);

                    // Create a new Table
                    $table = new Table(
                        $table_name,
                        $parent_table,
                        $rows_type,
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
                    switch ($rows_type) {
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
                    #OLD: if (is_a($parent_table, 'Table')) {
                    if (is_a($parent_table, Table::class)) {
                        // Add a foreign key
                        $table->setForeign($parent_table);

                        // Add as a child of parent table
                        $parent_table->addChild($table);
                    }
                    break;

                // Start with Field?
                case self::ELEMENT_FIELD:
                    //------------------------------------------------------------
                    // Retrieve Field parameters
                    $field_name = $this->cleanFieldName($elements[self::FIELD_NAME_POS]);
                    $field_type = $this->cleanFieldType($elements[self::FIELD_TYPE_POS]);

                    //------------------------------------------------------------
                    // Validate all Field parameters found
                    if (($this->isEmptyString($field_name)) || ($this->isEmptyString($field_type))) {
                        $msg = "Missing one or more parameters in line: '".$line."'";
                        $this->log($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }

                    // Validate the given field type
                    if (! FieldType::isValid($field_type)) {
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
                    if ($field_type === FieldType::CHECKBOX) {
                        // For a checkbox in a Suffix table
                        if ((RedCapEtl::BY_SUFFIXES === $table->rows_type)
                                || (RedCapEtl::BY_EVENTS_SUFFIXES === $table->rows_type)) {
                            // Lookup the choices using any one of the valid suffixes
                            $suffixes = $table->getPossibleSuffixes();
                            $lookup_field_name = $field_name.$suffixes[0];
                        } else {
                            $lookup_field_name = $field_name;
                        }

                        // Foreach category of the checkbox field
                        foreach ($this->lookupChoices[$lookup_field_name] as $cat => $label) {
                            // Form the variable name for this category
                            $fields[$field_name.RedCapEtl::CHECKBOX_SEPARATOR.$cat]
                                = FieldType::INT;
                        }
                    } else {
                        // Process a single field
                        $fields[$field_name] = $field_type;
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
                        if ((RedCapEtl::BY_SUFFIXES !== $table->rows_type) &&
                                (RedCapEtl::BY_EVENTS_SUFFIXES !== $table->rows_type) &&
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
                        if ((RedCapEtl::BY_SUFFIXES === $table->rows_type)
                                || (RedCapEtl::BY_EVENTS_SUFFIXES === $table->rows_type)) {
                            $possibles = $table->getPossibleSuffixes();

                            $field_found = false;

                            // Foreach possible suffix, is the field found?
                            foreach ($possibles as $sx) {
                                // In case this is a checkbox field
                                if ($field_type === FieldType::CHECKBOX) {
                                    // Separate root from category
                                    list($root_name, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                    // Form the exported field name
                                    $export_fname = $root_name.$sx.RedCapEtl::CHECKBOX_SEPARATOR.$cat;

                                    // Form the metadata field name
                                    // Checkbox fields have a single metadata field name, but
                                    // (usually) multiple exported field names
                                    $meta_fname = $root_name.$sx;
                                } else {
                                    // Otherwise, just append suffix
                                    $export_fname = $fname.$sx;
                                    $meta_fname = $fname.$sx;
                                }


                                //--------------------------------------------------------------
                                // SUFFIXES: Remove from warning that REDCap field is not in Map
                                //--------------------------------------------------------------
                                if (!empty($fieldNames[$export_fname])) {
                                    $field_found = true;

                                    // Remove this field from the list of fields to be mapped
                                    unset($redCapFields[$export_fname]);
                                }
                            } // Foreach possible suffix

                            //------------------------------------------------------------
                            // SUFFIXES: Warn that map field is not in REDCap
                            //------------------------------------------------------------
                            if (false === $field_found) {
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
                            if ($field_type === FieldType::CHECKBOX) {
                                // Separate root from category
                                list($root_name, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                // Form the metadata field name
                                // Checkbox fields have a single metadata field name, but
                                // (usually) multiple exported field names
                                $meta_fname = $root_name;
                            } else {
                                // $meta_fname is redundant here, but used later when
                                // deciding whether or not to create rows in Lookup
                                $meta_fname = $fname;
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
                            if (array_key_exists($meta_fname, $this->lookupChoices)) {
                                $this->makeLookupTable($table->name, $meta_fname);
                                $field->uses_lookup = $meta_fname;
                                $table->uses_lookup = true;
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



    protected function cleanTableName($table_name)
    {
        return $this->generalSqlClean($table_name);
    }


    protected function cleanRowsDef($rows_def)
    {
        return $this->generalSqlClean($rows_def);
    }


    protected function cleanFieldName($field_name)
    {
        return $this->generalSqlClean($field_name);
    }


    protected function cleanFieldType($field_type)
    {
        return $this->generalSqlClean($field_type);
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


    protected function parseRowsDef($rows_def)
    {
        $rows_def = trim($rows_def);

        $regex = '/'.self::SUFFIXES_SEPARATOR.'/';

        $rows_type = '';
        $suffixes = array();

        list($rows_encode, $suffixes_def) = array_pad(explode(self::ROWS_DEF_SEPARATOR, $rows_def), 2, null);

        switch ($rows_encode) {
            case self::ROOT:
                $rows_type = RedCapEtl::ROOT;
                break;

            case self::EVENTS:
                $suffixes = explode(self::SUFFIXES_SEPARATOR, $suffixes_def);
                $rows_type = (empty($suffixes[0])) ? RedCapEtl::BY_EVENTS : RedCapEtl::BY_EVENTS_SUFFIXES;
                break;

            case self::REPEATING_INSTRUMENTS:
                $rows_type = RedCapEtl::BY_REPEATING_INSTRUMENTS;
                break;

            case self::SUFFIXES:
                $suffixes = explode(self::SUFFIXES_SEPARATOR, $suffixes_def);
                $rows_type = (empty($suffixes[0])) ? false : RedCapEtl::BY_SUFFIXES;
                break;

            case (preg_match($regex, $rows_encode) ? true : false):
                $suffixes = explode(self::SUFFIXES_SEPARATOR, $rows_encode);
                $rows_type = (empty($suffixes[0])) ? false : RedCapEtl::BY_SUFFIXES;
                break;

            default:
                $rows_type = false;
        }

        return (array($rows_type,$suffixes));
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
        $field_primary = new Field(RedCapEtl::LOOKUP_TABLE_PRIMARY_ID, FieldType::INT);
        $field_field_name = new Field(RedCapEtl::LOOKUP_FIELD_TABLE_NAME, FieldType::STRING);
        $field_table_name = new Field(RedCapEtl::LOOKUP_FIELD_FIELD_NAME, FieldType::STRING);
        $field_category = new Field(RedCapEtl::LOOKUP_FIELD_CATEGORY, FieldType::STRING);
        $field_value = new Field(RedCapEtl::LOOKUP_FIELD_LABEL, FieldType::STRING);
        $this->lookupTable->addField($field_primary);
        $this->lookupTable->addField($field_table_name);
        $this->lookupTable->addField($field_field_name);
        $this->lookupTable->addField($field_category);
        $this->lookupTable->addField($field_value);
    }


    /**
     * Create a table that holds the categories and labels for a field
     * with multiple choices.
     */
    protected function makeLookupTable($table_name, $field_name)
    {

        if (empty($this->lookupTableIn[$table_name.':'.$field_name])) {
            $this->lookupTableIn[$table_name.':'.$field_name] = true;

            // Foreach choice, add a row
            $cur_id = 1;
            foreach ($this->lookupChoices[$field_name] as $category => $label) {
                // Set up the table/fieldcategory/label for this choice
                $data = array(
                    RedCapEtl::LOOKUP_TABLE_PRIMARY_ID => $cur_id++,
                    RedCapEtl::LOOKUP_FIELD_TABLE_NAME => $table_name,
                    RedCapEtl::LOOKUP_FIELD_FIELD_NAME => $field_name,
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
            $rules .= "TABLE,".$formName.",".$recordId.",".'ROOT'."\n";

            foreach ($metadata as $field) {
                if ($field['form_name'] == $formName) {
                    $type = 'string';
                
                    $validationType = $field['text_validation_type_or_show_slider_number'];
                    $fieldType      = $field['field_type'];
                
                    if ($fieldType === 'checkbox') {
                        $type = 'checkbox';
                    } elseif ($validationType === 'integer') { # value may be too large for db int
                        $type = 'string';
                    } elseif ($fieldType === 'dropdown' || $fieldType === 'radio') {
                        $type = 'int';
                    } elseif ($validationType === 'date_mdy') {
                        $type = 'date';
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


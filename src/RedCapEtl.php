<?php


namespace IU\REDCapETL;

use IU\PHPCap\RedCap;
use IU\PHPCap\PhpCapException;

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
 * * properties file - that contains the initial e-mail address to use for notifications
 *   the URL for the REDCap instance being used, the API token of the REDCap configuration
 *   project to use
 * * REDCap configuration project - provides information on the data and logging projects
 *   and the data store
 * * REDCap data project - the source for data extraction
 * * REDCap logging project - the place where messages are logged
 * * Database - the data store where the extracted data is loaded
 *
 */
class RedCapEtl
{

    // For the Schema Map
    const SCHEMA_ELEMENTS_SEPARATOR  = ',';
    const SCHEMA_ELEMENT_POS         = 0;
    const SCHEMA_ELEMENT_TABLE       = 'TABLE';
    const SCHEMA_ELEMENT_FIELD       = 'FIELD';
    const SCHEMA_TABLE_NAME_POS      = 1;
    const SCHEMA_TABLE_PARENT_POS    = 2;
    const SCHEMA_TABLE_ROWSTYPE_POS  = 3;
    const SCHEMA_FIELD_NAME_POS      = 1;
    const SCHEMA_FIELD_TYPE_POS      = 2;
    const CHECKBOX_SEPARATOR         = '___';

    const DEFAULT_EMAIL_SUBJECT = 'REDCap ETL Error';

    // Methods for encoding 1:many relationships as a 'rows_def'
    const ENCODE_ROOT            = 'ROOT';
    const ENCODE_EVENTS          = 'EVENTS';
    const ENCODE_SUFFIXES        = 'SUFFIXES';
    const ENCODE_REPEATING_INSTRUMENTS = 'REPEATING_INSTRUMENTS';

    const ROWS_DEF_SEPARATOR   = ':';
    const SUFFIXES_SEPARATOR   = ';';

    const ROOT                 = 0;
    const BY_EVENTS            = 1;
    const BY_SUFFIXES          = 2;
    const BY_EVENTS_SUFFIXES   = 3;
    const BY_REPEATING_INSTRUMENTS   = 4;
    const FORM_COMPLETE_SUFFIX = '_complete';

    // For Schema Map files
    const FILE_NOT_FOUND = 'NOFILE';

    // For creating output fields to represent events and suffixes
    const COLUMN_EVENT             = 'redcap_event';
    const COLUMN_SUFFIXES          = 'redcap_suffix';
    const COLUMN_EVENT_TYPE        = FieldType::STRING;
    const COLUMN_SUFFIXES_TYPE     = FieldType::STRING;
    const REDCAP_EVENT_NAME        = 'redcap_event_name';

    const COLUMN_REPEATING_INSTRUMENT      = 'redcap_repeat_instrument';
    const COLUMN_REPEATING_INSTRUMENT_TYPE = FieldType::STRING;
    const COLUMN_REPEATING_INSTANCE        = 'redcap_repeat_instance';
    const COLUMN_REPEATING_INSTANCE_TYPE   = FieldType::INT;

    // For parsing feedback
    const PARSE_VALID = 'valid';
    const PARSE_ERROR = 'error';
    const PARSE_WARN  = 'warn';

    // For setting whether or not DET invokes the ETL or just parses
    const TRIGGER_ETL_NO  = '0';
    const TRIGGER_ETL_YES = '1';

    // For Lookup tables
    const LOOKUP_TABLE_NAME        = 'Lookup';
    const LOOKUP_TABLE_PRIMARY_ID  = 'lookup_id';
    const LOOKUP_FIELD_TABLE_NAME  = 'table_name';
    const LOOKUP_FIELD_FIELD_NAME  = 'field_name';
    const LOOKUP_FIELD_CATEGORY    = 'category';
    const LOOKUP_FIELD_LABEL       = 'label';

    protected $det;          // For calls related to Data Entry Triggers
    public $notifier;        // For notifying of errors when there is no GUI

    protected $date;
    protected $log_id_base;

    protected $configProject;
    protected $dataProject;
    protected $logProject;

    protected $logger;

    protected $schema_map;

    protected $trigger_etl;
    protected $batch_size = 1;   // In effect batch size of 1 is no batching

    protected $schema;
    protected $lookup;
    protected $lookup_table;  // Table object that has label information for
                              // multiple choice REDCap fields.

    protected $lookup_table_in;  // Array of which table/fields have
                                       // already been entered into Lookup
    protected $lookup_choices;     // Takes $field name and returns cat/labels
    protected $redcap_fields;      // Array of fields available for mapping

    protected $rowsLoadedForTable = array();
  
    protected $dbcon;

    private $tablePrefix = '';   // Default: no prefix
                                 // This is intened for cases where multiple ETL instances
                                 // are writing to the same database. If this isn't used,
                                 // the Lookup table will be overwritten by each instance.

    private $labelViewSuffix = '_vLookup';

    private $fromEmailAddress;
    private $emailSubject;

    private $logFile;

    private $errorHandler;

    private $app;

    private $recordIdFieldName;   // The field name for the record ID
                                  // for the data project in REDCap

    private $configuration;
  

    /**
     * Constructor.
     *
     * @param Logger2 $logger logger for information and errors
     * @param array $properties associative array or property names and values.
     * @param string $propertiesFile the name of the properties file to use
     *     (used as an alternative to the properties array).
     */
    public function __construct(
        $logger,
        $properties = null,
        $propertiesFile = null
    ) {
        $this->logger = $logger;
        $this->errorHandler = new EtlErrorHandler();

        $this->app = $logger->getApp();

        $this->configuration = new Configuration($logger, $properties, $propertiesFile);


        $this->date = date('g:i:s a d-M-Y T');

        // REDCap must have a record_id when importing a new record. It does
        // not auto generate a new record_id on API Imports (or regular imports?),
        // even when the project is set to auto generate new record_ids.
        // Because multiple people may be using this application simultaneously,
        // it's not sufficient to simply use a timestamp. There is a risk that
        // even with the timestamp and a random number, logs might overwrite each
        // other, but I haven't found a better solution.
        $this->log_id_base = time().'-'.rand().'-';

        #-------------------------------------------------------------
        # Callback function for use in the RedCap class so
        # that project objects retrieved will have class EtlProject,
        # which has extensions for REDCapETL.
        #-------------------------------------------------------------
        $callback = function (
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        ) {
            return new EtlProject(
                $apiUrl,
                $apiToken,
                $sslVerify,
                $caCertificateFile,
                $errorHandler,
                $connection
            );
        };


        $apiUrl = $this->configuration->getRedCapApiUrl();

        #-----------------------------------------------------------
        # Create RedCap object to use for getting REDCap projects
        #-----------------------------------------------------------
        $superToken = null; // There is no need to create projects, so this is not needed
        $sslVerify  = $this->configuration->getSslVerify();
        $caCertFile = $this->configuration->getCaCertFile();

        try {
            $redCap = new RedCap($apiUrl, $superToken, $sslVerify, $caCertFile);
            $redCap->setProjectConstructorCallback($callback);
        } catch (PhpCapException $exception) {
            $message = 'Unable to set up RedCap object.';
            $this->errorHandler->throwException($message, EtlException::PHPCAP_ERROR, $exception);
        }


        #------------------------------------------------------
        # Create a REDCap DET (Data Entry Trigger) Handler,
        # in case it's needed.
        #------------------------------------------------------
        $projectId = $this->configuration->getProjectId();
        $this->det = new RedCapDetHandler(
            $projectId,
            $this->configuration->getAllowedServers(),
            $this->logger->getNotifier()
        );

        #----------------------------------------------------------------
        # Get the project that has the actual data
        #
        # Allow 'token_opt2' as the name of the data project token
        # for backward compatibility with the OPTIMISTIC project
        #----------------------------------------------------------------
        $dataToken = $this->configuration->getDataSourceApiToken();
        try {
            $this->dataProject = $redCap->getProject($dataToken);
        } catch (PhpCapException $exception) {
            $message = 'Could not get data project.';
            $this->errorHandler->throwException($message, EtlException::PHPCAP_ERROR, $exception);
        }

        # $endDataProject = microtime(true);
        # print "    Data project time: ".($endDataProject - $startDataProject)." seconds\n";


        // Create a new Schema
        $this->schema = new Schema();

        # $endLog = microtime(true);
        # print "    Log setup time: ".($endLog - $startLog)." seconds\n";

        #---------------------------------------------
        # Log the version number of REDCap ETL
        #---------------------------------------------
        $this->log('REDCap ETL version '.Version::RELEASE_NUMBER);


        $this->schema_map = $this->configuration->getTransformationRules();

        // Create another Schema just for lookup tables
        $this->lookup_table = new Table(
            $this->tablePrefix . RedCapEtl::LOOKUP_TABLE_NAME,
            RedCapEtl::LOOKUP_TABLE_PRIMARY_ID,
            RedCapEtl::ENCODE_ROOT,
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
        $this->lookup_table->addField($field_primary);
        $this->lookup_table->addField($field_table_name);
        $this->lookup_table->addField($field_field_name);
        $this->lookup_table->addField($field_category);
        $this->lookup_table->addField($field_value);

        #--------------------------------------------------------
        # Get metadata for fields that use categories and labels
        #--------------------------------------------------------
        $this->lookup_choices = $this->dataProject->getLookupChoices();

        #--------------------------------------------------
        # Get set of REDCap fields available for mapping
        #--------------------------------------------------
        $this->redcap_fields = $this->dataProject->getFieldNames();

        // Remove each instrument's completion field from the field list
        foreach ($this->redcap_fields as $field_name => $val) {
            if (preg_match('/'.RedCapEtl::FORM_COMPLETE_SUFFIX.'$/', $field_name)) {
                unset($this->redcap_fields[$field_name]);
            }
        }

        #---------------------------------------------------
        # Create a database connection for the database
        # where the transformed REDCap data will be stored
        #---------------------------------------------------
        $dbconfactory = new DBConnectFactory();
        $this->dbcon = $dbconfactory->createDbcon(
            $this->configuration->getDbConnection(),
            $this->tablePrefix,
            $this->labelViewSuffix
        );
    }


    /**
     * Parses the schema map, and creates a Schema object that describes
     * the schema of the database where the extracted data will be loaded.
     *
     * @return string if successful, return PARSE_VALID, if not successful,
     *    return a string with feedback about problems in parsing the schema_map
     */
    public function parseMap()
    {
        $parsedLineCount = 0;
        $info = '';
        $warnings = '';
        $errors = '';

        $fieldnames = $this->dataProject->getFieldNames();

        $this->recordIdFieldName = $this->dataProject->getRecordIdFieldName();

        // Log how many fields in REDCap could be parsed
        $msg = "Found ".count($this->redcap_fields)." fields in REDCap.";
        $this->log($msg);
        $info .= $msg."\n";

        // Break the schema_map into multiple lines
        $lines = preg_split('/\r\n|\r|\n/', $this->schema_map);

        // Foreach line in the Schema Map
        foreach ($lines as $line) {
            #print "line: $line\n"; // Jim

            // If line is nothing but whitespace and commas, skip it
            if (preg_match('/^[\s,]*$/', $line)) {
                continue;
            }

            // Get elements of the line, trimmed
            // trim removes leading and trailing whitespace
            $elements = array_map('trim', explode(RedCapEtl::SCHEMA_ELEMENTS_SEPARATOR, $line));

            #print 'LINE: '.$line."\n";

            // Check first element
            switch ($elements[RedCapEtl::SCHEMA_ELEMENT_POS]) {
                // Start with Table?
                case RedCapEtl::SCHEMA_ELEMENT_TABLE:
                    // Retrieve Table parameters
                    $parent_table_name = $this->tablePrefix
                        . $this->cleanTableName($elements[RedCapEtl::SCHEMA_TABLE_PARENT_POS]);

                    $table_name = $this->tablePrefix
                        . $this->cleanTableName($elements[RedCapEtl::SCHEMA_TABLE_NAME_POS]);

                    $rows_def = $this->cleanRowsDef($elements[RedCapEtl::SCHEMA_TABLE_ROWSTYPE_POS]);

                    // Validate all Table parameters found
                    if (($this->isEmptyString($parent_table_name)) ||
                            ($this->isEmptyString($table_name)) ||
                            ($this->isEmptyString($rows_def))) {
                        $msg = "Missing one or more parameters in line: '".$line."'";
                        $this->log($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }

                    list($rows_type, $suffixes) = $this->parseRowsDef($rows_def);

                    // Validate the given rows type
                    if (false === $rows_type) {
                        $msg = "Invalid rows_type in line: '".$line."'";
                        $this->log($msg);
                        $errors .= $msg."\n";
                        continue 2;
                    }

                    // Find parent Table
                    $parent_table = $this->schema->getTable($parent_table_name);

                    // Create a new Table
                    $table = new Table(
                        $table_name,
                        $parent_table,
                        $rows_type,
                        $suffixes,
                        $this->recordIdFieldName
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
                    if ($table->primary !== $this->recordIdFieldName) {
                        $field = new Field($this->recordIdFieldName, FieldType::STRING);
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
                    $this->schema->addTable($table);

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
                case RedCapEtl::SCHEMA_ELEMENT_FIELD:
                    //------------------------------------------------------------
                    // Retrieve Field parameters
                    $field_name = $this->cleanFieldName($elements[RedCapEtl::SCHEMA_FIELD_NAME_POS]);
                    $field_type = $this->cleanFieldType($elements[RedCapEtl::SCHEMA_FIELD_TYPE_POS]);

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
                        foreach ($this->lookup_choices[$lookup_field_name] as $cat => $label) {
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
                    # $fieldnames = $this->dataProject->get_fieldnames();
                    # Set above now...

                    foreach ($fields as $fname => $ftype) {
                        //-------------------------------------------------------------
                        // !SUFFIXES: Prep for and warn that map field is not in REDCap
                        //-------------------------------------------------------------
                        if ((RedCapEtl::BY_SUFFIXES !== $table->rows_type) &&
                                (RedCapEtl::BY_EVENTS_SUFFIXES !== $table->rows_type) &&
                                $fname !== 'redcap_data_access_group' &&
                                (empty($fieldnames[$fname]))) {
                            $msg = "Field not found in REDCap: '".$fname."'";
                            $this->log($msg);
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
                                if (!empty($fieldnames[$export_fname])) {
                                    $field_found = true;

                                    // Remove this field from the list of fields to be mapped
                                    unset($this->redcap_fields[$export_fname]);
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
                            unset($this->redcap_fields[$fname]);
                        }
        
                        if ($fname !== $this->recordIdFieldName) {
                            // Create a new Field
                            $field = new Field($fname, $ftype);

                            // Add Field to current Table (error if no current table)
                            $table->addField($field);

                            // If this field has category/label choices
                            if (array_key_exists($meta_fname, $this->lookup_choices)) {
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
        $msg = "Found ".count($this->redcap_fields)." unmapped fields in REDCap.";
        $this->log($msg);

        // Set warning if count of remaining redcap fields is above zero
        if (0 < count($this->redcap_fields)) {
            $warnings .= $msg."\n";

            // List fields, if count is five or less
            if (10 > count($this->redcap_fields)) {
                $msg = "Unmapped fields: ".  implode(', ', array_keys($this->redcap_fields));
                $this->log($msg);
                $warnings .= $msg;
            }
        }

        #print("\n\nLOOKUP TABLE:\n");
        #print_r($this->lookup_table);
        #print("\n\n");

        #--------------------------------------------------------------
        # Create a Lookup object for fast lookup searches in the code
        #--------------------------------------------------------------
        $this->lookup = new Lookup($this->lookup_table);

        #print("\n\nSCHEMA MAP\n{$this->schema->toString()}\n\n");
        #print("\n\nLOOKUP TABLE\n{$this->lookup_table->toString()}\n\n");

        if ('' !== $errors) {
            return(array(RedCapEtl::PARSE_ERROR,$errors.$info.$warnings));
        } elseif ('' !== $warnings) {
            return(array(RedCapEtl::PARSE_WARN,$info.$warnings));
        } else {
            return(array(RedCapEtl::PARSE_VALID,$info));
        }
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

        $regex = '/'.RedCapEtl::SUFFIXES_SEPARATOR.'/';

        $rows_type = '';
        $suffixes = array();

        list($rows_encode, $suffixes_def) = array_pad(explode(RedCapEtl::ROWS_DEF_SEPARATOR, $rows_def), 2, null);

        switch ($rows_encode) {
            case RedCapEtl::ENCODE_ROOT:
                $rows_type = RedCapEtl::ROOT;
                break;

            case RedCapEtl::ENCODE_EVENTS:
                $suffixes = explode(RedCapEtl::SUFFIXES_SEPARATOR, $suffixes_def);
                $rows_type = (empty($suffixes[0])) ? RedCapEtl::BY_EVENTS : RedCapEtl::BY_EVENTS_SUFFIXES;
                break;

            case RedCapEtl::ENCODE_REPEATING_INSTRUMENTS:
                $rows_type = RedCapEtl::BY_REPEATING_INSTRUMENTS;
                break;

            case RedCapEtl::ENCODE_SUFFIXES:
                $suffixes = explode(RedCapEtl::SUFFIXES_SEPARATOR, $suffixes_def);
                $rows_type = (empty($suffixes[0])) ? false : RedCapEtl::BY_SUFFIXES;
                break;

            case (preg_match($regex, $rows_encode) ? true : false):
                $suffixes = explode(RedCapEtl::SUFFIXES_SEPARATOR, $rows_encode);
                $rows_type = (empty($suffixes[0])) ? false : RedCapEtl::BY_SUFFIXES;
                break;

            default:
                $rows_type = false;
        }

        return (array($rows_type,$suffixes));
    }


    /**
     * Create a table that holds the categories and labels for a field
     * with multiple choices.
     */
    protected function makeLookupTable($table_name, $field_name)
    {

        if (empty($this->lookup_table_in[$table_name.':'.$field_name])) {
            $this->lookup_table_in[$table_name.':'.$field_name] = true;

            // Foreach choice, add a row
            $cur_id = 1;
            foreach ($this->lookup_choices[$field_name] as $category => $label) {
                // Set up the table/fieldcategory/label for this choice
                $data = array(
                    RedCapEtl::LOOKUP_TABLE_PRIMARY_ID => $cur_id++,
                    RedCapEtl::LOOKUP_FIELD_TABLE_NAME => $table_name,
                    RedCapEtl::LOOKUP_FIELD_FIELD_NAME => $field_name,
                    RedCapEtl::LOOKUP_FIELD_CATEGORY => $category,
                    RedCapEtl::LOOKUP_FIELD_LABEL => $label
                );

                // Add the row, using no foreign key or suffix
                $this->lookup_table->createRow($data, '', '');
            }
        }

        return true;
    }


    /**
     * Reads all records from the RedCapEtl project, transforms them
     * into Rows, and loads those rows
     *
     * Reads records out of REDCap in batches in order to reduce the likelihood
     * of causing memory issues on the Application server or Database server.
     */
    public function extractTransformLoad()
    {
        $startEtlTime = microtime(true);

        $extractTime   = 0.0;
        $transformTime = 0.0;
        $loadTime      = 0.0;

        #--------------------------------------------------
        # Extract the record ID batches
        #--------------------------------------------------
        $startExtractTime = microtime(true);
        $recordIdBatches = $this->dataProject->getRecordIdBatches((int) $this->batch_size);
        $endExtractTime = microtime(true);
        $extractTime += $endExtractTime - $startExtractTime;

        # Count and log the number of record IDs found
        $recordIdCount = 0;
        foreach ($recordIdBatches as $recordIdBatch) {
            $recordIdCount += count($recordIdBatch);
        }
        $this->log("Number of record_ids found: ". $recordIdCount);

        // Foreach record_id, get all REDCap records for that record_id.
        // There will be one record for each event for each record_id
        $record_events_cnt = 0;

        #-------------------------------------------------------
        # For each batch of data, extract, transform, and load
        #-------------------------------------------------------
        foreach ($recordIdBatches as $recordIdBatch) {
            #---------------------------------
            # Extract the data from REDCap
            #---------------------------------
            $startExtractTime = microtime(true);
            $recordBatch = $this->dataProject->getRecordBatch($recordIdBatch);
            $endExtractTime = microtime(true);
            $extractTime += $endExtractTime - $startExtractTime;

            foreach ($recordBatch as $recordId => $records) {
                $record_events_cnt += count($records);

                #-----------------------------------
                # Transform the data
                #-----------------------------------
                $startTransformTime = microtime(true);
                // For each root table
                foreach ($this->schema->getRootTables() as $root_table) {
                    // Transform the records for this record_id into rows
                    $this->transform($root_table, $records, '', '');
                }
                $endTransformTime = microtime(true);
                $transformTime += $endTransformTime - $startTransformTime;
            }

            #print("\n\n==============================================================================================\n");
            #print("\n\nSCHEMA MAP\n{$this->schema->toString()}\n\n");
            #print("\n\n==============================================================================================\n");

            #-------------------------------------
            # Load the data into the database
            #-------------------------------------
            $startLoadTime = microtime(true);
            $this->loadRows();
            $endLoadTime = microtime(true);
            $loadTime += $endLoadTime - $startLoadTime;
        }

        $endEtlTime = microtime(true);
        $this->logInfoToFile('Extract time:   '.$extractTime.' seconds');
        $this->logInfoToFile('Transform time: '.$transformTime.' seconds');
        $this->logInfoToFile('Load time:      '.$loadTime.' seconds');
        $this->logInfoToFile('ETL total time: '.($endEtlTime - $startEtlTime).' seconds');

        $this->reportRows();

        $this->log("Number of record events transformed: ". $record_events_cnt);
    
        return true;
    }


    /**
     * Transform the values from REDCap in the specified records into
     * values in the specified table and its child tables objects.
     * The rows are added to the Table objects as data rows, and NOT
     * stored in the database at this point.
     *
     * @param Table $table the table (and its child tables) in which the
     *        records values are being stored.
     *
     * @param array $records array of records for a single record_id. If there
     *     is more than one record, they represent multiple
     *     events or repeating instruments (forms).
     *
     * @param string $foreign_key if set, represents the value to use as the
     *     foreign key for any records created.
     *
     * @param string $suffix If set, represents the suffix used for the parent table.
     */
    protected function transform($table, $records, $foreign_key, $suffix)
    {
        // Look at row_event for this table
        switch ($table->rows_type) {
            // If root
            case RedCapEtl::ROOT:
                $this->createRowAndRecurse($table, $records, $foreign_key, $suffix);
                break;

            // If events
            case RedCapEtl::BY_EVENTS:
                // Foreach Record (i.e., foreach event)
                foreach ($records as $record) {
                    $this->createRowAndRecurse($table, array($record), $foreign_key, $suffix);
                }
                break;

            // If repeatable forms
            case RedCapEtl::BY_REPEATING_INSTRUMENTS:
                // Foreach Record (i.e., foreach repeatable form)
                foreach ($records as $record) {
                    $this->createRowAndRecurse($table, array($record), $foreign_key, $suffix);
                }
                break;

            // If suffix
            case RedCapEtl::BY_SUFFIXES:
                // Foreach Suffix
                foreach ($table->rows_suffixes as $new_suffix) {
                    $this->createRowAndRecurse($table, $records, $foreign_key, $suffix.$new_suffix);
                }
                break;

            // If events and suffix
            case RedCapEtl::BY_EVENTS_SUFFIXES:
                // Foreach Record (i.e., foreach event)
                foreach ($records as $record) {
                    // Foreach Suffix
                    foreach ($table->rows_suffixes as $new_suffix) {
                        $this->createRowAndRecurse($table, array($record), $foreign_key, $suffix.$new_suffix);
                    }
                }
                break;
        }

        return true;
    }


    /**
     * See 'transform' function for explanation of variables.
     */
    protected function createRowAndRecurse($table, $records, $foreign_key, $suffix)
    {
        // Create Row using 1st Record
        $primary_key = $table->createRow($records[0], $foreign_key, $suffix);

        // If primary key generated, recurse for child tables
        if ($primary_key) {
            // Foreach child table
            foreach ($table->getChildren() as $child_table) {
                $this->transform($child_table, $records, $primary_key, $suffix);
            }
        }
        return true;
    }


    /**
     * Creates the database tables where the data will be loaded, and
     * creates some views based on those tables. If there are existing
     * tables, then those tables are dropped first.
     */
    public function loadTables()
    {
        // foreach table, replace it
        // NOTE: This works on each table plus the lookup table
        $tables = array_merge(array($this->lookup_table), $this->schema->getTables());
        foreach ($tables as $table) {
            $this->dbcon->replaceTable($table);

            $msg = "Created table '".$table->name."'";

            // If this table uses the Lookup table, create a view
            if (true === $table->uses_lookup) {
                $this->dbcon->replaceLookupView($table, $this->lookup);
                $msg .= '; Lookup table created';
            }

            $this->log($msg);
        }
        return true;
    }


    /**
     * Write rows to the database.
     */
    protected function loadRows()
    {

        // foreach table object, store it's rows in the database and
        // then remove them from the table object
        // NOTE: This works on each table AND on each lookup table
        foreach (array_merge(array($this->lookup_table), $this->schema->getTables()) as $table) {
            #$rc = $this->dbcon->storeRows($table);
            #if (false === $rc) {
            #    $this->log("Error storing row in '".$table->name."': ".$this->dbcon->err_str);
            #}

            # Single row storage (stores one row at a time):
            # foreach row, load it
            foreach ($table->getRows() as $row) {
                $rc = $this->dbcon->storeRow($row);
                if (false === $rc) {
                    $this->log("Error storing row in '".$table->name."': ".$this->dbcon->err_str);
                }
            }

            // Add to summary how many rows created for this table
            if (array_key_exists($table->name, $this->rowsLoadedForTable)) {
                $this->rowsLoadedForTable[$table->name] += $table->getNumRows();
            } else {
                $this->rowsLoadedForTable[$table->name] = $table->getNumRows();
            }

            // Empty the rows for this table
            $table->emptyRows();
        }

        return true;
    }


    /**
     * Report rows written to the database
     */
    protected function reportRows()
    {
        // foreach table
        foreach ($this->rowsLoadedForTable as $table_name => $rows) {
            $msg = "Rows loaded for table '".$table_name."': ".$rows;
            $this->log($msg);
        }

        return true;
    }


    /**
     * For DET-invocations, upload the result and reset the etl_trigger
     */
    public function uploadResultAndReset($result, $record_id)
    {

        $records = array();
        $records[0] = array(
            'record_id' => $record_id,
            'trigger_etl' => RedCapEtl::TRIGGER_ETL_NO,
            'parse_feedback' => $result
        );

        try {
            $this->configProject->importRecords($records);
        } catch (PhpCapException $exception) {
            $message = 'Unable to load results and reset ETL trigger';
            $this->errorHandler->throwException($message, EtlException::PHPCAP_ERROR, $exception);
        }

        return true;
    }


    /**
     * Runs the entire ETL process
     *
     * WORK IN PROGRESS!!!
     */
    public function run()
    {
        try {
            $this->log("Starting processing.");

            //-------------------------------------------------------------------------
            // Parse Transformation Rules
            //-------------------------------------------------------------------------
            // NOTE: The $result is not used in batch mode. It is used
            //       by the DET handler to give feedback within REDCap.
            list($parseStatus, $result) = $this->parseMap();

            if ($parseStatus === RedCapEtl::PARSE_ERROR) {
                $message = "Transformation rules not parsed. Processing stopped.";
                $this->errorHandler->throwException($message, EtlException::INPUT_ERROR);
            } else {
                //----------------------------------------------------------------------
                // Extract, Transform, and Load
                //
                // These three steps are joined together at this level so that
                // the data from REDCap can be worked on in batches
                //----------------------------------------------------------------------
                $this->loadTables();
                $this->extractTransformLoad();

                $this->log("Processing complete.");
            }
        } catch (EtlException $exception) {
            $this->log('Processing failed.');
            throw $exception;  // re-throw the exception
        }
    }


    /**
     * Gets the DET (Data Entry Trigger) handler.
     */
    public function getDetHandler()
    {
        return $this->det;
    }

    public function getTriggerEtl()
    {
        return $this->trigger_etl;
    }

    public function log($message)
    {
        $this->logger->logInfo($message);
    }

    public function logInfoToFile($message)
    {
        $this->logger->logToFile($message, $this->logFile);
    }

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }
}

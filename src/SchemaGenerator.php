<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\REDCapETL\Rules\FieldRule;
use IU\REDCapETL\Rules\TableRule;
use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Schema;
use IU\REDCapETL\Schema\Table;

/**
 * Transformation rules used for transforming data from
 * the extracted format to the load format used in the
 * target database.
 */
class SchemaGenerator
{
    # Suffix for the REDCap field indicated the form has been completed
    const FORM_COMPLETE_SUFFIX = '_complete';

    # Parse status
    const PARSE_VALID = 'valid';
    const PARSE_ERROR = 'error';
    const PARSE_WARN  = 'warn';


    private $rules;

    /** @var array for multiple-choice fields, a map of field names to a map of values to labels
     *  for the choices for that field name. */
    private $lookupChoices;

    /** @var LookupTable a table object that maps multiple choice values to multiple choice labels */
    private $lookupTable;

    private $dataProject;

    private $logger;

    private $taskConfig;

    /** @var the ID of the task for the configuration for which the schema is being generated */
    private $taskId;

    private $tablePrefix;

    /**
     * Constructor.
     *
     * @param EtlRedCapProject $dataProject the REDCap project that
     *     contains the data to extract.
     * @param TaskConfig $taskConfig ETL task configuration information.
     * @param Logger $logger logger for logging ETL process information
     *     and errors.
     */
    public function __construct($dataProject, $taskConfig, $logger, $taskId = 1)
    {
        $this->dataProject   = $dataProject;
        $this->taskConfig    = $taskConfig;
        $this->tablePrefix   = $taskConfig->getTablePrefix();
        $this->logger        = $taskConfig->getLogger();
        $this->taskId        = $taskId;
    }


    /**
     * Generates the database schema from the rules (text).
     *
     * @param string $rulesText the transformation rules in text
     *     format.
     *
     * @return array the first element of the array is the Schema
     *    object for the database, the second is and array where
     *    the first element is that parse status, and the second
     *    is a string with info, warning and error messages.
     */
    public function generateSchema($rulesText)
    {
        $redCapApiUrl      = $this->taskConfig->getRedCapApiUrl();
        $projectInfo       = $this->dataProject->exportProjectInfo();
        $metadata          = $this->dataProject->exportMetadata();
        $recordIdFieldName = $this->dataProject->getRecordIdFieldName();
        $fieldNames        = $this->dataProject->getFieldNames();

        $formInfo = $this->dataProject->exportInstruments();
        $formNames = array_keys($formInfo);

        #----------------------------------------------------------
        # If surveys have been enabled, create a map of
        # survey timestamp fields for checking for field validity
        #----------------------------------------------------------
        $timestampFields = array();
        $surveysEnabled = $projectInfo['surveys_enabled'];
        if ($surveysEnabled) {
            foreach ($formNames as $formName) {
                $timestampFields[$formName.'_timestamp'] = 1;
            }
        }

        #------------------------------------------------------------------------------
        # Set up $unmappedRedCapFields to keep track of the user-created REDCap fields
        # (i.e., not ones automatically generated by REDCap) that have not been mapped
        # by the transformation rules
        #------------------------------------------------------------------------------
        $unmappedRedCapFields  = $this->dataProject->getFieldNames();
        foreach ($unmappedRedCapFields as $fieldName => $val) {
            if (preg_match('/'.self::FORM_COMPLETE_SUFFIX.'$/', $fieldName)) {
                unset($unmappedRedCapFields[$fieldName]);
            } elseif ($fieldName === $recordIdFieldName) {
                unset($unmappedRedCapFields[$fieldName]);
            }
        }

        #----------------------------------------------------------------
        # Create lookup table that maps multiple choice values to labels
        #----------------------------------------------------------------
        $this->lookupChoices = $this->dataProject->getLookupChoices();
        $keyType = $this->taskConfig->getGeneratedKeyType();
        $lookupTableName = $this->taskConfig->getLookupTableName();
        $this->lookupTable = new LookupTable($this->lookupChoices, $keyType, $lookupTableName);

        $info = '';
        $warnings = '';
        $errors = '';

        $schema = new Schema();

        #-----------------------------------------
        # Add the REDCap project info table
        #-----------------------------------------
        $projectInfoTableName = $this->taskConfig->getRedCapProjectInfoTable();
        $projectInfoTable = new ProjectInfoTable($projectInfoTableName);

        $row = $projectInfoTable->createDataRow($this->taskId, $redCapApiUrl, $projectInfo);
        $projectInfoTable->addRow($row);
        $schema->setProjectInfoTable($projectInfoTable);


        #---------------------------------------------------
        # Log how many fields in REDCap could be parsed
        #---------------------------------------------------
        $message = "Found ".count($unmappedRedCapFields)." user-defined fields in REDCap.";
        $this->logger->log($message);
        $info .= $message."\n";

        $table = null;
        
        $rulesParser = new RulesParser();
        $parsedRules = $rulesParser->parse($rulesText);
        $analyzer = new RulesSemanticAnalyzer();
        $parsedRules = $analyzer->check($parsedRules);
        
        # Log parsing errors, and add them to the errors string
        foreach ($parsedRules->getRules() as $rule) {
            foreach ($rule->getErrors() as $error) {
                $this->log($error);
                $errors .= $error."\n";
            }
        }
            
        // Process each rule from first to last
        foreach ($parsedRules->getRules() as $rule) {
            if ($rule->hasErrors()) {
                ;
            } elseif ($rule instanceof TableRule) {     # TABLE RULE
                #------------------------------------------------------------
                # Get the parent table - this will be:
                #   a Table object for non-root tables
                #   a string that is the primary key for root tables
                #------------------------------------------------------------
                if ($rule->isRootTable()) {
                    # If this is a root table, parentTable actually represents the primary key
                    # to be used for the table as specified by the user in the transformation rules,
                    # so do NOT prepend the table prefix (if any) to it
                    $parentTableName = $rule->parentTable;
                    $parentTable = $parentTableName;
                } else {
                    $parentTableName = $this->tablePrefix . $rule->parentTable;
                    $parentTable = $schema->getTable($parentTableName);
                }

                # Table creation will create the primary key
                $needsLabelView = $this->taskConfig->getLabelViews();
                $table = $this->generateTable(
                    $rule,
                    $parentTable,
                    $this->tablePrefix,
                    $recordIdFieldName,
                    $needsLabelView
                );

                $schema->addTable($table);

                #----------------------------------------------------------------------------
                # If the "parent table" is actually a table (i.e., this table is a child
                # table and not a root table)
                #----------------------------------------------------------------------------
                if (is_a($parentTable, Table::class)) {
                    $table->setForeign($parentTable);  # Add a foreign key
                    $parentTable->addChild($table);    # Add as a child of parent table
                }
            } elseif ($rule instanceof FieldRule) {
                #-------------------------------------------
                # FIELD RULE
                #-------------------------------------------
                 
                if ($table == null) {
                    break; // table not set, probably error with table rule
                    // Actually this should be flagged as an error
                }
                
                $fields = $this->generateFields($rule, $table);


                # For a single checkbox, one field will be generated for each option.
                # These generated fields will have type INT and an original field
                # type of CHECKBOX.
                $originalFieldType = $rule->dbFieldType;
                                        
                #-----------------------------------------------------------
                # Process each field
                #
                # Note: there can be more than one field, because a single
                # checkbox REDCap field may be stored as multiple database
                # fields (one for each option)
                #------------------------------------------------------------
                foreach ($fields as $field) {
                    $fname = $field->name;

                    #--------------------------------------------------------
                    # Replace '-' with '_'; needed for case where multiple
                    # choice values are specified as negative numbers
                    # or as text and have a '-' in them
                    #--------------------------------------------------------
                    $fname = str_replace('-', '_', $fname);

                    #-------------------------------------------------------------
                    # For non-suffixes fields, create warning if field name is
                    # not in REDCap
                    #-------------------------------------------------------------
                    if (!RowsType::hasSuffixes($table->rowsType) &&
                            $fname !== 'redcap_data_access_group' &&
                            $fname !== 'redcap_survey_identifier' &&
                            empty($timestampFields[$fname]) &&
                            (empty($fieldNames[$fname]))) {
                        $message = "Field not found in REDCap: '".$fname."'";
                        $this->logger->log($message);
                        $warnings .= $message."\n";
                        continue 2;
                    }

                    //------------------------------------------------------------
                    // SUFFIXES: Prep for warning that map field is not in REDCap
                    //           Prep for warning that REDCap field is not in Map
                    //------------------------------------------------------------

                    // For fields in a SUFFIXES table, use the possible suffixes,
                    // including looking up the tree of parent tables, to look
                    // for at least one matching field in the exportfieldnames
                    if (RowsType::hasSuffixes($table->rowsType)) {
                        $fieldFound = false;

                        // Foreach possible suffix, is the field found?
                        foreach ($table->getPossibleSuffixes() as $suffix) {
                            // In case this is a checkbox field
                            if ($originalFieldType === FieldType::CHECKBOX) {
                                // Separate root from category
                                list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                // Form the exported field name
                                $exportFieldName = $rootName.$suffix.RedCapEtl::CHECKBOX_SEPARATOR.$category;

                                // Form the original field name
                                // Checkbox fields have a single metadata field name, but
                                // (usually) multiple exported field names
                                $originalFieldName = $rootName.$suffix;
                            } else {
                                // Otherwise, just append suffix
                                $exportFieldName   = $fname.$suffix;
                                $originalFieldName = $fname.$suffix;
                            }

                            //--------------------------------------------------------------
                            // SUFFIXES: Remove from warning that REDCap field is not in Map
                            //--------------------------------------------------------------
                            if (!empty($fieldNames[$exportFieldName])) {
                                $fieldFound = true;
                                 // Remove this field from the list of fields to be mapped
                                unset($unmappedRedCapFields[$exportFieldName]);
                            }
                        } // Foreach possible suffix

                        //------------------------------------------------------------
                        // SUFFIXES: Warn that map field is not in REDCap
                        //------------------------------------------------------------
                        if (false === $fieldFound) {
                            $message = "Suffix field not found in REDCap: '".$fname."'";
                            $this->log($message);
                            $warnings .= $message."\n";
                            break; // continue 2;
                        }
                    } else {
                        #-----------------------------------------------------------------
                        # Non-suffixes field that was found in REDCap
                        #-----------------------------------------------------------------

                        // In case this is a checkbox field
                        if ($originalFieldType === FieldType::CHECKBOX) {
                            // Separate root from category
                            list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                            // Form the metadata field name
                            // Checkbox fields have a single metadata field name, but
                            // (usually) multiple exported field names
                            $originalFieldName = $rootName;
                        } else {
                            // $originalFieldName is redundant here, but used later when
                            // deciding whether or not to create rows in Lookup
                            $originalFieldName = $fname;
                        }

                        # Remove this field from the list of unmapped fields
                        unset($unmappedRedCapFields[$fname]);
                    }
        
                    #-----------------------------------------------------------------
                    # If the field name is the record ID field name, don't process
                    # it, because it should have already been automatically added
                    #-----------------------------------------------------------------
                    if ($field->dbName !== $recordIdFieldName) {
                        // Add Field to current Table (error if no current table)
                        $table->addField($field);

                        // If this field has category/label choices
                        if (array_key_exists($originalFieldName, $this->lookupChoices)) {
                            # Add label field here???????????????
                            # Need one label for each checkbox, because multiple values
                            # can be selected
                            # Types: dropdown, radio, checkbox (only checkbox can have multiple values)
                            $labelFieldSuffix = $this->taskConfig->getLabelFieldSuffix();
                            if (isset($labelFieldSuffix) && trim($labelFieldSuffix) !== '') {
                                $labelField = clone $field;
                                $labelFieldName = $field->getName() . $labelFieldSuffix;

                                $labelField->dbName     = $labelFieldName;
                                $labelField->type       = $this->taskConfig->getGeneratedLabelType()->getType();
                                $labelField->size       = $this->taskConfig->getGeneratedLabelType()->getSize();
                                $labelField->setUsesLookup(false);
                                $labelField->isLabel    = true;

                                $table->addField($labelField);
                            }

                            $this->lookupTable->addLookupField(
                                $table->getName(),
                                $originalFieldName,
                                $rule->dbFieldName
                            );

                            if (empty($rule->dbFieldName)) {
                                $field->setUsesLookup($originalFieldName);
                            } else {
                                $field->setUsesLookup($rule->dbFieldName);
                            }
                            $table->usesLookup = true;
                        }
                    }
                } // End foreach field to be created
            } // End if for rule types
        } // End foreach
        
        #-----------------------------------------
        # Add the REDCap metadata table
        #-----------------------------------------
        $metadataTableName = $this->taskConfig->getRedCapMetadataTable();
        $metadataTable = new MetadataTable($metadataTableName);

        $metadataMap = array();
        foreach ($metadata as $fieldMetadata) {
            $metadataMap[$fieldMetadata['field_name']] = $fieldMetadata;
        }

        foreach ($schema->getTables() as $table) {
            foreach ($table->getFields() as $field) {
                # For checkbox fields, need to remove the checbox value for looking up the metadata
                $fieldName = $field->name;
                if ($field->redcapType === 'checkbox') {
                    $fieldName = substr($fieldName, 0, strpos($fieldName, RedCapEtl::CHECKBOX_SEPARATOR));
                }

                if (array_key_exists($fieldName, $metadataMap)) {
                    $row = $metadataTable->createDataRow(
                        $this->taskId,
                        $table->getName(),
                        $field->dbName,
                        $metadataMap[$fieldName]
                    );
                    $metadataTable->addRow($row);
                }
            }
        }
        $schema->setMetadataTable($metadataTable);

        
        if ($parsedRules->getParsedLineCount() < 1) {
            $message = "Found no transformation rules.";
            $this->log($message);
            $errors .= $message."\n";
        }

        // Log how many fields in REDCap could be parsed
        $message = "Found ".count($unmappedRedCapFields)." unmapped user-defined fields in REDCap.";
        $this->logger->log($message);

        // Set warning if count of remaining redcap fields is above zero
        if (count($unmappedRedCapFields) > 0) {
            $warnings .= $message."\n";

            // List fields, if count is ten or less
            if (count($unmappedRedCapFields) <= 10) {
                $message = "Unmapped fields: ".  implode(', ', array_keys($unmappedRedCapFields));
                $this->logger->log($message);
                $warnings .= $message;
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

        $schema->setLabelViews($this->taskConfig->getLabelViews());
        $schema->setLabelViewSuffix($this->taskConfig->getLabelViewSuffix());
        $schema->setLookupTable($this->lookupTable);
        
        return array($schema, $messages);
    }


    public function generateTable($rule, $parentTable, $tablePrefix, $recordIdFieldName, $needsLabelView = true)
    {
        $tableName = $this->tablePrefix . $rule->tableName;
        $rowsType  = $rule->rowsType;

        $keyType = $this->taskConfig->getGeneratedKeyType();
        
        # Create the table
        $table = new Table(
            $tableName,
            $parentTable,
            $keyType,
            $rowsType,
            $rule->suffixes,
            $recordIdFieldName,
            $this->tablePrefix
        );

        $table->setNeedsLabelView($this->taskConfig->getLabelViews());

        #-----------------------------------------------------
        # Add redcap_data_source field to all tables
        #-----------------------------------------------------
        $field = new Field(RedCapEtl::COLUMN_DATA_SOURCE, FieldType::INT);
        $table->addField($field);

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
        if ($table->primary->dbName === $recordIdFieldName) {
            $errorMessage = 'Primary key field has same name as REDCap record id "'
                .$recordIdFieldName.'" on line '
                .$rule->getLineNumber().': "'.$rule->getLine().'"';
            throw new EtlException($errorMessage, EtlException::INPUT_ERROR);
        } else {
            $fieldTypeSpecifier = $this->taskConfig->getGeneratedRecordIdType();
            $field = new Field(
                $recordIdFieldName,
                $fieldTypeSpecifier->getType(),
                $fieldTypeSpecifier->getSize()
            );
            $table->addField($field);
        }


        #--------------------------------------------------------------
        # Figure out which identifier fields the rows should contain
        #--------------------------------------------------------------
        $hasEvent      = false;
        $hasInstrument = false;
        $hasInstance   = false;
        $hasSuffixes   = false;

        if (in_array(RowsType::BY_SUFFIXES, $rowsType)) {
            $hasSuffixes = true;
        }

        if ($this->dataProject->isLongitudinal()) {
            # Longitudinal study
            if (in_array(RowsType::BY_REPEATING_INSTRUMENTS, $rowsType)) {
                $hasEvent      = true;
                $hasInstrument = true;
                $hasInstance   = true;
            } elseif (in_array(RowsType::BY_REPEATING_EVENTS, $rowsType)) {
                $hasEvent      = true;
                $hasInstance   = true;
            } elseif (in_array(RowsType::BY_EVENTS, $rowsType)) {
                $hasEvent      = true;
            }

            if (in_array(RowsType::BY_EVENTS_SUFFIXES, $rowsType)) {
                $hasEvent      = true;
                $hasSuffixes   = true;
            }
        } else {
            # Classic (non-longitudinal) study
            if (in_array(RowsType::BY_REPEATING_INSTRUMENTS, $rowsType)) {
                $hasInstrument = true;
                $hasInstance   = true;
            }
        }

        #--------------------------------------------------------------
        # Create event/instrument/instance/suffix identifier fields
        #--------------------------------------------------------------
        if ($hasEvent) {
            $fieldTypeSpecifier = $this->taskConfig->getGeneratedNameType();
            $field = new Field(RedCapEtl::COLUMN_EVENT, $fieldTypeSpecifier->getType(), $fieldTypeSpecifier->getSize());
            $table->addField($field);
        }

        if ($hasInstrument) {
            $fieldTypeSpecifier = $this->taskConfig->getGeneratedNameType();
            $field = new Field(
                RedCapEtl::COLUMN_REPEATING_INSTRUMENT,
                $fieldTypeSpecifier->getType(),
                $fieldTypeSpecifier->getSize()
            );
            $table->addField($field);
        }

        if ($hasInstance) {
            $fieldTypeSpecifier = $this->taskConfig->getGeneratedInstanceType();
            $field = new Field(
                RedCapEtl::COLUMN_REPEATING_INSTANCE,
                $fieldTypeSpecifier->getType(),
                $fieldTypeSpecifier->getSize()
            );
            $table->addField($field);
        }

        if ($hasSuffixes) {
            $fieldTypeSpecifier = $this->taskConfig->getGeneratedSuffixType();
            $field = new Field(
                RedCapEtl::COLUMN_SUFFIXES,
                $fieldTypeSpecifier->getType(),
                $fieldTypeSpecifier->getSize()
            );
            $table->addField($field);
        }

        return $table;
    }

    /**
     * Generates the field(s) for a FIELD rule.
     *
     * @param Rule $rule the (FIELD) rule to generate the fields for.
     * @param Table $table the table the rules are being generated for.
     *
     * @return array an array of Field objects that represent the
     *     field(s) needed for this rule.
     */
    public function generateFields($rule, $table)
    {
        #------------------------------------------------------------
        # Create the needed fields
        #------------------------------------------------------------
        $fieldName   = $rule->redCapFieldName;
        $fieldType   = $rule->dbFieldType;
        $fieldSize   = $rule->dbFieldSize;
        $dbFieldName = $rule->dbFieldName;

        $fields = array();
                
        // If this is a checkbox field
        if ($fieldType === FieldType::CHECKBOX) {
            # For a checkbox in a Suffix table, append a valid suffix to
            # the field name to get a lookup table field name
            if (RowsType::hasSuffixes($table->rowsType)) {
                # Lookup the choices using any one of the valid suffixes,
                # since, for the same base field,  they all should have
                # the same choices
                $suffixes = $table->getPossibleSuffixes();
                $lookupFieldName = $fieldName.$suffixes[0];
            } else {
                $lookupFieldName = $fieldName;
            }

            $redcapFieldType = $this->dataProject->getFieldType($lookupFieldName);
            
            # Process each value of the checkbox
            foreach ($this->lookupChoices[$lookupFieldName] as $value => $label) {
                # It looks like REDCap uses the lower-case version of the
                # value for making the field name
                $value = strtolower($value);
                // Form the field names for this value
                $checkBoxFieldName = $fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value;
                $checkBoxDbFieldName = '';
                if (!empty($dbFieldName)) {
                    $checkBoxDbFieldName = $dbFieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value;
                }

                $field = new Field($checkBoxFieldName, FieldType::INT, null, $checkBoxDbFieldName, $redcapFieldType);
                $field->checkboxLabel = $label;
                $fields[$fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value] = $field;
            }
        } else {  # Non-checkbox field
            // Process a single field
            $redcapFieldType = $this->dataProject->getFieldType($fieldName);
            $field = new Field($fieldName, $fieldType, $fieldSize, $dbFieldName, $redcapFieldType);
            if (array_key_exists($fieldName, $this->lookupChoices)) {
                $field->valueToLabelMap = $this->lookupChoices[$fieldName];
            }
            $fields[$fieldName] = $field;
        }

        return $fields;
    }

    protected function log($message)
    {
        $this->logger->log($message);
    }
}

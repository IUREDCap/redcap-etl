<?php

namespace IU\REDCapETL\Schema;

use IU\REDCapETL\RedCapEtl;

/**
 * Table is used to store information about a relational table
 */
class Table
{
    public $name = '';

    public $parent = '';        // Table

    public $primary = '';       // Field used as primary key
    public $foreign = '';       // Field used as foreign key to parent

    protected $children = array();   // Child tables

    public $rows_type = '';
    public $rows_suffixes = array();        // Suffixes allowed for this table
    protected $possible_suffixes = array(); // Suffixes allowed for this table
                                            //   combined with any suffixe
                                            //   allowed for its parent table

    protected $fields = array();
    protected $rows = array();

    protected $primary_key = 1;

    public $uses_lookup = false;   // Are fields in this table represented
                                   // in the Lookup table?

    private $recordIdFieldName;

    /**
     * Creates a Table object.
     *
     * @param string $name the name of the table.
     *
     * @param mixed $parent a schmema object or a string, if the table
     *    is a root table, it will be a string that represents the
     *    name to use as the synthetic primary key. Otherwise it
     *    should be the table's parent Table object.
     *
     * @param string $recordIdFieldName the field name of the record ID
     *     in the REDCap data project.
     */
    public function __construct($name, $parent, $rows_type, $suffixes = array(), $recordIdFieldName = null)
    {
        $this->recordIdFieldName = $recordIdFieldName;

        $this->name = str_replace(' ', '_', $name);
        $this->parent = $parent;

        $this->rows_type = $rows_type;
        $this->rows_suffixes = $suffixes;

        // If Root, set the primary key based on what is given
        // ASSUMES: The field for the primary key will be given in
        //          the place of where a parent table would have been and
        //          will be of type string.
        if (RedCapEtl::ROOT === $this->rows_type) {
            $field = new Field($parent, FieldType::STRING);
            $this->primary = $field;
        } else {
            // Otherwise, create a new synthetic primary key
            $this->createPrimary();
        }

        return(1);
    }

    /**
     * Creates primary key field using the table name with
     * '_id' appended to it as the field's name.
     */
    public function createPrimary()
    {
        $primary_id = strtolower($this->name).'_id';
    
        $field = new Field($primary_id, FieldType::STRING);

        $this->primary = $field;
    }


    public function setForeign($parent_table)
    {
        $this->foreign = $parent_table->primary;
        return(1);
    }

    public function addField($field)
    {
        // If the field being added has the same name as the primary key,
        // do not add it again
        if ($this->primary->name != $field->name) {
            array_push($this->fields, $field);
        }
    }

    public function addRow($row)
    {
        array_push($this->rows, $row);
    }

    public function getFields()
    {
        return($this->fields);
    }

    /**
     * Returns regular fields, primary field, and, if
     * applicable, foreign field
     */
    public function getAllFields()
    {
        $allFields = $this->getFields();

        $fieldNames = array_column($allFields, 'name');

        if (is_object($this->foreign)) {
            if (!in_array($this->foreign->name, $fieldNames, true)) {
                array_unshift($allFields, $this->foreign);
            }
        }

        array_unshift($allFields, $this->primary);

        return($allFields);
    }


    public function getRows()
    {
        return($this->rows);
    }

    public function getNumRows()
    {
        return(count($this->rows));
    }

    public function emptyRows()
    {
        $this->rows = array();
        return(true);
    }

    public function addChild($table)
    {
        array_push($this->children, $table);
    }

    public function getChildren()
    {
         return($this->children);
    }

    public function nextPrimaryKey()
    {
        $this->primary_key += 1;
        return($this->primary_key - 1);
    }


    /**
     * Creates a row with the specified data in the table.
     *
     * @param string $data the data values used to create the row.
     * @param string $foreignKey the name of the foreign key field for the row.
     * @param string $suffix the suffix value for the row (if any).
     */
    public function createRow($data, $foreignKey, $suffix)
    {
        #---------------------------------------------------------------
        # If a row is being created for a repeating instrument, don't
        # include the data if it doesn't contain a repeating instrument
        # field.
        #---------------------------------------------------------------
        if ($this->rows_type === RedCapEtl::BY_REPEATING_INSTRUMENTS) {
            if (!array_key_exists(RedCapEtl::COLUMN_REPEATING_INSTRUMENT, $data)) {
                return false;
            }
        }


        // create potential Row
        $row = new Row($this);

        // set foreign key of potential Row
        if (strlen($foreignKey) != 0) {
            $row->data[$this->foreign->name] = $foreignKey;
        }

        $dataFound = false;

        // Foreach field
        foreach ($this->getFields() as $field) {
            if (isset($this->recordIdFieldName) && $field->name === $this->recordIdFieldName) {
                $row->data[$field->name] = $data[$field->name];
            } elseif (RedCapEtl::COLUMN_EVENT === $field->name) {
                // If this is the field to store the current event
                $row->data[$field->name] = $data[RedCapEtl::REDCAP_EVENT_NAME];
            } elseif (RedCapEtl::COLUMN_SUFFIXES === $field->name) {
                // if this is the field to store the current suffix
                $row->data[$field->name] = $suffix;
            } elseif ($field->name === RedCapEtl::COLUMN_REPEATING_INSTRUMENT) {
                # Just copy the repeating instrument field and don't count it
                # as a "data found" field
                $row->data[$field->name] = $data[$field->name];
            } elseif ($field->name === RedCapEtl::COLUMN_REPEATING_INSTANCE) {
                # Just copy the repeating instance field and don't count it
                # as a "data found" field
                $row->data[$field->name] = $data[$field->name];
            } else {
                // Otherwise, get data
                
                // If this is a checkbox field
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->name)) {
                    list($root_name,$cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->name);
                    $variableName = $root_name.$suffix.RedCapEtl::CHECKBOX_SEPARATOR.$cat;
                } else {
                    // Otherwise, just append suffix
                    $variableName = $field->name.$suffix;
                }


                # print "TABLE: ".($this->name)." \n";
                # print "FIELD: ".($field->name)."\n";
    
                // Add field and value to row
                $row->data[$field->name] = $data[$variableName];

                // Keep track of whether any data is found
                if (!empty($data[$variableName])) {
                    $dataFound = true;
                }
            }
        }

        if ($dataFound) {
            // Get and set primary key
            $primary_key = $this->nextPrimaryKey();
            $row->data[$this->primary->name] = $primary_key;

            // Add Row
            $this->addRow($row);

            return($primary_key);
        }

        return(false);
    }
    

    public function getPossibleSuffixes()
    {
        // If this table is BY_SUFFIXES and doesn't yet have its possible
        // suffixes set
        if (((RedCapEtl::BY_SUFFIXES === $this->rows_type) ||
            (RedCapEtl::BY_EVENTS_SUFFIXES === $this->rows_type)) &&
            (empty($this->possible_suffixes))) {
            // If there are no parent suffixes, use an empty string
            $parent_suffixes = $this->parent->getPossibleSuffixes();
            if (empty($parent_suffixes)) {
                $parent_suffixes = array('');
            }

            // Loop through all the possible_suffixes of the parent table
            foreach ($parent_suffixes as $par) {
                // Loop through all the possible_suffixes of the current table
                foreach ($this->rows_suffixes as $cur) {
                    array_push($this->possible_suffixes, $par.$cur);
                }
            }
        }
        
        return($this->possible_suffixes);
    }

    /**
     * Returns a string representation of this table object (intended for
     * debugging purposes).
     *
     * @param integer $indent the number of spaces to indent each line.
     */
    public function toString($indent = 0)
    {
        $in = str_repeat(' ', $indent);
        $string = '';

        $string .= "{$in}{$this->name} [";
        if (gettype($this->parent) == 'object') {
            $string .= $this->parent->name."]\n";
        } else {
            $string .= $this->parent."]\n";
        }

        $string .= "{$in}primary key: ".$this->primary->toString(0);
        $string .= "{$in}foreign key: ";
        if (gettype($this->foreign) == 'object') {
            $string .= $this->foreign->toString(0);
        } else {
            $string .= $this->foreign."\n";
        }

        $string .= "{$in}rows type: {$this->rows_type}\n";

        $string .= "{$in}Rows Suffixes:";
        foreach ($this->rows_suffixes as $suffix) {
            $string .= " ".$suffix;
        }
        $string .= "\n";

        $string .= "{$in}Possible Suffixes:";
        foreach ($this->possible_suffixes as $suffix) {
            $string .= " ".$suffix;
        }
        $string .= "\n";

        $string .= "{$in}Fields:\n";
        foreach ($this->fields as $field) {
            $string .= $field->toString($indent + 4);
        }

        $string .= "{$in}Rows:\n";
        foreach ($this->rows as $row) {
            $string .= $row->toString($indent + 4);
        }

        $string .= "{$in}Children:\n";
        foreach ($this->children as $child) {
            $string .= "{$in}    ".$child->name."\n";
        }

        $string .= "{$in}primary key value: ".$this->primary_key."\n";

        $string .= "{$in}uses lookup: ".$this->uses_lookup."\n";

        return $string;
    }
    
    /**
     * Gets the table's name.
     *
     * @return string the name of the table.
     */
    public function getName()
    {
        return $this->name;
    }
}

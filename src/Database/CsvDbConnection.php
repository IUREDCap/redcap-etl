<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\LookupTable;
use IU\REDCapETL\Schema\FieldType;

/**
 * Database connection class for CSV (Comma-Separated Values) files.
 * When this class is used, CSV files are generated, instead of
 * database tables.
 */
class CsvDbConnection extends DbConnection
{
    const FILE_EXTENSION = '.csv';

    private $directory;
    private $lookupTable;
    private $id;

    private $columnNamesMap;  // map from table name to array of column names

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        $this->directory = $dbString;

        $this->columnNamesMap = array();

        $idValues = array(DbConnectionFactory::DBTYPE_CSV, $dbString);
        $this->id = DbConnection::createConnectionString($idValues);

        # If the directory doesn't end with a separator, add one
        if (substr($dbString, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
            $this->directory .= DIRECTORY_SEPARATOR;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    private function getTableFile($table)
    {
        $file = $this->directory  . $table->getName() . CsvDbConnection::FILE_EXTENSION;
        return $file;
    }

    private function getLabelViewFile($table)
    {
        $file = $this->directory .  $table->getName() . $this->labelViewSuffix . CsvDbConnection::FILE_EXTENSION;
        return $file;
    }


    protected function existsTable($table)
    {
        return file_exists($this->getTableFile($table));
    }

    protected function existsLabelView($table)
    {
        return file_exists($this->getLabelViewFile($table));
    }

    public function dropTable($table, $ifExists = false)
    {
        if (!$ifExists || ($ifExists && $this->existsTable($table))) {
            $file = $this->getTableFile($table);
            unlink($file);
        }
    }

    public function createTable($table, $ifNotExists = false)
    {
        if (!$ifNotExists || ($ifNotExists && !$this->existsTable($table))) {
            $file = $this->getTableFile($table);
            $fh = fopen($file, 'w');

            $this->createTableHeader($fh, $table);

            fclose($fh);
        }
        return 1;
    }

    private function createTableHeader($fh, $table)
    {
        $fields = $table->getAllFields();
        $isFirst = true;
        foreach ($fields as $field) {
            if ($isFirst) {
                $isFirst = false;
            } else {
                fwrite($fh, ',');
            }
            fwrite($fh, '"'.$field->dbName.'"');
        }
        fwrite($fh, PHP_EOL);
    }

    private function createLabelViewHeader($fh, $table)
    {
        $fields = $table->getAllFields();
        $isFirst = true;
        foreach ($fields as $field) {
            if (!$field->isLabel()) {
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    fwrite($fh, ',');
                }
                fwrite($fh, '"'.$field->dbName.'"');
            }
        }
        fwrite($fh, PHP_EOL);
    }


    public function addPrimaryKeyConstraint($table)
    {
        ; // CSV doesn't support primary keys, so this is not supported
    }

    public function addForeignKeyConstraint($table)
    {
        ; // CSV doesn't support foreign keys, so this is not supported
    }

    public function dropLabelView($table, $ifExists = false)
    {
        if (!$ifExists || ($ifExists && $this->existsLabelView($table))) {
            $file = $this->getLabelViewFile($table);
            unlink($file);
        }
    }

    /**
     * Creates the spreadsheet that is used to store
     * the version of the table that had labels (instead
     * of numeric codes) for multiple choice fields.
     *
     * Note: no way to create a view with CSV, so just
     *       need to create another file.
     *
     * @param Table $table the table for which the
     *     "view" with labels is being created.
     *
     * @param LookupTable $lookupTable the lookup table for the schema
     *     for which tables are being created.
     */
    public function replaceLookupView($table, $lookupTable)
    {
        if (!isset($this->lookupTable)) {
            $this->lookupTable = $lookupTable;
        }
        
        if ($table->getNeedsLabelView()) {
            $labelViewFile = $this->getLabelViewFile($table);

            $fileHandle = fopen($labelViewFile, 'w');
            $this->createLabelViewHeader($fileHandle, $table);
            fclose($fileHandle);
        }
    }


    /**
     * Inserts the rows from the specified table
     * into the database.
     */
    protected function insertRows($table, $batchSize = null)
    {
        $rows = $table->getRows();
        foreach ($rows as $row) {
            $this->insertRow($row);
        }
    }

    /**
     * Insert the specified row into its table, and since CSV does not support
     * views, also insert the corresponding row into the label view table.
     */
    public function insertRow($row)
    {
        $table = $row->table;
        $usesLookup = $table->usesLookup;
        $needsLabelView = $table->getNeedsLabelView();

        $file  = $this->getTableFile($table);

        #----------------------------------
        # Lookup processing
        #----------------------------------
        $lfh = null;
        $lookup = null;
        if ($usesLookup && $needsLabelView) {
            $labelViewFile = $this->getLabelViewFile($table);
            $lfh = fopen($labelViewFile, 'a');
        }

        $fh  = fopen($file, 'a');

        $this->insertRowIntoFiles($fh, $lfh, $row);

        fclose($fh);
        if (isset($lfh)) {
            fclose($lfh);
        }
        return(1);
    }


    /**
     * Inserts a row into files. Since CSV does not support views, when a row is inserted
     * into a table, a corresponding row will also be inserted into its label "view" table.
     *
     * @param resource $fh table file handle
     * @param resource $lfh label "view" of table file handle
     * @param Row $row the row of data to insert
     */
    private function insertRowIntoFiles($fh, $lfh, $row)
    {
        $table = $row->table;
        $position = 0;
        $rowData = $row->getData();

        $labelFileHandle = $lfh;

        # Fix row data to contain all the columns in the table
        $columns = $this->getTableColumnNames($table->GetName());
        $dbFieldNameMap = $table->getDbFieldNameMap();
        $this->fileWrite($fh, $lfh, '');
        
        $isFirst = true;

        foreach ($columns as $column) {
            if (!array_key_exists($column, $dbFieldNameMap)) {
                # Field is not in this task; could be field from another task in workflow
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    $this->fileWrite($fh, $lfh, ',');
                }
            } else {
                $lfh = $labelFileHandle;
                $field = $dbFieldNameMap[$column];

                if ($field->isLabel) {
                    # If this is a label field, set the label "view" file handle to
                    # null, so the values for this field will not be saved in the
                    # label "view" table.
                    $lfh = null;
                }

                if ($isFirst) {
                    $isFirst = false;
                } else {
                    $this->fileWrite($fh, $lfh, ',');
                }

                $fieldType = $field->type;
                $value = $rowData[$field->dbName];

                #------------------------------------------------------
                # Calculate the label for the field (if any)
                #------------------------------------------------------
                $label = null;
                if ($field->usesLookup()) {
                    if ($field->isCheckbox()) {
                        if ($value === 1 || $value === '1') {
                            list($rootName, $checkboxValue) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->dbName);
                            $label = $this->lookupTable->getLabel(
                                $table->getName(),
                                $field->usesLookup(),
                                $checkboxValue
                            );
                            $label = '"' . $label . '"';
                        } else {
                            $label = '0';
                        }
                    } else {    // Non-checkbox field
                        $label = $this->lookupTable->getLabel($table->getName(), $field->usesLookup(), $value);
                        $label = '"' . $label . '"';
                    }
                }
 

                switch ($fieldType) {
                    case FieldType::CHECKBOX:
                        $this->fileWrite($fh, $lfh, $value, $label);
                        break;
                    case FieldType::DATE:
                    case FieldType::DATETIME:
                        $this->fileWrite($fh, $lfh, $value);
                        break;
                    case FieldType::FLOAT:
                        $this->fileWrite($fh, $lfh, $value);
                        break;
                    case FieldType::INT:
                        $this->fileWrite($fh, $lfh, $value, $label);
                        break;
                    case FieldType::CHAR:
                    case FieldType::VARCHAR:
                    case FieldType::STRING:
                        if (isset($label)) {
                            $this->fileWrite($fh, $lfh, '"'.$value.'"', $label);
                        } else {
                            $this->fileWrite($fh, $lfh, '"'.$value.'"');
                        }
                        break;
                    default:
                        $this->fileWrite($fh, $lfh, $value, $label);
                }
                $position++;
            }
        }
        $this->fileWrite($fh, $lfh, PHP_EOL);
    }


    /**
     * Writes the specified value (or it's label) to the
     * specified file or files.
     *
     * @param resource $fh table file handle
     * @param resource $lfh label table file handle
     */
    private function fileWrite($fh, $lfh, $value, $label = null)
    {
        fwrite($fh, $value);
        if (isset($lfh)) {
            if (isset($label)) {
                fwrite($lfh, $label);
            } else {
                fwrite($lfh, $value);
            }
        }
    }
    
    public function getTableColumnNames($tableName)
    {
        $columnNames = array();

        if (array_key_exists($tableName, $this->columnNamesMap)) {
            $columnNames = $this->columnNamesMap[$tableName];
        } else {
            $tableFile = $this->directory . $tableName . '.csv';

            @$fh = fopen($tableFile, 'r');
            if ($fh) {
                $columnNames = fgetcsv($fh);
                fclose($fh);
            }
            if (!empty($columnNames)) {
                # Create a cache for column names, otherwise they
                # will be retrieved from the table file for each
                # row that is inserted into the file.
                $this->columnNamesMap[$tableName] = $columnNames;
            }
        }

        return $columnNames;
    }

    public function processQueryFile($queryFile)
    {
        $message = "Processing a query file is not supported for CSV files";
        throw new EtlException($message, EtlException::INPUT_ERROR);
    }
    
    public function processQueries($queries)
    {
        $message = "Processing a queries is not supported for CSV files";
        throw new EtlException($message, EtlException::INPUT_ERROR);
    }

    /**
     * Gets the data as an array of maps from column name to data value.
     */
    public function getData($tableName, $orderByField = null)
    {
        $data = array();

        $tableFile = $this->directory . $tableName . '.csv';

        $fh = fopen($tableFile, "r");

        if (isset($fh) && !feof($fh)) {
            $columnNames = fgetcsv($fh);   # The first row is the column names
            while (!feof($fh)) {
                $row = fgetcsv($fh);
                if (!empty($row)) {
                    # Change row from array of data values to map from column name to data value
                    $row = array_combine($columnNames, $row);
                    $data[] = $row;
                }
            }
        }
        fclose($fh);

        if (!empty($orderByField)) {
            $orderByFieldValues = array_column($data, $orderByField);
            array_multisort($orderByFieldValues, SORT_ASC, $data);
        }

        return $data;
    }
}

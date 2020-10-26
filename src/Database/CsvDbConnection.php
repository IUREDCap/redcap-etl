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
    private $lookup;
    private $lookupTable;
    private $id;

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        $this->directory = $dbString;

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
        $file = $this->directory  . $table->name . CsvDbConnection::FILE_EXTENSION;
        return $file;
    }

    private function getLabelViewFile($table)
    {
        $file = $this->directory .  $table->name . $this->labelViewSuffix . CsvDbConnection::FILE_EXTENSION;
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
     * @param Table table the table for which the
     *     "view" with labels is being created.
     * @param LookupTable $lookupTable the lookup table for the schema
     *     for which tables are being created.
     */
    public function replaceLookupView($table, $lookupTable)
    {
        if (!isset($this->lookupTable)) {
            $this->lookupTable = $lookupTable;
        }
        
        $labelViewFile = $this->getLabelViewFile($table);

        $fileHandle = fopen($labelViewFile, 'w');
        $this->createTableHeader($fileHandle, $table);
        fclose($fileHandle);
    }


    /**
     * Inserts the rows from the specified table
     * into the database.
     */
    protected function insertRows($table)
    {
        $rows = $table->getRows();
        foreach ($rows as $row) {
            $this->insertRow($row);
        }
    }

    /**
     * Insert the specified row into its table.
     */
    public function insertRow($row)
    {
        $table = $row->table;
        $usesLookup = $table->usesLookup;

        $file  = $this->getTableFile($table);

        #----------------------------------
        # Lookup processing
        #----------------------------------
        $lfh = null;
        $lookup = null;
        if ($usesLookup) {
            $labelViewFile = $this->getLabelViewFile($table);
            $lfh = fopen($labelViewFile, 'a');
        }

        $fh  = fopen($file, 'a');

        $this->insertRowIntoFile($fh, $lfh, $row);

        fclose($fh);
        if (isset($lfh)) {
            fclose($lfh);
        }
        return(1);
    }


    /**
     * @param resource $fh table file handle
     * @param resource $lfh label "view" of table file handle
     * @param Row $row the row of data to insert
     */
    private function insertRowIntoFile($fh, $lfh, $row)
    {
        $table = $row->table;
        $isFirst = true;
        $position = 0;
        $rowData = $row->getData();
        foreach ($table->getAllFields() as $field) {
            $fieldType = $field->type;
            $value = $rowData[$field->name];

            if ($isFirst) {
                $isFirst = false;
            } else {
                $this->fileWrite($fh, $lfh, ',');
            }

            #------------------------------------------------------
            # Calculate the label for the field (if any)
            #------------------------------------------------------
            $label = null;
            if ($field->usesLookup) {
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->dbName)) {
                //if ($fieldType === FieldType::CHECKBOX) {  // This is wrong, because CHECKBOX field becomes int fields
                    if ($value === 1 || $value === '1') {
                        list($rootName, $checkboxValue) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->dbName);
                        $label = $this->lookupTable->getLabel($table->name, $field->usesLookup, $checkboxValue);
                    } else {
                        $label = '0';
                    }
                } else {    // Non-checkbox field
                    $label = $this->lookupTable->getLabel($table->name, $field->usesLookup, $value);
                }
            }
                    
            switch ($fieldType) {
                case FieldType::CHECKBOX:
                    //$label = null;
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
                    //$label = null;
                    $this->fileWrite($fh, $lfh, $value, $label);
                    break;
                case FieldType::CHAR:
                case FieldType::VARCHAR:
                case FieldType::STRING:
                    if (isset($label)) {
                        $this->fileWrite($fh, $lfh, '"'.$value.'"', '"'.$label.'"');
                    } else {
                        $this->fileWrite($fh, $lfh, '"'.$value.'"');
                    }
                    break;
                default:
                    $this->fileWrite($fh, $lfh, $value, $label);
            }
            $position++;
        }
        $this->fileWrite($fh, $lfh, PHP_EOL);
    }


    /**
     * Writes the specified value (or it's label) to the
     * specified file or files.
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
}

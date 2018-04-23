<?php

namespace IU\REDCapETL\Database;

/**
 *
 * DbConnection knows about Schema ojbects and how to read/write them to
 * storage systems such as MySQL and CSV files
 *
 * DbConnection is a parent class that is extended by storage-system
 * specific classes. It should not be instantiated.
 */
abstract class DbConnection
{
    public $errorString;
    protected $tablePrefix;
    protected $labelViewSuffix;
    
    private $errorHandler;

    public function __construct($dbString, $tablePrefix, $labelViewSuffix)
    {
        $this->tablePrefix = $tablePrefix;
        $this->labelViewSuffix = $labelViewSuffix;
    }

    /**
     * Replaces the specified table in the database
     *
     * @param Table $table the table to be replaced.
     */
    public function replaceTable($table)
    {

        if ($this->existsTable($table)) {
            $this->dropTable($table);
        }

        $this->createTable($table);
    }


    abstract protected function existsTable($table);

    abstract protected function dropTable($table);

    abstract protected function createTable($table);

    abstract public function replaceLookupView($table, $lookup);

    public function storeRow($row)
    {

        if ($this->existsRow($row)) {
            $this->updateRow($row);
        } else {
            $rc = $this->insertRow($row);
        }

        // If there's an error
        if (false === $rc) {
            return(false);
        }

        return(1);
    }

    public function storeRows($table)
    {
        $rc = $this->insertRows($table);
        return $rc;
    }

    abstract protected function existsRow($row);

    abstract protected function updateRow($row);

    abstract protected function insertRow($row);

    abstract protected function insertRows($table);
    
    abstract public function processQueryFile($queryFile);
}

<?php

namespace IU\REDCapETL\Database;

/**
 * Abstract database connection class that is used as
 * a parent class by storage-system specific classes.
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

    abstract public function createTable($table, $ifNotExists = false);

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

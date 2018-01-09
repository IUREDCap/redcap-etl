<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\DbUnit\DataSet\AbstractDataSet;
use PHPUnit\DbUnit\DataSet\DefaultTableMetadata;
use PHPUnit\DbUnit\DataSet\DefaultTable;
use PHPUnit\DbUnit\DataSet\DefaultTableIterator;

/**
 * This class defines an ArrayDataSet. By defining this, we can create
 * DataSets from in-code arrays, rather than reading them in from files.
 *
 * This allows us to put the expected values for our tests in code rather
 * than having to create separate files for them.
 */
class ArrayDataSet extends AbstractDataSet
{

    protected $tables = array();

    // @param array $data
    public function __construct(array $data)
    {
        foreach ($data as $tableName => $rows) {
            $columns = array();

            if (isset($rows[0])) {
                $columns = array_keys($rows[0]);
            }

            $metaData = new DefaultTableMetaData($tableName, $columns);
            $table = new DefaultTable($metaData);

            foreach ($rows as $row) {
                $table->addRow($row);
            }
            $this->tables[$tableName] = $table;
        }
    }

    protected function createIterator($reverse = false)
    {
        return new DefaultTableIterator($this->tables, $reverse);
    }

    public function getTable($tableName)
    {
        if (!isset($this->tables[$tableName])) {
            throw new InvalidArgumentException("$tableName is not a table in the current database.");
        }

        return $this->tables[$tableName];
    }
}

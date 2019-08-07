<?php

namespace IU\REDCapETL\Database;

/**
 * Abstract database connection class that is used as
 * a parent class by storage-system specific classes.
 */
abstract class DbConnection
{
    const DB_CONNECTION_STRING_SEPARATOR = ':';
    const DB_CONNECTION_STRING_ESCAPE_CHARACTER = '\\';
    
    public $errorString;
    protected $tablePrefix;
    protected $labelViewSuffix;
    
    private $errorHandler;

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
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

    abstract public function insertRow($row);

    abstract protected function insertRows($table);
    
    abstract public function processQueryFile($queryFile);
    
    /**
     * Creates a connection string from the specified values. The
     * following characters are escaped using a backslash: '\', ':'
     *
     * @param array $values an array of string values from which to
     *     create the connection string.
     */
    public static function createConnectionString($values)
    {
        $connectionString = '';
        
        $isFirst = true;
        foreach ($values as $value) {
            if ($isFirst) {
                $isFirst = false;
            } else {
                $connectionString .= self::DB_CONNECTION_STRING_SEPARATOR;
            }
            
            $escapedValue = '';
            for ($i = 0; $i < strlen($value); $i++) {
                $char = $value[$i];
                if ($char === '\\') {  # Escape character
                    $escapedValue .= '\\\\';
                } elseif ($char === ':') {
                    $escapedValue .= '\\:';
                } else {
                    $escapedValue .= $char;
                }
            }
            $connectionString .= $escapedValue;
        }

        return $connectionString;
    }
    
    public static function parseConnectionString($connectionString)
    {
        $connectionValues = array();
        
        $escaped = false;
        $value = '';
        
        for ($i = 0; $i < strlen($connectionString); $i++) {
            $char = $connectionString[$i];
            if ($char === '\\') { // Escape character
                if ($escaped) {
                    $value .= '\\';
                    $escaped = false;
                } else {
                    $escaped = true;
                }
            } elseif ($char === ':') {
                if ($escaped) {
                    # if this is an escaped ':'
                    $value .= ':';
                    $escaped = false;
                } else {
                    # if this is a separator
                    array_push($connectionValues, $value);
                    $value = '';
                }
            } else {
                if ($escaped) {
                    $value .= '\\';
                    $escaped = false;
                }
                $value .= $char;
            }
        }
        array_push($connectionValues, $value);
                            
        return $connectionValues;
    }


    /**
     * Parses the SQL queries in the specified text and
     * returns an array of queries.
     */
    public function parseSqlQueries($text)
    {
        $queries = array();

        $separators = [];    // SQL query separators
        $lastChar = '';
        $quoteStarted = false;
        $quoteEnded   = false;
        $charEscapeStarted = false;
        $inComment = false;
        $atEnd = false;

        for ($i = 0; $i < strlen($text); $i++) {
            if ($i === strlen($text) - 1) {
                $atEnd = true;
            }

            $char = $text[$i];

            if ($quoteStarted) {
                # In quote
                if ($quoteEnded) {
                    if ($char === "'") {
                        # quote wasn't actually ended,
                        # end quote was escape quote, and this is the
                        # quote being escaped
                        $quoteEnded = false;
                    } else {
                        $quoteStarted = false;
                        $quoteEnded   = false;
                    }
                } else {
                    if ($charEscapeStarted) {
                        $charEscapeStarted = false;
                    } elseif ($char === '\\') {
                        $charEscapeStarted = true;
                    } elseif ($char === "'") {
                        $quoteEnded = true;
                    }
                }
            } elseif ($inComment) {
                # In comment

                # If end of line reached, comment ends
                if ($char === "\n") {
                    $inComment = false;
                    $text[$i] = ' ';
                }
            } else {
                #--------------------------------
                # Not in quote or comment
                #--------------------------------
                if ($char === ';') {
                    $separators[] = $i;
                } elseif ($char === "'") {
                    $quoteStarted = true;
                } elseif ($char === '-' && !$atEnd && $text[$i+1] === '-') {
                    $inComment = true;
                } elseif ($char === "\n" || $char === "\r" || $char === "\t") {
                    $text[$i] = ' ';
                }
            }
            $lastChar = $char;
        }

        ###print_r($separators);

        $startIndex = 0;
        $query = '';
        for ($i = 0; $i < count($separators); $i++) {
            $query = substr($text, $startIndex, $separators[$i] - $startIndex);
            $queries[] = trim($query);
            $startIndex = $separators[$i] + 1;
        }
        $i = count($separators) - 1;
        $query = trim(substr($text, $separators[$i] + 1));
        if (!empty($query)) {
            $queries[] = $query;
        }

        return $queries;
    }
}

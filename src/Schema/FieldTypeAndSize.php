<?php

namespace IU\REDCapETL\Schema;

/**
 * Contains a field type specification.
 */
class FieldTypeAndSize
{
    public $type;  // FieldType
    public $size;  // int

    /**
     * Constructor.
     *
     * @param FieldType $type the type (name) for the field.
     * @param int $size the size of the field (for field types that have sizes)
     */
    public function __construct($type, $size)
    {
        $this->type = $type;
        $this->size = $size;
    }
}

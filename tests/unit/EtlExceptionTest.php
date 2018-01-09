<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for EtlException class.
 */
class EtlExceptionTest extends TestCase
{

    public function setUp()
    {
    }
    
    public function testConstructor()
    {
        $exception = new EtlException('Test exception', 1, null);
        $this->assertNotNull($exception, 'exception not null check');
    }
}

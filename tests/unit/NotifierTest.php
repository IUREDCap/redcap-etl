<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Notifier class.
 */
class NotifierTest extends TestCase
{
    private $project;

    public function setUp()
    {
    }
    
    public function testConstructor()
    {
        $sender     = 'sender@mailinator.com';
        $recipients = 'recipient1@mailinator.com,recipient2@mailinator.com';
        $subject    = 'REDCap-ETL Notifier Test';
        
        $notifier = new Notifier($sender, $recipients, $subject);
        $this->assertNotNull($notifier, 'Notifer not null check');
        
        $this->assertEquals($sender, $notifier->getSender(), 'Sender check');
        $this->assertEquals($recipients, $notifier->getRecipients(), 'Recipients check');
        $this->assertEquals($subject, $notifier->getSubject(), 'Subject check');
    }
}

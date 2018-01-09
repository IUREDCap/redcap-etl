<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class TablesTest extends DatabaseTestCase
{

    public function testDemographyTable()
    {
        $expectedDataSet = new ArrayDataSet(
            array(
                  'Demography' => array(
                         array('record_id' => 1,
                 'fruit' => '2',
                             'name' => 'John Doe',
                             'phone' => '123-456-7890',
                             'dob' => '1980-10-05',
                 'color' => '3')
            ))
        );
        $expectedTable = $expectedDataSet->getTable('Demography');

        $queryTable = $this->getConnection()->createQueryTable(
            'Demography',
            'SELECT record_id, fruit, name, phone, dob, color '.
            'FROM Demography'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    public function testDemographyAndBmiTables()
    {
        $expectedDataSet = new ArrayDataSet(array(
                  'Demography' => array(
                         array('record_id' => 1,
                               'name' => 'John Doe'
                               ),
                   ),
           'BMI' => array(
             array('record_id' => 1,
                   'height' => 72.0,
                   'weight' => 203.5
                   )
                   )));
        $expectedTable1 = $expectedDataSet->getTable('Demography');
        $expectedTable2 = $expectedDataSet->getTable('BMI');

        $queryTable1 = $this->getConnection()->createQueryTable(
            'Demography',
            'SELECT record_id, name FROM Demography'
        );

        $queryTable2 = $this->getConnection()->createQueryTable(
            'BMI',
            'SELECT record_id, height, weight FROM BMI'
        );

        $this->assertTablesEqual($expectedTable1, $queryTable1);
        $this->assertTablesEqual($expectedTable2, $queryTable2);
    }

    public function testVisitInfoTable()
    {
        $expectedDataSet = new ArrayDataSet(array(
                  'VisitInfo' => array(
                         array('visitinfo_id' => 1,
                   'record_id' => 1,
                               'visit_date' => '2016-10-01',
                   'satisfaction' => 1),
                         array('visitinfo_id' => 2,
                   'record_id' => 1,
                               'visit_date' => '2016-10-08',
                   'satisfaction' => 5)
                   )));
        $expectedTable = $expectedDataSet->getTable('VisitInfo');

        $queryTable = $this->getConnection()->createQueryTable(
            'VisitInfo',
            'SELECT visitinfo_id, record_id, visit_date, satisfaction '.
            'FROM VisitInfo'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }


    public function testVisitInfoAndVisitResultsTable()
    {

        $expectedDataSet = new ArrayDataSet(array(
                  'VisitInfo' => array(
                         array('visitinfo_id' => 1,
                   'record_id' => 1,
                               'visit_date' => '2016-10-01'),
                         array('visitinfo_id' => 2,
                   'record_id' => 1,
                               'visit_date' => '2016-10-08')
                   ),
                  'VisitResults' => array(
                         array('visitresults_id' => 1,
                   'record_id' => 1,
                   'sleep_hours' => 7.5),
                         array('visitresults_id' => 2,
                   'record_id' => 1,
                   'sleep_hours' => 8.5)
                   )));
        $expectedTable1 = $expectedDataSet->getTable('VisitInfo');
        $expectedTable2 = $expectedDataSet->getTable('VisitResults');

        $queryTable1 = $this->getConnection()->createQueryTable(
            'VisitInfo',
            'SELECT visitinfo_id, record_id, visit_date FROM VisitInfo'
        );

        $queryTable2 = $this->getConnection()->createQueryTable(
            'VisitResults',
            'SELECT visitresults_id, record_id, sleep_hours FROM VisitResults'
        );

        $this->assertTablesEqual($expectedTable1, $queryTable1);
        $this->assertTablesEqual($expectedTable2, $queryTable2);
    }


    public function testContactTable()
    {
        $expectedDataSet = new ArrayDataSet(array(
                  'Contact' => array(
                         array('contact_id' => 1,
                               'record_id' => 1,
                               'email' => 'johndoe@iu.edu'),
                         array('contact_id' => 2,
                               'record_id' => 1,
                               'email' => 'johndoe@gmail.com')
                   )));
        $expectedTable = $expectedDataSet->getTable('Contact');

        $queryTable = $this->getConnection()->createQueryTable(
            'Contact',
            'SELECT contact_id, record_id, email '.
            'FROM Contact'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }


    public function testRegression6()
    {

        $expectedDataSet = new ArrayDataSet(array(
                  'VisitResults' => array(
                         array('visitresults_id' => 1,
                               'record_id' => 1),
                         array('visitresults_id' => 2,
                               'record_id' => 1)
                   ),
                  'Labs' => array(
                         array('labs_id' => 1,
                               'visitresults_id' => 1,
                               'lab' => '5.0'),
                         array('labs_id' => 2,
                               'visitresults_id' => 1,
                               'lab' => '6.5'),
                         array('labs_id' => 3,
                               'visitresults_id' => 2,
                               'lab' => '7.5'),
                         array('labs_id' => 4,
                               'visitresults_id' => 2,
                               'lab' => '9.0')
                   )));
        $expectedTable1 = $expectedDataSet->getTable('VisitResults');
        $expectedTable2 = $expectedDataSet->getTable('Labs');

        $queryTable1 = $this->getConnection()->createQueryTable(
            'VisitResults',
            'SELECT visitresults_id, record_id FROM VisitResults'
        );

        $queryTable2 = $this->getConnection()->createQueryTable(
            'Labs',
            'SELECT labs_id, visitresults_id, lab FROM Labs'
        );

        $this->assertTablesEqual($expectedTable1, $queryTable1);
        $this->assertTablesEqual($expectedTable2, $queryTable2);
    }


    public function testRecipientsAndSentTables()
    {

        $expectedDataSet = new ArrayDataSet(array(
                  'Recipients' => array(
                         array('recipients_id' => 1,
                   'record_id' => 1,
                   'recip' => 'Spouse'),
                         array('recipients_id' => 2,
                   'record_id' => 1,
                   'recip' => 'Parent')
                   ),
                  'Sent' => array(
                         array('sent_id' => 1,
                   'recipients_id' => 1,
                               'sent' => '2016-08-05'),
                         array('sent_id' => 2,
                   'recipients_id' => 1,
                               'sent' => '2016-08-09'),
                         array('sent_id' => 3,
                   'recipients_id' => 2,
                               'sent' => '2016-09-05'),
                         array('sent_id' => 4,
                   'recipients_id' => 2,
                               'sent' => '2016-09-09')
                   )));
        $expectedTable1 = $expectedDataSet->getTable('Recipients');
        $expectedTable2 = $expectedDataSet->getTable('Sent');

        $queryTable1 = $this->getConnection()->createQueryTable(
            'Recipients',
            'SELECT recipients_id, record_id, recip FROM Recipients'
        );

        $queryTable2 = $this->getConnection()->createQueryTable(
            'Sent',
            'SELECT sent_id, recipients_id, sent FROM Sent'
        );

        $this->assertTablesEqual($expectedTable1, $queryTable1);
        $this->assertTablesEqual($expectedTable2, $queryTable2);
    }

    public function testLookupTable()
    {
        $expectedDataSet = new ArrayDataSet(array(
                  'Lookup' => array(
                         array('category' => '1',
                   'label' => 'red'),
                         array('category' => '2',
                   'label' => 'yellow'),
                         array('category' => '3',
                   'label' => 'blue')
                   )));
        $expectedTable = $expectedDataSet->getTable('Lookup');

        $queryTable = $this->getConnection()->createQueryTable(
            'Lookup',
            'SELECT category,label '.
            'FROM Lookup '.
            'where table_name like "Demography" '.
            'and field_name like "color"'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }


    public function testDemographyVLookupTable()
    {
        $expectedDataSet = new ArrayDataSet(array(
                  'Demography_v' => array(
                         array('record_id' => 1,
                 'fruit' => 'pear',
                             'name' => 'John Doe',
                             'phone' => '123-456-7890',
                             'dob' => '1980-10-05',
                 'color' => 'blue')
                   )));
        $expectedTable = $expectedDataSet->getTable('Demography_v');

        $queryTable = $this->getConnection()->createQueryTable(
            'Demography_v',
            'SELECT record_id, fruit, name, phone, dob, color '.
            'FROM Demography_vLookup'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    public function testDemography2()
    {
        $expectedDataSet = new ArrayDataSet(array(
                  'Demography' => array(
                         array('record_id' => 1,
                 'rooms___1' => 0,
                 'rooms___22' => 1,
                 'rooms___303' => 1)
                   )));
        $expectedTable = $expectedDataSet->getTable('Demography');

        $queryTable = $this->getConnection()->createQueryTable(
            'Demography',
            'SELECT record_id, rooms___1, rooms___22, rooms___303 '.
            'FROM Demography'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }


    public function testContactTable2()
    {

        $expectedDataSet = new ArrayDataSet(array(
                  'Contact' => array(
                         array('contact_id' => 1,
                   'record_id' => 1,
                   'echeck___1' => 0,
                   'echeck___2' => 1),
                         array('contact_id' => 2,
                   'record_id' => 1,
                   'echeck___1' => 1,
                   'echeck___2' => 0),
                   )));
        $expectedTable = $expectedDataSet->getTable('Contact');

        $queryTable = $this->getConnection()->createQueryTable(
            'Contact',
            'SELECT contact_id, record_id, echeck___1, echeck___2 '.
            'FROM Contact'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }


    public function testContactVLookupTable()
    {

        $expectedDataSet = new ArrayDataSet(array(
                  'Contact_v' => array(
                         array('contact_id' => 1,
                             'workat' => 'Home'),
                         array('contact_id' => 2,
                             'workat' => 'Coffee Shop'),
                   )));
        $expectedTable = $expectedDataSet->getTable('Contact_v');

        $queryTable = $this->getConnection()->createQueryTable(
            'Contact_v',
            'SELECT contact_id, workat '.
            'FROM Contact_vLookup'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    public function testDemographyVLookupTable2()
    {

        $expectedDataSet = new ArrayDataSet(array(
                  'Demography_v' => array(
                         array('record_id' => 1,
                 'rooms___1' => 0,
                 'rooms___22' => 'Den',
                 'rooms___303' => 'Kitchen')
                   )));
        $expectedTable = $expectedDataSet->getTable('Demography_v');

        $queryTable = $this->getConnection()->createQueryTable(
            'Demography_v',
            'SELECT record_id, rooms___1, rooms___22, rooms___303 '.
            'FROM Demography_vLookup'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }


    public function testContactVLookupTable2()
    {
        $expectedDataSet = new ArrayDataSet(array(
                  'Contact_v' => array(
                         array('contact_id' => 1,
                   'record_id' => 1,
                   'echeck___1' => 0,
                   'echeck___2' => 'Night'),
                         array('contact_id' => 2,
                   'record_id' => 1,
                   'echeck___1' => 'Morning',
                   'echeck___2' => 0),
                   )));
        $expectedTable = $expectedDataSet->getTable('Contact_v');

        $queryTable = $this->getConnection()->createQueryTable(
            'Contact_v',
            'SELECT contact_id, record_id, echeck___1, echeck___2 '.
            'FROM Contact_vLookup'
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    public function testFollowupTable()
    {

          $expectedDataSet = new ArrayDataSet(array(
                  'Followup' => array(
                         array('followup_id' => 1,
                   'record_id' => 1,
                               'impression' => '1'),
                         array('followup_id' => 2,
                   'record_id' => 1,
                               'impression' => '2'),
                         array('followup_id' => 3,
                   'record_id' => 1,
                               'impression' => '3'),
                         array('followup_id' => 4,
                   'record_id' => 1,
                               'impression' => '4')
                   )));
          $expectedTable1 = $expectedDataSet->getTable('Followup');

          $queryTable1 = $this->getConnection()->createQueryTable(
              'Followup',
              'SELECT followup_id, record_id, impression FROM Followup'
          );

          $this->assertTablesEqual($expectedTable1, $queryTable1);
    }
}

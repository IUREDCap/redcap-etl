<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class VisitsTestUtility
{
    public static function dropTablesAndViews($dbh)
    {
        $dbh->exec("DROP TABLE IF EXISTS BMI");
        $dbh->exec("DROP TABLE IF EXISTS Contact");
        $dbh->exec("DROP VIEW  IF EXISTS Contact_vLookup");
        $dbh->exec("DROP TABLE IF EXISTS Demography");
        $dbh->exec("DROP VIEW  IF EXISTS Demography_vLookup");
        $dbh->exec("DROP TABLE IF EXISTS Followup");
        $dbh->exec("DROP VIEW  IF EXISTS Followup_vLookup");
        $dbh->exec("DROP TABLE IF EXISTS Labs");
        $dbh->exec("DROP TABLE IF EXISTS Lookup");
        $dbh->exec("DROP TABLE IF EXISTS Recipients");
        $dbh->exec("DROP TABLE IF EXISTS Sent");
        $dbh->exec("DROP TABLE IF EXISTS VisitInfo");
        $dbh->exec("DROP VIEW  IF EXISTS VisitInfo_vLookup");
        $dbh->exec("DROP TABLE IF EXISTS VisitResults");
    }

    public static function testAll($testCase, $dbh)
    {
        self::testDemographyTable($testCase, $dbh);
        self::testBmiTable($testCase, $dbh);
        self::testVisitInfoTable($testCase, $dbh);
        self::testVisitResultsTable($testCase, $dbh);
        self::testContactTable($testCase, $dbh);
        self::testLabsTable($testCase, $dbh);
        self::testRecipientsTable($testCase, $dbh);
        self::testSentTable($testCase, $dbh);
        self::testLookupTable($testCase, $dbh);
        self::testDemographyView($testCase, $dbh);
        self::testContactView($testCase, $dbh);
        self::testFollowupTable($testCase, $dbh);
    }


    public static function testDemographyTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'record_id' => 1,
                'fruit' => '2',
                'name' => 'John Doe',
                'phone' => '123-456-7890',
                'dob' => '1980-10-05',
                'color' => '3',
                'rooms___1' => 0,
                'rooms___22' => 1,
                'rooms___303' => 1
            )
        );

        $sql = 'SELECT record_id, fruit, name, phone, dob, color,'
            .' rooms___1, rooms___22, rooms___303 '
            .' FROM Demography ORDER BY record_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Demography table test');
    }

    public static function testBmiTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'record_id' => 1,
                'height' => 72.0,
                'weight' => 203.5
            )
        );

        $sql = 'SELECT record_id, height, weight FROM BMI';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'BMI table test');
    }


    public static function testVisitInfoTable($testCase, $dbh)
    {
        $expectedData =array(
            array(
                'visitinfo_id' => 1,
                'record_id' => 1,
                'visit_date' => '2016-10-01',
                'satisfaction' => 1
            ),
            array(
                'visitinfo_id' => 2,
                'record_id' => 1,
                'visit_date' => '2016-10-08',
                'satisfaction' => 5
            )
        );

        $sql = 'SELECT visitinfo_id, record_id, visit_date, satisfaction '
            .'FROM VisitInfo ORDER BY visitinfo_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'VisitInfo table test');
    }


    public static function testVisitResultsTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'visitresults_id' => 1,
                'record_id' => 1,
                'sleep_hours' => 7.5
            ),
            array(
                'visitresults_id' => 2,
                'record_id' => 1,
                'sleep_hours' => 8.5
            )
        );

        $sql = 'SELECT visitresults_id, record_id, sleep_hours'
            .' FROM VisitResults ORDER BY visitresults_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'VisitResults table test');
    }


    public static function testContactTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'contact_id' => 1,
                'record_id' => 1,
                'email' => 'johndoe@iu.edu',
                'echeck___1' => 0,
                'echeck___2' => 1
            ),
            array(
                'contact_id' => 2,
                'record_id' => 1,
                'email' => 'johndoe@gmail.com',
                'echeck___1' => 1,
                'echeck___2' => 0
            )
        );

        $sql = 'SELECT contact_id, record_id, email, echeck___1, echeck___2 '
            .'FROM Contact ORDER BY contact_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Contact table test');
    }


    public static function testLabsTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'labs_id' => 1,
                'visitresults_id' => 1,
                'lab' => '5.0'
            ),
            array(
                'labs_id' => 2,
                'visitresults_id' => 1,
                'lab' => '6.5'
            ),
            array(
                'labs_id' => 3,
                'visitresults_id' => 2,
                'lab' => '7.5'
            ),
            array(
                'labs_id' => 4,
                'visitresults_id' => 2,
                'lab' => '9.0'
            )
        );

        $sql = 'SELECT labs_id, visitresults_id, lab FROM Labs ORDER BY labs_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Labs table test');
    }


    public static function testRecipientsTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'recipients_id' => 1,
                'record_id' => 1,
                'recip' => 'Spouse'
            ),
            array(
                'recipients_id' => 2,
                'record_id' => 1,
                'recip' => 'Parent'
            )
        );

        $sql = 'SELECT recipients_id, record_id, recip'
            .' FROM Recipients ORDER BY recipients_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Recipients table test');
    }


    public static function testSentTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'sent_id' => 1,
                'recipients_id' => 1,
                'sent' => '2016-08-05'
            ),
            array(
                'sent_id' => 2,
                'recipients_id' => 1,
                'sent' => '2016-08-09'
            ),
            array(
                'sent_id' => 3,
                'recipients_id' => 2,
                'sent' => '2016-09-05'
            ),
            array(
                'sent_id' => 4,
                'recipients_id' => 2,
                'sent' => '2016-09-09'
            )
        );

        $sql = 'SELECT sent_id, recipients_id, sent FROM Sent ORDER BY sent_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Sent table test');
    }


    public static function testLookupTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'category' => '1',
                'label' => 'red'
            ),
            array(
                'category' => '2',
                'label' => 'yellow'
            ),
            array(
                'category' => '3',
                'label' => 'blue'
            )
        );

        $sql =
            "SELECT category,label "
            ." FROM Lookup "
            ." WHERE table_name LIKE 'Demography' "
            ." AND field_name like 'color'"
            ." ORDER BY category";

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Lookup table test');
    }



    public static function testDemographyView($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'record_id' => 1,
                'fruit' => 'pear',
                'name' => 'John Doe',
                'phone' => '123-456-7890',
                'dob' => '1980-10-05',
                'color' => 'blue',
                'rooms___1' => 0,
                'rooms___22' => 'Den',
                'rooms___303' => 'Kitchen'
            )
        );

        $sql = 'SELECT record_id, fruit, name, phone, dob, color, '
            .' rooms___1, rooms___22, rooms___303 '
            .' FROM Demography_vLookup ORDER BY record_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Demography view test');
    }

    public static function testContactView($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'contact_id' => 1,
                'record_id' => 1,
                'workat' => 'Home',
                'echeck___1' => 0,
                'echeck___2' => 'Night'
            ),
            array(
                'contact_id' => 2,
                'record_id' => 1,
                'workat' => 'Coffee Shop',
                'echeck___1' => 'Morning',
                'echeck___2' => 0
            )
        );

        $sql =
            'SELECT contact_id, record_id, workat, echeck___1, echeck___2 '
            .'FROM Contact_vLookup ORDER BY contact_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Contact view test');
    }


    public static function testFollowupTable($testCase, $dbh)
    {
        $expectedData = array(
            array(
                'followup_id' => 1,
                'record_id' => 1,
                'impression' => '1'
            ),
            array(
                'followup_id' => 2,
                'record_id' => 1,
                'impression' => '2'
            ),
            array(
                'followup_id' => 3,
                'record_id' => 1,
                'impression' => '3'
            ),
            array(
                'followup_id' => 4,
                'record_id' => 1,
                'impression' => '4'
            )
        );

        $sql = 'SELECT followup_id, record_id, impression '
            .' FROM Followup ORDER BY followup_id';

        $statement  = $dbh->query($sql);
        $actualData = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $testCase->assertEquals($expectedData, $actualData, 'Followup table test');
    }
}

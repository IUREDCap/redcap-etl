<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

class VisitsWebTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/visits.ini';

    private static $configuration;
    private static $dbh;

    public static function setUpBeforeClass()
    {
        #------------------------------------------------------------
        # Get the database connection
        #
        # For accessing the values after the ETL process has run
        # to see if they're correct
        #------------------------------------------------------------
        $logger = new Logger2('visits_test');

        $useWebScriptLogFile = true;
        $redCapEtl = new RedCapEtl($logger, self::CONFIG_FILE, $useWebScriptLogFile);
        $redCapEtl->setTriggerEtl();
        $configuration = $redCapEtl->getConfiguration();

        self::$configuration = $configuration;

        list($dbHost, $dbUser, $dbPassword, $dbName) = $configuration->getMySqlConnectionInfo();
        $dsn = 'mysql:dbname='.$dbName.';host='.$dbHost;
        try {
            self::$dbh = new \PDO($dsn, $dbUser, $dbPassword);
        } catch (Exception $exception) {
            print "ERROR: ".($exception->getMessage())."\n";
        }
        # Drop the existing tables and views (if any) before the ETL process runs
        VisitsTestUtility::dropTablesAndViews(self::$dbh);
    }


    public static function runWebEtl()
    {
        $configRecord = 1;

        $url = self::$configuration->getProperty(ConfigProperties::WEB_SCRIPT_URL);

        $curlHandle = curl_init($url);
        $data = [
            'project_id' => self::$configuration->getProjectId(),
            'record'     => $configRecord
        ];

        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curlHandle);

        if (curl_error($curlHandle)) {
            print "\n*****CURL ERROR: ".curl_Error($curlHandle)."\n";
        }

        curl_close($curlHandle);
    }

    public function testAll()
    {
        try {
            self::runWebEtl();
            VisitsTestUtility::testAll($this, self::$dbh);
        } catch (\Exception $exception) {
            print "ERROR: ".($exception->getMessage())."\n";
        }
    }
}

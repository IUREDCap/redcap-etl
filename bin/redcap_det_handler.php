<?php

#--------------------------------------------------------------------------
# This is a handler that is called from REDCap to validate transformation
# Rules and, possibly, run the ETL process: Extracting data from REDCap,
# transforming REDCap Records into Tables/Rows and then
# loading it in the database.
#
# The handler is called by REDCap's Data Entry Trigger for the
# Configuration project. This handler will attempt to parse the SchemaMap
# and will use the REDCap API to import feedback about that attempt
# into a freeform field in the Configuration project.
#
# If the flag in the Configuration project is set to kick off the ETL,
# then this handler will also do the ETL.
#
# Regardless of whether or not the ETL is done, this handler will always
# use the REDCap API to reset the flag in the Configuration project to
# not do the ETL. The ETL will only be done if the flag has been
# explicitly set.
#--------------------------------------------------------------------------

//--------------------------------------------------------------------------
// Required libraries
//--------------------------------------------------------------------------
// NOTE: INSTALL_DIR will be replaced by the installation program
set_include_path(get_include_path() . PATH_SEPARATOR . 'REPLACE_INSTALL_DIR');

require('REPLACE_INSTALL_DIR'.'/dependencies/autoload.php');

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\Logger2;

$app = basename(__FILE__, '.php');
$logger = new Logger2($app);

#--------------------------------------------------------------------------------
# The following line should get replaced during install; don't change the format
#--------------------------------------------------------------------------------
$propertiesFile = null;

#------------------------------------------------------------
# Try to create a REDCap ETL Object
#------------------------------------------------------------
try {
    $redCapEtl = new RedCapEtl($logger, null, $propertiesFile);
    $logger->logInfo('Executing web script '.__FILE__);
    $detHandler = $redCapEtl->getDetHandler();
    list($project_id,$record_id) = $detHandler->getDetParams();

    #-----------------------------------------------------------------------
    # Data Entry Trigger: Check for allowed project/servers
    #-----------------------------------------------------------------------
    $detHandler->checkDetId($project_id);
    $detHandler->checkAllowedServers();

    #-----------------------------------------------------------------------
    # Parse Map
    #-----------------------------------------------------------------------
    list($parse_status, $result) = $redCapEtl->parseMap();
 
    # If the parsing of the schema map failed.
    if ($parse_status === TransformationRules::PARSE_ERROR) {
        $msg = "Schema map not fully parsed. Processing stopped.";
        $redCapEtl->log($msg);
        $result .= $msg."\n";
    } else {
        $result .= "Schema map is valid.\n";

        if ($redCapEtl->getTriggerEtl() !== RedCapEtl::TRIGGER_ETL_YES) {
            // ETL not requested
            $msg = "Web-invoked process stopped after parsing, per default.";
            $redCapEtl->log($msg);
            $result .= $msg."\n";
        } else {
            $result .= "ETL proceeding. Please see log for results\n";

            //--------------------------------------------------------------------
            // Extract, Transform, and Load
            //
            // These three steps are joined together at this level so that
            // the data from REDCap can be worked on in batches
            //--------------------------------------------------------------------
            $redCapEtl->loadTables();
            $redCapEtl->extractTransformLoad();
        } // ETL requested
    } // parseMap valid

    // Provide a timestamp for the results
    $result = date('g:i:s a d-M-Y T') . "\n" . $result;

    #-----------------------------------------------------------
    # Upload the results, and set ETL trigger back to default
    #-----------------------------------------------------------
    $redCapEtl->uploadResultAndReset($result, $record_id);
} catch (EtlException $exception) {
    $logger->logException($exception);
    exit(2);
}

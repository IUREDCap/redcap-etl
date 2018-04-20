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
use IU\REDCapETL\Logger;

$app = basename(__FILE__, '.php');
$logger = new Logger($app);

#--------------------------------------------------------------------------------
# The following line should get replaced during install; don't change the format
#--------------------------------------------------------------------------------
$propertiesFile = null;

#------------------------------------------------------------
# Run parsing or ETL, depending on configuration
#------------------------------------------------------------
try {
    $useWebScriptLogFile = true;
    $redCapEtl = new RedCapEtl($logger, $propertiesFile, $useWebScriptLogFile);
    $logger = $redCapEtl->getLogger();
    $redCapEtl->runForDet();
} catch (EtlException $exception) {
    $logger->logException($exception);
    exit(2);
}

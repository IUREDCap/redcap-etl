<?php

namespace IU\REDCapETL;

/**
 * Class containing functions related to handling calls from
 * REDCap's Data Entry Trigger.
 */
class RedCapDetHandler
{
    protected $debug = '';
    
    private $errorHandler;
    
    private $projectId = '';
    private $allowedServers;

    private $logger;
    
    /**
     * Creates a REDCap DET (Data Entry Trigger) handler.
     *
     */
    public function __construct($projectId, $allowedServers, $logger)
    {
        $this->errorHandler = new EtlErrorHandler();
     
        $this->debug = 'no';

        $this->projectId = $projectId;

        // Create array of allowed servers (by hostname)
        if (preg_match("/,/", $allowedServers)) {
            if ('yes' == $this->debug) {
                print "Found multiple allowed servers<br/>";
            }
            $this->allowedServers = preg_split("/,/", $allowedServers);
        } else {
            $this->allowedServers = array($allowedServers);
        }
    }

    /**
     * Gets the DET (Data Entry Trigger) parameters from an HTTP/HHTPS request.
     */
    public function getDetParams()
    {
        // If $_POST['project_id'] is empty, this program assumes
        // that it is being tested by using a URL from a web browser rather
        // than being called by a REDCap Data Entry Trigger.  To perform such
        // a test, use a URL like:
        //
        //     https://redcap-testing.uits.iu.edu/apis/ctp_baseline_handler.php?
        //             project_id=801&record=20   NOTE: _not_ record_id
        //
        // In this case, project_id and record_id are read from GET parameters,
        // $DEBUG is forced to 'yes';
        //
        $projectId  = '';
        $recordId   = '';
        $instrument = '';
        
        # If this is a POST
        if (!empty($_POST['project_id'])) {
            # Note: filter_var used instead of filter_input to make it easier to write unit tests;
            #       filter_input does not pick up modifications to $_POST
            $projectId  = filter_var($_POST['project_id'], FILTER_SANITIZE_NUMBER_INT);
            
            if (!empty($_POST['record'])) {
                $recordId   = filter_var($_POST['record'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            }
            
            if (!empty($_POST['instrument'])) {
                $instrument = filter_var($_POST['instrument'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            }
        } else {
            $projectId = htmlspecialchars($_GET['project_id']);
            $recordId = htmlspecialchars($_GET['record']); // NOT 'record_id'
            $this->debug = 'yes';
        }

        return(array($projectId, $recordId, $instrument));
    }


    /**
     * Make sure the request is from an approved server. If not, log
     * the failure and end.
     */
    public function checkAllowedServers()
    {

        // Determine the hostname of the server making this request
        $serverRemoteAddress = $_SERVER['REMOTE_ADDR'];

        // allow IPv6 local host (::1)
        if ('::1' === $serverRemoteAddress) {
            return true;
        }

        // filter_var() is only avaialbe for PHP >= 5.2, so ip2long is used here
        // and will need to be changed if IPV6 addresses are to be processed.
        // #if(filter_var($serverRemoteAddress,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))

        // If not a valid IP address
        if (!ip2long($serverRemoteAddress)) {
            $error = "Invalid server remote address: ".$_SERVER['REMOTE_ADDR']."\n";
            $this->errorHandler->throwException($error, EtlException::DET_ERROR);
            // $this->notifier->notify($error);
            exit(1);
        }

        // Check to see if the requesting server is allowed
        $hostname = gethostbyaddr($serverRemoteAddress);
        if (($hostname === null) || ($hostname === "") ||
        ! in_array($hostname, $this->allowedServers)) {
            $error = "Server remote address not allowed: ".$_SERVER['REMOTE_ADDR'];
            if (isset($hostname)) {
                $error .= " (hostname = ".$hostname.")\n";
            }
            $this->errorHandler->throwException($error, EtlException::DET_ERROR);
            //$this->notifier->notify($error);

            exit(1);
        }

        return true;
    }


    /**
     * Checks that the project_id supplied by a call from a Data Entry Trigger
     * supplies the expected project id.
     */
    public function checkDetId($detId)
    {

        if ((int) $detId !== (int) $this->projectId) {
            $error = "Project id supplied by data entry trigger ('".$detId."') ".
                "does not match expected id for survey ('".$this->projectId."').";
            $this->errorHandler->throwException($error, EtlException::DET_ERROR);
            //$this->notifier->notify($error);
        }

        return true;
    }
}

<?php

namespace IU\REDCapETL;

/**
 * Class containing functions related to handling calls from
 * REDCap's Data Entry Trigger.
 */
class RedCapDetHandler
{

    protected $debug = '';

    protected $pid = '';
    protected $allowed_servers;
    protected $notifier = '';  // Object for reporting errors

    public function __construct($pid, $allowed_servers, $notifier)
    {
    
        $this->debug = 'no';

        $this->pid = $pid;
        $this->notifier = $notifier;

        // Create array of allowed servers (by hostname)
        if (preg_match("/,/", $allowed_servers)) {
            if ('yes' == $this->debug) {
                print "Found multiple allowed servers<br/>";
            }
            $this->allowed_servers = preg_split("/,/", $allowed_servers);
        } else {
            $this->allowed_servers = array($allowed_servers);
        }
    }

    public function getDetParams()
    {

        // If either project_id or record_id are empty, this program assumes
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
        if (!isset($_POST['project_id']) || !isset($_POST['record'])) {
            $project_id = htmlspecialchars($_GET['project_id']);
            $record_id = htmlspecialchars($_GET['record']); // NOT 'record_id'
            $this->debug = 'yes';
        } else {
            $project_id = htmlspecialchars($_POST['project_id']);
            $record_id = htmlspecialchars($_POST['record']); // NOT 'record_id'
        }

            return(array($project_id,$record_id));
    }


    /**
     * Make sure the request is from an approved server. If not, log
     * the failure and end.
     */
    public function checkAllowedServers()
    {

        // Determine the hostname of the server making this request
        $server_remote_addr = $_SERVER['REMOTE_ADDR'];

        // allow IPv6 local host (::1)
        if ('::1' === $server_remote_addr) {
            return true;
        }

        // filter_var() is only avaialbe for PHP >= 5.2, so ip2long is used here
        // and will need to be changed if IPV6 addresses are to be processed.
        // #if(filter_var($server_remote_addr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))

        // If not a valid IP address
        if (!ip2long($server_remote_addr)) {
            $error = "Invalid server remote address: ".$_SERVER['REMOTE_ADDR']."\n";
            $this->notifier->notify($error);
            exit(1);
        }

        // Check to see if the requesting server is allowed
        $hostname = gethostbyaddr($server_remote_addr);
        if (($hostname === null) || ($hostname === "") ||
        ! in_array($hostname, $this->allowed_servers)) {
            $error = "Server remote address not allowed: ".$_SERVER['REMOTE_ADDR'];
            if (isset($hostname)) {
                $error .= " (hostname = ".$hostname.")\n";
            }
            $this->notifier->notify($error);

            exit(1);
        }

        return true;
    }


    /**
     * Checks that the project_id supplied by a call from a Data Entry Trigger
     * supplies the expected project id.
     */
    public function checkDetId($det_id)
    {

        if ((int) $det_id !== (int) $this->pid) {
            $error = "Project id supplied by data entry trigger ('".$det_id."') ".
            "does not match expected id for survey ('".$this->pid."').";
            $this->notifier->notify($error);
        }

        return true;
    }
}

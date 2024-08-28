<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

if (empty($permissions["maintenance"]))
    die("Error: You do not have permission to access this page.");

require_once("rolling-curl/RollingCurl.php");
require_once("pathDefinitions.php");
require_once("networkHelper.php");
$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

function writeDebug($file, $line, $string)
{
    if(!is_dir(TEMP_ROOT))
        @mkdir(TEMP_ROOT);
    
    $dateTime = date("m/d/Y H:i:s");
    
    $entry = "[$dateTime]\t$line:$string\r\n";
    
    @file_put_contents(TEMP_ROOT . "/$file", $entry, FILE_APPEND);
}

switch($action)
{	
	/**
	 * Executes an uploaded file
	 */
	case "execute":
	{
		$hash = "";
		if(isset($_REQUEST['hash']))
			$hash = $_REQUEST['hash'];
		
		if($hash == "")
        {
            writeDebug("deploymentLog.txt", __LINE__, "Missing hash in execute");
			die("Error: Empty parameter");
        } 
        
        $vidProcs = getVideoProcessorChildren();
        foreach($vidProcs as $vidChild)
        {
            $results = @file_get_contents("https://$vidChild/helpers/deploymentHelper.php?action=execute&hash=$hash&u=" . base64_encode("PEC") . "&p=" . base64_encode("lenpec4321") . "&session=false");
            
            if($results === FALSE)
            {
                writeDebug("deploymentLog-$hash.txt", __LINE__, "No https connection to execute vid proc installer");
                
                $results = @file_get_contents("http://$vidChild/helpers/deploymentHelper.php?action=execute&hash=$hash&u=" . base64_encode("PEC") . "&p=" . base64_encode("lenpec4321") . "&session=false");
                
                if($results === FALSE)
                {
                    writeDebug("deploymentLog-$hash.txt", __LINE__, "No http connection to execute vid proc installer");                    
                    die("Error: Unable to execute installer on video processor.");
                }
            }
        }
        
		// execute uploaded file
		$result = getFileFromHash($hash);
		
		if(!$result)
        {
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to getFileFromHash('$hash')");
			die("Error: Unable to execute file for $hash");
        }
		else
		{
            try
            {
                $WshShell = new COM("WScript.Shell"); 
                $oExec = $WshShell->Run($result . " /S", 1, false); 
                writeDebug("deploymentLog-$hash.txt", __LINE__, "Ran installer with no exceptions reported");
                die("Success");
            }
            catch(Exception $e)
            {
                writeDebug("deploymentLog-$hash.txt", __LINE__, "Exception while attempting to execute $result");
                die("Error: Unable to execute file. - $e");
            }
		}
	}
	break;
    
    /**
	 * Gets existing installers on system
	 */
    case "getexistinginstallers":
    {
        if(!is_dir(TEMP_ROOT))
            @mkdir(TEMP_ROOT);

        if(!is_dir(TEMP_ROOT . "/Installers"))
            @mkdir(TEMP_ROOT . "/Installers");

        $fileList = @scandir(TEMP_ROOT . "/Installers");
        
        if($fileList === FALSE)
        {
            writeDebug("deploymentLog.txt", __LINE__, "Error creating/opening Installers directory");
            die("Error: Could not open Installers directory.");
        }
        
        $listCopy = $fileList;
        unset($fileList);
        $fileList = [];
        
        foreach($listCopy as $file)
            if($file != "." && $file != "..")
                $fileList[] = $file;
            
        if(count($fileList) == 0)
        {
            writeDebug("deploymentLog.txt", __LINE__, "Reported no existing installers to users due to empty list");
            die("Error: No existing installers.");
        }
        
        foreach($fileList as $file)
        {
            try
            {
                $fso = new COM("Scripting.FileSystemObject");
                $version = $fso->GetFileVersion(TEMP_ROOT . "/Installers/" . $file);

                $fileParts = pathinfo($file);

                die($version . "|" . $fileParts["filename"]);
            }
            catch(Exception $e)
            {
                writeDebug("deploymentLog.txt", __LINE__, "Exception while attempting to get version of $file - $e");
                die("Error: Could not create Scripting.FileSystemObject");
            }
            
        }
        
        writeDebug("deploymentLog.txt", __LINE__, "Unexpectedly reached this line. This should not happen.");
        echo "Error: Undefined error.";
    }
    break;
    
    /**
	 * Runs a deployed file on all intersections in management group
	 */
    case "executeall":
    {
        $hash = "";
		if(isset($_REQUEST['hash']))
			$hash = $_REQUEST['hash'];
        
        if($hash == "")
        {
            writeDebug("deploymentLog.txt", __LINE__, "Missing hash in executeall");
			die("Error: Empty parameter");
        }
        
        executeAll($hash);
    }
    break;
	
	/**
	 * Checks to see if a file exists on this machine
	 */
	case "fileexists":
	{
		$hash = "";
		if(isset($_REQUEST['hash']))
			$hash = $_REQUEST['hash'];
		
        if($hash == "")
        {
            writeDebug("deploymentLog.txt", __LINE__, "Missing hash in fileexists");
			die("Error: Empty parameter");
        }
		
		$filePath = getFileFromHash($hash);
		
		if(!$filePath)
			die("DNE");
        else
            die("Exists");
	}
	break;
	
	/**
	 * Retrieves the propagation status for a certain $hash
	 */
	case "status":
	{
		$hash = "";
		if(isset($_REQUEST['hash']))
			$hash = $_REQUEST['hash'];

		if($hash == "")
        {
            writeDebug("deploymentLog.txt", __LINE__, "Missing hash in status");
			die("Error: Empty parameter");
        }

		$statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";

		if(!@readfile($statusFile))
        {
            writeDebug("deploymentLog.txt", __LINE__, "No status file found for $hash");
			die("Error: No status file found for $hash.");
        }
	}
	break;
	
	/**
	 * Upload a  file to system
	 */
	case "putfile":
	{
		putFile($_FILES['deployFile']['name'], $_FILES['deployFile']['tmp_name']);
	}
	break;

	case "propagatefile":
	{
		$hash = "";
		if(isset($_REQUEST['hash']))
			$hash = $_REQUEST['hash'];

		if($hash == "")
        {
            writeDebug("deploymentLog.txt", __LINE__, "Missing hash in status");
			die("Error: Empty parameter");
        }
		
		propagateFile($hash);
	}
	break;
}

function executeAll($hash)
{
    ob_end_clean();
    header("Connection: close");
    ignore_user_abort();
    ob_start();
    echo $hash;
    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush();
    flush();
    
    $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";
	
	// create status XML document for caller to reference
	$statusXML = new SimpleXMLElement("<corridor></corridor>");	
	$statusXML->addAttribute("status", "executing");
	

	$Intersections = getCorridorIntersectionsIncludingSelf();


	if($Intersections === FALSE)
	{
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Could not load Corridor.xml / \$Intersections === FALSE");
		die("Error: Unable to load Corridor.xml for propagation.");
	}		
        
    $protocol = "https://";

	// get all intersection IPs
	$intersectionArr = [];
	foreach ($Intersections as $IntIP => $name)
	{
    
	    $intersectionArr[] = $protocol . $IntIP . "/helpers/deploymentHelper.php";

	    $intersectionXML = $statusXML->addChild("intersection");
	    $intersectionXML->addAttribute("ip", $IntIP);
	    $intersectionXML->addAttribute("status", "executing");
	}

	if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
    {
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Could not write $statusFile");
        die("Error: Unable to write propagation status file.");
    }
	
	$postParams = ["action"=>"execute", "hash"=>$hash, "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321"), "session" => "false"];
	$collector = new ExecutionCollector();
	$collector->run($intersectionArr, $postParams, $hash);
}

/**
 * Propagates a given file to all IPs in Corridor.xml
 * @param string $hash Hash of previously uploaded file to propagate
 */
function propagateFile($hash)
{
	//2 hour time limit to propagate file
	set_time_limit(7200);
	$statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";
	
	// create status XML document for caller to reference
	$statusXML = new SimpleXMLElement("<corridor></corridor>");	
	$statusXML->addAttribute("status", "checking");
	
	$corridor = simplexml_load_file(CORRIDOR_CONF_FILE);
	
	if($corridor == FALSE)
    {
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to load Corridor.xml for propagation.");
		die("Error: Unable to load Corridor.xml for propagation.");
    }
    
    $protocol = "https://";
	
	// get all intersection IPs
	$intersectionArr = [];
	foreach($corridor->Intersection as $Intersection)
	{
        if((string)$Intersection["IP"] == "" || (string)$Intersection["IP"] == "127.0.0.1" || ip2long((string)$Intersection["IP"]) === false)
            continue;
        
        $intersectionArr[] = $protocol . (string)$Intersection["IP"] . "/helpers/deploymentHelper.php";

        $intersectionXML = $statusXML->addChild("intersection");
        $intersectionXML->addAttribute("ip", (string)$Intersection["IP"]);
        $intersectionXML->addAttribute("status", "checking");
	}	
    
	if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
    {
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Could not write $statusFile.");
        die("Error: Unable to find file.");
    }
    
    if(count($intersectionArr) != 0)
    {
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Found more than 1 intersection");
        
        $postParams = ["action"=>"fileexists", "hash"=>$hash, "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321"), "session" => "false"];
        $collector = new CheckExistanceCollector();
        $IPResults = $collector->run($intersectionArr, $postParams, $hash);
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Returned fine from CheckExistanceCollector()");

        $sendArr = [];
        foreach($IPResults as $ip => $value)
            if(!$value)
                $sendArr[] = $protocol . $ip . "/helpers/deploymentHelper.php";
            
        if(count($sendArr) != 0)
        {
            $filePath = getFileFromHash($hash);

            if(!$filePath)
            {
                writeDebug("deploymentLog-$hash.txt", __LINE__, "Bad return from getFileFromHash($hash)");
                @file_put_contents($statusFile, "Error: Unable to find file to distribute");
                die("Error: Unable to find file.");
            }
            
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Attempting to run PropagationCollector()");

            $postParams = ["action"=>"putfile", "deployFile"=>"@$filePath", "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321"), "session" => "false"];
            $collector = new PropagationCollector();
            $collector->run($sendArr, $postParams, $hash);
            
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Returned fine from PropagationCollector()");
        }
        else
        {
            // update status file to show that we can execute
            $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";
            $statusXML = @simplexml_load_file($statusFile);

            if($statusXML === FALSE)
            {
                writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to read $statusFile");
                die("Error: Unable to propagate file.");
            }
            
            $statusXML["status"] = "sent";

            if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
            {
                writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to write $statusFile");
                die("Error: Unable to propagate file.");
            }
        }
    }
}

/**
 * Propagates a given file to a video only processor of THIS processor
 * @param string $hash Hash of previously uploaded file to propagate
 */
function sendToVideoProc($hash, $ip)
{
	//2 hour time limit to propagate file
	set_time_limit(7200);
    
    $sendArr[] = "https://" . $ip . "/helpers/deploymentHelper.php";
   
    writeDebug("deploymentLog-$hash.txt", __LINE__, "Sending to video processor $ip");

    $filePath = getFileFromHash($hash);

    if(!$filePath)
    {
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Bad return from getFileFromHash($hash)");
        die("Error: Unable to find file.");
    }

    writeDebug("deploymentLog-$hash.txt", __LINE__, "Attempting to run VideoPropagationCollector() to send to video processor");

    $postParams = ["action"=>"putfile", "deployFile"=>"@$filePath", "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321"), "session" => "false"];
    $collector = new VideoPropagationCollector();
    $result = $collector->run($sendArr, $postParams, $hash);

    if($result)
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Returned fine from VideoPropagationCollector()");
    else
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Error in VideoPropagationCollector()");
    
    return $result;
}

/**
 * Check if a file exists
 * @param type $hash hash value to check
 * @return boolean false if no archive for $hash, otherwise full path to archive file
 */
function getFileFromHash($hash)
{	
	// attempt to create management group archive dir if it doesnt exist
	if(!is_dir(TEMP_ROOT))
		@mkdir(TEMP_ROOT);
    
    if(!is_dir(TEMP_ROOT . "/Installers"))
		@mkdir(TEMP_ROOT . "/Installers");
	
	$dirHandle = @opendir(TEMP_ROOT . "/Installers");
			
	if($dirHandle == FALSE)
    {
        writeDebug("deploymentLog.txt", __LINE__, "Could not open Temp directory");
		die("Error: Could not open Temp directory.");
    }
	
	// loop thru files in directory
	while (($file = readdir($dirHandle)) !== false) 
	{
		if($file == "." || $file == "..")
			continue;

		$parts = pathinfo($file);

		// return this file name, if the hashes match
		if($parts["filename"] == $hash)
			return TEMP_ROOT . "/Installers/" . $file;
	}

	closedir($dirHandle);	
	return false;
}

/**
 * Handles uploading of files
 * @param type $name name from POST upload
 * @param type $tmp_name tmp_name from POST upload
 */
function putFile($name, $tmp_name)
{	
	// check for hacks & crufty people
	if($name == "" || $tmp_name == "")
    {
        writeDebug("deploymentLog.txt", __LINE__, "Empty parameter \$name or \$tmp_name");
		die("Error: Empty parameter");
    }
	if(str_contains($name, '/'))
    {
        writeDebug("deploymentLog.txt", __LINE__, "Error: Illegal character in $name.");
		die("Error: Illegal character in file name.");
    }
	if(str_contains($name, '\\'))
    {
        writeDebug("deploymentLog.txt", __LINE__, "Error: Illegal character in $name.");
		die("Error: Illegal character in file name.");
    }
    
    if(!is_dir(TEMP_ROOT))
		@mkdir(TEMP_ROOT);
    
    if(!is_dir(TEMP_ROOT . "/Installers"))
		@mkdir(TEMP_ROOT . "/Installers");
    
    $outarray = [];
    $return_var = -1;
    
    exec(PROG_VALID_EXE . " " . $tmp_name, $outarray, $return_var);
    
    if($return_var != 0)
    {
        @unlink($tmp_name);
        writeDebug("deploymentLog.txt", __LINE__, "Validator returned $return_var on $tmp_name");
        die("Error: Uploaded file is not a valid signed Rhythm Engineering installer.");
    }

	$hash = md5_file($tmp_name); 
    
	if($hash !== FALSE)
	{
		$fileParts = pathinfo($name);
		
		$fileList = @scandir(TEMP_ROOT . "/Installers");
		    
		if($fileList === FALSE)
        {
            writeDebug("deploymentLog.txt", __LINE__, "Could not open Installers directory.");
		    die("Error: Could not open Installers directory.");
        }
		
		foreach($fileList as $file)
		    if($file != "." && $file != "..")
		        @unlink(TEMP_ROOT . "/Installers/$file");

		$destinationFileName = TEMP_ROOT . "/Installers/$hash." . $fileParts["extension"];
		//Move uploaded file and md5 sum the new file just to be sure it was correct.
		$movestatus = move_uploaded_file($tmp_name, $destinationFileName);
        
		if($movestatus === TRUE)
		{
			$newhash = md5_file($destinationFileName);
            
			if($newhash !== FALSE && $newhash === $hash)
			{                
                $vidProcs = getVideoProcessorChildren();
                
                foreach($vidProcs as $vidIPs)
                    if(!sendToVideoProc($newhash, $vidIPs))
                        die("Error: Unable to send installer to video processor.");
                
                // let user know what the file hash is for status tracking
				die($hash);
			}
			else
			{
				if($newhash === FALSE)
				{
                    writeDebug("deploymentLog.txt", __LINE__, "Unable to MD5 sum uploaded file after move.");
					die("Error: Unable to MD5 sum uploaded file after move.");
				}
				else if($newhash != $hash)
				{
                    writeDebug("deploymentLog.txt", __LINE__, "MD5 mismatch when uploading file.  Expecting $hash and got $newhash.");
					die("Error: MD5 mismatch when uploading file.  Expecting $hash and got $newhash.");
				}

			}

		}
		else
		{
            writeDebug("deploymentLog.txt", __LINE__, "Unable to copy uploaded file to Installers directory. Error code: $movestatus");
			die("Error: Unable to copy uploaded file to Installers directory. Error code: $movestatus");
		}
	}
	else
	{
        writeDebug("deploymentLog.txt", __LINE__, "Unable to compute MD5 sum of $tmp_name");
		die("Error: Unable to compute MD5 sum of uploaded file!");
	}	
}

function getVideoProcessorChildren()
{    
    $Intersection = @simplexml_load_file(INTERSECTION_CONF_FILE);
    
    if($Intersection === FALSE)
    {
        writeDebug("deploymentLog.txt", __LINE__, "Could not open Intersection.xml.");
        die("Error: Could not open Intersection.xml.");
    }
    
    $ipList = [];

    foreach($Intersection->xpath("//VideoDetectionDevice") as $vdd)
    {                  
    	$ip = (string)$vdd->attributes()["machine"];
		if($ip != "." && $ip != getInSyncIP() && isValidIP($ip) === TRUE)
        {
            // found a video only processor
            
	     if(!in_array($ip, $ipList))
			$ipList[] = (string)$vdd->attributes()["machine"];
        }
    }
    
    return $ipList;
}

class ExecutionCollector 
{
    private $rc;
    private $statusHash;
    private $error;

    function __construct()
    {
        $this->rc = new RollingCurl([$this, 'processResponse']);
        $this->rc->window_size = 10;
        $this->statusHash = "";
        $this->error = false;
    }

    function processResponse($response, $info, $request)
    {        
        if ($info['retried']) 
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - \$info['retried'] set to true on, bailing out...");
            return;
        }
        
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Could not read $statusFile");
            return;
        }
        
        $intersection = &$statusXML->xpath("//intersection[@ip='" . $info["primary_ip"] . "']");
        
        if($intersection == FALSE)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Could not get intersection IP from $statusFile - " . base64_encode(@file_get_contents($statusFile)));
            return;
        }
        
        if($response == "Success")
            $intersection[0]["status"] = "done";
        else
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Received response other than Success - $response");
            $this->error = true;
            $intersection[0]["status"] = "error";
            $intersection[0]["message"] = "Could not execute file";
        }
        
        if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Unable to write $statusFile");
        }
    }

    function run($urls, $postParams, $hash)
    {
        $this->statusHash = $hash;
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Running ExecutionCollector->run");
        
        foreach ($urls as $url)
        {
            $request = new RollingCurlRequest($url);
            $request->options = [CURLOPT_POST => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_POSTFIELDS => $postParams];
            $fallback_request = new RollingCurlRequest(preg_replace('/^https:/i', 'http:', $url));
            $fallback_request->options = $request->options;
            $request->fallback_request = $fallback_request;
            $this->rc->add($request);
        }
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Executing ExecutionCollector->execute from ->run");
        
        $this->rc->execute();
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Returned from execute");
        
        // update status file to reflect that everyone has been checked
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
        {
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to load $statusFile");
            return false;
        }

        if($this->error)
            $statusXML["status"] = "error";
        else
            $statusXML["status"] = "done";
        
        if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to write $statusFile");
        
        return $this->error;
    }
}

class CheckExistanceCollector 
{
    private $rc;
    private $statusArr;
    private $statusHash;

    function __construct()
    {
        $this->rc = new RollingCurl([$this, 'processResponse']);
        $this->rc->window_size = 10;
        $this->statusArr = [];
        $this->statusHash = "";
    }

    function processResponse($response, $info, $request)
    {        
        if ($info['retried'])
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - \$info['retried'] set to true, bailing out...");
            return;
        }
        
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Could not read $statusFile");
            return;
        }
        
        $intersection = &$statusXML->xpath("//intersection[@ip='" . $info["primary_ip"] . "']");
        
        if($intersection == FALSE)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Could not get intersection IP from $statusFile - " . base64_encode(@file_get_contents($statusFile)));
            return;
        }
        
        if($response == "Exists")
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - File already exists");
            $intersection[0]["status"] = "skip";
            $intersection[0]["message"] = "File already exists, skipping upload...";
            $this->statusArr[$info["primary_ip"]] = true;
        }
        else
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, "Sending to " . $info["primary_ip"]);
            $intersection[0]["status"] = "sending";
            $this->statusArr[$info["primary_ip"]] = false;
        }

        if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Unable to write $statusFile");
    }

    function run($urls, $postParams, $hash)
    {
        $this->statusHash = $hash;
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Running CheckExistanceCollector->run");
        
        foreach ($urls as $url)
        {
            $request = new RollingCurlRequest($url);
            $request->options = [CURLOPT_POST => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 15, CURLOPT_POSTFIELDS => $postParams];
            $fallback_request = new RollingCurlRequest(preg_replace('/^https:/i', 'http:', $url));
            $fallback_request->options = $request->options;
            $request->fallback_request = $fallback_request;
            $this->rc->add($request);
        }
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Running CheckExistanceCollector->execute");
        
        $this->rc->execute();
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Returned from CheckExistanceCollector->execute");
        
        // update status file to reflect that everyone has been checked
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
        {
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to read $statusFile");
            return $this->statusArr;
        }

        $statusXML["status"] = "checked";
        
        if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
        {
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to write $statusFile");
        }
        
        return $this->statusArr;
    }
}

class PropagationCollector 
{
    private $rc;
    private $statusHash;
    private $error;

    function __construct()
    {
        $this->rc = new RollingCurl([$this, 'processResponse']);
        $this->rc->window_size = 10;
        $this->error = false;
    }

	function isValidMd5($md5 ='')
	{
	    if(preg_match('/^[a-f0-9]{32}$/', $md5) === 1)
	    {
	        return TRUE;
	    }
	    return FALSE;
	}

    function processResponse($response, $info, $request)
    {        
        if ($info['retried']) 
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - \$info['retried'] set to true, bailing out...");
            return;
        }
        
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Could not read $statusFile");
            return;
        }
        
        $intersection = &$statusXML->xpath("//intersection[@ip='" . $info["primary_ip"] . "']");
        
        if($intersection == FALSE)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Could not get intersection IP from $statusFile - " . base64_encode(@file_get_contents($statusFile)));
            return;
        }
        
        if($info["http_code"] == 0)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Error sending file - " . $info["http_code"] . " == 0");
            
            $this->error = true;
            $intersection[0]["status"] = "error";
            $intersection[0]["message"] = "Unable to send file.";
        }
        else
        {        
			//Ensures that if the status hash is not an md5 or the response is not an md5 that we error.
			//If both status hash and response are md5, then we do a string comparison for equality.
			//Our response should only be successful if we get the exact hash that we are requesting.

            if(($this->isValidMd5($response)) && (($this->statusHash) == $response))
            {
                writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, "sent to " . $info["primary_ip"]);
				$intersection[0]["status"] = "sent";
            }
            else
            {
                $this->error = true;
                $intersection[0]["status"] = "error";
                
				if(!($this->isValidMd5($response)))
				{
                    writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, "Non-MD5 response from " . $info["primary_ip"] . " - $response");
	                $intersection[0]["message"] = "Unable to send file.  Received non-md5 response!";
				}
				else if(($this->statusHash) != $response)
				{
                    writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, "MD5 mismatch on " . $info["primary_ip"] . " - $response");                    
	                $intersection[0]["message"] = "Unable to send file.  md5 sum mismatch!";
				}
            }
        }

        if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, "Unable to write $statusFile");
    }

    function run($urls, $postParams, $hash)
    {
        $this->statusHash = $hash;
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Running PropagationCollector->run");
        
        foreach ($urls as $url)
        {
            $request = new RollingCurlRequest($url);
            $request->options = [CURLOPT_POST => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 25, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 36000, CURLOPT_POSTFIELDS => $postParams, CURLOPT_HTTPHEADER => ['Expect:']];
            $fallback_request = new RollingCurlRequest(preg_replace('/^https:/i', 'http:', $url));
            $fallback_request->options = $request->options;
            $request->fallback_request = $fallback_request;
            $this->rc->add($request);
        }
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Running PropagationCollector->execute");
        
        $this->rc->execute();
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Returned from PropagationCollector->execute");
        
        // after updating the intersections, check to see if ALL intersections 
        // are complete or any returned an error
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
        {
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to read $statusFile");
            return;
        }

        if($this->error)
            $statusXML["status"] = "error";
        else
            $statusXML["status"] = "sent";
        
        if(@file_put_contents($statusFile, $statusXML->asXML()) === FALSE)
            writeDebug("deploymentLog-$hash.txt", __LINE__, "Unable to write $statusFile");
        
        return $this->error;
    }
}

class VideoPropagationCollector 
{
    private $rc;
    private $statusHash;
    private $error;

    function __construct()
    {
        $this->rc = new RollingCurl([$this, 'processResponse']);
        $this->rc->window_size = 10;
        $this->error = false;
    }

    function processResponse($response, $info, $request)
    {        
        if ($info['retried']) 
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - \$info['retried'] set to true, bailing out...");
            $this->error = true;
            return;
        }
        
        if($info["http_code"] == 0)
        {
            writeDebug("deploymentLog-" . $this->statusHash . ".txt", __LINE__, $info["primary_ip"] . " - Error sending file - " . $info["http_code"] . " == 0");
            $this->error = true;
            return;
        }
    }

    function run($urls, $postParams, $hash)
    {
        $this->statusHash = $hash;
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Running VideoPropagationCollector->run");
        
        foreach ($urls as $url)
        {
            $request = new RollingCurlRequest($url);
            $request->options = [CURLOPT_POST => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 25, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 36000, CURLOPT_POSTFIELDS => $postParams, CURLOPT_HTTPHEADER => ['Expect:']];
            $fallback_request = new RollingCurlRequest(preg_replace('/^https:/i', 'http:', $url));
            $fallback_request->options = $request->options;
            $request->fallback_request = $fallback_request;
            $this->rc->add($request);
        }
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Running VideoPropagationCollector->execute");
        
        $this->rc->execute();
        
        writeDebug("deploymentLog-$hash.txt", __LINE__, "Returning from VideoPropagationCollector->execute");
        
        return !$this->error;
    }
}

?>

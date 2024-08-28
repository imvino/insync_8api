<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end


if($permissions["maintenance"] != 1)
	die("Error: You do not have permission to access this page.");

require_once("pathDefinitions.php");
require_once("networkHelper.php");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

switch($action)
{
	/**
	 * Receives a file upload from another proc
	 */
	case "receivefile":
	{
		$target = "";
		if(isset($_REQUEST['target']))
			$target = $_REQUEST['target'];
		
		if($target == "")
			ErrorExit ("Empty target");
		
		if(!isset($_FILES['file']['tmp_name']))
			ErrorExit("No file uploaded");

		receiveFile($target, $_FILES['file']['tmp_name']);
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
			ErrorExit("Empty parameter");

		$statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";

		if(!@readfile($statusFile))
			ErrorExit("No status file found for $hash.");
	}
	break;
	
	/**
	 * Upload a file and propagate to management group
	 */
	case "putfile":
	{
		$target = "";
		if(isset($_REQUEST['target']))
			$target = $_REQUEST['target'];
		
		if($target == "")
			ErrorExit ("Empty target");
		
		if(!isset($_FILES['file']['tmp_name']))
			ErrorExit("Error: No file uploaded");
		
		putFile($target, $_FILES['file']['tmp_name']);
	}
	break;
}

/**
 * Handles uploading of files and propagates to management group
 * @param string $target directory path if file is a zip, otherwise full path
 *	and file name to target file
 * @param string $tmp_name tmp_name from POST upload
 */
function putFile($target, $tmp_name)
{
	$contents = @file_get_contents($tmp_name);
	if($contents === FALSE)
		ErrorExit("Cannot open uploaded file");

	$hash = md5($contents);
	
	$statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";

	// propagate to the management group

	// create status XML document for CentralSync
	$statusXML = new SimpleXMLElement("<corridor></corridor>");	
	$statusXML->addAttribute("status", "working");
	
	$Intersections = getCorridorIntersections();
	if($Intersections === FALSE)
	{
		ErrorExit("No management group file to load from!");
	}
	if ($Intersections)
	{	
		// let user know what the corridor hash is and flush the buffer
		echo $hash;
		flush();
		ob_flush();
	
		// get all intersection IPs
		$intersectionArr = [];
		foreach ($Intersections as $IntIP => $name)
		{
			$intersectionArr[] = ["IP"=>$IntIP, "sent"=>false];
		
			$intersectionXML = $statusXML->addChild("intersection");
			$intersectionXML->addAttribute("ip", $IntIP);
			$intersectionXML->addAttribute("status", "working");
		}
	
		@file_put_contents($statusFile, $statusXML->asXML());
	
		$errorSending = false;
	
		// propagate file to all intersections
		foreach($intersectionArr as $Intersection)
		{
			// 5 minute limit per IP before killing script
			set_time_limit(300);
		
			$post = ['action' => 'receivefile', 'u' => base64_encode("PEC"), 'p' => base64_encode("lenpec4321"), 'target' => $target, 'file' => "@" . $tmp_name];
		
			$result = sendPostCommand($Intersection["IP"], $post);
		
			// send file to machine
			if(!$result)
			{
				$intersectionXML = $statusXML->xpath("intersection[@ip='" . $Intersection["IP"] . "']")[0];			
				$intersectionXML["status"] = "error";
				$intersectionXML["message"] = "Could not send file";			
				@file_put_contents($statusFile, $statusXML->asXML());
				$errorSending = true;
				break;
			}
		}
	
		if($errorSending)
			exit;
	
		// move file on this machine
	
		$fh = @fopen($tmp_name, "r");
		$blob = fgets($fh, 5);
		fclose($fh);
		if (str_contains($blob, 'PK'))
		{
			// zip file, unzip contents to $target directory
		
			$zip = new ZipArchive;
			$res = $zip->open($tmp_name);
		
			if ($res === TRUE) 
			{
				$zip->extractTo($target);
				$zip->close();
			}
			else
				ErrorExit("Could not unzip file.");
		}
		else
			move_uploaded_file($tmp_name, $target);
	
		foreach($intersectionArr as $Intersection)
		{
			$intersectionXML = $statusXML->xpath("intersection[@ip='" . $Intersection["IP"] . "']")[0];			
		
			if($intersectionXML["status"] == "working")
			{
				$intersectionXML["status"] = "completed";
				if(isset($intersectionXML["message"]))
					unset($intersectionXML["message"]);
			}
		}
	
		$statusXML["status"] = "done";
		@file_put_contents($statusFile, $statusXML->asXML());
	}
	else
	{
		ErrorExit("No other Intersections on Management Group!");
	}
}

/**
 * Send a POST call to another intersection machine
 * @param string $ip
 * @param array $data POST array()
 * @param int $timeout defaults to 60
 * @return boolean|string false on failure, otherwise string with return contents
 */
function sendPostCommand($ip, $data, $timeout = 60)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: multipart/form-data"]);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux i686; rv:6.0) Gecko/20100101 Firefox/6.0Mozilla/4.0 (compatible;)");
	
	$protocol = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER['HTTPS'] != "")
		$protocol = "https";
	
	curl_setopt($ch, CURLOPT_URL, $protocol . "://$ip/helpers/propagationHelper.php");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$response = curl_exec($ch);
	
	if(curl_errno($ch))
	{
		curl_close($ch);
		return false;
	}
	else
	{
		curl_close($ch);
		return $response;
	}
}

/**
 * Receives a file from another machine
 * @param string $target full directory path & file name to move file into, or 
 *  just directory if a zip
 * @param string $tmp_name tmp_name from POST upload
 */
function receiveFile($target, $tmp_name)
{	
	if($tmp_name == "")
		ErrorExit('Empty parameter tmp_name');
	
	if($target == "")
		ErrorExit('Empty parameter target');
	
	$fh = @fopen($tmp_name, "r");
	$blob = fgets($fh, 5);
	fclose($fh);
	if (str_contains($blob, 'PK'))
	{
		// zip file, unzip contents to $target directory
		
		$zip = new ZipArchive;
		$res = $zip->open($tmp_name);
		
		if ($res === TRUE) 
		{
			$zip->extractTo($target);
			$zip->close();
		}
		else
			ErrorExit("Could not unzip file.");
	}
	else
		move_uploaded_file($tmp_name, $target);
	
	die("Success");
}

/**
 * Helper function to return an error header and spit out an error string
 * @param type $message Error message
 */
function ErrorExit($message)
{
	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	die("Error: " . $message);
}

?>

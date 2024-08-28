<?php

if(!isset($loggedIn) || !$loggedIn)
{
    // this must be included on all pages to authenticate the user
    require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
    $permissions = authSystem::ValidateUser();
    // end
    
    if (empty($permissions["cameracontrols"]))
        die("Error: You do not have permission to access this page.");
}

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

switch(strtolower($action))
{
	/**
	 * Adds a new recording event to file
	 */
	case "add":
	{
		$cam = "";
		if(isset($_REQUEST['cam']))
			$cam = $_REQUEST['cam'];

		$startDay = "";
		if(isset($_REQUEST['startDay']))
			$startDay = $_REQUEST['startDay'];

		$startTime = "";
		if(isset($_REQUEST['startTime']))
			$startTime = $_REQUEST['startTime'];

		$fps = "";
		if(isset($_REQUEST['fps']))
			$fps = $_REQUEST['fps'];

		$timestamp = "";
		if(isset($_REQUEST['timestamp']))
			$timestamp = $_REQUEST['timestamp'];

		$duration = "";
		if(isset($_REQUEST['duration']))
			$duration = $_REQUEST['duration'];
	
		addRecording($cam, $startDay, $startTime, $fps, $timestamp, $duration);
	}
	break;

	/**
	 * Retrieves the currently selected Recording drive
	 */
	case "getdrive":
	{
		getRecordingDrive();
	}
	break;

	/**
	 * Sets the active recording drive
	 */
	case "setdrive":
	{
		$drive = "";
		if(isset($_REQUEST['drive']))
			$drive = $_REQUEST['drive'];
		
		setRecordingDrive($drive);
	}
	break;
	
	/**
	 * Builds the current recording schedule table
	 */
	case "view":
	{
		viewSchedule();
	}
	break;

	case "delete":
	{
		$cam = "";
		if(isset($_REQUEST['cam']))
			$cam = $_REQUEST['cam'];

		$startDay = "";
		if(isset($_REQUEST['startDay']))
			$startDay = $_REQUEST['startDay'];

		$duration = "";
		if(isset($_REQUEST['duration']))
			$duration = $_REQUEST['duration'];
		
		if(strlen($cam) == 0 || strlen($startDay) == 0 || strlen($duration) == 0)
			die("Error");
		
		deleteEvent($cam, $startDay, $duration);
	}
	break;
}

/**
 * Adds a new recording event to file
 */
function addRecording($cam, $startDay, $startTime, $fps, $timestamp, $duration)
{	
	require_once("pathDefinitions.php");
	
	$recordingXML = simplexml_load_file(RECORDING_CONF_FILE);
	
	$recording = $recordingXML->addChild("Recording");
	
	$recording->addAttribute("camera", $cam);
	$recording->addAttribute("startDay", $startDay);
	$recording->addAttribute("startTime", $startTime);
	$recording->addAttribute("FPS", $fps);
	$recording->addAttribute("timestamp", $timestamp);
	$recording->addAttribute("durationSecs", $duration);
	
	file_put_contents(RECORDING_CONF_FILE, $recordingXML->asXML());
	
	die("Success");
}

/**
 * Retrieves the currently selected Recording drive
 */
function getRecordingDrive()
{
	require_once("pathDefinitions.php");
	
	$recordingXML = @simplexml_load_file(RECORDING_CONF_FILE);
    
    if($recordingXML === FALSE)
        exit;
    
	$drive = $recordingXML->Drive['name'];
	
	echo $drive;
	
	exit;
}

/**
 * Sets the active recording drive
 * @param string $drive Drive letter
 */
function setRecordingDrive($drive)
{	
	require_once("pathDefinitions.php");
	
	$recordingXML = @simplexml_load_file(RECORDING_CONF_FILE);
    
    if($recordingXML == false)
        $recordingXML = simplexml_load_string('<?xml version="1.0"?><RecordingSchedule><Drive name="' . $drive . '"/></RecordingSchedule>');
	
	$recordingXML->Drive['name'] = $drive;
	
	file_put_contents(RECORDING_CONF_FILE, $recordingXML->asXML());
	
	die("Success");
}

/**
 * Builds the current recording schedule table
 */
function viewSchedule()
{
	require_once("pathDefinitions.php");
	
	$recordingXML = @simplexml_load_file(RECORDING_CONF_FILE);
    
    if($recordingXML === FALSE)
        if(file_put_contents(RECORDING_CONF_FILE, '<?xml version="1.0"?><RecordingSchedule><Drive name=""/></RecordingSchedule>') === FALSE)
            die("<td colspan='7'><p>Unable to create Recording Configuration file!</p></td>");
    
    if(!isset($recordingXML->Recording))
        die("<td colspan='7'><p>No recordings scheduled.</p></td>");
    
	$recordings = $recordingXML->Recording;
	
	foreach($recordings as $Recording)
	{
		$startTime = $Recording['startTime'];
		$timeStartUnix = strtotime($startTime);
		$duration = $Recording['durationSecs'];	// XML stores this in seconds
		$times = strftime("%I:%M %p", $timeStartUnix)." - ".strftime("%I:%M %p", $timeStartUnix+$duration);
	
		echo "<tr>";
		echo "<td>" . $Recording['startDay'] ."</td>";
		echo "<td class='text-center'>" . $times. "</td>";
		echo "<td class='text-center'>" . $Recording['camera'] ."</td>";
		echo "<td class='text-center'>" . $Recording['timestamp'] ."</td>";
		echo "<td class='text-center'>" . frameRateToHRF($Recording['FPS']) ."</td>";
		
		$size = 20000 * floatval($Recording['FPS']) * intval($Recording['durationSecs']);
		
		echo "<td class='text-center'>" . bytesToSize($size) . "</td>";
		
		$randID = "a" . random_int(111111,9999999);
		
		echo "<td class='text-center'><a href='' id='$randID' class='delete-recording' title='Delete'><span>×</span></a><script>$('#$randID').click(function(e){\$.get('/helpers/recordingHelper.php?action=delete&cam=" . $Recording['camera'] . "&startDay=" . $Recording['startDay'] . "&duration=" . $Recording['durationSecs'] . "'); e.preventDefault(); reloadData();})</script></td>";
		echo "</tr>";
	}
	
	if(count($recordings) == 0)
		echo "<td colspan='7'><p>No recordings scheduled.</p></td>";
	
	exit;
}

/**
 * Deletes a recording event from the schedule
 * @param string $cam Camera name
 * @param string $startDay Start Date
 * @param integer $duration Duration
 */
function deleteEvent($cam, $startDay, $duration)
{	
	require_once("pathDefinitions.php");
	
	$recordingXML = simplexml_load_file(RECORDING_CONF_FILE);
	$recordings = $recordingXML->Recording;
	
	$recordingCount = count($recordings);
	
	for($i=0; $i < $recordingCount; $i++)
	{		
		if($cam == $recordings[$i]['camera'] && $startDay == $recordings[$i]['startDay'] && $duration == $recordings[$i]['durationSecs'])
		{
			// found our entry, now delete it!
			
			unset($recordings[$i]);
			
			file_put_contents(RECORDING_CONF_FILE, $recordingXML->asXML());
			
			die("Success");
		}
	}
	
	die("Error");
}

/**
 * Converts a framerate to human readable format
 * @param decimal $rate framerate / 1 second
 * @return string human readable equivalent of $rate
 */
function frameRateToHRF($rate)
{
	if($rate == 10)
		return "Normal";
	else if($rate == 1)
		return "Once a Second";
	else if($rate == 0.2)
		return "Every 5 Seconds";
	else if($rate == 0.1)
		return "Every 10 Seconds";
	else if($rate == 0.033333)
		return "Every 30 Seconds";
	else if($rate == 0.016667)
		return "Every Minute";
	else if($rate == 0.003333)
		return "Every 5 Minutes";
}

/**
 * Gets the drive letters of all system drives
 * @return array Array of drive letters
 */
function getDriveList($permissions)
{
	$driveArr = [];
	
	$fso = new COM('Scripting.FileSystemObject');
	$D = $fso->Drives;

	foreach ($D as $d) 
	{       
		if ($d->IsReady)
		{
            $dO = $fso->GetDrive($d);
            $s = "";

            if ($dO->DriveType == 3)
                $n = $dO->ShareName;
            else
            {
                $n = $dO->VolumeName;
                $s = bytesToSize($dO->FreeSpace);
            }

            if(strcasecmp($dO->DriveLetter, "c") != 0 || $permissions["username"] === "PEC")
                $driveArr[] = [$dO->DriveLetter, $n, $s];
		}
	}
	
	return $driveArr;
}

/**
 * Converts byte value to human readable format
 * @param type $bytes
 * @param type $precision
 * @return string human readable equivalent of $bytes
 */
function bytesToSize($bytes, $precision = 2)
{	
	$kilobyte = 1024;
	$megabyte = $kilobyte * 1024;
	$gigabyte = $megabyte * 1024;
	$terabyte = $gigabyte * 1024;
	
	if (($bytes >= 0) && ($bytes < $kilobyte)) {
		return $bytes . ' B';

	} elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
		return round($bytes / $kilobyte, $precision) . ' KB';

	} elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
		return round($bytes / $megabyte, $precision) . ' MB';

	} elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
		return round($bytes / $gigabyte, $precision) . ' GB';

	} elseif ($bytes >= $terabyte) {
		return round($bytes / $terabyte, $precision) . ' TB';
	} else {
		return $bytes . ' B';
	}
}

?>

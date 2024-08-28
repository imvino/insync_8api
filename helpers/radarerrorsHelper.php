<?php

if(!isset($loggedIn) || !$loggedIn)
{
	// this must be included on all pages to authenticate the user
	require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
	$permissions = authSystem::ValidateUser();
	// end
    
    if (empty($permissions["reports"]))
        die("Error: You do not have permission to access this page.");
}

$loggedIn = true;
ini_set('memory_limit','256M');

require_once("pathDefinitions.php");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

switch($action)
{
	/**
	 * Loads initial view on request
	 */
	case "load":
	{
		$start = "";
		if(isset($_REQUEST['start']))
			$start = $_REQUEST['start'];
		
		$end = "";
		if(isset($_REQUEST['end']))
			$end = $_REQUEST['end'];
		
		$filter = "";
		if(isset($_REQUEST['filtertext']))
			$filter = $_REQUEST['filtertext'];		
		
		if($start == "" || $end == "")
			die('{"error": "Start or End dates are missing"}');
        
        // limit time range to two days for memory/performance reasons
        $startStamp = strtotime($start);
        $endStamp = strtotime($end);

        //if($endStamp-$startStamp >= 172800)
        //    die('{"error":"The requested time span was too large. Please choose a timespan of <48 hours."}');

		$notificationData = loadNotificationData($start, $end);        
		
		$RadarError = [];
		
		foreach ($notificationData as $key => $value)
		{
			$RadarError[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}

		$jsonData = [];
		if (count($RadarError) > 0)
		{
			$jsonData["RadarError"] = $RadarError;
		}

		echo json_encode($jsonData);
	}
	break;
}

function loadNotificationData($startDateTime, $endDateTime, $filterText)
{
	//$validDates = createDateRange($startDateTime, $endDateTime, "Ymd");
	
	$startTimestamp = strtotime($startDateTime);
	$endTimestamp = strtotime($endDateTime);
	
	// Get all files from C:\hawkeye\log\RadarErrors
	$directoryPath = "C:\hawkeye\log\RadarErrors";
	$notificationFiles = [];
	if (file_exists($directoryPath))
	{
		$path = @opendir($directoryPath);
		while($file = readdir($path))
		{
			if ($file != '.' and $file != '..')
			{
				// add the filename, to be sure not to
				// overwrite a array key
				$ctime = filectime($data_path . $file) . ',' . $file;
				$notificationFiles[$ctime] = $file;
			}
       }
	   closedir($path);
	   //krsort($notificationFiles);
	}
	
	if($notificationFiles == FALSE)
		die('{"error": "Unable to find any notification files"}');	
	
	// Select files from C:\hawkeye\log\RadarErrors for the date range entered. Default would be the current date
	// Logic is copied from another implementation.
	
	$notificationList = [];
	foreach($notificationFiles as $file)
	{
		$notificationList[] = $file;
	}
	
	if(count($notificationList) == 0)
		return false;
		
	$notificationData = [];
	foreach($notificationList as $file)
	{
		$fullPath = $directoryPath."/".$file;
		
		if (file_exists($fullPath))
		{
			$myfile = fopen($fullPath, "r");
			while (!feof($myfile))
			{
				$contents = fgets($myfile);
				$contents = str_replace("\r\n", "", $contents);
				$lineParts = explode("|", $contents);
				$lineTime = strtotime($lineParts[0]);
				
				if($lineTime >= $startTimestamp && $lineTime <= $endTimestamp)
				{
					$stringdateandtime = $lineParts[0];
					$stringMessage = $lineParts[4];
					$formattedString = $stringMessage;
					
					if ($filterText === "")
					{
						$notificationData[$stringdateandtime] = $formattedString;
					}
					else
					{
						if (stripos($formattedString, (string) $filterText) !== false)
						{
							$notificationData[$stringdateandtime] = $formattedString;
						}
					}

				}
			}
		}
	}
	ksort($notificationData);
	//krsort($datalist);
	
	//$myfile = fopen("C:\Newfile.txt", "w") or die("Unable to open file!");
	//foreach ($notificationData as $testData)
	//foreach ($notificationData as $key => $value)
	//{
	//	$txt = $key . "/" . $value . "\n";
	//	$txt = $testData . "\n";
	//	fwrite($myfile, $txt);
	//}
	//fclose($myfile);		
	
	return $notificationData;
}

function createDateRange($startDate, $endDate, $outputFormat)
{
	$startTimestamp = strtotime($startDate) - 86400;
	$endTimestamp = strtotime($endDate) + 86400;
	
	$dateArray = [];
	
	for($date = $startTimestamp; $date <= $endTimestamp; $date += 86400)
		$dateArray[] = date($outputFormat, $date);
	
	return $dateArray;
}
?>
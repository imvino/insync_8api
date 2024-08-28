<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

if (empty($permissions["reports"]))
    die("Error: You do not have permission to access this page.");

require_once("pathDefinitions.php");
require_once("FileIOHelper.php");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

$intersectionName = "Intersection";

switch($action)
{
    /**
	 * Loads valid dates for stats generation
	 */
	case "getoldest":
	{
		$files = @scandir(TMC_STATS_ROOT, SCANDIR_SORT_ASCENDING);
        $validFiles = [];
        
        foreach($files as $file)
            if(str_starts_with($file, "TM_"))
                $validFiles[] = $file;
        
        if(count($validFiles) == 0)
            echo "{\"year\":\"-1\",\"month\":\"-1\",\"day\":\"-1\"}";
        else
        {
            $year = substr($validFiles[0], 3, 4);
            $month = substr($validFiles[0], 7, 2);
            $day = substr($validFiles[0], 9, 2);
            echo "{\"year\":\"$year\",\"month\":\"$month\",\"day\":\"$day\"}";
        }
	}
	break;
    
	/**
	 * Loads data for specified date range
	 */
	case "loadjson":
	{
		$startDateTime = "";
		if(isset($_REQUEST['startDateTime']))
			$startDateTime = $_REQUEST['startDateTime'];
		
		$endDateTime = "";
		if(isset($_REQUEST['endDateTime']))
			$endDateTime = $_REQUEST['endDateTime'];
		
		if($startDateTime == "" || $endDateTime == "")
			die("Error: Start or End dates are missing");
		
		$data = "vehicle";
		if(isset($_REQUEST['data']))
			$data = $_REQUEST['data'];
		
		if($data == "vehicle")
		{
			$statsArray = loadTMCStats($startDateTime, $endDateTime);
			
			// Get phases with cameras for systems with hawkeye detection
			$activeCameraPhases = getActiveCameraPhases();

			if($statsArray)
			{
                $flotData = [];
                
                require_once("phaseHelper.php");
                
				// If there are phases with camera for hawkeye systems, use that list as active phases
				if (count($activeCameraPhases) > 0)
					$active = $activeCameraPhases;
				else
					$active = getActivePhases();
				               
                $flotData["active"] = $active;
				$flotData["normal"] = convertTMCtoFlot($statsArray, "vehicle");
                $flotData["hourly"] = convertTMCtoFlot($statsArray, "vehiclehourly");
                $flotData["timezone"] = date_default_timezone_get();
                header('Content-Type: application/json');
				echo json_encode($flotData);
			}
			else
				die("{\"error\":\"Could not load statistics.\"}");
		}
        else if($data == "pedestrian")
		{
			$statsArray = loadTMCStats($startDateTime, $endDateTime);

			if($statsArray)
			{
                require_once("phaseHelper.php");                

                $flotData["active"] = getActivePedestrians();
				$flotData["normal"] = convertTMCtoFlot($statsArray, "pedestrian");
                $flotData["hourly"] = convertTMCtoFlot($statsArray, "pedestrianhourly");
                $flotData["timezone"] = date_default_timezone_get();
                header('Content-Type: application/json');
				echo json_encode($flotData);
			}
			else
				die("{\"error\":\"Could not load statistics.\"}");
		}
		else if($data == "period")
		{
			$statsArray = loadPeriodStats($startDateTime, $endDateTime);
			
			if($statsArray)
			{
				$flotData = convertPeriodToFlot($statsArray);
                header('Content-Type: application/json');
				echo $flotData;
			}
			else
				die("{\"error\":\"Could not load statistics.\"}");
		}
		else if($data == "tempload")
		{
            ini_set('memory_limit','100M');
            
			$statsArray = loadPerformanceStats($startDateTime, $endDateTime);

			if($statsArray)
			{
				$flotData = convertCPUToFlot($statsArray);
                $flotData["timezone"] = date_default_timezone_get();
                header('Content-Type: application/json');
				echo json_encode($flotData);
			}
			else
				die("{\"error\":\"Could not load CPU statistics.\"}");
		}
	}
	break;
    
    /**
     * Retrieve data from green/red split graph
     */
    case "getsplits":
    {
        $startDateTime = "";
		if(isset($_REQUEST['startDateTime']))
			$startDateTime = $_REQUEST['startDateTime'];
		
		$endDateTime = "";
		if(isset($_REQUEST['endDateTime']))
			$endDateTime = $_REQUEST['endDateTime'];
		
		if($startDateTime == "" || $endDateTime == "")
			die("Error: Start or End dates are missing");
        
        require_once("phaseHelper.php");
        $phases = getActivePhases();
        $phaseArr = [];
        foreach($phases as $order => $phase)
        {
            $phaseArr[$phase] = [];
            
            $phaseArr[$phase]["g_max"] = -1;
            $phaseArr[$phase]["g_min"] = 999999999;
            $phaseArr[$phase]["g_avg"] = 0;
            $phaseArr[$phase]["count"] = 0;
        }
        
        $splits = loadSplits($startDateTime, $endDateTime, $phases);
        
        $totalTime = 0;
        
        foreach($splits as $timestamp => $data)
            foreach($data as $phase => $value)
                $totalTime += $value;
        
        foreach($splits as $phase => $data)
        {
            foreach($data as $timestamp => $value)
            {
                // min
                if($value < $phaseArr[$phase]["g_min"])
                    $phaseArr[$phase]["g_min"] = $value;
                
                // max
                if($value > $phaseArr[$phase]["g_max"])
                    $phaseArr[$phase]["g_max"] = $value;
                
                // avg
                $phaseArr[$phase]["g_avg"] += $value;
                $phaseArr[$phase]["count"]++;
            }
        }
        
        foreach($phaseArr as $phase => &$data)
        {
            $data["g_total"] = $data["g_avg"];
            
            if($phaseArr[$phase]["count"] != 0)
                $data["g_avg"] /= $phaseArr[$phase]["count"];
            
            $data["g_avg"] = round($data["g_avg"]);
        }
        
        // delete lingering variable
        // dumb that you have to do this.
        unset($data);
        
        ksort($phaseArr);
        
        $dataArr = [];
        
        { // build bar graphs
            $dataArr["chart1"] = [];

            $dataArr["chart1"]["bars"] = [];
            $dataArr["chart1"]["errors"] = [];
            $dataArr["chart1"]["ticks"] = [];

            $count = 0;
            foreach($phaseArr as $phase => $data)
            {   
                if($data["g_min"] == 999999999 || $data["g_max"] == -1)
                    continue;
                
                $dataArr["chart1"]["errors"][] = [$count, (float)$data["g_avg"], (float)$data["g_avg"] - (float)$data["g_min"], (float)$data["g_max"] - (float)$data["g_avg"]];
                $dataArr["chart1"]["ticks"][] = [$count, "Phase $phase"];
                $count++;
            }

            $count = 0;
            foreach($phaseArr as $phase => $data)
            {            
                if($data["g_min"] == 999999999 || $data["g_max"] == -1)
                    continue;
                
                $dataArr["chart1"]["bars"][] = [$count, (float)$data["g_max"], (float)$data["g_min"]];
                $count++;
            }
        }
        
        { // build pie charts
            $dataArr["chart2"] = [];

            $dataArr["chart2"]["data"] = [];

            $count = 0;
            foreach($phaseArr as $phase => $data)
            {            
                $dataArr["chart2"]["data"][] = ["label"=>"Phase $phase", "data"=> (float)$data["g_total"]];
                $count++;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($dataArr);
    }
    break;
    
    case "getrawsplits":
        {
            $startDateTime = "";
            if (isset($_REQUEST['startDateTime']))
                $startDateTime = $_REQUEST['startDateTime'];

            $endDateTime = "";
            if (isset($_REQUEST['endDateTime']))
                $endDateTime = $_REQUEST['endDateTime'];

            if ($startDateTime == "" || $endDateTime == "")
                die("Error: Start or End dates are missing");

            require_once("phaseHelper.php");
            $phases = getActivePhases();
            $splits = loadSplits($startDateTime, $endDateTime, $phases);
            
            header('Content-Type: application/json');
            echo json_encode($splits);
        }
    break;
	
	/**
	 * gets a list of active directions for cartoon drawing
	 */
	case "getactivedirections":
	{
		$associationArray = [];
		
		// Get phases with cameras for systems with hawkeye detection
		$activeCameraPhases = getActiveCameraPhases();
		
		require_once("databaseInterface.php");
		$intersection = getFile("Intersection.xml");
		$intersectionXML = simplexml_load_string($intersection);
		
		if (count($activeCameraPhases) == 0)
		{
			foreach($intersectionXML->Intersection->Direction as $Directions)
			{
				if($Directions["name"] == "North")
				{
					foreach($Directions->Phases->Phase as $Phase)
					{
						// thru
						if((int)$Phase["name"] % 2 == 0)
							$associationArray["SBT"] = (int)$Phase["name"];
						// left
						else
							$associationArray["SBL"] = (int)$Phase["name"];
					}
				}
				else if($Directions["name"] == "South")
				{
					foreach($Directions->Phases->Phase as $Phase)
					{
						// thru
						if((int)$Phase["name"] % 2 == 0)
							$associationArray["NBT"] = (int)$Phase["name"];
						// left
						else
							$associationArray["NBL"] = (int)$Phase["name"];
					}
				}
				else if($Directions["name"] == "West")
				{
					foreach($Directions->Phases->Phase as $Phase)
					{
						// thru
						if((int)$Phase["name"] % 2 == 0)
							$associationArray["EBT"] = (int)$Phase["name"];
						// left
						else
							$associationArray["EBL"] = (int)$Phase["name"];
					}
				}
				else if($Directions["name"] == "East")
				{
					foreach($Directions->Phases->Phase as $Phase)
					{
						// thru
						if((int)$Phase["name"] % 2 == 0)
							$associationArray["WBT"] = (int)$Phase["name"];
						// left
						else
							$associationArray["WBL"] = (int)$Phase["name"];
					}
				}
			}
		}
		else
		{
			foreach($intersectionXML->Intersection->Direction as $Directions)
			{
				if($Directions["name"] == "North")
				{
					foreach($Directions->Phases->Phase as $Phase)
					{
						if (in_array($Phase["name"], $activeCameraPhases))
						{
							// thru
							if((int)$Phase["name"] % 2 == 0)
								$associationArray["SBT"] = (int)$Phase["name"];
							// left
							else
								$associationArray["SBL"] = (int)$Phase["name"];
						}
					}
				}
				else if($Directions["name"] == "South")
				{
					foreach($Directions->Phases->Phase as $Phase)
					{
						if (in_array($Phase["name"], $activeCameraPhases))
						{
							// thru
							if((int)$Phase["name"] % 2 == 0)
								$associationArray["NBT"] = (int)$Phase["name"];
							// left
							else
								$associationArray["NBL"] = (int)$Phase["name"];
						}
					}
				}
				else if($Directions["name"] == "West")
				{
					foreach($Directions->Phases->Phase as $Phase)
					{
						if (in_array($Phase["name"], $activeCameraPhases))
						{
							// thru
							if((int)$Phase["name"] % 2 == 0)
								$associationArray["EBT"] = (int)$Phase["name"];
							// left
							else
								$associationArray["EBL"] = (int)$Phase["name"];
						}
					}
				}
				else if($Directions["name"] == "East")
				{
					foreach($Directions->Phases->Phase as $Phase)
					{
						if (in_array($Phase["name"], $activeCameraPhases))
						{
							// thru
							if((int)$Phase["name"] % 2 == 0)
								$associationArray["WBT"] = (int)$Phase["name"];
							// left
							else
								$associationArray["WBL"] = (int)$Phase["name"];
						}
					}
				}
			}			
		}
		
        header('Content-Type: application/json');
		echo json_encode($associationArray);
	}
	break;
	
	/**
	 * Forces download of CSV
	 */
	case "downloadcsv":
	{
		$startDateTime = "";
		if(isset($_REQUEST['startDateTime']))
			$startDateTime = $_REQUEST['startDateTime'];
		
		$endDateTime = "";
		if(isset($_REQUEST['endDateTime']))
			$endDateTime = $_REQUEST['endDateTime'];
		
		if($startDateTime == "" || $endDateTime == "")
			die("Error: Start or End dates are missing");
		
		$vehicleCounts = "off";
		if(isset($_REQUEST['vehicleCounts']))
			$vehicleCounts = $_REQUEST['vehicleCounts'];
		
		$pedestrianCounts = "off";
		if(isset($_REQUEST['pedestrianCounts']))
			$pedestrianCounts = $_REQUEST['pedestrianCounts'];
		
		$hourlySummaries = "off";
		if(isset($_REQUEST['hourlySummaries']))
			$hourlySummaries = $_REQUEST['hourlySummaries'];
		
		if($vehicleCounts != "on" && $pedestrianCounts != "on" && $hourlySummaries != "on")
			die("Error: No data selected to put into CSV.");
		
		if($vehicleCounts == "on")
			$vehicleCounts = true;
		else
			$vehicleCounts = false;
		
		if($pedestrianCounts == "on")
			$pedestrianCounts = true;
		else
			$pedestrianCounts = false;
		
		if($hourlySummaries == "on")
			$hourlySummaries = true;
		else
			$hourlySummaries = false;
		
		$statsArray = loadTMCStats($startDateTime, $endDateTime);

		if($statsArray)
		{
			$csvData = convertTMCtoCSV($statsArray, $vehicleCounts, $pedestrianCounts, $hourlySummaries, $startDateTime, $endDateTime);
			
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename=statistics.csv");
			header("Pragma: no-cache");
			header("Expires: 0");
			
			echo $csvData;
		}
		else
			die("Error: Could not read any statistics from that period.");	
	}
	break;
	
	/**
	 * Loads data for specified date range
	 */
	case "gettotals":
	{
		$startDateTime = "";
		if(isset($_REQUEST['startDateTime']))
			$startDateTime = $_REQUEST['startDateTime'];
		
		$endDateTime = "";
		if(isset($_REQUEST['endDateTime']))
			$endDateTime = $_REQUEST['endDateTime'];
		
		if($startDateTime == "" || $endDateTime == "")
			die("Error: Start or End dates are missing");

		$stats = loadTMCStats($startDateTime, $endDateTime);
		
        header('Content-Type: application/json');
		echo json_encode(getTotals($stats));
	}
	break;
	
	/**
	 * Forces download of raw TMC files
	 */
	case "downloadraw":
	{
		$startDateTime = "";
		if(isset($_REQUEST['startDateTime']))
			$startDateTime = $_REQUEST['startDateTime'];
		
		$endDateTime = "";
		if(isset($_REQUEST['endDateTime']))
			$endDateTime = $_REQUEST['endDateTime'];
		
		if($startDateTime == "" || $endDateTime == "")
			die("Error: Start or End dates are missing");
		
		downloadRawLogs($startDateTime, $endDateTime);		
	}
	break;
}

function loadSplits($startDate, $endDate, $phases)
{
    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    
    $splitArr = [];

    $dateRangeArray = createDateRange($startDate, $endDate, "Ymd");
    
    foreach($dateRangeArray as $date)
    {
        $greenSplitArr = [];
        
        $filename = GREEN_DURATIONS_STATS_ROOT . "/GD_" . $date . "_000000.txt";
        if(file_exists($filename))
            $greenSplitArr = parseSplitFile($filename, $date, $startTimestamp, $endTimestamp, $phases);

        foreach($greenSplitArr as $phase => $data)
            foreach($data as $timestamp => $duration)
                $splitArr[$phase][$timestamp] = $duration;
    }
    
    return $splitArr;
}

function parseSplitFile($filename, $date, $startTimestamp, $endTimestamp, $activePhases)
{    
    $splitArray = [];

    if (($handle = fopen($filename, "r")) !== FALSE) 
    {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
        {
            // skip invalid row
            if(count($data) != 3)
                continue;
            
            $timestamp = strtotime($data[1]);
            
            if(in_array($data[0], $activePhases) && $timestamp >= $startTimestamp && $timestamp <= $endTimestamp)
                $splitArray[$data[0]][strtotime($data[1])] = $data[2];
        }
        
        fclose($handle);
    }
    
    return $splitArray;
}

function getTotals($statsArr)
{
	$jsonData = [];
	
	for($i=1; $i <= 8; $i++)
	{
		$jsonData["veh" . $i] = 0;
		$jsonData["ped" . $i] = 0;
	}
	
	foreach($statsArr as $dateArr)
	{
		foreach($dateArr as $time => $data)
		{
			for($i=1; $i <= 8; $i++)
			{
				if(isset($data["veh" . $i]))
					$jsonData["veh" . $i] += $data["veh" . $i];
				
				if(isset($data["ped" . $i]))
					$jsonData["ped" . $i] += $data["ped" . $i];
			}
		}
	}
	
	$loggedIn = true;

	require_once("phaseHelper.php");
	$phaseNames = getPhaseNames();
	
	require_once("databaseInterface.php");
	$intersection = getFile("Intersection.xml");
	$intersectionXML = simplexml_load_string($intersection);
	$phases = $intersectionXML->xpath("//Phase");
	
	// Get phases with cameras for systems with hawkeye detection and build phaseList
	$activeCameraPhases = getActiveCameraPhases();
	
	if (count($activeCameraPhases) > 0)
	{
		foreach ($activeCameraPhases as $newphase)
			$phaseList[] = (int)$newphase;
	}		
	else
	{
		foreach($phases as $phase)
			$phaseList[] = (int)$phase["name"];		
	}
	
	sort($phaseList);

	// Get active pedestrians
	$pedList = getActivePedestrians();
	
	for($i=1; $i <= 8; $i++)
	{
		if(!in_array($i, $phaseList))
			unset($jsonData["veh" . $i]);
				
		if(!in_array($i, $pedList))
			unset($jsonData["ped" . $i]);
	}

	return $jsonData;
}

function convertCPUToFlot($statsArray)
{
	$numCPUs = count(current($statsArray)["cpuload"]);
	
	$flotData = [];
    $flotData["load"] = [];
    $flotData["temp"] = [];
    $flotData["speed"] = [];
	
	for($i=0; $i < $numCPUs; $i++)
	{
		$flotData["load"][$i]["label"] = "CPU #" . ($i+1);
		$flotData["load"][$i]["lines"]["show"] = true;
		$flotData["load"][$i]["data"] = [];
        
        $flotData["temp"][$i]["label"] = "CPU #" . ($i+1);
		$flotData["temp"][$i]["lines"]["show"] = true;
		$flotData["temp"][$i]["data"] = [];
        
        $flotData["speed"][$i]["label"] = "CPU #" . ($i+1);
		$flotData["speed"][$i]["lines"]["show"] = true;
		$flotData["speed"][$i]["data"] = [];

		foreach($statsArray as $time => $data)
        {
			$flotData["load"][$i]["data"][] = [$time*1000, $data["cpuload"][$i]];
            $flotData["temp"][$i]["data"][] = [$time*1000, $data["cputemp"][$i]];
            $flotData["speed"][$i]["data"][] = [$time*1000, $data["cpufreq"][$i]];
        }
	}
	
	return $flotData;
}

function loadPerformanceStats($startDateTime, $endDateTime)
{	
	$dateRangeArray = createDateRange($startDateTime, $endDateTime, "Ymd");
	
	$startTimestamp = strtotime($startDateTime);
	$endTimestamp = strtotime($endDateTime);
	
	// remove dates with no log files
	$validDates = [];
	foreach($dateRangeArray as $date)
		if(file_exists(PERFORMANCE_STATS_ROOT . "/" . "PS_" . $date . "_000000.txt"))
			$validDates[] = $date;
		
	if(count($validDates) == 0)
		return false;
	
	$graphData = [];
	
	foreach($validDates as $date)
	{
		$filename = PERFORMANCE_STATS_ROOT . "/" . "PS_" . $date . "_000000.txt";
		
		$useDay = substr($date, 4, 2) . "/" . substr($date, 6, 2) . "/" . substr($date, 0, 4);
		
		$file = fopen($filename, "rb");
			
		// read lines until fgetcsv === FALSE (EOF)
		$lineNum = 0;
		while(($line = fgetcsv($file, 0, "\t")) !== FALSE)
		{
			$timestamp = $line[0];
			
			if($timestamp < $startTimestamp || $timestamp > $endTimestamp)
				continue;
			
			$jsonData = json_decode($line[1], true);
			
			if(isset($jsonData["error"]))
				continue;
			
			$graphData[$line[0]] = $jsonData;
		}
		
		fclose($file);
	}
	
	return $graphData;
}

function downloadRawLogs($startDateTime, $endDateTime)
{
	$startMonth = substr($startDateTime, 0, 2);
	$startDay = substr($startDateTime, 3, 2);
	$startYear = substr($startDateTime, 6, 4);
	
	$endMonth = substr($endDateTime, 0, 2);
	$endDay = substr($endDateTime, 3, 2);
	$endYear = substr($endDateTime, 6, 4);	
	
	// convert user dates to Unix timestamp
	$unixDateStart = strtotime("$startMonth/$startDay/$startYear 00:00"); // use 0 starting time for file comparison
	$unixDateEnd = strtotime($endDateTime);
	
	//add a day to the end date and convert to Unix timestamp
	$unixDateEndAdditional = strtotime("$endMonth/$endDay/$endYear 00:00");
	$unixDateEndAdditional = strtotime('+1day', $unixDateEndAdditional);	
	
	$logFilesTemp = scandir(TMC_STATS_ROOT);
	$logFiles = [];
	
	$additionalDay = "";
	$lastfileFlag = "Y";	
	
	$columnMap = [];
		
	// turn each filename into a Unix timestamp
	foreach($logFilesTemp as $log)
	{
		if(str_starts_with($log, "TM_"))
		{
			$timeStr = substr($log,3,4) . "/" . substr($log,7,2). "/" . substr($log,9,2) . " 00:00";
			
			$logT = strtotime($timeStr);
			
			// Don't add files out of date range for performance reasons
			if($logT >= $unixDateStart && $logT <= $unixDateEnd)
				$logFiles[] = $logT;
				
			//Add file for an additional day after end date.
			if($logT == $unixDateEndAdditional)
			$additionalDay = $logT;			
		}
	}
	
	if(count($logFiles) < 1)
	{
		echo "<h3>No log files are present in this date range.</h3>";
		exit;
	}
	
	// one log file, just pass it through as .txt
	if(count($logFiles) == 1)
	{
		$turningFileTemp = TEMP_ROOT . "/TMP_" . date("Ymd", $logFiles[0]) . "_000000.txt";
		$lastfileFlag = "Y";
		createTempfile($turningFileTemp, $logFiles[0], $nextDayVal, $additionalDay, $lastfileFlag);		
	
		$turningFileShort = "TurningMovementCount_" . date("mdY", $logFiles[0]) . ".txt";
		
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=$turningFileShort");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		ob_clean();
		
		readfile($turningFileTemp);
	}
	// For multiple files, zip the contents and send to user
	else
	{
		// create temp directory if there isnt one
		if(!file_exists(TEMP_ROOT))
			mkdir(TEMP_ROOT);
		
		$zip = new ZipArchive;
		$fileCounter = 0;
		
		$randFilename = TEMP_ROOT . "/" .uniqid() . ".zip";
		
		if ($zip->open($randFilename, ZipArchive::OVERWRITE) === TRUE) 
		{
			foreach($logFiles as $log)
			{
				$fileCounter++;
				if ($fileCounter >= count($logFiles))
				{
					$lastfileFlag = "Y";
					$logFiles[$fileCounter] = "";
				}
				else
				{
					$lastfileFlag = "N";
				}			
			
				$name = TEMP_ROOT . "/TMP_" . date("Ymd", $log) . "_000000.txt";
				createTempfile($name, $log, $logFiles[$fileCounter], $additionalDay, $lastfileFlag);				
				$zip->addFile($name, "TurningMovementCount_" . date("mdY", $log) . ".txt");
			}

			$zip->close();
		}
		else
			die("Error: Unable to create temporary zip file '" . $randFilename . "'.");
			
		ini_set("zlib.output_compression", "0"); 
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"tmc.zip\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($randFilename));
		ob_clean();
		flush();
		
		readfile($randFilename);
		@unlink($randFilename);
	}
}

function convertTMCtoCSV($statsArray, $vehicleCounts, $pedestrianCounts, $hourlySummaries, $startDateTime, $endDateTime)
{
	global $intersectionName;
	
	$startTimestamp = strtotime($startDateTime);
	$endTimestamp = strtotime($endDateTime);
	
	$loggedIn = true;
	
	require_once("phaseHelper.php");
	$phaseNames = getPhaseNames();

	// Get phases with cameras for systems with hawkeye detection and build phaseList
	$activeCameraPhases = getActiveCameraPhases();
	
	require_once("databaseInterface.php");
	$intersection = getFile("Intersection.xml");
	$intersectionXML = simplexml_load_string($intersection);
	$phases = $intersectionXML->xpath("//Phase");

	if (count($activeCameraPhases) > 0)
	{
		foreach ($activeCameraPhases as $newphase)
			$phaseList[] = (int)$newphase;
	}		
	else
	{
		foreach($phases as $phase)
			$phaseList[] = (int)$phase["name"];
	}

	sort($phaseList);
	
	$phaseCount = count($phaseList);
	
	// Get active pedestrians
	$pedList = getActivePedestrians();
	
	$pedCount = count($pedList);
	
	$outputArray = [];
	$outputCSV = "";	
	$outputCSV .= $intersectionName . "\r\n\r\n";
	
	foreach($statsArray as $date=>$timeArray)
	{
		$timeList = ["12:00 AM", "12:15 AM", "12:30 AM", "12:45 AM", "01:00 AM", "01:15 AM", "01:30 AM", "01:45 AM", "02:00 AM", "02:15 AM", "02:30 AM", "02:45 AM", "03:00 AM", "03:15 AM", "03:30 AM", "03:45 AM", "04:00 AM", "04:15 AM", "04:30 AM", "04:45 AM", "05:00 AM", "05:15 AM", "05:30 AM", "05:45 AM", "06:00 AM", "06:15 AM", "06:30 AM", "06:45 AM", "07:00 AM", "07:15 AM", "07:30 AM", "07:45 AM", "08:00 AM", "08:15 AM", "08:30 AM", "08:45 AM", "09:00 AM", "09:15 AM", "09:30 AM", "09:45 AM", "10:00 AM", "10:15 AM", "10:30 AM", "10:45 AM", "11:00 AM", "11:15 AM", "11:30 AM", "11:45 AM", "12:00 PM", "12:15 PM", "12:30 PM", "12:45 PM", "01:00 PM", "01:15 PM", "01:30 PM", "01:45 PM", "02:00 PM", "02:15 PM", "02:30 PM", "02:45 PM", "03:00 PM", "03:15 PM", "03:30 PM", "03:45 PM", "04:00 PM", "04:15 PM", "04:30 PM", "04:45 PM", "05:00 PM", "05:15 PM", "05:30 PM", "05:45 PM", "06:00 PM", "06:15 PM", "06:30 PM", "06:45 PM", "07:00 PM", "07:15 PM", "07:30 PM", "07:45 PM", "08:00 PM", "08:15 PM", "08:30 PM", "08:45 PM", "09:00 PM", "09:15 PM", "09:30 PM", "09:45 PM", "10:00 PM", "10:15 PM", "10:30 PM", "10:45 PM", "11:00 PM", "11:15 PM", "11:30 PM", "11:45 PM"];
		
		$useDate = substr($date, 4, 2) . "/" . substr($date, 6, 2) . "/" . substr($date, 0, 4);
		
		$outputCSV .= $useDate . "\r\n";
		
		if($vehicleCounts)
		{
			$outputCSV .= ",Phase Volume Counts,";
			
			for($i=1;$i<$phaseCount;$i++)
				$outputCSV .= ",";
		}
		
		if($pedestrianCounts)
		{
			$outputCSV .= "Pedestrian Counts,";

			for($i=1;$i<$pedCount;$i++)
				$outputCSV .= ",";
		}
		
		$outputCSV .= "\r\n";
		$outputCSV .= ",";
	
		if($vehicleCounts)
			for($i=1;$i<=8;$i++)
				if(in_array($i, $phaseList))
					$outputCSV .= htmlspecialchars_decode($phaseNames[$i]['short']) . ",";
		
		if($pedestrianCounts)
			for($i=1;$i<=8;$i++)
				if(in_array($i, $pedList))
					$outputCSV .= htmlspecialchars_decode($phaseNames[$i]['short']) . ",";
					
		$outputCSV .= "\r\n";
		$outputCSV .= "Time,";
		
		if($vehicleCounts)
			for($i=1;$i<=8;$i++)
				if(in_array($i, $phaseList))
					$outputCSV .= "Phase $i,";
					
		if($pedestrianCounts)
			for($i=1;$i<=8;$i++)
				if(in_array($i, $pedList))
					$outputCSV .= "Phase $i,";
					
		$outputCSV .= "\r\n";
		
		foreach($timeList as $time)
		{	
		
			if(strtotime($useDate . " " . $time) < $startTimestamp || strtotime($useDate . " " . $time) > $endTimestamp)
				continue;
					
			$outputCSV .= "$time,";
			
			if($vehicleCounts)
				for($i=1;$i<=8;$i++)
					if(in_array($i, $phaseList))
						$outputCSV .= (int)$timeArray[$time]["veh" . $i] . ",";
						
			if($pedestrianCounts)
				for($i=1;$i<=8;$i++)
					if(in_array($i, $pedList))
						$outputCSV .= (int)$timeArray[$time]["ped" . $i] . ",";
						
			$outputCSV .= "\r\n";
			
			if($hourlySummaries)
			{
				if(str_contains($time, ":45"))
				{
					$outputCSV .= "Total,";
					
					if($vehicleCounts)
					{
						for($i=1;$i<=8;$i++)
						{
							if(in_array($i, $phaseList))
							{
								$searchTime = $time;
								$phaseTotal = 0;
								
								$phaseTotal += (int)$timeArray[str_replace(":45",":00",$time)]["veh" . $i] . ",";
								$phaseTotal += (int)$timeArray[str_replace(":45",":15",$time)]["veh" . $i] . ",";
								$phaseTotal += (int)$timeArray[str_replace(":45",":30",$time)]["veh" . $i] . ",";
								
								$phaseTotal += (int)$timeArray[$time]["veh" . $i];

								$outputCSV .= "$phaseTotal,";
							}
						}
					}
								
					if($pedestrianCounts)
					{
						for($i=1;$i<=8;$i++)
						{
							if(in_array($i, $pedList))
							{
								$searchTime = $time;
								$phaseTotal = 0;
								
								$phaseTotal += (int)$timeArray[str_replace(":45",":00",$time)]["ped" . $i] . ",";
								$phaseTotal += (int)$timeArray[str_replace(":45",":15",$time)]["ped" . $i] . ",";
								$phaseTotal += (int)$timeArray[str_replace(":45",":30",$time)]["ped" . $i] . ",";
								
								$phaseTotal += (int)$timeArray[$time]["ped" . $i];

								$outputCSV .= "$phaseTotal,";
							}
						}
					}
								
					$outputCSV .= "\r\n\r\n";
				}
			}
		}
	}
	
	return $outputCSV;
}

function convertTMCtoFlot($statsArray, $data)
{
	require_once("phaseHelper.php");
	$phaseNames = getPhaseNames();
	
	if($data == "vehicle")
		$data = "veh";
	else if($data == "vehiclehourly")
		$data = "veh_hourly";
	else if($data == "pedestrian")
		$data = "ped";
	else if($data == "pedestrianhourly")
		$data = "ped_hourly";
	
	$outputArray = [];
	
	foreach($statsArray as $date=>$timeArray)
	{
		$useDate = substr($date, 4, 2) . "/" . substr($date, 6, 2) . "/" . substr($date, 0, 4);
		
		foreach($timeArray as $time => $phaseData)
		{
			if(str_contains($data, "hourly") && !str_contains($time, ":00"))
				continue;
					
			for($phase = 1; $phase <= 8; $phase++)
				if(isset($phaseData[$data . $phase]))
					$outputArray[$phase][] = [strtotime($useDate . " " . $time)*1000, (int)$phaseData[$data . $phase]];
		}
		
		$finishArray = [];
		
		$activePhases = 0;
		
		for($phase = 1; $phase <= 8; $phase++)
		{
			if(isset($outputArray[$phase]))
			{
				$finishArray[$activePhases]["label"] = $data . "|Phase $phase (" . $phaseNames[$phase]['short'] . ")";
				$finishArray[$activePhases]["data"] = $outputArray[$phase];
				
				$activePhases++;
			}
		}
	}
	
	return $finishArray;
}

function convertPeriodToFlot($logData)
{
	$outputArray = $logData;
	
	$finishArray = [];
	
	$finishArray["data"][0]["label"] = "Period Length";
	$finishArray["data"][0]["points"] = ["show"=>true];
	$finishArray["data"][0]["lines"] = ["show"=>true];
	$finishArray["data"][0]["data"] = $outputArray;
    
    $finishArray["timezone"] = date_default_timezone_get();
	
	return json_encode($finishArray);
}

function loadPeriodStats($startDateTime, $endDateTime)
{
	$dateRangeArray = createPeriodGraphDateRange($startDateTime, $endDateTime, "Ymd");

	// remove dates with no log files
	$validDates = [];
	foreach($dateRangeArray as $date)
		if(file_exists(PERIOD_STATS_ROOT . "/" . "PD_" . $date . "_000000.txt"))
			$validDates[] = $date;
		
	if(count($validDates) == 0)
		return false;
	
	$graphData = [];
	
    $startTime = (int)strtotime($startDateTime);
    $endTime = (int)strtotime($endDateTime);
    
    // contains the valid period data points
    // found within the date range, plus one
    // extra data point prior to the start date
    $periodData = [];
    
    // process all of the potential files
	foreach($validDates as $date)
	{
        $filename = PERIOD_STATS_ROOT . "/" . "PD_" . $date . "_000000.txt";
        $useDay = substr($date, 4, 2) . "/" . substr($date, 6, 2) . "/" . substr($date, 0, 4);
        $file = fopen($filename, "rb");

        // test file date for being out of range
        // if so, stop file processing
        // this assumes file dates are given in order
        $fileStartTime = (int)strtotime("$useDay 00:00:00");
        if ($fileStartTime > $endTime)
        {
            break;
        }
        
        // read lines until fgetcsv === FALSE (EOF)           
        while(($line = fgetcsv($file)) !== FALSE)
        {
            $currLineTime = (int)strtotime("$useDay $line[0]");
            $currPeriod = $line[1];
            
            if($currLineTime < $startTime)
            {
                // keep re-initializing the period
                // data array and adding the 
                // first period data to it
                $periodData = [];
                $periodData[] = [(int)$currLineTime, $currPeriod];
            }
            else if($currLineTime > $endTime)
            {
                break;
            }
            else
            {
                // add to the data list
                $periodData[] = [(int)$currLineTime, $currPeriod];
            }
        }

        fclose($file);
	}
        
    // filters the period data
    // removing any TUNNELLESS
    // entries with less than 5 sec.
    // duration
    $periodDataFiltered = [];
    
    if (count($periodData) > 0)
    {
        // keep the first data point, regardless of how
        // long the duration is
        $periodDataFiltered[] = $periodData[0];
        
        $numPeriodDataPoints = count($periodData);
        
        // filters the period data
        // removing any TUNNELLESS
        // entries with less than 5 sec.
        // duration
        for ($i=1; $i<$numPeriodDataPoints; $i++)
        {
            $nextI = $i+1;
            if ($nextI < $numPeriodDataPoints)
            {
                $timePeriodNow = $periodData[$i][0];
                $periodNow = $periodData[$i][1];
                $timePeriodNext = $periodData[$nextI][0];
                
                if ($periodNow == "TUNNELLESS")
                {
                    if (($timePeriodNext - $timePeriodNow) >= 5)
                    {
                        $periodDataFiltered[] = $periodData[$i];
                    }
                }
                else
                {
                    $periodDataFiltered[] = $periodData[$i];
                }
            }
            else
            {
                $periodDataFiltered[] = $periodData[$i];
            }
        }
    }
    else
    {
        return $graphData;
    }
    
    if (count($periodDataFiltered) < 1)
    {
        return $graphData;
    }
    
    // first data point is outside the range
    // we just need to create a graph point
    // at start data with the period of that
    // data point outside the range
    
    // if the data point is tunnelless, then we
    // don't have to plot it
    $firstPeriod = $periodDataFiltered[0][1];
    if ($firstPeriod != "TUNNELLESS" && count($validDates) > 1)
    {
        $graphData[] = [$startTime*1000, $firstPeriod];
    }
	else if ($firstPeriod != "TUNNELLESS" && count($validDates) == 1) 	
	{
		$graphData[] = [$periodDataFiltered[0][0]*1000, $firstPeriod];
	}
	
    //if ($firstPeriod != "TUNNELLESS")
    //{
    //    $graphData[] = array($startTime*1000, $firstPeriod);
    //}
    
    $numFilteredPeriodDataPoints = count($periodDataFiltered);
    for ($i=1; $i<$numFilteredPeriodDataPoints; $i++)
    {
        // if the data point is tunnelless
        // we must create a data point at this 
        // time that represents the prior period,
        // unless it was also tunnelless
        if ($periodDataFiltered[$i][1] == "TUNNELLESS")
        {
            $previousPeriod = $periodDataFiltered[$i-1][1];
            if ($previousPeriod != "TUNNELLESS")
            {
                $graphData[] = [$periodDataFiltered[$i][0]*1000, $previousPeriod];
            }
            $graphData[] = [$periodDataFiltered[$i][0]*1000, null];
        }
        else
        {
            $graphData[] = [$periodDataFiltered[$i][0]*1000, $periodDataFiltered[$i][1]];
        }
    }
    
    // lastly, we may need to add a datapoint at end time
    // for the last period
    $lastPeriod = $periodDataFiltered[$numFilteredPeriodDataPoints-1][1];
    if ($lastPeriod != "TUNNELLESS")
    {
        $graphData[] = [$endTime*1000, $lastPeriod];
    }
    
	return $graphData;
}

function loadTMCStats($startDateTime, $endDateTime)
{
	// create array of all dates between start and end
	$dateRangeArray = createDateRange($startDateTime, $endDateTime, "Ymd");

	// remove dates with no log files
	$validDates = [];
	foreach($dateRangeArray as $date)
		if(file_exists(TMC_STATS_ROOT . "/" . "TM_" . $date . "_000000.txt"))
			$validDates[] = $date;
		
	if(count($validDates) == 0)
		return false;
	
	global $intersectionName;
	$intersectionName = "Intersection";
	
    $graphPhaseData = [];
            
	foreach ($validDates as $date) 
	{
		$filename = TMC_STATS_ROOT . "/" . "TM_" . $date . "_000000.txt";

		$contents = @file_get_contents($filename);
        
        if($contents === FALSE)
            continue;

        $lines = explode("\n", $contents);

		for($lineNum = 0; $lineNum < count($lines); $lineNum++)
		{
            $line = explode(",", $lines[$lineNum]);
            
            if(count($line) <= 1)
                continue;
            
			// skip head line
			if ($lineNum == 0) 
			{
				$parts = explode(" : ", $line[0]);
				$intersectionName = $parts[0];
				continue;
			}

			// parse column mapping
			if ($lineNum == 1) 
			{
				$columnMap = getColumnMapping($line);
				continue;
			}

			$month = substr($date, 4, 2);
			$day = substr($date, 6, 2);
			$year = substr($date, 0, 4);

			// get timestamp for CSV line
			$lineTime = strtotime("$month/$day/$year " . $line[$columnMap["time"]]) - 900;
            
            $graphPhaseData[date("Ymd", $lineTime)][date("h:i A", $lineTime)] = [];

			// Add Phase data
			for ($phase = 1; $phase <= 8; $phase++) 
			{
				if(isset($columnMap["pc" . $phase]))
				{                    
					if (!isset($line[$columnMap["pc" . $phase]]))
						$graphPhaseData[date("Ymd", $lineTime)][date("h:i A", $lineTime)]["ped" . $phase] = 0;
					else
						$graphPhaseData[date("Ymd", $lineTime)][date("h:i A", $lineTime)]["ped" . $phase] = $line[$columnMap["pc" . $phase]];
				}
				else
					$graphPhaseData[date("Ymd", $lineTime)][date("h:i A", $lineTime)]["ped" . $phase] = 0;
                
				$graphPhaseData[date("Ymd", $lineTime)][date("h:i A", $lineTime)]["veh" . $phase] = $line[$columnMap["vc" . $phase]];
			}
		}
	}

	// brute force through the loop and add any dates/times that are missing
	// ugly but it works for now	
	foreach ($graphPhaseData as $date => $value)
	{
		// list of required times
		$timeList = ["12:00 AM", "12:15 AM", "12:30 AM", "12:45 AM", "01:00 AM", "01:15 AM", "01:30 AM", "01:45 AM", "02:00 AM", "02:15 AM", "02:30 AM", "02:45 AM", "03:00 AM", "03:15 AM", "03:30 AM", "03:45 AM", "04:00 AM", "04:15 AM", "04:30 AM", "04:45 AM", "05:00 AM", "05:15 AM", "05:30 AM", "05:45 AM", "06:00 AM", "06:15 AM", "06:30 AM", "06:45 AM", "07:00 AM", "07:15 AM", "07:30 AM", "07:45 AM", "08:00 AM", "08:15 AM", "08:30 AM", "08:45 AM", "09:00 AM", "09:15 AM", "09:30 AM", "09:45 AM", "10:00 AM", "10:15 AM", "10:30 AM", "10:45 AM", "11:00 AM", "11:15 AM", "11:30 AM", "11:45 AM", "12:00 PM", "12:15 PM", "12:30 PM", "12:45 PM", "01:00 PM", "01:15 PM", "01:30 PM", "01:45 PM", "02:00 PM", "02:15 PM", "02:30 PM", "02:45 PM", "03:00 PM", "03:15 PM", "03:30 PM", "03:45 PM", "04:00 PM", "04:15 PM", "04:30 PM", "04:45 PM", "05:00 PM", "05:15 PM", "05:30 PM", "05:45 PM", "06:00 PM", "06:15 PM", "06:30 PM", "06:45 PM", "07:00 PM", "07:15 PM", "07:30 PM", "07:45 PM", "08:00 PM", "08:15 PM", "08:30 PM", "08:45 PM", "09:00 PM", "09:15 PM", "09:30 PM", "09:45 PM", "10:00 PM", "10:15 PM", "10:30 PM", "10:45 PM", "11:00 PM", "11:15 PM", "11:30 PM", "11:45 PM"];

		foreach ($timeList as $time) 
		{
			if (!isset($graphPhaseData[$date][$time])) 
			{
				for ($phase = 1; $phase <= 8; $phase++)
					$graphPhaseData[$date][$time]["ped" . $phase] = 0;

				for ($phase = 1; $phase <= 8; $phase++)
				{
					$graphPhaseData[$date][$time]["veh" . $phase] = 0;
				}
			}

			$sortingArray = [];
			
			foreach ($timeList as $ltime) 
				if (isset($graphPhaseData[$date][$ltime]))
					$sortingArray[$ltime] = $graphPhaseData[$date][$ltime];

			$graphPhaseData[$date] = $sortingArray;
		}
	}

	// summarize hourly data
	foreach($graphPhaseData as $date => $dateData)
	{
		foreach ($dateData as $time => $phaseArray) 
		{
			// if we're on our 00 time line, add up this and next 3 points
			if (strstr($time, ":00")) 
			{
				// keep total count per hour for each phase
				$totalPhase = [];

				for ($phase = 1; $phase <= 8; $phase++) 
				{
					if(!isset($totalPhase["veh_hourly" . $phase]))
						$totalPhase["veh_hourly" . $phase] = 0;
					
					// if this vehicle phase contains data
					if (isset($phaseArray["veh" . $phase])) 
					{						
						if (isset($graphPhaseData[$date][str_replace("00", "15", $time)]))
							$totalPhase["veh_hourly" . $phase] += $graphPhaseData[$date][str_replace("00", "15", $time)]["veh" . $phase];
						if (isset($graphPhaseData[$date][str_replace("00", "30", $time)]))
							$totalPhase["veh_hourly" . $phase] += $graphPhaseData[$date][str_replace("00", "30", $time)]["veh" . $phase];
						if (isset($graphPhaseData[$date][str_replace("00", "45", $time)]))
							$totalPhase["veh_hourly" . $phase] += $graphPhaseData[$date][str_replace("00", "45", $time)]["veh" . $phase];

						$totalPhase["veh_hourly" . $phase] += $phaseArray["veh" . $phase];
					}
					
					if(!isset($totalPhase["ped_hourly" . $phase]))
						$totalPhase["ped_hourly" . $phase] = 0;

					// if this ped phase contains data
					if (isset($phaseArray["ped" . $phase])) 
					{
						if (isset($graphPhaseData[$date][str_replace("00", "15", $time)]))
							$totalPhase["ped_hourly" . $phase] += $graphPhaseData[$date][str_replace("00", "15", $time)]["ped" . $phase];
						if (isset($graphPhaseData[$date][str_replace("00", "30", $time)]))
							$totalPhase["ped_hourly" . $phase] += $graphPhaseData[$date][str_replace("00", "30", $time)]["ped" . $phase];
						if (isset($graphPhaseData[$date][str_replace("00", "45", $time)]))
							$totalPhase["ped_hourly" . $phase] += $graphPhaseData[$date][str_replace("00", "45", $time)]["ped" . $phase];

						$totalPhase["ped_hourly" . $phase] += $phaseArray["ped" . $phase];
					}
				}

				$graphPhaseData[$date][$time] = array_merge($graphPhaseData[$date][$time], $totalPhase);
			}
		}
	}
			
	// purge data before $startTime and after $endTime
	$startTimestamp = strtotime($startDateTime);
	$endTimestamp = strtotime($endDateTime);
	
	$startDate = substr($startDateTime, 6, 4) . substr($startDateTime, 0, 2) . substr($startDateTime, 3, 2);
	$endDate = substr($endDateTime, 6, 4) . substr($endDateTime, 0, 2) . substr($endDateTime, 3, 2);
	
	foreach($graphPhaseData as $date => $timeArray)
	{        
		foreach($timeArray as $time => $phaseData)
		{            
			if($date <= $startDate)
			{
				$convertedDate = substr($date, 4, 2) . "/" . substr($date, 6, 2) . "/" . substr($date, 0, 4);
				
				$entryTimestamp = strtotime($convertedDate . " " . $time);
				
				if($entryTimestamp < $startTimestamp)
					unset($graphPhaseData[$date][$time]);
			}
			
			if($date >= $endDate)
			{
				$convertedDate = substr($date, 4, 2) . "/" . substr($date, 6, 2) . "/" . substr($date, 0, 4);
				
				$entryTimestamp = strtotime($convertedDate . " " . $time);
				
				if($entryTimestamp >= $endTimestamp)
					unset($graphPhaseData[$date][$time]);
			}
		}
        
        if(count($graphPhaseData[$date]) == 0)
            unset($graphPhaseData[$date]);
	}
    
	require_once("phaseHelper.php");

	// Get active pedestrians
	$pedList = getActivePedestrians();
	
	foreach($graphPhaseData as $date => $timeArray)
	{
		foreach($timeArray as $time => $phaseData)
		{
			for($i=1; $i <= 8; $i++)
			{
				if(isset($phaseData["ped" . $i]))
					if(!in_array($i, $pedList))
						unset($graphPhaseData[$date][$time]["ped" . $i]);
					
				if(isset($phaseData["ped_hourly" . $i]))
					if(!in_array($i, $pedList))
						unset($graphPhaseData[$date][$time]["ped_hourly" . $i]);
			}
		}
	}

	return $graphPhaseData;
}

function getColumnMapping($lineArray)
{
	$columnMap = [];
	
	foreach($lineArray as $key => $val)
	{			
		if($val == "TIME")
		{
			$columnMap["time"] = $key;
		}			
		else if($val[0] == "V")
		{
			for($i=1; $i < 9; $i++)
				if($val  == "VEHICLE COUNT PHASE_" . $i)
					$columnMap["vc" . $i] = $key;
		}
		else if($val[0] == "S")
		{
			for($i=1; $i < 9; $i++)
				if($val  == "STOP_DELAY PHASE_" . $i)
					$columnMap["sd" . $i] = $key;
		}					
		else if($val[0] == "L")
		{
			for($i=1; $i < 9; $i++)
				if($val  == "LOS PHASE_" . $i)
					$columnMap["los" . $i] = $key;
		}			
		else if($val[0] == "E")
		{
			if($val  == "EXTENDED COUNT North Bound")
				$columnMap["ecNB"] = $key;
			else if($val  == "EXTENDED COUNT South Bound")
				$columnMap["ecSB"] = $key;
			else if($val  == "EXTENDED COUNT East Bound")
				$columnMap["ecEB"] = $key;
			else if($val  == "EXTENDED COUNT West Bound")
				$columnMap["ecWB"] = $key;
		}
		else if(str_starts_with($val, "Ph"))
		{
			for($i=1; $i < 9; $i++)
				for($j=1; $j < 10; $j++)
					if($val  == "Phase" . $i . "_" . $j)
						$columnMap["sub" . $i . "_" . $j] = $key;
		}				
		else if(str_starts_with($val, "PedC"))
		{
			for($i=1; $i < 9; $i++)
				if($val  == "PedCount_Ped_Phase_" . $i)
					$columnMap["pc" . $i] = $key;
		}			
		else if(str_starts_with($val, "PedW"))
		{
			for($i=1; $i < 9; $i++)
				if($val  == "PedWait_Ped_Phase_" . $i)
					$columnMap["pw" . $i] = $key;
		}
	}
								
	return $columnMap;
}

function createDateRange($startDate, $endDate, $outputFormat)
{    
	$startTimestamp = strtotime($startDate);
	$endTimestamp = strtotime($endDate) + 86400;
	
	$dateArray = [];
	
	for($date = $startTimestamp; $date <= $endTimestamp; $date += 86400)
		$dateArray[] = date($outputFormat, $date);
	
	return $dateArray;
}

/*
 * Gets a date range that should cover the start and end date
 * and at least 1 day prior to the start date.
 */
function createPeriodGraphDateRange($startDate, $endDate, $outputFormat)
{    
	$startTimestamp = strtotime($startDate) - 86400;
	$endTimestamp = strtotime($endDate);
	
	$dateArray = [];
	
	for($date = $startTimestamp; $date <= $endTimestamp; $date += 86400)
		$dateArray[] = date($outputFormat, $date);
	
	return $dateArray;
}

//
// The functions below are used as part of downloadRawLogs()
//Logic:
//Read the actual TMC files (TM_) in InSync\Statistics\TurningMovementCounts and create a temporary file.
//Time is modifed and updated in the new file. 
// - First time record (12:00 AM) is ignored
// - 12:15 AM is written as 12:00 AM, 12:30AM as 12:15AM etc.
// - 11:45 PM record is retrieved by reading first record of next day's file (12:00AM) if available. If not available, 11:30 PM would be the last record.
//Stop delay, Level of service and extended count columns are removed from the new files. 
// 
function createTempfile($turningFileTemp, $logFileVal, $nextDayVal, $additionalDay, $lastfileFlag)
{
	$newminutes = "";
	$newhours = 0;
	$tempFile = fopen($turningFileTemp, "w");

	//read file and adjust time and write to temp file
	$turningFileLong = TMC_STATS_ROOT . "/TM_" . date("Ymd", $logFileVal) . "_000000.txt";
	$handle = fopen($turningFileLong, "r");
	if ($handle)
	{
		$linecount = 0;
		while (($line = fgets($handle)) !== false)
		{
			if ($linecount != 2)
			{
				if ($linecount == 1)
					$line = removeColumnsinError($line);

				if ($linecount > 2)
				{
					$line = removeColumnsinError($line);
					$line = getModifiedLine($line);
				}
				fwrite($tempFile, $line);
			}
			else if ($linecount == 2)
			{
				$firstTimeRecord = substr($line, 0, strpos($line, ","));
				if ($firstTimeRecord != "12:00 AM")
				{
					$line = removeColumnsinError($line);
					$line = getModifiedLine($line);				
					fwrite($tempFile, $line);
				}
			}
			$linecount++;
		}
		
		fclose($handle);		

		//if there is another file to be read to update the last record, read it now and update file
		if ($nextDayVal <> "" && $lastfileFlag <> "Y")
		{
			$line = getlastRecord($nextDayVal);
			
			//first available time
			$firstTimeRecord = substr($line, 0, strpos($line, ","));
			if ($firstTimeRecord == "11:45 PM")
			{		
				$line = removeColumnsinError($line);
				fwrite($tempFile, $line);	
			}
		}
					
		//read the additional file if available and get the first time based record and update the tmporary file.
		if ($additionalDay <> "" && $lastfileFlag == "Y")
		{
			$line = getlastRecord($additionalDay);
			
			//first available time
			$firstTimeRecord = substr($line, 0, strpos($line, ","));
			if ($firstTimeRecord == "11:45 PM")
			{					
				$line = removeColumnsinError($line);
				fwrite($tempFile, $line);
			}
		}
		
	}
	else
	{
		die("Error: Unable to open file '" . $turningFileLong . "'.");
		exit;
	}

	fclose($tempFile);
}

//Modify the line read with new minutes and hours.
function getModifiedLine($line)
{
	$minutes = substr($line, strpos($line, ":")+1, 2);
	$hours = (int)substr($line, 0,  strpos($line, ":"));
	$amORpm = substr($line, strpos($line, ",")-2, 2);
							
	if ($minutes == "15")
	{
		$newminutes = "00";
	}
	else if ($minutes == "30")
	{
		$newminutes = "15";
	}
	else if ($minutes == "45")
	{
		$newminutes = "30";
	}						
	else if ($minutes  == "00")
	{
		$newminutes = "45";
		
		if ($hours == 1)
		{
			$newhours = 12;
			//replace hour with 12
			$line = substr_replace($line, (string)$newhours, 0, strlen((string)$hours));
		}
		else
		{
			$newhours = $hours - 1;
			$line = substr_replace($line, (string)$newhours, 0, strlen((string)$hours));		
			if ($hours == 12 && $amORpm == "AM")
			{
				$line = substr_replace($line, "PM", strpos($line, $amORpm), 2);
			}
			else if ($hours == 12 && $amORpm == "PM")
			{
				$line = substr_replace($line, "AM", strpos($line, $amORpm), 2);
			}
		}
	}
	$line = substr_replace($line, $newminutes, strpos($line, ":") +1, 2);

	return $line;
}

function removeColumnsinError($line)
{
	$originallineArray = [];
	$modifiedlineArray = [];
	$modifiedline = "";
	$arraycolCount = 0;

	$originallineArray = explode(",", $line);
	foreach ($originallineArray as $dataColumn)
	{	
		if ($arraycolCount < 9 || $arraycolCount > 28)
		{
			$modifiedlineArray[] = $dataColumn;
		}
		$arraycolCount++;
	}
	
	$modifiedline = implode(",", $modifiedlineArray);
	$modifiedline = trim($modifiedline). PHP_EOL;
	return $modifiedline;
}

function getlastRecord($additionalDay)
{
	//read file and adjust time and write to temp file
	$turningFileLong = TMC_STATS_ROOT . "/TM_" . date("Ymd", $additionalDay) . "_000000.txt";
	$handle1 = fopen($turningFileLong, "r");
	if ($handle1)
	{
		$count1 = 0;
		while (($line = fgets($handle1)) !== false)
		{
			if ($count1 == 2)
			{
				$line = getModifiedLine($line);
				break;
			}
			$count1++;
		}
		fclose($handle1);
		return $line;
	}
	else
	{
		die("Error: File not found '" . $turningFileLong . "'.");
		exit;
	}
}

// Return a list of phases with cameras for systems using hawkeye detection
// This is used show data only for phases with cameras
function getActiveCameraPhases()
{
	$phasesWithCamera = [];
	$systemConfigurationType = getSystemType();
	
	if ($systemConfigurationType === 0 || $systemConfigurationType === "" )
	{
		return [];
	}
	else
	{
		$phasesWithCamera = getPhasesWithCamera();
		sort($phasesWithCamera, SORT_NUMERIC);
		return $phasesWithCamera;
	}
}

?>

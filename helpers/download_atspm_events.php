<?php

require_once("pathDefinitions.php");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

switch($action)
{
	/**
	 * Download Atsmp data from InSync (C:\InSync\Statistics\AtspmData)
	 */
	case "downloadatspmdata":
	{
		$startDateTime = "";
		if(isset($_REQUEST['startDateTime']))
			$startDateTime = $_REQUEST['startDateTime'];
		
		$endDateTime = "";
		if(isset($_REQUEST['endDateTime']))
			$endDateTime = $_REQUEST['endDateTime'];
		
		if($startDateTime == "" || $endDateTime == "")
			die("Error: Start or End dates are missing");
		
		// Commenting it to allow users to enter any date range
		// get the differnce between dates in seconds
		//$differenceInSeconds = dateDifference($endDateTime,$startDateTime);
		
		// if the difference is greater than 10 days
		//if ($differenceInSeconds > 864000)
		//{
		//	$json = json_encode(array("Error: Difference between start and end dates cannot be more than 10 days."));
		//	header("Content-type: application/json");
		//	header("Content-Disposition: attachment; filename=ErrorFile.json");
		//	echo $json;
		//	exit;
		//}
		
		// download .csv and .zip files if they fall within the start and end dates.
		// create a .zip file with the selected files. 
		downloadDataFiles($startDateTime, $endDateTime);		
	}
	break;	
	
	/**
	 * Download lane detector configuration json file (C:\InSync\Config\lane_detector_configuration.json)
	 */	
	case "downloadconfigdata":
	{
		if (file_exists(LANE_DETECTOR_CONFIG))
		{
			$fileName = basename(LANE_DETECTOR_CONFIG);
			header("Content-type: text/json");
			header("Content-Disposition: attachment; filename=$fileName");
			header("Pragma: no-cache");
			header("Expires: 0");
			
			ob_clean();
			readfile(LANE_DETECTOR_CONFIG);
		}
		else
		{
			$json = json_encode(["Error: Lane detector configuration json file not found."]);
			header("Content-type: application/json");
			header("Content-Disposition: attachment; filename=ErrorFile.json");
			echo $json;

			//die("<h3>Error: File not found - C:\InSync\Config\lane_detector_configuration.json. Contact Rhythm Engineering</h3>");
		}
	}
	break;

}

function downloadDataFiles($startDateTime, $endDateTime)
{
	$unixDateStart = strtotime($startDateTime);				
	$unixDateEnd = strtotime($endDateTime);

	$convertedStartDate = date("Ymd_His", $unixDateStart);
	$convertedEndDate = date("Ymd_His", $unixDateEnd);

	$atspmFilesTemp = scandir(ATSPM_DATA_ROOT);
	$atspmFiles = [];
	
	foreach($atspmFilesTemp as $file)
	{
		if(str_starts_with($file, "atspm_events_"))
		{
			$fileDateTime = substr($file,13,15);
			
			if($fileDateTime >= $convertedStartDate && $fileDateTime <= $convertedEndDate)
			{
				$atspmFiles[] = $file;
			}
		}
	}

	if(count($atspmFiles) < 1)
	{
		$json = json_encode(["Error: No Atspm csv files found for this date range."]);
		header("Content-type: application/json");
		header("Content-Disposition: attachment; filename=ErrorFile.json");
		echo $json;						
		//echo "<h3>No Atspm files are present in this date range.</h3>";
		exit;
	}
	
	$zipFileName = "ATSPM_Events_" . date(\YMD) . "_" . date(\HIS) . ".zip";	// name given to the zip file
	$zip = new ZipArchive;
	$randFilename = ATSPM_DATA_ROOT . "/" .uniqid() . ".zip";	

	if ($zip->open($randFilename, ZipArchive::OVERWRITE) === TRUE) 
	{
		foreach($atspmFiles as $file)
		{
			$newName = substr($file,0,32);
			$pathToFile = ATSPM_DATA_ROOT . "/" . $file;	// path to file
			$zip->addFile($pathToFile, $newName);	  	    // path to file and new name to be given
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
	//header("Content-Disposition: attachment; filename=\"ATSPM_Events.zip\"");
	header("Content-Disposition: attachment; filename=\"$zipFileName\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . filesize($randFilename));
	ob_clean();
	flush();
	
	readfile($randFilename);
	@unlink($randFilename);
	
}

function dateDifference($endDateTime, $startDateTime)
{
	$start = strtotime($startDateTime);				
	$end = strtotime($endDateTime);

	// date difference in seconds
	return $end-$start;
}

?>

<?php

// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

if(empty($permissions["maintenance"]))
	die("Error: You do not have permission to access this page.");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

require_once("pathDefinitions.php");

switch($action)
{
    /**
	 * Network Utilities
	 */
	case "networkutil":
	{
        $cmd = "";
        if(isset($_REQUEST['cmd']))
            $cmd = $_REQUEST['cmd'];
        
        if($cmd == "")
            exit;
        
        if(!str_starts_with($cmd, "ping ") && !str_starts_with($cmd, "arp "))
            if($cmd != "ping" && $cmd != "arp")
                die("Invalid Command!<br />");
            
        $descriptorspec = [1 => ["pipe", "w"], 2 => ["pipe", "w"]];

        $process = proc_open(escapeshellcmd($cmd), $descriptorspec, $pipes, NULL, NULL, ["bypass_shell"=>TRUE]);
        
        $emptyarr = [];
        $start = microtime(true);
        
        if (is_resource($process)) 
        { 
            $replace = ["\r\n", "\n"];
            
            while(!feof($pipes[1]) && microtime(true) - $start < 30)
            {
                $subject = fgets($pipes[1]);
                $subject = str_replace(" ", "&nbsp;", $subject);
                $subject = str_replace($replace, "<br />", $subject);
                echo $subject;
            }

            while(!feof($pipes[2]) && microtime(true) - $start < 30)
            {
                $subject = fgets($pipes[2]);
                $subject = str_replace(" ", "&nbsp;", $subject);
                $subject = str_replace($replace, "<br />", $subject);
                echo $subject;
            }

            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $status = proc_get_status($process);
            if(!$status["running"])
                proc_close($process);
            else
            {
                proc_terminate($process);
                echo "<br />Maximum execution time reached.<br />";
            }
        }
        else
        {
            echo "<br />Unable to open process!<br />";
        }
	}
	break;

    /**
	 * Restarts processor via ApplicationMonitor
	 */
	case "restartpc":
	{
		$WshShell = new COM("WScript.Shell"); 
        
        $AppMonEXE = APP_MON_EXE;

        try
        {
            $WshShell->Run("$AppMonEXE /system_restart", 1, false); 
        }
        catch(Exception)
        {
            die("Error: Could not execute ApplicationMonitor /system_restart");
        }
        die("Restarting processor, the webserver will be unavailable until the restart is complete.");
	}
	break;

    /**
	 * Restarts kiosk process
	 */
	case "restartkiosk":
	{
		$WshShell = new COM("WScript.Shell"); 
        
        $AppMonEXE = APP_MON_EXE;

        try
        {
            $WshShell->Run("$AppMonEXE /killkiosk", 1, true); 
        }
        catch(Exception)
        {
            die("Could not execute ApplicationMonitor /killkiosk");
        }

        die("Success");
	}
	break;
    
    /**
	 * Clears historical information from processor
	 */
	case "clearproc":
	{
		$WshShell = new COM("WScript.Shell"); 
        
        $InSync = INSYNC_EXE;

        try
        {
            $WshShell->Run("$InSync /clearstorage", 1, true); 
        }
        catch(Exception)
        {
            die("Could not execute InSync /clearstorage");
        }

        die("Success");
	}
	break;
    
    /**
	 * Enable remote desktop access
	 */	
	case "enableRdp":
	{
	$WshShell = new COM("WScript.Shell");
	$EnableRdp = ENABLE_RDP;
	try
	{
		$WshShell->Run($EnableRdp);
	}
	catch(Exception)
	{
		die("Could not enable RDP using $EnableRdp ");
	}
	die("Success");		
	}
	break;
	
    /**
	 * Disable remote desktop access
	 */		
	case "disableRdp":
	{
	$WshShell = new COM("WScript.Shell");
	$DisableRdp = DISABLE_RDP;
	$WriteFilterExe = WRITE_FILTER_EXE;	
	try
	{
		$WshShell->Run($DisableRdp);
		$WshShell->Run("$WriteFilterExe -commit_hklm_system_hive_to_disk");		
	}
	catch(Exception)
	{
		die("Could not disable RDP using $DisableRdp ");
	}
	die("Success");		
	}
	break;	
	
	/**
	 * Restores an archive from a file
	 */
	case "restore":
	{
		$file = "";
		if(isset($_REQUEST['file']))
			$file = $_REQUEST['file'];

		if(strlen($file) == 0)
			die("Error: No file specified.");
        
        $video = false;
		if(isset($_REQUEST['video']))
			if($_REQUEST['video'] == "true")
                $video = true;
        
		restoreArchive($file, $video);
	}
	break;
    
    /**
	 * Restores an archive from a file
	 */
	case "getarchivelist":
	{
		$drive = "";
		if(isset($_REQUEST['drive']))
			$drive = $_REQUEST['drive'];

		if(strlen($drive) == 0)
			die("Error: No drive specified.");

		echo json_encode(getArchiveList());
	}
	break;
	
	/**
	 * Retrieves a list of archive file contents
	 */
	case "view":
	{
		$file = "";
		if(isset($_REQUEST['file']))
			$file = $_REQUEST['file'];

		if(strlen($file) == 0)
			die("Error: No file specified.");

		getArchiveFileList($file);
	}
	break;

	/**
	 * Creates a new archive
	 */
	case "archive":
	{
        ini_set('memory_limit','1024M');
        
        require_once("databaseInterface.php");
		require_once("pathDefinitions.php");
	
		$download = "";
		if(isset($_REQUEST['download']))
			$download = $_REQUEST['download'];

		$target = "C";
		if(isset($_REQUEST['target']))
			$target = $_REQUEST['target'];
		
		if(!is_dir("$target:/InSync/Conf/Archives/InSync/"))
        {
            if(!file_exists("$target:/InSync"))
				if(!@mkdir ("$target:/InSync"))
                    die("Error: Could not create archive directory!");
			
			if(!file_exists("$target:/InSync/Conf"))
				if(!@mkdir ("$target:/InSync/Conf"))
                    die("Error: Could not create archive directory!");
			
			if(!file_exists("$target:/InSync/Conf/Archives"))
				if(!@mkdir ("$target:/InSync/Conf/Archives"))
                    die("Error: Could not create archive directory!");
			
			if(!file_exists("$target:/InSync/Conf/Archives/InSync"))
				if(!@mkdir ("$target:/InSync/Conf/Archives/InSync"))
                    die("Error: Could not create archive directory!");
        }
        
        $intersection = getFile("Intersection.xml");
        $intersectionXML = @simplexml_load_string($intersection);
        
        if($intersectionXML === FALSE)
            die("Error");
        
        $name = "";
        if(isset($intersectionXML->Intersection["name"]))
            $name = (string)$intersectionXML->Intersection["name"];
        
        $name = str_replace(" ", "_", $name);
        $name = preg_replace('/[^0-9a-z\_\-]/i','', $name);
        
        if(strlen($name) != 0)
            $name .= ".";

		if($target == "C")
			cleanArchives();  //No curly braces, just for josh.

		$target .= ":/InSync/Conf/Archives/InSync/" . $name . date("Ymd_His") . ".insync";

		$logs = false;
		if(isset($_REQUEST['logs']))
		{
			$logs = $_REQUEST['logs'];
			if($logs == "true" || $logs == "TRUE")
				$logs = true;
			else
				$logs = false;
		}

		if(archiveFiles($target, $download, $logs))
			die("Success: Saved as $target");
		else
			die("Error");
	}
	break;
	
	case "putfile":
	{
		putArchiveFile($_FILES['file']['tmp_name']);
	}
	break;
}



function cleanArchives()
{
    $archivefilelist = glob(INSYNC_CONF_ARCHIVE_ROOT . '/*.insync');

    //Maintain at least 20 archives in the archive
    if(is_array($archivefilelist) && count($archivefilelist) > 20)
    {
    	$creationtimes = [];
        foreach($archivefilelist as $filename)
        {
            $creationtimes[$filename] = @filemtime($filename);
        }
        asort($creationtimes, SORT_NUMERIC);

        $deletionlist = [];

	    foreach ($creationtimes as $name => $date)
        {
            if(count($deletionlist) < count($creationtimes) - 20)
            {
				//Delete oldest files beyond 5 count.
                $deletionlist[$name] = $date;
            }
	    }

	    foreach ($deletionlist as $name => $date)
        {
            //Delete the archive
            @unlink($name);
	    }
    }    
}

/**
 * Handles uploading of files
 * @param type $tmp_name tmp_name from POST upload
 */
function putArchiveFile($tmp_name)
{
	$hash = md5_file($tmp_name);
    
    $archiveName = TEMP_ROOT . "/$hash.insync";
	
	move_uploaded_file($tmp_name, $archiveName);
    
    $archive = gzopen($archiveName, "rb");
		
    if($archive === FALSE)
        die("Error: Invalid restoration file.");

    $contents = '';
    while (!gzeof($archive))
        $contents .= gzread($archive, 8192);

    gzclose($archive);

    $xmlDoc = @simplexml_load_string($contents);

    if($xmlDoc === FALSE)
        die("Error: Invalid restoration file.");
    
    if($xmlDoc->getName() != "Archive")
        die("Error: Invalid restoration file.");
	
	// let user know what the file hash is for status tracking
	die("Success: " . TEMP_ROOT . "/$hash.insync");
}

/**
 * Retrieves a list of archive file contents
 * @param type $file Full path to file
 */
function getArchiveFileList($file)
{
	if(file_exists($file))
	{
		$archive = gzopen($file, "rb");
		
		if($archive === FALSE)
			die("Error: Cannot open file.");
		
		$contents = '';
		while (!gzeof($archive))
			$contents .= gzread($archive, 8192);
		
		gzclose($archive);
		
		$xmlDoc = simplexml_load_string($contents);
		
		if($xmlDoc === FALSE)
			die("Error: Invalid restoration file.");
		
		foreach($xmlDoc->DatabaseEntry as $dbFile)
		{
			echo $dbFile["name"] . "<br />";
		}
		
		foreach($xmlDoc->File as $diskFile)
		{
			echo $diskFile["name"] . "<br />";
		}
		
		foreach($xmlDoc->StatisticFile as $statFile)
		{
			echo $statFile["name"] . "<br />";
		}
	}
	else
		die("Error: Invalid file specified.");
}

/**
 * Restores an archive from a file
 * @param type $file Full file path
 * @param bool $video true to restore only video information
 */
function restoreArchive($file, $video)
{
	if(file_exists($file))
	{
		ini_set('memory_limit','400M');
		
		require_once("pathDefinitions.php");
		require_once("databaseInterface.php");
        require_once("webdb.php");
		
		$archive = gzopen($file, "rb");
		
		if($archive === FALSE)
			die("Error: Cannot open file.");
		
		$contents = '';
		while (!gzeof($archive))
			$contents .= gzread($archive, 8192);
		
		gzclose($archive);
		
		$xmlDoc = @simplexml_load_string($contents);
		
		if($xmlDoc === FALSE)
			die("Error: Invalid restoration file.");
        
        $systemVersion = @file_get_contents("../includes/version.txt");
        if($systemVersion === FALSE)
            die("Error: Cannot get system version.");
        
        if(!isset($xmlDoc["version"]))
            die("Error: Cannot get archive version.");
        
        $xmlVersion = (string)$xmlDoc["version"];
        
        $systemParts = explode(".", $systemVersion);
        $xmlParts = explode(".", $xmlVersion);

        if($xmlParts[0] > $systemParts[0])
            die("Error: Restore file is for a newer version of InSync. (Archive: $xmlVersion, Processor: $systemVersion)");
        if($xmlParts[0] == $systemParts[0])
        {
            if($xmlParts[1] > $systemParts[1])
                die("Error: Restore file is for a newer version of InSync. (Archive: $xmlVersion, Processor: $systemVersion)");
            else if($xmlParts[1] == $systemParts[1])
            {
                if($xmlParts[2] > $systemParts[2])
                    die("Error: Restore file is for a newer version of InSync. (Archive: $xmlVersion, Processor: $systemVersion)");
                else if($xmlParts[2] == $systemParts[2])
                {
                    if($xmlParts[3] > $systemParts[3])
                        die("Error: Restore file is for a newer version of InSync. (Archive: $xmlVersion, Processor: $systemVersion)");
                }
            }
        }
		
		$error = "";
        $WshShell = new COM("WScript.Shell"); 
        
        if($video)
        {
            if(!isset($xmlDoc->VideoProcessor))
                die("No video processor configuration found in this restoration file.");
            
            $networkSettings = base64_decode((string)$xmlDoc->VideoProcessor);
            
            if(!@file_put_contents(NETWORK_SETTINGS_CONF_FILE, $networkSettings))
                die("Unable to write Network Settings file to disk.");
            
            $manageIPConfigEXE = MANAGE_IP_CONF_EXE;
        
            try
            {
                $WshShell->Run("$manageIPConfigEXE -restore", 1, true); 
            }
            catch(Exception)
            {
                $error = "Could not execute ManageIPConfig -restore";
                die("Error:<br />" . $error);
            }
            
            die("Success");
        }
        
        $appMonitorEXE = APP_MON_EXE;
        
        try
        {
            $WshShell->Run("$appMonitorEXE /restore", 1, true); 
        }
        catch(Exception $e)
        {
            $error = "Could not execute AppMonitor /restore";
            die("Error:<br />" . $error);
        }
		
		foreach($xmlDoc->DatabaseEntry as $dbFile)
			putFileFromString($dbFile["name"], base64_decode($dbFile));
		
		foreach($xmlDoc->File as $diskFile)
		{
			if(constant($diskFile["name"]) == NULL)
			{
				$error .= "No definition in Paths for " . $diskFile["name"] . "<br />";
				continue;
			}
			
			if(@file_put_contents(constant($diskFile["name"]), base64_decode($diskFile)) === FALSE)
				$error .= "Could not write " . constant($diskFile["name"]) . "<br />";
		}
		
		foreach($xmlDoc->StatisticFile as $statFile)
		{
			if(constant($statFile["path"]) == NULL)
			{
				$error .= "No definition in Paths for " . $statFile["path"] . "<br />";
				continue;
			}
			
			if(!is_dir(constant($statFile["path"])))
			{
				if(!mkdir(constant($statFile["path"])))
				{
					$error .= constant($statFile["path"]) . " did not exist, and could not be created. <br />";
					continue;
				}
			}
			
			if(@file_put_contents(constant($statFile["path"]) . "/" . $statFile["name"], base64_decode($statFile)) === FALSE)
				$error .= "Could not write " . constant($statFile["path"]) . "/" . $statFile["name"] . "<br />";
		}
        
        
        // store rename phases info
        $db = openWebDB();

        if ($db !== FALSE) 
        {    
            pg_query($db, "BEGIN TRANSACTION");
            
            $dbError = false;
            
            if(!pg_query($db, "DELETE FROM phase_renaming"))
		$dbError = true;
            
            foreach($xmlDoc->PhaseRenaming->Phase as $phase)
            {
                if($dbError)
                    break;

                if (!pg_query_params($db, 'INSERT INTO phase_renaming ("user", phase_number, short, long) values ($1, $2, $3, $4)', 
                        [base64_decode((string)$phase["user"]), (string)$phase["number"], base64_decode((string)$phase["short"]), base64_decode((string)$phase["long"])]))
                {
                    pg_query($db, "ROLLBACK TRANSACTION");
                    pg_close($db);
                    $dbError = true;
                }
            }
            
            if(!$dbError)
            {
                pg_query($db, "COMMIT TRANSACTION");
                pg_close($db);
            }
        }
        
        $manageIPConfigEXE = MANAGE_IP_CONF_EXE;
        
        try
        {
            $WshShell->Run("$manageIPConfigEXE -restore", 1, false); 
        }
        catch(Exception)
        {
            $error = "Could not execute ManageIPConfig -restore";
            die("Error:<br />" . $error);
        }
        
        try
        {
            $WshShell->Run("$appMonitorEXE /update", 1, false); 
        }
        catch(Exception)
        {
            $error = "Could not execute AppMonitor /update";
        }
		
		if(strlen($error) != 0)
			die("Error:<br />" . $error);
		else
			die("Success");
	}
	else
		die("Error: Invalid file specified.");
}

/**
 * Creates a new archive
 * @param string $target Full path of file to save to
 * @param boolean $download Force download of file
 * @param boolean $logs Include statistic files
 */
function archiveFiles($target, $download, $logs)
{
	require_once("pathDefinitions.php");
	require_once("databaseInterface.php");
    require_once("webdb.php");
    
    $version = @file_get_contents("../includes/version.txt");
    
    if($version === FALSE)
        return false;
	
	$xml = '<Archive version="' . trim($version) . '">';
	
	$IntersectionXML = getFile("Intersection.xml");
	if(!str_starts_with($IntersectionXML, "Error"))
		$xml .= '<DatabaseEntry name="Intersection.xml">' . base64_encode($IntersectionXML) . "</DatabaseEntry>\r\n";
	
	$intersection_diagramXML = getFile("intersection_diagram.xml");
	if(!str_starts_with($intersection_diagramXML, "Error"))
		$xml .= '<DatabaseEntry name="intersection_diagram.xml">' . base64_encode($intersection_diagramXML) . "</DatabaseEntry>\r\n";
	
	$intersection_backgroundPNG = getFile("intersection_background.png");
	if(!str_starts_with($intersection_backgroundPNG, "Error"))
		$xml .= '<DatabaseEntry name="intersection_background.png">' . base64_encode($intersection_backgroundPNG) . "</DatabaseEntry>\r\n";
    
    $pathsXML = @simplexml_load_file(PATHS_CONF_FILE);
    if($pathsXML !== FALSE)
    {
        foreach($pathsXML->Path as $path)
        {
            if(isset($path["Archive"]) && $path["Archive"] == "true")
            {
                $contents = @file_get_contents($path["Location"]);
                if($contents !== FALSE)
                    $xml .= '<File name="' . $path["Name"] . '">' . base64_encode($contents) . "</File>\r\n";
            }
        }
    }
    
    $intersectionDOM = @simplexml_load_string($IntersectionXML);
    if($intersectionDOM !== FALSE)
    {
        foreach($intersectionDOM->Intersection->Direction->DetectionDevices->VideoDetectionDevice as $vdd)
        {
            if(isset($vdd["machine"]) && (string)$vdd["machine"] != ".")
            {
                $networkSettings = @file_get_contents(NETWORK_SETTINGS_CONF_FILE);
                $networkXML = @simplexml_load_string($networkSettings);
                
                if($networkXML !== FALSE)
                {
                    $networkXML->IPAddress = (string)$vdd["machine"];                    
                    $xml .= '<VideoProcessor>' . base64_encode($networkXML->asXML()) . '</VideoProcessor>';
                }
            }
        }
    }
    
    
    // store rename phases info
    $db = openWebDB();

    if ($db !== FALSE) 
    {    
        if($result = pg_query($db, 'SELECT * from phase_renaming'))
        {
            $xml .= "<PhaseRenaming>";
            while ($row = pg_fetch_assoc($result))
                $xml .= '<Phase number="' . $row["phase_number"] . '" user="' . base64_encode($row["user"]) . '" short="' . base64_encode($row["short"]) . '" long="' . base64_encode($row["long"]) . '"/>';
            $xml .= "</PhaseRenaming>";
        }

	pg_close($db);
    }
    
	
	if($logs)
	{
		$logDirs = [GREEN_SPLITS_STATS_ROOT, HISTORY_STATS_ROOT, MISC_STATS_ROOT, PERIOD_STATS_ROOT, RED_SPLITS_STATS_ROOT, SUBPHASE_STATS_ROOT, TMC_STATS_ROOT];
		
		$constants = ["GREEN_SPLITS_STATS_ROOT", "HISTORY_STATS_ROOT", "MISC_STATS_ROOT", "PERIOD_STATS_ROOT", "RED_SPLITS_STATS_ROOT", "SUBPHASE_STATS_ROOT", "TMC_STATS_ROOT"];
		
		for($i = 0; $i < count($logDirs); $i++)
		{
			$logDir = $logDirs[$i];
			
			$dirHandle = @opendir($logDir);
			
			if($dirHandle == FALSE)
				continue;
			
			while (($file = readdir($dirHandle)) !== false)
			{
				if($file == "." || $file == "..")
					continue;
				
				$file = $logDir . "/" . $file;
				
				$contents = @file_get_contents($file);
				
				$pathInfo = pathinfo($file);
				
				if($contents !== FALSE)
					$xml .= '<StatisticFile path="' . $constants[$i] . '" name="'. $pathInfo["filename"] . "." . $pathInfo["extension"] .'">' . base64_encode($contents) . "</StatisticFile>\r\n";
			}
			closedir($dirHandle);
		}
	}
	
	$xml .= "</Archive>";
	
	if($download == "true")
	{
		$parts = pathinfo($target);

		$compressed = gzencode($xml);
		
		@ob_end_clean();

		
		header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");		
		header("Content-Length: " . strlen($compressed));
		header('Content-Disposition: attachment; filename="' . $parts["filename"] . '.insync"');
		
		if(ini_get('zlib.output_compression'))
			ini_set('zlib.output_compression', 'Off');
		
		echo $compressed;
		exit;
	}
	else
	{
		$file = @gzopen($target, "wb");

		if($file === FALSE)
			die("Error: Could not save file.");

		gzwrite($file, $xml);
		gzclose($file);

		return true;
	}
}

/**
 * Gets the list of archive on a drive
 * @return array of archives
 */
function getArchiveList($target)
{   
    $thumbRestoreFiles = [];
    
	if (file_exists("$target:/InSync/Conf/Archives/InSync")) 
    {
        $dir = opendir("$target:/InSync/Conf/Archives/InSync");

        while (($filename = readdir($dir)) !== false) 
            if ($filename != '.' && $filename != '..') 
                if (strpos($filename, ".insync"))
                    $thumbRestoreFiles[] = $filename;

        closedir($dir);
    }
    
    $fileList = [];
    $count = 0;
    
    foreach($thumbRestoreFiles as $file)
    {
        $seperatorPos = strpos($file, ".");
        
        if($seperatorPos !== FALSE)
            $file = substr($file, $seperatorPos);
        
        $datetime = substr($file, 4, 2) . "/" . substr($file, 6, 2) . "/" . substr($file, 0, 4) . " " . substr($file, 9, 2) . ":" . substr($file, 11, 2);
        $fileList[$count]["name"] = $datetime;
        $fileList[$count]["path"] = "$target:/InSync/Conf/Archives/InSync/$file";
        $count++;
    }      
    
    return $fileList;
}

/**
 * Gets the drive letters of any inserted restore drives
 * @return array array of drive  letters
 */
function getRestoreDrives()
{
	$driveArr = [];
	
	$fso = new COM('Scripting.FileSystemObject');
	$D = $fso->Drives;
	foreach ($D as $d) 
	{
        if(!$d->IsReady)
            continue;
        
		$dO = $fso->GetDrive($d);
		
		if(strcasecmp($dO->DriveLetter, "c") != 0)
			$driveArr[] = $dO->DriveLetter;
	}
	
	return $driveArr;
}

/**
 * Gets the drive letter of any inserted restore drive
 * @return string the drive letter of any inserted restore drive
 */
function getRestoreDrive()
{
	$driveArr = [];
	
	$fso = new COM('Scripting.FileSystemObject');
	$D = $fso->Drives;
	foreach ($D as $d) 
	{
        if(!$d->IsReady)
            continue;
        
		$dO = $fso->GetDrive($d);
		
		if(strcasecmp($dO->DriveLetter, "c") != 0)
			$driveArr[] = $dO->DriveLetter;
	}
	
	return $driveArr[0];
}
?>

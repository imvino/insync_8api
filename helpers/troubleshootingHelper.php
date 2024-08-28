<?php
use Unirest\Request;
require_once("pathDefinitions.php");
session_start();
if(!isset($_SESSION["username"]) || ($_SESSION["username"] != "PEC" && $_SESSION["username"] != "kiosk" && $_SESSION["username"] != "ADMIN"))
{
   session_write_close();
   die("This page is for Rhythm Engineering use only.");
}
session_write_close();

function GetIntersectionXML()
{
   require_once("databaseInterface.php");
   $intersection = getFile("Intersection.xml");
   $intersectionXML = @simplexml_load_string($intersection);

   if($intersectionXML === FALSE)
      die('{"error":"Could not load configuration"}');
  
  return $intersectionXML;
}

$action = "";
if(isset($_REQUEST["action"]))
   $action = $_REQUEST["action"];

$startDate = "";
if(isset($_REQUEST["startDate"]))
   $startDate = $_REQUEST["startDate"];

$endDate = "";
if(isset($_REQUEST["endDate"]))
   $endDate = $_REQUEST["endDate"];

require_once("pathDefinitions.php");
if($action == "gettestips")
{
   require_once("networkHelper.php");
   require_once("databaseInterface.php");

   $intersections = GetAllCorridorIPs(true);

   if(count($intersections) == 0)
      die('{"error":"No intersections found in this Management Group."}');

   $testData["intersections"] = $intersections;

   $intersection = getFile("Intersection.xml");
   $intersectionXML = @simplexml_load_string($intersection);

   if($intersectionXML === FALSE)
      die('{"error":"Could not load configuration"}');

   $cameras = [];

   foreach($intersectionXML->VideoStreamSettings->VideoStream as $stream)
   {
      $name = parse_url((string)$stream["Name"], PHP_URL_HOST);
      $friendlyName = (string)$stream["FriendlyName"];

      if (!empty($name))
	  {
		$cameras[$friendlyName] = $name;
	  }
   }

   $testData["cameras"] = $cameras;

   $contextcameras = [];
   foreach($intersectionXML->VideoStreamSettings->VideoStream as $stream)
   {
	   $url = parse_url((string)$stream["URL"], PHP_URL_HOST);
	   $name = (string)$stream["Name"];
	   $cameraType = (string)$stream["CameraType"];
	   
	   if (!empty($url) && $cameraType==="Context Camera")
	   {
		$contextcameras["$name"] = $url;
	   }
   }
   $testData["contextcameras"] = $contextcameras;

   $cyclopsdetector = [];
   foreach($intersectionXML->VideoStreamSettings->VideoStream as $stream)
   {
	   $url = parse_url((string)$stream["URL"], PHP_URL_HOST);
	   $cyclops = "Cyclops Device";
	   $cameraType = (string)$stream["CameraType"];
	   
	   if ($cameraType==="Multiview Context Camera")
	   {
			$cyclopsdetector[$cyclops] = $url;
	   }
   }
   $testData["cyclopsdetector"] = $cyclopsdetector;   

   $testData["ntp"] = (string)$intersectionXML->NTP["IP"];

   $emailSettings = @file_get_contents(EMAIL_NOTIFICATION_SETTINGS_CONF_FILE);

   if($emailSettings !== FALSE)
   {
      $emailXML = @simplexml_load_string($emailSettings);

      if($emailXML !== FALSE)
         if(isset($emailXML->EnableEmail) && (string)$emailXML->EnableEmail == "True")
            if(isset($emailXML->SMTPServer) && (string)$emailXML->SMTPServer != "")
               $testData["smtp"] = (string)$emailXML->SMTPServer . ":" . (string)$emailXML->SMTPPort;
   }

   die(json_encode($testData));
}

if ($action == "testntp")
{
   require_once("ntpHelper.php");

   if(testNTP($_REQUEST["ip"]) == FALSE)
      die("FAILED");
   else
      die("PASSED");
}

if ($action == "testsmtp")
{
   $fp = @fsockopen($_REQUEST["ip"], -1, $errno, $errstr, 30);

   if (!$fp)
      die ("Could not connect");
   else
   {
      $return = "";

      while (!feof($fp))
      {
         $return .= fgets($fp, 128);

         if(strlen($return) >= 3)
            break;
      }

      fclose($fp);

      if(str_starts_with($return, "220"))
         die("PASSED");
      else
         die("FAILED");
   }
}

if ($action == "testcyclops")
{
   $fp = @fsockopen($_REQUEST["ip"], 9090, $errno, $errstr, 30);

   if (!$fp)
      die ("Could not connect");
   else
   {
      $return = "";

      while (!feof($fp))
      {
         $return .= fgets($fp, 128);

         if(strlen($return) >= 3)
            break;
      }

      fclose($fp);

	  $pos = strpos($return, "OK");  
	  if($pos >= 0)
		  die("PASSED");
	  else
		  die("FAILED");
   }
}

if ($action == "testcamera")
{

	// Using this for Bosch Camera
	function ReadCameraImageNew($cameraURL, $username, $password)
	{
		require_once("constants.php");
		require_once("getcameralist.php");
		require_once(SITE_DOCUMENT_ROOT . "helpers/httpclient/Unirest.php");

		Request::auth($username, $password, CURLAUTH_DIGEST);
		$response = Request::get($cameraURL, $headers, $query);    
	
		if (($response->code / 100) > 2)
		{
			return "No image found.";
		}
	
		return $response->body;		
	}	

   function ReadCameraImage($cameraURL, $username, $password)
   {
      require_once("constants.php");
      require_once("getcameralist.php");
      require_once("httpclient/http.php");

      require_once("httpclient/sasl.php");
      require_once("httpclient/digest_sasl_client.php");

      $http = new http_class;
      $http->user = $username;

      $http->password = $password;
      $http->GetRequestArguments($cameraURL, $arguments);
      $http_error = $http->Open($arguments);

      if ($http_error == "")
      {

         $http_error = $http->SendRequest($arguments);

         if ($http_error == "")
         {
            $body = "";
            $http_error = $http->ReadWholeReplyBody($body);


            if (($http->response_status / 100) > 2)
            {
               $http->Close();
               return $http->response_status;
            }


            if ($http_error == "")
            {
               $http->Close();
               return $body;
            }

         }
      }

      $http->Close();

      return $http_error;
   }
   function IsValidJPG($data)
   {
      $size_info = getimagesizefromstring($data);
      $width = $size_info[0];
      $height = $size_info[1];
      $type = $size_info[2];

      if($type == IMAGETYPE_JPEG)
      {
         return TRUE;
      }
      return FALSE;
   }

   $cameraIP = $_REQUEST["ip"];
   
   $intersectionXML = GetIntersectionXML();

   $cameraHTTPPort = 80;
   foreach($intersectionXML->VideoStreamSettings->VideoStream as $stream)
   {
      // Name attribute is the URL
      $name = parse_url((string)$stream["Name"], PHP_URL_HOST);
      $httpPort = $stream["CameraHTTPPort"];
      if (!empty($name) && !empty($httpPort))
	  {
		if ($name == $cameraIP)
        {
            $cameraHTTPPort = $httpPort;
        }
	  }
   }
   
   // Bosch Camera
   $cameraURL = "http://$cameraIP:$cameraHTTPPort/snap.jpg?JpegSize=320x240";		
   $body = ReadCameraImageNew($cameraURL, "service", "TJg7\$dax\$bnU");	   
   if (imagecreatefromstring($body) != FALSE)
   {
	   die("PASSED, found Bosch camera");
   }   

   // AXIS 221 camera
   $cameraURL = "http://$cameraIP:$cameraHTTPPort/jpg/image.jpg?resolution=320x240&compression=75";   
   $body = ReadCameraImage($cameraURL, "admin", "travis12");
   
   if ($body == "404" || $body == "401")
   {
		// samsung xnz (L6320)
		$cameraURL = "http://$cameraIP:$cameraHTTPPort/stw-cgi/video.cgi?msubmenu=snapshot&action=view";
		$body = ReadCameraImage($cameraURL, "admin", "TJg7\$dax\$bnU");						   
	
		if ($body == "404" || $body == "401")
		{	
		  // Samsung v2 (6320)
		  $cameraURL = "http://$cameraIP:$cameraHTTPPort/cgi-bin/video.cgi?msubmenu=jpg&resolution=9&compression=15";
		  $body = ReadCameraImage($cameraURL, "admin", "TJg7\$dax\$bnU");
		  
		  if ($body == "401" || $body == "404")
		  {
			 // Samsung v1 (5200)
			$cameraURL = "http://$cameraIP:$cameraHTTPPort/cgi-bin/video.cgi?msubmenu=jpg&resolution=4&compression=15";
			$body = ReadCameraImage($cameraURL, "admin", "4321");

			if ($body == "401" || $body == "404")
			{
				// AXIS Thermal
				$cameraURL = "http://$cameraIP:$cameraHTTPPort/axis-cgi/jpg/image.cgi?compression=30";
				$body = ReadCameraImage($cameraURL, "root", "TJg7\$dax\$bnU");

				if ($body == "401" || $body == "404")
				{
					// try FLIR ITS
					$cameraURL = "http://$cameraIP:$cameraHTTPPort/api/image/current";
					$body = ReadCameraImage($cameraURL, "", "");
						
					// try FLIR FC-S lookup
					if ($body == "401" || $body == "404")
					{
						for($i = 0; $i < 10; $i++)
						{
							$cameraURL = "http://$cameraIP:$cameraHTTPPort/graphics/livevideo/stream/stream$i.jpg";
							$body = ReadCameraImage($cameraURL, "", "");

							if(IsValidJPG($body) === TRUE)
								die("PASSED, found FLIR camera");
						}
						
						// try Dahua context camera
						if ($body == "401" || $body == "404")
						{
							$cameraURL = "http://$cameraIP:$cameraHTTPPort/cgi-bin/snapshot.cgi?1";
							$body = ReadCameraImage($cameraURL, "admin", "travis123");
							
							if(IsValidJPG($body) === TRUE)
								die("PASSED, found Dahua Context camera");
						}
						
					}
					else
					{
						if(IsValidJPG($body) === TRUE)
						{
							die("PASSED, found FLIR camera");
						}
					}
				}
				else
				{
					if(IsValidJPG($body) === TRUE)
						die("PASSED, found Axis Thermal camera");											
				}
			}
			else
			{
				if(IsValidJPG($body) === TRUE)
				{
					die("PASSED, found Samsung camera");
				}
			}
		  }
		  else
		  {		  
			  if(IsValidJPG($body) === TRUE)
			  {
				 die("PASSED, found Samsung camera");
			  }
		  }
		}
		else
		{
			if(IsValidJPG($body) === TRUE)
			{
				die("PASSED, found Samsung camera");
			}				
		}
   }
   else
   {
      if(IsValidJPG($body) === TRUE)
      {
         die("PASSED, found Axis camera");
      }
   }

   die("FAILED, unable to retrieve image from device IP.");
}

if ($action == "testinsync")
{
   $fp = @fsockopen($_REQUEST["ip"], 20000, $errno, $errstr, 30);

   if (!$fp)
      die ("Could not connect");
   else
   {
      fwrite($fp, "VV,,END");

      $return = "";

      while (!feof($fp))
         $return .= fgets($fp, 128);

      echo $return;

      fclose($fp);
   }
}

if($action == "diskstatus")
{
   set_time_limit(500);

   $output = shell_exec("chkdsk");

   for($i = 100; $i >= 0; $i--)
      $output = str_replace("$i percent completed. ", "", $output);

   $output = str_replace(["\r\n", "\n"], "<br />", $output);

   echo $output;
}

if($action == "delete")
{
   require_once("pathDefinitions.php");

   $path = "";
   if(isset($_REQUEST["path"]))
      $path = $_REQUEST["path"];

   if($path == "")
      die("No path to download");

   foreach (@scandir(VIDEO_ROOT . "/$path") as $file)
   {
      if ($file == '.' || $file == '..')
         continue;

      unlink(VIDEO_ROOT . "/$path/$file");
   }

   rmdir(VIDEO_ROOT . "/$path");
}

if($action == "videodl")
{
   require_once("pathDefinitions.php");

   $path = "";
   if(isset($_REQUEST["path"]))
      $path = $_REQUEST["path"];

   if($path == "")
      die("No path to download");

   $type = "xml";
   if(isset($_REQUEST["type"]))
      $type = $_REQUEST["type"];

   header("Content-Type: application/$type");
   header('Content-Disposition: attachment; filename="'.basename(VIDEO_ROOT . "/$path").'"');
   header('Content-Transfer-Encoding: binary');
   header('Content-Length: '.filesize(VIDEO_ROOT . "/$path"));
   header('Connection: close');

   // disable buffering, needed for really large MJPEG files.
   ob_end_clean();
   flush();

   readfile(VIDEO_ROOT . "/$path");
}

if($action == "getvideos")
{
   require_once("pathDefinitions.php");

   $fileList = @scandir(VIDEO_ROOT);

   if($fileList == FALSE)
      die("Unable to load video list.");

   $validDir = [];

   foreach($fileList as $file)
   {
      if($file == "." || $file == "..")
         continue;
      if(!is_dir(VIDEO_ROOT . "/$file"))
         continue;

      $validDir[] = $file;
   }

   echo "<div style='background-color: white;width:100%;height:100%;'><table style='color:black;width:100%;text-align:center'>";
   echo "<tr><td></th><th>Camera</th><th>Date</th><th>Config</th><th>Background</th><th>HST File</th><th>Video</th></tr>";

   foreach($validDir as $dir)
   {
      $year = substr($dir,0,4);
      $month = substr($dir,4,2);
      $day = substr($dir,6,2);

      $hour = substr($dir,9,2);
      $min = substr($dir,11,2);
      $sec = substr($dir,13,2);

      $date = "$month/$day/$year - $hour:$min:$sec";

      $fileList = @scandir(VIDEO_ROOT . "/$dir");

      if($fileList == FALSE)
         continue;

      $name = "";
      $bitmap = "";
      $hst = "";
      $video = "";

      foreach($fileList as $file)
      {
		$pos = strpos($file, ".mjpeg");

		if($pos !== FALSE)
		{
			$path_parts = pathinfo($file);
			
			$name = substr($file, 0, strpos($file, "."));
				
			$bitmap = $path_parts['filename'] . ".Background.bmp";
				
			if(file_exists(VIDEO_ROOT . "/$dir/" . $path_parts['filename'] . ".Background.hst"))
				$hst = $path_parts['filename'] . ".Background.hst";
				
			$video = $path_parts['filename'] . ".mjpeg";
		}
      }

      if($name == "")
         continue;

      $size = " (".formatSizeUnits(filesize(VIDEO_ROOT . "/$dir/$video")).")";
         
      echo "<tr>";
      echo '<td><a href="#" class="delete" style="display:block;position:relative" title="Delete Video" onclick="deleteVideo(\'' . $dir . '\')"><span class="icon-default" style="display: block; width: 17px; height: 17px; top: 0; left: 0;background-position:0 -248px"></span></span></a></td>';
      echo "<td>$name</td><td>$date</td>";
	  echo "<td><a href='/helpers/troubleshootingHelper.php?action=videodl&path=$dir/Intersection.xml&type=xml'>Config</a></td>";
	  echo "<td><a href='/helpers/troubleshootingHelper.php?action=videodl&path=$dir/$bitmap&type=bmp'>BMP</a></td>";
		
	  if($hst != "")
		echo "<td><a href='/helpers/troubleshootingHelper.php?action=videodl&path=$dir/$hst&type=hst'>HST</a></td>";
	  else
		echo "<td>N/A</td>";
			
	  echo "<td><a href='/helpers/troubleshootingHelper.php?action=videodl&path=$dir/$video&type=mjpeg'>Video $size</a></td>";
      echo "</tr>";
   }

   echo "</table></div>";
}

if($action == "gettasks")
{
   require_once("pathDefinitions.php");
   require_once("insyncInterface.php");

   $descriptorspec = [1 => ["pipe", "w"]];
   $process = proc_open(TOOLS_ROOT . "/ProcessQuery.exe", $descriptorspec, $pipes, NULL, NULL, ["bypass_shell"=>TRUE]);

   $queryData = "";

   if (is_resource($process))
   {
      while(!feof($pipes[1]))
         $queryData .= fgets($pipes[1]);

      fclose($pipes[1]);

      proc_close($process);
   }

   $queryXML = @simplexml_load_string($queryData);

   if($queryXML === FALSE)
      exit;

   $insync = new InSyncInterface();
   $perfData = $insync->getPerformanceData();

   $perfXML = @simplexml_load_string($perfData);

   if($perfXML === FALSE)
      exit;

   $cpu = [];
   $count = 0;

   foreach($perfXML->CPU->Load as $load)
   {
      if(isset($cpu[$count]["load"]))
         $cpu[$count]["load"] += (float)$load["Current"];
      else
         $cpu[$count]["load"] = (float)$load["Current"];

      $count++;
   }

   $count = 0;

   foreach($perfXML->CPU->Temperature as $temp)
   {
      if(isset($cpu[$count]["temp"]))
         $cpu[$count]["temp"] += (float)$temp["Temperature"];
      else
         $cpu[$count]["temp"] = (float)$temp["Temperature"];

      $count++;
   }

   $avgLoad = 0;
   $avgTemp = 0;

   foreach($cpu as $item)
   {
      $avgLoad += $item["load"];
      $avgTemp += $item["temp"];
   }

   if($avgLoad != 0 && $count != 0)
      $avgLoad /= $count;

   if($avgTemp != 0 && $count != 0)
      $avgTemp /= $count;

   $cpuElem = $queryXML->addChild("CPU");
   $cpuElem->addAttribute("Load", $avgLoad);
   $cpuElem->addAttribute("Temp", $avgTemp);

   echo $queryXML->asXML();
}

if($action == "endprocess")
{
   $pid = -1;
   if(isset($_REQUEST["pid"]))
      $pid = $_REQUEST["pid"];

   if($pid == -1)
      die("No process selected");

   passthru("taskkill /F /PID " . escapeshellarg($pid));
}

if($action == "clearstorage")
{   
    $WshShell = new COM("WScript.Shell"); 
    
    $InSync = INSYNC_EXE;

    try
    {
        $WshShell->Run("$InSync /clearstorage", 1, true); 
    }
    catch(Exception)
    {
        die("Could not execute $InSync /clearstorage");
    }

    die("Success");
}

if($action == "download")
{
   if($startDate == "" || $endDate == "")
      die("Invalid start/end dates");

   set_time_limit(300);
   ini_set('memory_limit', '200M');

   $startTimestamp = strtotime($startDate);
   $endTimestamp = strtotime($endDate);

   require_once("pathDefinitions.php");

   $logDirs = [APP_MON_LOG => "Application Monitor", BOOT_LOG => "Boot", CAMERAIO_LOG => "CameraIO", SUPERVISION_LOG => "SuperVision", CONFIG_SETUP_LOG => "Config Wizard", EVENT_LOG => "Eventlog", INSTALL_LOG => "Install", INSYNC_LOG => "InSync", INTERSECTIONMODEL_LOG => "IntersectionModel", OPTIMIZER_LOG => "Optimizer", DEVICE_MANAGER_LOG => "DeviceManager", INSYNCUI_LOG => "InSyncUI", IOBOARD_PINGER_LOG => "IOBoard Pinger", KIOSK_LOG => "Kiosk", MANAGE_IP_CONF_LOG => "ManageIP", MASTER_CONTROL_LOG => "Master Control", NOTIFICATION_LOG => "Notifications", NETWORK_DEVICE_MANAGER_LOG => "Network Device Manager", NTP_LOG => "NTP", STARTUP_LOG => "Startup", PROG_VALID_LOG => "Validator", RHYTHM_TIME_SERVICE_LOG => "TimeService", SYSTEM_RESET_WD_LOG => "Watchdog", WOLFIO_LOG => "WolfIO", WRITE_FILTER_LOG => "Write Filter", HISTORY_STATS_ROOT => "History", VIDEO_STREAMER_LOG => "Video Streamer", DETECTOR_COMM_SERVICE_LOG => "Detector Comm Service", INTRAFFIC_SYNC_LOG => "InTrafficSyncs"];

   $zip = new ZipArchive();

   $zipFilePath = TEMP_ROOT . "/" . time() . ".zip";

   if ($zip->open($zipFilePath, ZIPARCHIVE::CREATE) !== TRUE)
      die("Cannot create zip file in TEMP_ROOT");

   foreach($logDirs as $logDir=>$name)
   {
      $zip->addEmptyDir($name);

      $dirHandle = @opendir($logDir);

      if($dirHandle == FALSE)
         continue;

      while (($file = readdir($dirHandle)) !== false)
      {
         if($file == "." || $file == "..")
            continue;

         // Parse the filename assuming the format "Prefix_YYYYMMDD_hhmmss.txt".
         $parts = explode("_", $file);
         if (count($parts) >= 3 && strlen($parts[count($parts)-2]) == 8 && is_numeric($parts[count($parts)-2]))
         {
            $iPartDate = count($parts)-2;
            
            $year = substr($parts[$iPartDate], 0, 4);
            $month = substr($parts[$iPartDate], 4, 2);
            $day = substr($parts[$iPartDate], 6, 2);

            $iPartTime = count($parts)-1;

            $hour = substr($parts[$iPartTime], 0, 2);
            $min = substr($parts[$iPartTime], 2, 2);
            $sec = substr($parts[$iPartTime], 4, 2);
            
            $fileTime = strtotime("$month/$day/$year $hour:$min:$sec");
         }
         else
         {
            // If parsing the filename for date-time failed, just grab it.
            // This gets things like Boot/Boot.log
            $fileTime = $startTimestamp;
         }

         if($fileTime >= $startTimestamp && $fileTime <= $endTimestamp)
            $zip->addFile($logDir . "/" . $file, $name . "/" . $file);
      }
      closedir($dirHandle);
   }

   $descriptorspec = [1 => ["pipe", "w"]];
   $process = proc_open(EVENT_LOG_DUMP_EXE . " $startTimestamp $endTimestamp", $descriptorspec, $pipes, NULL, NULL, ["bypass_shell"=>TRUE]);

   $queryData = "";

   if (is_resource($process))
   {
      while(!feof($pipes[1]))
         $queryData .= fgets($pipes[1]);

      fclose($pipes[1]);

      proc_close($process);
   }

   $zip->addFromString("EventLog.xml", $queryData);
   


   require_once("maintenanceHelper.php");

   $archiveName = TEMP_ROOT . "/" . time() . ".insync";

   if(archiveFiles($archiveName, false, true))
      $zip->addFile($archiveName, "Current_Configuration.insync");

   $zip->close();

   unlink($archiveName);

   if(ini_get('zlib.output_compression'))
      ini_set('zlib.output_compression', 'Off');

   header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
   header("Content-Type: application/octet-stream");
   header("Content-Transfer-Encoding: Binary");
   header("Content-Disposition: attachment; filename=troubleshooting_package.zip");
   header("Content-Length: " . filesize($zipFilePath));

   ignore_user_abort(true);
   readfile($zipFilePath);

   register_shutdown_function('shutdown', $zipFilePath);
   exit;
}

function shutdown($file)
{
   unlink($file);
}

function formatSizeUnits($bytes)
 {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 0) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 0) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 0) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
}
?>

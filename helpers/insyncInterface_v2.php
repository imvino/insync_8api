<?php

// this checks to see if we're calling this page as a require() from another script
// if so, validation should be done there, and we should skip it here
// if(!isset($loggedIn) || !$loggedIn)
// {
// 	// this must be included on all pages to authenticate the user
// 	require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
// 	$permissions = authSystem::ValidateUser();
// 	// end
// }
//include_once("/helpers/pathDefinitions.php");
//require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/FileIOHelper.php");
//require "./websocket_client.php";
require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/websocket_client.php");

$server = 'localhost:8181/getImage';

$action = "";

if (isset($_REQUEST["action"]))
	$action = $_REQUEST["action"];

switch ($action) 
{
	/**
	 * Retrieves InSync status information
	 */
	case "getStatus": 
		{
			header('Content-Type: application/xml');
			header("Cache-Control: no-store, no-cache, must-revalidate");

			$insync = new InSyncInterface();

			echo $insync->getStatus();
		}
		break;
        
    case "manualcalls":
       {
          $insync = new InSyncInterface();
          $message = $_REQUEST['message'];

          echo $insync->setManualCalls($message);
       }
       break;
       
    case "ercalls":
        {
          $insync = new InSyncInterface();
          $message = $_REQUEST['message'];
          echo $insync->setErCalls($message);
        }
      break;
       
    /**
     * Sets and gets NTP remote management settings
     */
    case "ntpSetting":
        {
            header('Content-Type: text/plain');
            header("Cache-Control: no-store, no-cache, must-revalidate");
			$insync = new InSyncInterface();

			$ntpSetting = "";
			if(isset($_REQUEST['value']))
			{
				if($_REQUEST['value'] == "000" || $_REQUEST['value'] == "001" || $_REQUEST['value'] == "010" || $_REQUEST['value'] == "011" || $_REQUEST['value'] == "100" || $_REQUEST['value'] == "101" || $_REQUEST['value'] == "110" || $_REQUEST['value'] == "111")
				{
					$ntpSetting = $_REQUEST['value'];
				}
			}
            echo $insync->setAndGetNTPMode($ntpSetting);
        }
        break;
		
    /**
     * Signals InSync to do a one time sync with the NTP server with optional alternate server
     */
    case "ntpSyncWithOptionalAlt":
        {
	
            header('Content-Type: text/plain');
            header("Cache-Control: no-store, no-cache, must-revalidate");
            $insync = new InSyncInterface();

            $altServerIP = "";
            $altSet = false;

            // get alt IP and validate
            if (isset($_REQUEST['alt']))
            {
                $altSet = true;
                if (filter_var($_REQUEST['alt'], FILTER_VALIDATE_IP))
                {
                    $altServerIP = $_REQUEST['alt'];
                }
            }

            // check result
            if ($altSet && $altServerIP == "")
            {
                echo "Error: alt IP invalid.";
            }
            else
            {
                echo $insync->ntpSyncWithOptionalAlt($altServerIP);
            }
        }
        break;
        
        /**
        * Signals InSync to do a one time sync with the given NTP server
        */
        case "ntpSync":
        {
	
            header('Content-Type: text/plain');
            header("Cache-Control: no-store, no-cache, must-revalidate");
            $insync = new InSyncInterface();

            $server = "";

            // get alt IP and validate
            if (isset($_REQUEST['server']) && filter_var($_REQUEST['server'], FILTER_VALIDATE_IP))
            {
                $server = $_REQUEST['server'];
                echo $insync->ntpSync($server);
            }
            else
            {   
                echo "Invalid server IP";
            }
        }
        break;

	/**
	 * Retrieves InSync Network status information about current Global Config in XML format
	 */
	case "getConfigurationNetworkStatus": 
		{
			header('Content-Type: application/xml');
			header("Cache-Control: no-store, no-cache, must-revalidate");

			$insync = new InSyncInterface();

			echo $insync->getConfigurationNetworkStatus();
		}
		break;
      
   case "getPedestrianInfo":
      {
         header('Content-Type: application/xml');
         header("Cache-Control: no-store, no-cache, must-revalidate");
         
         $insync = new InSyncInterface();
         
         echo $insync->getPedestrianInfo();
      }
      break;

	/**
	 * Retrieves InSync Network status information about All Intersections in Management Group in XML format
	 */
	case "getGlobalNetworkStatus": 
		{
			header('Content-Type: application/xml');
			header("Cache-Control: no-store, no-cache, must-revalidate");

			$insync = new InSyncInterface();

			echo $insync->getGlobalNetworkStatus();
		}
		break;

	
	/**
	 * Retrieves processor data
	 */
	case "getProcessorStatus": 
		{
			header('Content-Type: application/xml');
			header("Cache-Control: no-store, no-cache, must-revalidate");

			$insync = new InSyncInterface();

			echo $insync->getPerformanceData();
		}
		break;
    
    /**
     * Retrieves a list of camera assigned to this intersection
     */
    case "getCameraList":
        {
            $insync = new InSyncInterface();
            
            echo json_encode($insync->getCameraList());
        }
        break;
	
    case "getCameraStatus":
        {
			$deviceName = "";
			if(isset($_REQUEST['deviceName']))
				$deviceName = $_REQUEST['deviceName'];

            $insync = new InSyncInterface();
            echo $insync->getCameraStatus($deviceName);

        }
        break;
    

	case "getImage": 
		{
			$viewCamera = "";
			if(isset($_REQUEST['viewCamera']))
				$viewCamera = $_REQUEST['viewCamera'];
			
			if($viewCamera == "cartoon")
				header('Content-Type: image/png');
			else
				header('Content-Type: image/jpeg');
			
			header("Cache-Control: no-store, no-cache, must-revalidate");

			$insync = new InSyncInterface();
			
			$filter = "normal";
			if(isset($_REQUEST['filter']))
				$filter = $_REQUEST['filter'];

			$quality = 80;
			if(isset($_REQUEST['quality']))
				$quality = $_REQUEST['quality'];
			
			$mode = "simple";
			if(isset($_REQUEST['mode']))
				$mode = $_REQUEST['mode'];

			if($quality > 100)
				$quality = 100;
			if($quality < 1)
				$quality = 1;
			
			$width = 320;
			$height = 240;
			if(isset($_REQUEST["width"]) && isset($_REQUEST["height"]))
			{
				$width = $_REQUEST["width"];
				$height = $_REQUEST["height"];
			}
			
			require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/FileIOHelper_v2.php");
			if (isCameraTypeContext($viewCamera) != true && isCameraTypeMultiViewContext($viewCamera) != true)
			{
				echo $insync->getImage($viewCamera, $filter, $quality, $width, $height, $mode);
			}
			else
			{
                // this context camera might be a cyclops camera; we need to determine if we need to get the image
                // from insync (cameraio, for night mode) or from videostreamer (day mode cyclops)
                if ($insync->getCameraViewImageSource($viewCamera) == "insync")
                {
                    echo $insync->getImage($viewCamera, $filter, $quality, $width, $height, $mode);
                }
                else
                {
                    // get transparent image
                    //echo $insync->getTransParentImage($viewCamera, $filter, $quality, $width, $height, $mode);
                    $src = $insync->getTransParentImage($viewCamera, $filter, $quality, 320, 240, $mode);
                    
                    // format message to send
                    // Eg: {"cameraName":"West Bound"}
                    //$var1 = '{"cameraName"';
                    //$var2 = ":";
                    //$var3 = '"'.$viewCamera.'"';
                    //$var4 = '}';
                    //$message = $var1.$var2.$var3.$var4;
                    $message = 	'{"cameraName"' . ":" . '"' . $viewCamera . '"' .'}';
                    
                    // get cropped image
                    $sp = websocket_open($server, 8181,'',$errstr, 10);
                    websocket_write_text($sp,$message);
                    $returnData = websocket_read($sp,$errstr);
                    fclose($sp);

                    // Convert string to image
                    $img = imagecreatefromstring($returnData);
                    
                    // output image into a variable 
                    ob_start();
                    imagepng($img);
                    $dest =  ob_get_clean();
                    
                    //merge transparent + cropped images
                    $final_img = imagecreatetruecolor(320, 240);
                    imagealphablending($final_img, true);
                    imagesavealpha($final_img, true);

                    // $dest = cropped, $src = transparent
                    $src1 = imagecreatefromstring($src);
                    $dest1 = imagecreatefromstring($dest);				

                    imagecopy($final_img, $dest1, 0, 0, 0, 0, 320, 240);
                    imagecopy($final_img, $src1, 0, 0, 0, 0, 320, 240);

                    header('Content-Type: image/png');
                    imagepng($final_img);

                    imagedestroy($dest1);
                    imagedestroy($src1);
				}
			}
		}
		break;
		
	case "getTransParentImage": 
		{
			$viewCamera = "";
			if(isset($_REQUEST['viewCamera']))
				$viewCamera = $_REQUEST['viewCamera'];
			
			if($viewCamera == "cartoon")
				header('Content-Type: image/png');
			else
				header('Content-Type: image/jpeg');
			
			header('Content-Type: image/png');
			header("Cache-Control: no-store, no-cache, must-revalidate");

			$insync = new InSyncInterface();
			
			$filter = "normal";
			if(isset($_REQUEST['filter']))
				$filter = $_REQUEST['filter'];

			$quality = 80;
			if(isset($_REQUEST['quality']))
				$quality = $_REQUEST['quality'];
			
			$mode = "simple";
			if(isset($_REQUEST['mode']))
				$mode = $_REQUEST['mode'];

			if($quality > 100)
				$quality = 100;
			if($quality < 1)
				$quality = 1;
			
			$width = 320;
			$height = 240;
			if(isset($_REQUEST["width"]) && isset($_REQUEST["height"]))
			{
				$width = $_REQUEST["width"];
				$height = $_REQUEST["height"];
			}

			// get transparent image
			echo $insync->getTransParentImage($viewCamera, $filter, $quality, $width, $height, $mode);
		
		}
		break;	
        
	case "getCameraViewImageSourceXML": 
		{
            $viewCamera = "South Bound";
			if(isset($_REQUEST['viewCamera']))
				$viewCamera = $_REQUEST['viewCamera'];
			
			$insync = new InSyncInterface();
            $viewSource = $insync->getCameraViewImageSourceXml($viewCamera);

			header('Content-Type: application/xml');
			header("Cache-Control: no-store, no-cache, must-revalidate");

			echo $viewSource;
        }
		break;
		
	case "getCameraViewImageSource": 
		{
            $viewCamera = "South Bound";
			if(isset($_REQUEST['viewCamera']))
				$viewCamera = $_REQUEST['viewCamera'];
			
			$insync = new InSyncInterface();
            $viewSource = $insync->getCameraViewImageSource($viewCamera);

			header('Content-Type: text/plain');
			header("Cache-Control: no-store, no-cache, must-revalidate");

			echo $viewSource;
        }
		break;
	
	case "getSunriseSunsetInfo": 
		{
			$insync = new InSyncInterface();
            $xmlResponse = $insync->getSunriseSunsetInfo();

			header('Content-Type: application/xml');
			header("Cache-Control: no-store, no-cache, must-revalidate");

			echo $xmlResponse;
        }
		break;
}

/**
 * Interfaces with InSync via port 50000
 */
class InSyncInterface 
{
	public $ip = "127.0.0.1";
	
	function __construct($ip = "127.0.0.1")
	{
		$this->ip = $ip;
		$this->persistent = TRUE;
	}
	
	private $persistent = TRUE;
    


    /**
     * Gets a connection to port 50000 with InSync.
     */
    private function getInSyncConnection()
    {
		if($this->persistent === TRUE)
	        return @stream_socket_client("tcp://$this->ip:50000", $errno, $errstr, 10, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT);
		else
	        return @stream_socket_client("tcp://$this->ip:50000", $errno, $errstr, 10, STREAM_CLIENT_CONNECT);
		
    }

    public function writeInSyncCommandAndGetResult($pXML)
    {
		$objSocket = $this->getInSyncConnection();
        
		if($objSocket === FALSE)
			$objSocket = $this->getInSyncConnection(); //Retry once
        
		if($objSocket !== FALSE)
		{
			$writeerr = $this->writeInSyncConnection($objSocket, $pXML);
            
			if($writeerr === FALSE)
			{
                @fclose($objSocket);
        		$objSocket = $this->getInSyncConnection();

                if($objSocket !== FALSE)
				{
                    //retry once.  Persistent connections fail on write if they are timed out/closed/etc.
				    $writeerr = $this->writeInSyncConnection($objSocket, $pXML);
                    
					if($writeerr === FALSE)
					{
						@fclose($objSocket);
						return FALSE;
					}
				}
				else
					return FALSE;
			}
	        return $this->readInSyncConnection($objSocket);
		}
		return FALSE;
    }

    private function writeInSyncConnection($pObjSocket, $pXML)
    {
		$err = @fwrite($pObjSocket, pack("L", strlen($pXML)));
        
		if($err === FALSE || $err != 4)
		{
			@fclose($pObjSocket);
			return FALSE;
		}
        
		$err = @fwrite($pObjSocket, $pXML);
        
		if($err === FALSE || $err != strlen($pXML))
		{
			@fclose($pObjSocket);
			return FALSE;
		}
        
		return TRUE;
    }

    private function readInSyncConnection($pObjSocket)
    {
        $szReturn = "";
        $header = @fread($pObjSocket, 4);
        
        if($header === FALSE || strlen($header) < 4)
        {
			//Unable to receive header
            @fclose($pObjSocket);
            return FALSE;
        }

        $header_format = 'LSize';

        $reqlength = unpack($header_format, $header);

        $contents = "";
        $remaining = $reqlength["Size"];
        
        do
        {
            $chunk = @fread($pObjSocket, $remaining);
            
            if($chunk !== FALSE)
            {
                $contents .= $chunk;
                $remaining -= strlen($chunk);
            }
            else
            {
				//Failed to read expected data.
                @fclose($pObjSocket);
                return FALSE;
            }
        } while($remaining > 0);
        
        if($remaining != 0)
        {
			//Premature data closure
            @fclose($pObjSocket);
            return FALSE;
        }
        
        return $contents;
    }
    
    /**
	 * Retrieves a list of IPs InSync recognizes as being in the Management Group
	 * @return string XML of IP list

	 */
	public function getApprovedIPList() 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="getapprovediplist" /></request>';

        	$szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			return "No data from InSync.";

		$xmlReturn = simplexml_load_string($szReturn);
        
		$xmlData = base64_decode($xmlReturn->getapprovediplist[0]);
          
        $xmlList = simplexml_load_string($xmlData);

        $ipList = array();

        foreach($xmlList->children() as $intersection)
                $ipList[] = (string)$intersection["IP"];

		return $ipList;
	}


	/**
	 * Gets status of InSync
	 * @return string InSync status

	 */
	public function getStatus() 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="insyncstatus" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			return "<error>No data from InSync.</error>";

		$xmlReturn = simplexml_load_string($szReturn);

		$xmlData = base64_decode($xmlReturn->insyncstatus[0]);

		return $xmlData;
	}

	/**	
	 * Retrieves InSync Network status information for current configuration intersections only!
	 * @return string InSync Network Status data
	 */
	public function getConfigurationNetworkStatus() 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="configurationnetworkstatus" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			die("<ConfigurationNetworkStatus Error=\"No data from InSync\" />");

		$xmlReturn = simplexml_load_string($szReturn);

		$xmlData = base64_decode($xmlReturn->configurationnetworkstatus[0]);

		return $xmlData;
	}
   
   /**
    * Retrieves Pedestrian Phase information at the processor
    * @return string Pedestrian Phase Information data
    */
   public function getPedestrianInfo()
   {
      $xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="pedestrianinfo" /></request>';
      
      $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			die("<PedestrianInfo Error=\"No data from InSync\" />");

		$xmlReturn = simplexml_load_string($szReturn);

		$xmlData = base64_decode($xmlReturn->pedestrianinfo[0]);

		return $xmlData;
   }

	/**	
	 * Retrieves InSync Network status information for all intersections in the Management Group
	 * @return string InSync Network Status data
	 */
	public function getGlobalNetworkStatus() 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="globalnetworkstatus" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			die("<GlobalNetworkStatus Error=\"No data from InSync\" />");

		$xmlReturn = simplexml_load_string($szReturn);

		$xmlData = base64_decode($xmlReturn->globalnetworkstatus[0]);

		return $xmlData;
	}



	/**
	 * Gets current Corridor Hash

	 * @return string Corridor Hash

	 */
	public function getCorridorHash() 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="corridorhash" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			return "No data from InSync.";

		$xmlReturn = simplexml_load_string($szReturn);

		$xmlData = base64_decode($xmlReturn->corridorhash[0]);

		return $xmlData;
	}


	
	/**
	 * Gets processor performance data
	 * @return string InSync data
	 */
	public function getPerformanceData() 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="performance" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			die("<error>No data from InSync.</error>");

		$xmlReturn = simplexml_load_string($szReturn);

		$xmlData = base64_decode($xmlReturn->performance[0]);

		return $xmlData;
	}
    
    /**
     * Returns a JSON array of cameras assigned to the intersection
     * and the intersection name
     */
    public function getCameraList()
    {
        require_once("libraries/intersectionUtil_v2.php");
        
        $intersectionUtil = new IntersectionUtil();
        
        $cameraList = $intersectionUtil->getCameraNames();
        
        $arrayData = array();
        $arrayData["name"] = (string)$intersectionUtil->getReadOnlySimpleXml()->Intersection["name"];
        $arrayData["list"] = $cameraList;
        
        return $arrayData;
    }

	/**
	 * Gets light status from InSync
	 * @return object InSync light status
	 */
	public function getLightState() 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="lightstatus" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			die("No data from InSync.");

		$xmlReturn = simplexml_load_string($szReturn)->lightstatus[0];

		$xmlData = base64_decode($xmlReturn);

		return $xmlData;
	}


	/**
	 * Gets WebUI Detector Mode status from InSync
	 * @return bool InSync Adaptive Mode disabled by WebUI
	 */
	public function getWebUIDetectorMode() 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="webuidetectormode" operation="read"/></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			die("No data from InSync.");

		$xmlReturn = simplexml_load_string($szReturn)->webuidetectormode[0];

		$respData = base64_decode($xmlReturn);

		if($respData == "True")
		{
			return TRUE;
		}
		else if($respData == "False")
		{
			return FALSE;
		}
		die("Corrupt data from InSync");
	}

	/**
	 * Sets WebUI Detector Mode status to InSync
	 * @return bool InSync Adaptive Mode disabled by WebUI
	 */
	public function setWebUIDetectorMode($enableOrDisable) 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="webuidetectormode" operation="write" value="' . $enableOrDisable . '"/></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			die("No data from InSync.");

		$xmlReturn = simplexml_load_string($szReturn)->webuidetectormode[0];

		$respData = base64_decode($xmlReturn);

		if($respData == "True")
		{
			return TRUE;
		}
		else if($respData == "False")
		{
			return FALSE;
		}
		die("Corrupt data from InSync");
	}





    //To set manual calls on InSyncInterfaceServer.cs 
    public function setManualCalls($xml)
    {
        //Could would be timed here with First.txt
        
        
        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
         		
        $xmlReturn = simplexml_load_string($szReturn)->setmanualcalls[0];

		$respData = base64_decode($xmlReturn);
        
        			
        //With how the backend is currently set up, I just let it die. 

		if($respData == "True")
		{
			return TRUE;
		}
		else if($respData == "False")
		{
			return FALSE;
		}
		die("Corrupt data from InSync");
    }
    
    //To write out the emergency mode file.
    public function setErCalls($message)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] ."/helpers/pathDefinitions.php");

       
        $emArray = array();   
        foreach($message as $key=>$value)
        {
            if (substr($key, 0,1) == "E")
            {
                $jsonObject["Emergency" . substr($key,1,2)] = $value;
                $contents = json_encode($jsonObject);
            }
        }
       
       if ((!file_exists(HAWKEYE_EMERGENCY_MODE_PHASES)) && (file_exists(HAWKEYE_CONF)))
        {
            $emfile = fopen(HAWKEYE_EMERGENCY_MODE_PHASES, "w") or die("Unable to open file!");

            fwrite($emfile, $contents);							
            fclose($emfile);
        }
        if (file_exists(HAWKEYE_EMERGENCY_MODE_PHASES))
        {

            $this->SaveJson($contents);
        } 
            

    }
        
   
    public function SaveJson($emArray)
{
	$myfile = fopen(HAWKEYE_EMERGENCY_MODE_PHASES, "w") or die("Unable to open file!");		
	//$emContents = json_encode($emArray);
	fwrite($myfile, $emArray);	
	fclose($myfile);
}

    /**
	 * Sets NTP operational modes.  Use "" or a string that isn't 3 characters long to not set a value in InSync.
	 * @return string Current settings for NTP Operational modes.
	 */
	public function setAndGetNTPMode($setting) 
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="remotentp" value="' . $setting . '"/></request>';
        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
		if (strlen($szReturn) == 0 || $szReturn == FALSE)
			die("No data from InSync.");

		$xmlReturn = simplexml_load_string($szReturn)->remotentp[0];
		$respData = base64_decode($xmlReturn);

		if($respData == "000" || $respData == "001" || $respData == "010" || $respData == "011" || $respData == "100" || $respData == "101" || $respData == "110" || $respData == "111")
		{
            return $respData;
		}
		die("Corrupt data from InSync");
	}



	 /**
	  * Retrieves camerastatus
	  * @param string $cameraName "North Bound", etc.
	 */
	public function getCameraStatus($cameraName) 
	{		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="camerastatus" name="' . $cameraName . '" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if($szReturn == FALSE)
			return '{"Error":"Unable to communicate with InSync"}';
		else if (strlen($szReturn) == 0)
			return '{"Error":"No data from InSync"}';

		$xmlTest = @simplexml_load_string($szReturn);
		if($xmlTest !== FALSE && isset($xmlTest->camerastatus[0]))
		{
			$xmlReturn = $xmlTest->camerastatus[0];

			if($xmlReturn !== FALSE)
			{
				$xmlData = base64_decode($xmlReturn);
				if($xmlData !== FALSE)
				{
					return $xmlData;
				}
			}
		}
		return '{"Error":"Corrupt data from InSync"}';
	}



	 /**
	  * Retrieves a camera image
	  * @param string $cameraName "North Bound", etc.
	  * @param string $filter "normal","raw",etc
	  * @param integer $quality 0-100
	  * @param integer $width Image requested size
	  * @param integer $height Image requested size
	  * @param string $mode 'simple' or 'advanced', only for cartoon view
	  * @return image binary image data
	 */
	public function getImage($cameraName, $filter, $quality, $width, $height, $mode) 
	{		
		$source = "insync";
		
		if($cameraName == "cartoon")
		{
			$cameraName = $mode;
			$source = "cartoon";
		}
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="image" name="' . $cameraName . '" source="' . $source . '" filter="' . $filter . '" quality="' . $quality . '" width="' . $width . '" height="' . $height . '" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        
		if($szReturn == FALSE)
			return $this->drawErrorImage("Unable to communicate with InSync", $width, $height);
		else if (strlen($szReturn) == 0)
			return $this->drawErrorImage("No data from InSync", $width, $height);

		$xmlReturn = @simplexml_load_string($szReturn);
			
		if(isset($xmlReturn->image[0]["error"]))
			return $this->drawErrorImage($xmlReturn->image[0]["error"], $width, $height);
		else
		{
			$imgData = base64_decode($xmlReturn->image[0]);
			
			if(!$imgData)
				return $this->drawErrorImage("Invalid Image", $width, $height);
			
			return $imgData;
		}
	}
	
	public function getTransParentImage($cameraName, $filter, $quality, $width, $height, $mode) 
	{		
		$source = "insync";
		
		if($cameraName == "cartoon")
		{
			$cameraName = $mode;
			$source = "cartoon";
		}
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="transparentimage" name="' . $cameraName . '" source="' . $source . '" filter="' . $filter . '" quality="' . $quality . '" width="' . $width . '" height="' . $height . '" /></request>';

        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
		
		if($szReturn == FALSE)
			return $this->drawErrorImage("Unable to communicate with InSync", $width, $height);
		else if (strlen($szReturn) == 0)
			return $this->drawErrorImage("No data from InSync", $width, $height);

		$xmlReturn = @simplexml_load_string($szReturn);
			
		if(isset($xmlReturn->image[0]["error"]))
			return $this->drawErrorImage($xmlReturn->image[0]["error"], $width, $height);
		else
		{
			$imgData = base64_decode($xmlReturn->image[0]);
			
			if(!$imgData)
				return $this->drawErrorImage("Invalid TransParent Image", $width, $height);
					
			
			return $imgData;
		}
	}	
    
    /**
    * Returns the camera view image source for the given camera view/direction.
    * @param string $cameraName String name of the camera view to display.
    * @return string The camera view image source ("insync", "videostreamer")
    */
    public function getCameraViewImageSource($cameraName) 
	{			
        // send a request to get the camera view image source for this camera view
        $szReturn = $this->getCameraViewImageSourceXml($cameraName);
		$xmlReturn = @simplexml_load_string($szReturn);
        
        $returnVal = "insync";
        
        // if the return is false, just return "insync"
        if($szReturn == FALSE)
        {
            $returnVal = "insync";
        }
        else if(isset($xmlReturn->camera_view_image_source[0]["type"]))
        {
            $returnVal = $xmlReturn->camera_view_image_source[0]["type"];
        }
        
        return $returnVal;
	}	
	
	public function getCameraViewImageSourceXml($cameraName) 
	{			
        // send a request to get the camera view image source for this camera view
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="camera_view_image_source" cameraViewName="' . $cameraName . '" /></request>';
        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
		return $szReturn;
	}
	
	public function getSunriseSunsetInfo()
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="getSunriseSunsetInfo"/></request>';
        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
		return $szReturn;
	}
	
	/**
	* Draws an error image
	* @param string $errStr String to draw on image
	* @return image binary image data
	*/
   public function drawErrorImage($errStr, $width = 320, $height = 240)
   {
	   $img = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . "/img/no-camera.png");
	   $black = imagecolorallocate($img, 0, 0, 0);

	   $fontWidth = imagefontwidth(4);
	   $fontWidth *= strlen($errStr);

	   imagestring($img, 4, 160 - ($fontWidth / 2), 210, $errStr, $black);

	   $scaled_img = imagecreatetruecolor($width, $height);
	   imagecopyresampled($scaled_img, $img, 0, 0, 0, 0, $width, $height, 320, 240);
	   ob_start();
	   imagejpeg($scaled_img, NULL, 85);
	   $imageContent = ob_get_clean();

	   return $imageContent;
   }
   
   /**
    * Signals InSync to perform an NTP sync.
    * @param string $server NTP Server IP.
    * @return string Result of NTP synchronization.
    */
   public function ntpSync($server)
   {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="ntpsync" server="'. $server .'"/></request>';
        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        if (strlen($szReturn) == 0 || $szReturn == FALSE)
            die("No data from InSync.");

        $xmlReturn = simplexml_load_string($szReturn)->ntpsync[0];
        $respData = base64_decode($xmlReturn);

        return $respData;
   }
   
   /**
    * Signals InSync to perform an NTP sync
    * and if it fails to attempt to sync with
    * an optional alternate server that is provided.
    * @param string $serverIP NTP Server IP.
    * @param string $altServerIP Alternate Server IP.
    * @return string Result of NTP synchronization.
    */
   public function ntpSyncWithOptionalAlt($altServerIP)
   {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><request><command type="ntpsyncWithOptionalAlt" alt_server="'. $altServerIP .'"/></request>';
        $szReturn = $this->writeInSyncCommandAndGetResult($xml);
        if (strlen($szReturn) == 0 || $szReturn == FALSE)
            die("No data from InSync.");

        $xmlReturn = simplexml_load_string($szReturn)->ntpsyncWithOptionalAlt[0];
        $respData = base64_decode($xmlReturn);

        return $respData;
   }
}
?>

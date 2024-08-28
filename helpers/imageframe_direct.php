<?php
use Unirest\Request;
require_once(__DIR__ . "/../helpers/constants.php");
require_once(SITE_DOCUMENT_ROOT . "helpers/getcameralist.php");
require_once(SITE_DOCUMENT_ROOT . "helpers/httpclient/Unirest.php");

// NOTE: this reads static copy of cameralist.php for efficiency reasons, since it is not called from a session

Function ReadCameraImage($cameraURL, $username, $password)
{
    Request::auth($username, $password, CURLAUTH_DIGEST);
    $response = Request::get($cameraURL, $headers, $query);    
    
    if (($response->code / 100) > 2)
    {
        return "No image found.";
    }
    
    return $response->body;
}

// Read camera name from the cn= parameter
$iCamera = 0;

if (isset($_REQUEST['cn']))
{
 	$cameraName = $_REQUEST['cn'];
	// find the index of the camera
	for ($i=0;$i<$aNumberCameras;$i++)
	{
		if ($aCameras[$i]["Name"] == $cameraName || $aCameras[$i]["LongName"] == $cameraName)
		{
			$iCamera = $i;
			break;
		}
	} 
}

// Read image quality level from the q= parameter (1-100)
$nQuality = 75;
if (isset($_REQUEST['q']))
{
	if (strlen($_REQUEST['q']))
	{
		if (($_REQUEST['q'] >= 1) && ($_REQUEST['q'] <= 100))
		{
			$nQuality = IntVal($_REQUEST['q']);
		}
	}
}

// read camera IP
$cameraIP = "";
if (isset($_REQUEST['ip']))
{
	$cameraIP = $_REQUEST['ip'];
}

// read camera snapshot port
$cameraHTTPPort = "80";
if (isset($_REQUEST['httpPort']))
{
	$cameraHTTPPort = $_REQUEST['httpPort'];
}

// Get the profile number if available for 'Rhythm' profile. This is currently used only on the XNZ-L6320 camera
$ProfileNumber = GetProfileNumber($cameraIP, $cameraSnapshotPort);

// If we did not get a profile number back, set it to an invalid value so that no image is loaded
if ($ProfileNumber === "")
	$ProfileNumber = "99";

// Talk directly to the camera to get the image
$cameraURL = "";
	
$nQuality = ceil($nQuality * 0.39);
		
if ($nQuality < 0) $nQuality = 0;
elseif ($nQuality > 100) $nQuality = 100;
        
if ($cameraIP == "")
{
	$nPort = $aCameras[$iCamera]['Port'];
	$szAddress = $aCameras[$iCamera]['Address'];
    $cameraURL = "http://{$szAddress}:{$nPort}{$aCameras[$iCamera]['Path']}?compression=$nQuality";
} else {
	$cameraURL = "http://$cameraIP/jpg/image.jpg?resolution=320x240&compression=$nQuality";
}

// axis 221 url and user/pass
$body = ReadCameraImage($cameraURL, "admin", "travis12");

// no response/page
if (imagecreatefromstring($body) == FALSE)
{
	if ($cameraIP != "")
	{
        // Bosch
        $cameraURL = "http://$cameraIP:$cameraHTTPPort/snap.jpg?JpegSize=320x240";		
        $body = ReadCameraImage($cameraURL, "service", "TJg7\$dax\$bnU");

        if (imagecreatefromstring($body) == FALSE)
        {
            // Scale quality setting to match Samsung 1-20 best to worst range.
            $nQuality = $nQuality / 5;
            if ($nQuality < 1) $nQuality = 1;
            elseif ($nQuality > 20) $nQuality = 20;
            
			// samsung xnz (L6320)
			$cameraURL = "http://$cameraIP:$cameraHTTPPort/stw-cgi/video.cgi?msubmenu=snapshot&action=view&Profile=$ProfileNumber";
			$body = ReadCameraImage($cameraURL, "admin", "TJg7\$dax\$bnU");					

            // unauthorized
            if (imagecreatefromstring($body) == FALSE)			
			{ 
				// samsung v2 (6320)
				$cameraURL = "http://$cameraIP:$cameraHTTPPort/cgi-bin/video.cgi?msubmenu=jpg&resolution=9&compression=$nQuality";		
				$body = ReadCameraImage($cameraURL, "admin", "TJg7\$dax\$bnU");
				
				// unauthorized
				if (imagecreatefromstring($body) == FALSE)
				{
					// samsung v1 (5200)
					$cameraURL = "http://$cameraIP:$cameraHTTPPort/cgi-bin/video.cgi?msubmenu=jpg&resolution=4&compression=$nQuality";          
					$body = ReadCameraImage($cameraURL, "admin", "4321");
					
					if (imagecreatefromstring($body) == FALSE)
					{
						// axis thermal
						$cameraURL = "http://$cameraIP:$cameraHTTPPort/axis-cgi/jpg/image.cgi?compression=$nQuality";          
						$body = ReadCameraImage($cameraURL, "root", "TJg7\$dax\$bnU");
						
						if (imagecreatefromstring($body) == FALSE)
						{
							// try FLIR ITS
							$cameraURL = "http://$cameraIP:$cameraHTTPPort/api/image/current";
							$body = ReadCameraImage($cameraURL, "", "");
							if (imagecreatefromstring($body) == FALSE)
							{
								// try FLIR FC-S lookup
								for($i = 0; $i < 10; $i++)
								{
									$cameraURL = "http://$cameraIP:$cameraHTTPPort/graphics/livevideo/stream/stream$i.jpg";          
									$body = ReadCameraImage($cameraURL, "", "");
									if(imagecreatefromstring($body) == FALSE)
										continue;
									else
										break;
								}
							}
						}
					}
				}
			}
        }
	}
	else
	{
		$body = "";
	}
}

$im = imagecreatefromstring($body);
$im2 = imagecreatetruecolor(320, 240);
$imSize = getimagesizefromstring($body);

if ($imSize[0]==426 && $imSize[1]==240)
{
    imagecopyresized($im2, $im, 0, 0, 53, 0, 320, 240, 320, 240);
}
else
{
    imagecopyresized($im2, $im, 0, 0, 0, 0, 320, 240, $imSize[0], $imSize[1]);
}

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Content-type: image/jpeg");	

imagejpeg($im2);

Function GetProfileNumber($cameraIP, $cameraHTTPPort)
{
	$cameraURL = "http://$cameraIP:$cameraHTTPPort/stw-cgi/media.cgi?msubmenu=videoprofile&action=view";
	$returnValue = ReadCameraImage($cameraURL, "admin", "TJg7\$dax\$bnU");
	
	//$separator = "\r\n";
	$separator = "\n";
	$line = strtok($returnValue, $separator);
	$checkString = ".Name=Rhythm";
	$pNumber = "";
	
	while ($line !== false && $line !== "")
	{
		if (strpos($line, $checkString))
		{
			$pNumber = substr($line, (strpos($line, $checkString) - 1), 1);
			break;
		}
		$line = strtok($separator);
	}
	return $pNumber;
}

?>

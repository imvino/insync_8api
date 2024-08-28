<?php
require_once(__DIR__ . "/../helpers/constants.php");
require_once(SITE_DOCUMENT_ROOT . "helpers/getcameralist.php");

// NOTE: this reads static copy of cameralist.php for efficiency reasons, since it is not called from a session

// Read camera name from the cn= parameter
$idCamera = 0x14;	// default to by name
$iCamera = 0;

if (isset($_REQUEST['cn']))
{
 	$cameraName = $_REQUEST['cn'];
 	if (strcmp($cameraName, "North")==0 || strcmp($cameraName, "North_Bound")==0 || strcmp($cameraName, "North Bound")==0)
 	{
		$idCamera = 0x10;
	}
	elseif (strcmp($cameraName, "South")==0 || strcmp($cameraName, "South_Bound")==0 || strcmp($cameraName, "South Bound")==0)
	{
		$idCamera = 0x11;
	}
	elseif (strcmp($cameraName, "East")==0 || strcmp($cameraName, "East_Bound")==0 || strcmp($cameraName, "East Bound")==0)
	{
		$idCamera = 0x12;
	}
	elseif (strcmp($cameraName, "West")==0 || strcmp($cameraName, "West_Bound")==0 || strcmp($cameraName, "West Bound")==0)
	{
		$idCamera = 0x13;
	}
	else
	{
		$idCamera = 0x14;	// view will be explicitly named
	}

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
	if (($_REQUEST['q'] >= 1) && ($_REQUEST['q'] <= 100))
	{
		$nQuality = IntVal($_REQUEST['q']);
	}
}

// Read size from the w= and h= parameters
$xres = 0;	// 0 = native size
$yres = 0;	// 0 = native size
if (isset($_REQUEST['w']) && strlen($_REQUEST['h']))
{
	$xres = IntVal($_REQUEST['w']);
	$yres = IntVal($_REQUEST['h']);
}



// set this variable to true, so insyncInterface doesnt try to authenticate
$loggedIn = true;
require_once(SITE_DOCUMENT_ROOT . "helpers/insyncInterface.php");
$insync = new InSyncInterface();

$filter = 'normal';

if ($nOperation == 255) {
	$filter = 'normal';
} else if ($nOperation == 254) {
	$filter = 'raw';
} else if ($nOperation == 50) {
	$filter = 'detector';
} else if ($nOperation == 25) {
	$filter = 'raw';
} else if ($nOperation == 24) {
	$filter = 'panomorh_unwrapped_4';
} else if ($nOperation == 23) {
	$filter = 'panomorh_unwrapped_3';
} else if ($nOperation == 22) {
	$filter = 'panomorh_unwrapped_2';
} else if ($nOperation == 21) {
	$filter = 'panomorh_unwrapped_1';
} else if ($nOperation == 20) {
	$filter = 'panomorph_raw';
} else if ($nOperation == 19) {
	$filter = 'background';
} else if ($nOperation == 18) {
	$filter = 'foreground';
} else if ($nOperation == 17) {
	$filter = 'reference';
} else if ($nOperation == 1) {
	$filter = 'edge_raw';
} else if ($nOperation == 0) {
	$filter = 'edge';
}
$body =  $insync->getImage($aCameras[$iCamera]["LongName"], $filter, $nQuality, $xres, $yres, 'simple');

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Content-length: ".strlen($body));
header("Content-type: image/jpeg");	
echo $body
?>

<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

if (empty($permissions["maintenance"]))
    die("Error: You do not have permission to access this page.");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

$lat = -200;
if(isset($_REQUEST['lat']))
	$lat = $_REQUEST['lat'];

$lon = -200;
if(isset($_REQUEST['lon']))
	$lon = $_REQUEST['lon'];

switch($action)
{
	/**
	 * Saves lat/lon to DB
	 */
	case "save":
	{
		if($lat != -200 && $lon != -200)
		{
			require_once("databaseInterface.php");

			$intersectionXML = getFile("Intersection.xml");	
			
			$intersectionObject = @simplexml_load_string($intersectionXML);
			
			if($intersectionObject === FALSE)
				die("Error saving coordinates!");
				
			$intersectionObject->Intersection["Location"] = $lat . "," . $lon;
			$intersectionXML = $intersectionObject->asXML();

			putFileFromString("Intersection.xml", $intersectionXML);

			die("Success");
		}
		else
			die("Error: Invalid coordinates!");
	}
	break;

	/**
	 * Retrieves lat/lon from DB
	 */
	case "get":
	{
		$loggedIn = true;
		require_once("databaseInterface.php");
	
		$intersectionXML = getFile("Intersection.xml");	
		$intersectionObject = @simplexml_load_string($intersectionXML);
		if($intersectionObject === FALSE)
		{
			echo "Error: Unable to read Intersection Configuration";
			return;
		}
		$coords = $intersectionObject->Intersection["Location"];
		if($coords == null && count($intersectionObject->xpath("Intersection")) == 1) {
			echo "Unconfigured";
		}
		else if($coords != null && strlen($coords) != 0) {
		    // See if CentralSync applied phase locations
		    //$configuredInCentralSync = true;
		    //if (count($intersectionObject->xpath("Intersection/Direction/Phases/Phase/@Location")) === 0) {
		    //    $configuredInCentralSync = false;
		    //}

            //if ($configuredInCentralSync) {
            //        echo "ReadOnlyCoordinates: " . $coords;
            //} else {
                    echo "Coordinates: " . $coords;
            //}
		}
		else
			echo "Error";
	
	}
	break;
}
?>

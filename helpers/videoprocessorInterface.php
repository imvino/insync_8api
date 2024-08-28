<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

if (empty($permissions["cameracontrols"]))
    die("Error: You do not have permission to access this page.");

require_once("pathDefinitions.php");
require_once("databaseInterface.php");
require_once("networkHelper.php");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

$camera = "";
if(isset($_REQUEST['camera']))
	$camera = $_REQUEST['camera'];

switch($action)
{
	/**
	 * Kill a camera process
	 */
	case "kill":
	{
		$fp = fopen(VIDEOPROCESSOR_COMMAND_FILE, 'w') or die("can't open file");

		fwrite($fp, $camera . ",");
		fwrite($fp, '0,Kill');
		fclose($fp);
		
		file_put_contents(VIDEOPROCESSOR_COMMAND_HISTORY_FILE, "$camera,0,Kill");
	}
	break;
}
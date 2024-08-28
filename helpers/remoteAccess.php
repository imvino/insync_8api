<?php
// No authentication required to access this page.
// Enable : http://ProcessorIPAddress/helpers/remoteAccess.php?action=enableRdp
// Disable: http://ProcessorIPAddress/helpers/remoteAccess.php?action=disableRdp 

// Use Case: Intersections at Orange County, FL has RDP disabled.
// 		 	 The user is not able to login to WebUI because of database corruption.
//           To clear database errors we need access to the processor but RDP is disabled.
//			 In a case like this, run this php script to enable RDP.

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

require_once("pathDefinitions.php");

switch($action)
{
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
}
?>

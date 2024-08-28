<?php

/**
 * Helper to get or set the current system type.
 * Auth has to be provided in the url.
 * Only the PEC account has rights to be able
 * to perform this command.
 * For action=getType, the system type is returned.
 * For action=setType&type=(video,adaptive,detector)
 * the SystemType.bin file is re-written and
 * the ApplicationMonitor.exe is invoked with the /update flag.
 */
 
require_once( "pathDefinitions.php" );
require_once( "../auth/authSystem.php" );

// make sure user is PEC or insync
if( isset( $_REQUEST["u"] ) )
{
	if ( base64_decode($_REQUEST['u']) != "PEC" && base64_decode($_REQUEST['u']) != "insync")
		die( "false" );
}
else
{
	die( "false" );
}

// auth first
$result = authSystem::ValidateUser();
	
// get the action
$action = "";
if(isset($_GET["action"]))
	$action = $_GET["action"];

// get the type
$type = "";
if(isset($_GET["type"]))
	$type = strtolower( $_GET["type"] );

if ( $action == "setType" )
{
	// get the current system type
	$currentSystemType = GetSystemType( );
	
	// set the system type if the current
	// type is different
	if ( $currentSystemType != $type )
		die( SetSystemType( $type ) );
	else
		die( "true" );
}
else if ( $action == "getType" )
{
	die( GetSystemType( ) );
}
else
{
	die( "Unknown action: $action" );
}

/**
 * Get the system type of the current system.
 * The SystemType.bin file is parsed for the
 * current system type.
 * The current system type is returned: video, adaptive,
 * detector, or adaptive_unset (if the SystemType.bin 
 * file doesn't exist or does not have any contents).
 */
function GetSystemType( )
{
	// parse the SystemType.bin file
	if ( file_exists( SYSTEM_TYPE_CONF_FILE ) )
	{
		$lines = file( SYSTEM_TYPE_CONF_FILE );
		$line_count = count( $lines );

		// if the file is empty assume adaptive
		if ( count( $lines ) == 0 || rtrim( $lines[ 0 ], "\r\n" ) == "" )
		{
			return "adaptive_unset";
		}

		$lineData = rtrim( $lines[ 0 ], "\r\n" );
		$lineDataArr = explode( " ", $lineData );	
		$systemType = strtolower( $lineDataArr[ 0 ] );
		
		if ( $systemType != "detector"
			&& $systemType != "adaptive"
			&& $systemType != "video" )
		{
			$systemType = "invalid";
		}
		return $systemType;
	}
	else
	{
		return "adaptive_unset";
	}	
}

/**
 * Set the system type to detector, video,
 * or adaptive.  Write the SystemType.bin file
 * and invoke the ApplicationMonitor.exe with /update
 * flag
 */
function SetSystemType( $systemTypeIn )
{
	$systemType = strtolower( $systemTypeIn );

	// check for valid system type
	if ( $systemType != "video" && $systemType != "adaptive" && $systemType != "detector" )
		die( "Attempt to set invalid system type: $systemType ");
		
	// write to the SYSTEM_TYPE_CONF_FILE
	$write_success = file_put_contents( SYSTEM_TYPE_CONF_FILE, $systemType );
	
	// invoke ApplicationMonitor.exe with the 
	// /update flag 
	if ( file_exists( APP_MON_EXE ) )
	{
		$shell = new COM( "WScript.Shell" );
		$shell->Run( APP_MON_EXE . " /update", 0, false );
		$shell = null;		
	}
	else
	{
		die( "Error: unable to find ApplicationMonitor.exe" );
	}
	
	if ( $write_success === false )
	{
		return "Error: error writing to SystemType.bin file";
	}
	
	return "true";
}

exit;

?>

<?php

// Will need to require_once this file in order to use
// the global path references

// Read the Paths.xml file for locations, if it exists
// otherwise, use the default paths.

$pathsXML = 'C:/InSync/Conf/Paths.xml';

if ( file_exists( $pathsXML ) )
{
	$contents = file_get_contents($pathsXML);
	
	if($contents === FALSE)
	{
		setDefaultPaths();
		exit;
	}
	
	$xml = simplexml_load_string($contents, null, LIBXML_NOENT);
	
	if($xml === FALSE)
	{
		setDefaultPaths();
		exit;
	}
	
	foreach($xml->Path as $obj)
		define( $obj["Name"], $obj["Location"] );
}
else
{
	setDefaultPaths();
}

function setDefaultPaths()
{
	// file paths
	define( 'INSYNC_ROOT' , 'C:/InSync' );
	define( 'CONF_ROOT', INSYNC_ROOT . '/Conf' );
	define( 'WEBUI_ROOT', INSYNC_ROOT . '/WebUI/www' );
	define( 'SCRIPTS_ROOT', INSYNC_ROOT . '/Scripts' );
	define( 'STARTUP_SCRIPTS_ROOT', SCRIPTS_ROOT . '/Startup' );
	define( 'TOOLS_ROOT', INSYNC_ROOT . '/Tools' );
	define( 'APPS_ROOT', INSYNC_ROOT . '/Apps' );
	define( 'INTERSECTION_CONFIG_UI_ROOT' , INSYNC_ROOT . '/IntersectionConfigUI' );

	// web relative paths
	define( 'INTERSECTION_CONFIG_UI_WEB_REL' , '/confui' );

	// scripts
	define( 'INSYC_SM', STARTUP_SCRIPTS_ROOT . '/InSyncSM.bat' );
	define( 'VIDEO_SM', STARTUP_SCRIPTS_ROOT . '/VideoSM.bat' );

	// configuration files
	define( 'INTERSECTION_CONF_FILE', CONF_ROOT . '/Intersection.xml' );
	define( 'SHARPNESS_CONF_FILE', CONF_ROOT . '/Temp/CameraIO/SharpnessConfiguration.txt' );

	// applications
	define( 'INSYNC_EXE', APPS_ROOT . '/InSync.exe' );
	define( 'CAMERAIO_EXE', APPS_ROOT . '/CameraIO.exe' );
	define( 'APP_MON_EXE', APPS_ROOT . '/ApplicationMonitor.exe' );
	define( 'WOLFIO_EXE', APPS_ROOT . '/WolfIO.exe' );
	define( 'IOBOARD_PINGER_EXE' , APPS_ROOT . '/IOBoardPinger.exe' );

	// web apps
	define( 'INTERSECTION_CONFIG_UI_JAR_WEB_REL', INTERSECTION_CONFIG_UI_WEB_REL . '/IntersectionConfigUI.jar' );
}


?>
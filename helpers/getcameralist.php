<?php
require_once (__DIR__ . '/../helpers/constants.php');
require_once (SITE_DOCUMENT_ROOT . 'helpers/pathDefinitions.php');

// this module reads the Intersection.xml file and loads data into the arrays that other pages use
// pages that update the configuration files set UpdateConfiguration
// global variables

$aCameras = [];

// first make sure an xml file exists
$FileNameXml = INTERSECTION_CONF_FILE;
if (!file_exists($FileNameXml))
{
	return;
}

// re-read the camera stuff every time...
{
	// XML read/write using DOMDocument class
	// create
	$XmlDoc = new DOMDocument();
	// options
	$XmlDoc->preserveWhiteSpace = true;
	// read
	$XmlDoc->load($FileNameXml);
	// NOTE: $XmlDoc now has structure from XML file
	// display relevant info
	// from VideoStream elements
	$nodeList = $XmlDoc->getElementsByTagName("VideoStream");
	$numberCamerasXml = $nodeList->length;
	// copy info into array from node
	for($i=0; $i<$numberCamerasXml; $i++)
	{
	 	$child = $nodeList->item($i);
		
		// get the name and friendly name
		$vName = $child->getAttribute('Name');
		$longName = $child->getAttribute('FriendlyName');
		
		$cameraType = $child->getAttribute('CameraType');
		
		// for the context camera type, the URL is stored in
		// the URL attribute and the friendly name is stored
		// in the Name attribute
		if ($cameraType == 'Context Camera')
		{
			$longName = $child->getAttribute('Name');
			$vName = $child->getAttribute('URL');
		}
				
		// parse and get fields to go into $aCameras
		// $vName should be like http://99.99.99.99/jpg/image.jpg (maybe with :port# on end of IP)
		$parsed = parse_url($vName);
		$ipAddress = $parsed['host'];
		if (isset($parsed['port']))
		{
			$port = intval($parsed['port']);
		}
		else
		{
			$port = 80;
		}
		$path = $parsed['path'];
		if (isset($parsed['query']))
		{
			$path .= '?' . $parsed['query'];
		}
		$pieces = explode('.', $ipAddress);
		$lastByte = $pieces[3];
		$shortName = str_replace(' ', '_', $longName);
		// put into aCameras
		$entry = ['Name' => $shortName, 'LongName'=>$longName, 'Address' => $ipAddress, 'LastByte'=>$lastByte, 'Port' =>$port, 'Path' => $path];
		$aCameras[] = $entry;
	}

	$videoServerNodes = $XmlDoc->getElementsByTagName("VideoServer");
	if($videoServerNodes->length > 0)
	{
		// loop thru all video servers
		// TODO: handle the case where there's video servers that are single camera views and a panomorphic video server at the same intersection
		for($i_server=0; $i_server<$videoServerNodes->length; $i_server++)
		{
			$servernode = $videoServerNodes->item($i_server);
			$type = $servernode->getAttribute('type');

			$vName = $servernode->getAttribute('Name');
			$longName = $servernode->getAttribute('FriendlyName');
			$parsed = parse_url($vName);
			$ipAddress = $parsed['host'];
			$path = $parsed['path'];					
			$pieces = explode('.', $ipAddress);
			$lastByte = $pieces[3];
			$shortName = str_replace(' ', '_', $longName);

			if ($type == 'PanomorphicServer')
			{
				if (isset($parsed['port']))
				{
					$port = intval($parsed['port']);
				}
				else
				{
					$port = 80;
				}
				
				if (isset($parsed['query']))
				{
					$path .= '?' . $parsed['query'];
				}
				
				// put into aCameras
				$entry = ['Name' => $shortName, 'LongName'=>$longName, 'Address' => $ipAddress, 'LastByte'=>$lastByte, 'Port' =>$port, 'Path' => $path];
				$aCameras[] = $entry;
			}
			
			if ($type == 'Panomorphic')
			{
				$directionList = $XmlDoc->getElementsByTagName("Direction");
				for( $dir=0; $dir < $directionList->length; $dir++ )
				{
					$directionNode = $directionList->item($dir);
					$directionName = $directionNode->getAttribute('name');
					
					$shortName = '';
					$longName = '';
					
					if ($directionName == 'North')
					{
						$shortName = 'South_Bound';
						$longName = 'South Bound';
					}
					else if ($directionName == 'South')
					{
						$shortName = 'North_Bound';
						$longName = 'North Bound';
					}
					else if ($directionName == 'East')
					{
						$shortName = 'West_Bound';
						$longName = 'West Bound';
					}
					else if ($directionName == 'West')
					{
						$shortName = 'East_Bound';
						$longName = 'East Bound';
					}
					
					$lastByte = "";
					$port = 80;
					
					// put into aCameras
					$entry = ['Name' => $shortName, 'LongName'=>$longName, 'Address' => $ipAddress, 'LastByte'=>$lastByte, 'Port' =>$port, 'Path' => $path];
					$aCameras[] = $entry;
				}
			}
			
 			if ($type == "")
			{
				$child = $servernode;
				$vName = $child->getAttribute('Name');
				$longName = $child->getAttribute('FriendlyName');
				// parse and get fields to go into $aCameras
				// $vName should be like http://99.99.99.99/jpg/image.jpg (maybe with :port# on end of IP)
				$parsed = parse_url($vName);
				$ipAddress = $parsed['host'];
				if (isset($parsed['port']))
				{
					$port = intval($parsed['port']);
				}
				else
				{
					$port = 80;
				}
				$path = $parsed['path'];
				if (isset($parsed['query']))
				{
					$path .= '?' . $parsed['query'];
				}
				$pieces = explode('.', $ipAddress);
				$lastByte = $pieces[3];
				$shortName = str_replace(' ', '_', $longName);
				// put into aCameras
				$entry = ['Name' => $shortName, 'LongName'=>$longName, 'Address' => $ipAddress, 'LastByte'=>$lastByte, 'Port' =>$port, 'Path' => $path];
				$aCameras[] = $entry;
			} 
		}
	}
	
	// now find intersection name from Intersection element
	$intersectionNodeList = $XmlDoc->getElementsByTagName("Intersection");
	$intersectionNode = $intersectionNodeList->item(0);
	$atts = $intersectionNode->attributes;
	foreach($atts as $attribute)
	{
		if ($attribute->name == 'name')
		{
			$aIntersectionName = $attribute->value;
			break;
		}
	}

	// save in session variables
	$_SESSION['aCameras'           ] = $aCameras         ;
	$_SESSION['aIntersectionName'  ] = $aIntersectionName;
	$_SESSION['ConfigurationLoaded'] = true;
	unset($_SESSION['UpdateConfiguration']);
} // if need to read configuration file

$aNumberCameras = count($aCameras);
$aMaxCameraNumber = $aNumberCameras - 1;
?>

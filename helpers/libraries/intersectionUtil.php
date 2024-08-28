<?php
use \Exception;
$loggedIn = true;
require_once __DIR__ . '/../constants.php';
require_once SITE_DOCUMENT_ROOT . 'helpers/pathDefinitions.php';
require_once SITE_DOCUMENT_ROOT . "helpers/databaseInterface.php";

class CameraDetail {
    public $url = null;

    function __construct(public $name, public $cameraUrl, public $videoServer) {
        $this->url = "helpers/insyncInterface.php?action=getImage&viewCamera=" . $this->name;
    }
}

class IntersectionUtil {
    private $intersectionXML = null;
    private $intersectionObject = null;
    private $cameras = [];

    function __construct($simpleXml = null) {
        if ($simpleXml === null) {
            $this->intersectionXML = getFile("Intersection.xml");	
            $this->intersectionObject = simplexml_load_string($this->intersectionXML);
        } else {
            $this->intersectionObject = $simpleXml;
        }
		
		if ($this->intersectionObject == FALSE) {
            return FALSE;
        }

        $this->cameras = [];

        foreach($this->intersectionObject->xpath("VideoStreamSettings/VideoServer") as $VideoServer) {
            $cameraUrl = (string)$VideoServer["Name"];
            foreach($VideoServer->xpath("View") as $View) {
                $this->cameras[(string)$View["Name"]] = new CameraDetail((string)$View["Name"], $cameraUrl, true);
            }
        }

        foreach($this->intersectionObject->xpath("VideoStreamSettings/VideoStream") as $VideoStream)
		{
			// if the camera is a context camera, the Url is in the URL attribute
			// the friendly name is in the Name attribute
			if (((string)$VideoStream["CameraType"]) == 'Context Camera')
			{
				$friendlyName = (string)$VideoStream["Name"];
				$cameraUrl = (string)$VideoStream["URL"];
				$this->cameras[$friendlyName] = new CameraDetail($friendlyName, $cameraUrl, false);				
			}
			
			// Process Multiview Context Cameras.																												//
			// Sample from Intersection.xml:																													//	
			// <VideoStream CameraType="Multiview Context Camera" URL="http://10.102.1.17:9090" Username="rhythm" Password="Welcome1" RefreshRate="1">			//
			//  	<Views>																																		//
			//  		<ContextCameraView Name="North Bound" Direction="North Bound" Phases="2,5" RegionOriginXY="1,1" RegionDimensionWH="161,120" />			//
			//  		<ContextCameraView Name="South Bound" Direction="South Bound" Phases="6,1" RegionOriginXY="385,102" RegionDimensionWH="181,135" />		//
			//  		<ContextCameraView Name="West Bound Adv" Direction="West Bound" Phases="3,7" RegionOriginXY="220,219" RegionDimensionWH="148,106" />	//	
			//  	</Views>																																	//
			// </VideoStream>																																	//

			// Commenting to be use later if required. 
			//else if (((string)$VideoStream["CameraType"]) == 'Multiview Context Camera')
			//{				
			//	$cameraUrl = (string)$VideoStream["URL"];
			//	foreach($this->intersectionObject->xpath("VideoStreamSettings/VideoStream/Views/ContextCameraView") as $ViewStream)
			//	{
			//		$friendlyName = (string)$ViewStream["Name"];
			//		$this->cameras[$friendlyName] = new CameraDetail($friendlyName, $cameraUrl, false);
			//	}
			//}
			
			else if (((string)$VideoStream["CameraType"]) != 'Multiview Context Camera' && ((string)$VideoStream["CameraType"]) != 'Context Camera')
			{
				$friendlyName = (string)$VideoStream["FriendlyName"];
				$cameraUrl = (string)$VideoStream["Name"];		
				$this->cameras[$friendlyName] = new CameraDetail($friendlyName, $cameraUrl, false);				
			}
        }
    }
	
    function getCameraNames() {
        $names = array_keys($this->cameras);
        sort($names, SORT_STRING);
        $sorted_names = [];

        foreach (['North Bound', 'South Bound', 'East Bound', 'West Bound'] as $direction) {
            foreach($names as $name) {
                if (str_starts_with($name, $direction)) {
                    $sorted_names[] = $name;
                }
            }
        }

        // Merge any non-standard names last
        $sorted_names = array_merge($sorted_names, array_diff($names, $sorted_names));

        return $sorted_names;
    }

    function getCameras() {
        return $this->cameras;
    }

    /**
     * Return the parsed intersection.xml.
     * 
     * The results of this call should not be modified.
     * 
     * @return SimpleXmlElement the SimpleXmlElement
     */
    function getReadOnlySimpleXml() {
	return $this->intersectionObject;
    }
}
?>

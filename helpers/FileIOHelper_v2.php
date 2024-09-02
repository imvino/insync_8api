<?php
// // this must be included on all pages to authenticate the user
// require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
// $permissions = authSystem::ValidateUser();
// // end

// if (empty($permissions["reports"]))
//     die("Error: You do not have permission to access this page.");

require_once "pathDefinitions.php";

$action = "";
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

switch ($action) {
    case "getSystemConfigurationType":
        {
            $systemType = getSystemType();
            echo $systemType;

            break;
        }
    case "isContextCamera":
        {
            $cameraName = "";
            if (isset($_REQUEST['cameraName'])) {
                $cameraName = $_REQUEST['cameraName'];
            }

            echo isCameraTypeContext($cameraName);
            break;
        }
    case "phasesWithCamera":
        {
            $cameraPhases = getPhasesWithCamera();
            echo implode("", $cameraPhases);
            break;
        }
}

function getSystemType()
{
    $systemType = "";
    if (file_exists(SYSTEM_CONFIGURATOR_CONFIG)) {
        $string = file_get_contents(SYSTEM_CONFIGURATOR_CONFIG);
        $array = json_decode($string, true);
        $systemType = $array["SystemConfiguration"];
    }
    return $systemType;
}

function isCameraTypeContext($cameraName)
{
    $isContext = false;

    $Intersection = @simplexml_load_file(INTERSECTION_CONF_FILE);
    if ($Intersection !== false) {
        foreach ($Intersection->xpath("//VideoStream") as $str) {
            if (($str->attributes()["Name"] == $cameraName) &&
                ($str->attributes()["CameraType"] == "Context Camera")) {
                $isContext = true;
            }
        }
    }

    return $isContext;

}

function isCameraTypeMultiViewContext($cameraName)
{
    $isMultiviewContext = false;
    $Intersection = @simplexml_load_file(INTERSECTION_CONF_FILE);
    if ($Intersection !== false) {
        foreach ($Intersection->xpath("//VideoStream") as $str) {
            if ($str->attributes()["CameraType"] == "Multiview Context Camera") {
                foreach ($Intersection->xpath("//VideoStream/Views/ContextCameraView") as $view) {
                    if ($view->attributes()["Name"] == $cameraName) {
                        $isMultiviewContext = true;
                    }
                }
            }
        }
    }
    return $isMultiviewContext;
}

// get phases with cameras. Context camers not included.
function getPhasesWithCamera()
{
    require_once "databaseInterface_v2.php";

    $cameraDetectors = array();
    $cameraPhases = array();

    $intersectionXML = getFile("Intersection.xml");
    $intersectionObject = simplexml_load_string($intersectionXML);

    foreach ($intersectionObject->Intersection->Direction as $direction) {
        foreach ($direction->DetectionDevices->VideoDetectionDevice as $videodetectiondevice) {
            $cameraDetectors[] = $videodetectiondevice["name"];
        }
        foreach ($direction->Phases->Phase as $phase) {
            $ph = strval($phase["name"]);

            foreach ($phase->Subphase->Detectors->Detector as $detector) {
                if (!in_array($ph, $cameraPhases) && in_array(strval($detector["name"]), $cameraDetectors)) {
                    $cameraPhases[] = (int) $ph;
                }
            }
        }
    }
    return $cameraPhases;
}

<?php
// // this must be included on all pages to authenticate the user
// require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
// $permissions = authSystem::ValidateUser();
// // end

// if (empty($permissions["cameracontrols"]))
//     die("Error: You do not have permission to access this page.");

require_once "pathDefinitions.php";
require_once "databaseInterface_v2.php";
require_once "networkHelper.php";

$action = "";
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

$camera = "";
if (isset($_REQUEST['camera'])) {
    $camera = $_REQUEST['camera'];
}

switch ($action) {
    /**
         * Reboots camera
         */
    case "reboot":
        {
            $fp = fopen(SHARPNESS_CONF_FILE, 'w') or die("can't open file");

            fwrite($fp, $camera . ",");
            fwrite($fp, '0,Reboot');
            fclose($fp);

            file_put_contents(CAMERA_PICKUP_FILE, "$camera,0,Reboot");

            syncWithVideoProcessor();
        }
        break;

    /**
         * Grabs new background image for camera
         */
    case "background":
        {
            $fp = fopen(SHARPNESS_CONF_FILE, 'w') or die("can't open file");

            fwrite($fp, $camera . ",");
            fwrite($fp, '0,Background');
            fclose($fp);

            file_put_contents(CAMERA_PICKUP_FILE, "$camera,0,Background");

            syncWithVideoProcessor();
        }
        break;

    /**
         * Empties the background image for camera
         */
    case "clear_background":
        {
            $fp = fopen(SHARPNESS_CONF_FILE, 'w') or die("can't open file");

            fwrite($fp, $camera . ",");
            fwrite($fp, '0,ClearBackground');
            fclose($fp);

            file_put_contents(CAMERA_PICKUP_FILE, "$camera,0,ClearBackground");

            syncWithVideoProcessor();
        }
        break;

    /**
         * Copies a segment at locationX/Y into the background image for camera
         */
    case "grab_segment":
        {
            if (isset($_REQUEST['locationX']) && isset($_REQUEST['locationY'])) {
                $fp = fopen(SHARPNESS_CONF_FILE, 'w') or die("can't open file");

                fwrite($fp, $camera . ",");
                fwrite($fp, $_REQUEST['locationX'] . ' ' . $_REQUEST['locationY']);
                fwrite($fp, ',GrabSegment');
                fclose($fp);

                file_put_contents(CAMERA_PICKUP_FILE, "$camera," . $_REQUEST['locationX'] . ' ' . $_REQUEST['locationY'] . ",GrabSegment");

                syncWithVideoProcessor();
            }
        }
        break;

    /**
         * Sets camera into emergency mode
         */
    case "emergency":
        {
            $bEmergency = false;
            $strPathFogParams = FOG_PARAMETERS_CONF_FILE;
            if (file_exists($strPathFogParams)) {
                $aFogParams = file($strPathFogParams);
                $bEmergency = (trim($aFogParams[2]) == 'true') ? true : false;
            } else {
                $bEmergency = false;
            }

            $fpSharpnessConfig = fopen(SHARPNESS_CONF_FILE, 'w') or die("can't open file");

            $stringCmra = $camera . ",";
            $qlty = $bEmergency ? '3' : 'EM';

            fwrite($fpSharpnessConfig, $stringCmra);
            fwrite($fpSharpnessConfig, $qlty . ",");
            fwrite($fpSharpnessConfig, "Submit");
            fclose($fpSharpnessConfig);

            file_put_contents(CAMERA_PICKUP_FILE, "$camera,EM,Submit");

            syncWithVideoProcessor();
        }
        break;

    /**
         * Sets camera into fog mode
         */
    case "fog":
        {
            // Check if fog mode is allowed
            $fpSharpnessConfig = fopen(SHARPNESS_CONF_FILE, 'w') or die("can't open file");
            $stringCmra = $camera . ",";

            $localtime = localtime(time(), true);

            // Format: <CameraName>,MM-DD-YYYY,DisableFog
            fwrite($fpSharpnessConfig, $stringCmra);
            fwrite($fpSharpnessConfig, $localtime["tm_mon"] + 1);
            fwrite($fpSharpnessConfig, "-");
            fwrite($fpSharpnessConfig, $localtime["tm_mday"]);
            fwrite($fpSharpnessConfig, "-");
            fwrite($fpSharpnessConfig, $localtime["tm_year"] + 1900);
            fwrite($fpSharpnessConfig, ",DisableFog");
            fclose($fpSharpnessConfig);

            file_put_contents(CAMERA_PICKUP_FILE, "$camera,0,DisableFog");

            syncWithVideoProcessor();
        }
        break;

    /**
         * Toggles recording on the camera
         */
    case "record":
        {
            $fpSharpnessConfig = fopen(SHARPNESS_CONF_FILE, 'w') or die("can't open file");
            $stringCmra = $camera . ",";

            // Format: <CameraName>,Toggle,Record
            fwrite($fpSharpnessConfig, $stringCmra);
            fwrite($fpSharpnessConfig, "Toggle");
            fwrite($fpSharpnessConfig, ",Record");
            fclose($fpSharpnessConfig);

            file_put_contents(CAMERA_PICKUP_FILE, "$camera,Toggle,Record");

            syncWithVideoProcessor();
        }
        break;

    /**
         * Called to receive a config file via GET
         * Used to propagate settings to video processors
         */
    case "receiveconfig":
        {
            $contents = "";
            if (isset($_REQUEST['contents'])) {
                $contents = $_REQUEST['contents'];
            }

            $results = @file_put_contents(SHARPNESS_CONF_FILE, $contents);
            $results = @file_put_contents(CAMERA_PICKUP_FILE, $contents);

            if ($results === false) {
                echo "Error";
            } else {
                echo "Success";
            }

        }
        break;
}

/**
 * Propagates camera settings to video processor
 */
function syncWithVideoProcessor()
{
    // If there is a secondary camera processor, send the SharpnessConfiguration.txt
    // to it now.

    $intersectionXML = getFile("Intersection.xml");
    $intersectionObject = simplexml_load_string($intersectionXML);

    $videoDetectionDevices = $intersectionObject->xpath("//VideoDetectionDevice");

    // Find all remote processors
    $aMachines = [];
    foreach ($videoDetectionDevices as $vdd) {
        $machine = (string) ($vdd->attributes()['machine']); // Either an IP address or "."
        if ($machine != "." && $machine != getInSyncIP() && isValidIP($machine) === true) {
            if (!in_array($machine, $aMachines)) {
                array_push($aMachines, $machine);
            }

        }
    }

    foreach ($aMachines as $machine) {
        $contents = @file_get_contents(SHARPNESS_CONF_FILE);

        if ($contents === false) {
            return false;
        }

        $contents = urlencode($contents);

        $remote_url = 'https://' . $machine . '/helpers/cameraioInterface_v2.php?action=receiveconfig&u=UEVD&p=bGVucGVjNDMyMQ%3D%3D&contents=' . $contents;

        $results = @file_get_contents($remote_url);

        if ($results === false) {
            $remote_url = 'http://' . $machine . '/helpers/cameraioInterface_v2.php?action=receiveconfig&u=UEVD&p=bGVucGVjNDMyMQ%3D%3D&contents=' . $contents;
            $results = @file_get_contents($remote_url);
            if ($results === false) {
                echo "Error Syncing";
            } else {
                echo $results;
            }

        } else {
            echo $results;
        }

    }
}
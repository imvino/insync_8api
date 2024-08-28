<?php
require_once(__DIR__ . '/../helpers/constants.php');
require_once SITE_DOCUMENT_ROOT . 'auth/authSystem.php';
require_once(SITE_DOCUMENT_ROOT . "helpers/insyncInterface.php");

$insync = new InSyncInterface();
$permissions = authSystem::ValidateUser();

if (!(isset($permissions['enabled']) && isset($permissions['api']))) {
    throw new Exception('Access denied.');
}

$allowedIps = $insync->getApprovedIPList();
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
    throw new Exception('Access denied.');
}

function getParameter($key, $default) {
    if (isset($_REQUEST[$key])) {
        return $_REQUEST[$key];
    } else {
        return $default;
    }
}

$action = getParameter('action', 'webuiversion');

if ($action == "webuiversion") {
    echo file_get_contents(SITE_DOCUMENT_ROOT . 'includes/version.txt');
} else if ($action == 'cameraimage') {
    $camera_name = $_REQUEST["camera_name"];
    $filter = getParameter('filter', 'normal');
    $quality = (int)getParameter('quality', 75);
    $width = (int)getParameter('width', 320);
    $height = (int)getParameter('height', 240);
    $mode = getParameter('mode', 'simple');

    // set this variable to true, so insyncInterface doesnt try to authenticate
    $loggedIn = true;

    if (isset($camera_name) && isset($filter)
            && isset($quality) && isset($width)
            && isset($height) && isset($mode)) {
            echo base64_encode($insync->getImage($camera_name, $filter,
                    $quality, $width, $height, $mode));
    } else {
            echo base64_encode($insync->drawErrorImage("Invalid request.", $width, $height));
    }
} else if ($action == 'lightstate') {
    // set this variable to true, so insyncInterface doesnt try to authenticate
    $loggedIn = true;

    echo $insync->getLightState();
} else {
    echo "Unknown operation: " . $action;
}
?>

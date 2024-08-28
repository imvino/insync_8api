<?php
header('Content-Type: application/json');

require_once("../helpers/pathDefinitions.php");
require_once("../helpers/corridorDesignerHelper_v2.php");

function getCorridor() {
    
    if(file_exists(CORRIDORVIEWER_CONF_FILE)) {
        $corridorXML = simplexml_load_file(CORRIDORVIEWER_CONF_FILE);
        $corridorData = generateFromXML($corridorXML);
        return json_encode($corridorData);
    }
    return json_encode(array('error' => 'Corridor configuration file not found'));
}

function getImage($ip, $cam, $filter, $quality, $width, $height) {
    // Implement the image fetching logic here
    // This is a placeholder and should be replaced with actual image fetching code
    $imageUrl = "/helpers/corridorViewerHelper_v2.php?action=getremoteimage&ip=$ip&cam=$cam&filter=$filter&quality=$quality&width=$width&height=$height";
    return json_encode(array('imageUrl' => $imageUrl));
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'getCorridor':
        echo getCorridor();
        break;
    case 'getImage':
        $ip = isset($_GET['ip']) ? $_GET['ip'] : '';
        $cam = isset($_GET['cam']) ? $_GET['cam'] : '';
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'normal';
        $quality = isset($_GET['quality']) ? $_GET['quality'] : '75';
        $width = isset($_GET['width']) ? $_GET['width'] : '320';
        $height = isset($_GET['height']) ? $_GET['height'] : '240';
        echo getImage($ip, $cam, $filter, $quality, $width, $height);
        break;
    default:
        echo json_encode(array('error' => 'Invalid action'));
}
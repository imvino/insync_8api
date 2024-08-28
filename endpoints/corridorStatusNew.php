<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  // In production, replace * with your frontend URL
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once("../helpers/networkHelper.php");

function getIntersectionStatus() {
    $intersections = getCorridorIntersections();
    $result = [];

    if ($intersections !== FALSE) {
        foreach ($intersections as $ip => $name) {
            $status = checkIntersectionStatus($ip);
            $result[] = [
                'name' => $name,
                'ip' => $ip,
                'status' => $status
            ];
        }
    }

    return $result;
}

function checkIntersectionStatus($ip) {
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $xmlString = @file_get_contents("http://" . $ip . "/specialcalls.php", false, $ctx);

    if ($xmlString !== false) {
        $xml = simplexml_load_string($xmlString);
        foreach ($xml->Network[0]->attributes() as $a => $b) {
            if ($a == "IP" && (string)$b === $ip) {
                return "Online";
            }
        }
    }

    return "Offline";
}

$intersectionStatus = getIntersectionStatus();
echo json_encode($intersectionStatus);
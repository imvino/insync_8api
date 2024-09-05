<?php
header('Content-Type: application/json');

// Uncomment these lines when ready to implement authentication
// require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
// $permissions = authSystem::ValidateUser();
// if (empty($permissions["maintenance"])) {
//     http_response_code(403);
//     echo json_encode(["error" => "You do not have permission to access this resource."]);
//     exit;
// }

require_once "databaseInterface_v2.php";

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function getCoordinates()
{
    $intersectionXML = getFile("Intersection.xml");
    $intersectionObject = @simplexml_load_string($intersectionXML);
    if ($intersectionObject === false) {
        return ["error" => "Unable to read Intersection Configuration"];
    }
    $coords = $intersectionObject->Intersection["Location"];
    if ($coords == null && count($intersectionObject->xpath("Intersection")) == 1) {
        return ["status" => "Unconfigured"];
    } else if ($coords != null && strlen($coords) != 0) {
        return ["coordinates" => (string) $coords];
    } else {
        return ["error" => "Unknown error occurred"];
    }
}

function saveCoordinates($lat, $lon)
{
    if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
        return ["error" => "Invalid coordinates"];
    }

    $intersectionXML = getFile("Intersection.xml");
    $intersectionObject = @simplexml_load_string($intersectionXML);
    if ($intersectionObject === false) {
        return ["error" => "Error saving coordinates"];
    }

    $intersectionObject->Intersection["Location"] = $lat . "," . $lon;
    $intersectionXML = $intersectionObject->asXML();

    if (putFileFromString("Intersection.xml", $intersectionXML)) {
        return ["status" => "Success"];
    } else {
        return ["error" => "Failed to save coordinates"];
    }
}

switch ($method) {
    case 'GET':
        if ($action === 'get') {
            echo json_encode(getCoordinates());
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
        }
        break;

    case 'POST':
        if ($action === 'save') {
            $data = json_decode(file_get_contents('php://input'), true);
            $lat = $data['lat'] ?? null;
            $lon = $data['lon'] ?? null;
            if ($lat !== null && $lon !== null) {
                echo json_encode(saveCoordinates($lat, $lon));
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Missing latitude or longitude"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

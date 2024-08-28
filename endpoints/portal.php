<?php
header('Content-Type: application/json');

// Include necessary files
require_once("../helpers/pathDefinitions.php");

// Function to parse portal configuration
function parsePortalConfig() {
    $result = [
        'title' => 'Untitled Portal',
        'corridors' => []
    ];

    if (file_exists(PORTAL_CONF_FILE)) {
        $portal = @simplexml_load_file(PORTAL_CONF_FILE);
        
        if ($portal !== FALSE) {
            if ($portal['title'] != "") {
                $result['title'] = str_replace("&quot;", "\"", $portal['title']);
            }
            
            foreach ($portal->children() as $child) {
                if (strtolower($child->getName()) == "map") {
                    $result['maps'][] = [
                        'name' => str_replace("&quot;", "\"", $child["name"]),
                        'url' => (string)$child["url"]
                    ];
                } elseif (strtolower($child->getName()) == "corridor") {
                    $corridor = [
                        'name' => $child["name"] != "" ? str_replace("&quot;", "\"", $child["name"]) : "Unnamed Management Group",
                        'items' => []
                    ];
                    
                    foreach ($child->children() as $subChildren) {
                        if (strtolower($subChildren->getName()) == "map") {
                            $corridor['items'][] = [
                                'type' => 'map',
                                'name' => $subChildren["name"] != "" ? str_replace("&quot;", "\"", $subChildren["name"]) : "Unnamed Map",
                                'url' => (string)$subChildren["url"]
                            ];
                        } elseif (strtolower($subChildren->getName()) == "intersection") {
                            $intersection = [
                                'type' => 'intersection',
                                'name' => $subChildren["name"] != "" ? str_replace("&quot;", "\"", $subChildren["name"]) : "Unnamed Camera",
                                'ip' => (string)$subChildren["ip"],
                                'devices' => []
                            ];
                            
                            foreach ($subChildren->children() as $device) {
                                $deviceType = strtolower($device->getName());
                                $intersection['devices'][] = [
                                    'type' => $deviceType,
                                    'name' => $device["name"] != "" ? str_replace("&quot;", "\"", $device["name"]) : ($deviceType == "camera" ? "Camera" : "DIN Relay"),
                                    'ip' => (string)$device["ip"]
                                ];
                            }
                            
                            $corridor['items'][] = $intersection;
                        }
                    }
                    
                    $result['corridors'][] = $corridor;
                }
            }
        } else {
            $result['error'] = "Error while attempting to read the portal configuration file.";
        }
    } else {
        $result['error'] = "No portal configuration is present on this system.";
    }
    
    return $result;
}

// Get the portal configuration
$portalConfig = parsePortalConfig();

// Output the JSON response
echo json_encode($portalConfig, JSON_PRETTY_PRINT);
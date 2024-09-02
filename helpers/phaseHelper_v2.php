<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/helpers/insyncInterface_v2.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/helpers/webdb.php";
// if(!isset($loggedIn) || !$loggedIn)
// {
//     // this must be included on all pages to authenticate the user
//     require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
//     $permissions = authSystem::ValidateUser();
//     // end

//     if (empty($permissions["web"]) && empty($permissions["api"]))
//         if($permissions["username"] != "kiosk")
//             die("Error: You do not have permission to access this page.");
// }

$action = "";
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

if ($action == "reset") {
    setDefaultPhaseNames();
}

if ($action == "save") {
    for ($i = 0; $i <= 8; $i++) {
        if (isset($_REQUEST["phase" . $i . "long"])) {
            $phase[$i]['long'] = htmlspecialchars($_REQUEST["phase" . $i . "long"]);

            // remove special chars
            $phase[$i]['long'] = str_replace(",", "", $phase[$i]['long']);
            $phase[$i]['long'] = str_replace("\"", "", $phase[$i]['long']);

            // crop to 40 chars
            if (strlen($phase[$i]['long']) > 40) {
                $phase[$i]['long'] = substr($phase[$i]['long'], 0, 40);
            }

        } else {
            $phase[$i]['long'] = "";
        }

        if (isset($_REQUEST["phase" . $i . "short"])) {
            $phase[$i]['short'] = htmlspecialchars($_REQUEST["phase" . $i . "short"]);

            // remove special chars
            $phase[$i]['short'] = str_replace(",", "", $phase[$i]['short']);
            $phase[$i]['short'] = str_replace("\"", "", $phase[$i]['short']);

            // crop to 6 chars
            if (strlen($phase[$i]['short']) > 6) {
                $phase[$i]['short'] = substr($phase[$i]['short'], 0, 6);
            }

        } else {
            $phase[$i]['short'] = "";
        }

    }

    $db = openWebDB();

    if (!$db) {
        die("Error: Could not connect to DB");
    }

    pg_query($db, "BEGIN TRANSACTION");
    $username = $permissions['username'] ?? 'PEC'; //default_user for testing

    if (pg_query_params($db, 'delete from phase_renaming where "user" = $1', [$username])) {
        foreach ($phase as $index => $names) {
            if (!pg_query_params($db, 'INSERT INTO phase_renaming ("user", phase_number, short, long) values ($1, $2, $3, $4)',
                [$username, $index, $names['short'], $names['long']])) {
                pg_query($db, "ROLLBACK TRANSACTION");
                pg_close($db);
                die("Unable to update phase names: " . pg_last_error($db));
            }
        }
        pg_query($db, "COMMIT TRANSACTION");
        pg_close($db);
        die("Success");
    } else {
        pg_query($db, "ROLLBACK TRANSACTION");
        pg_close($db);
        die("Unable to remove old phase names: " . pg_last_error($db));
    }
}

function getActivePedestrians()
{
    $insync = new InsyncInterface();
    $pedInfoData = $insync->getPedestrianInfo();
    $xml = simplexml_load_string($pedInfoData);

    $activePhases = [];
    foreach ($xml->PedPhase as $pedPhase) {
        $number = (int) $pedPhase["Number"];
        $activePhases[] = $number;
    }

    sort($activePhases, SORT_NUMERIC);
    return $activePhases;
}

function getActivePhases()
{
    require_once "databaseInterface_v2.php";

    $intersectionXML = getFile("Intersection.xml");
    $intersectionObject = simplexml_load_string($intersectionXML);

    // get active phases
    $activePhases = [];
    foreach ($intersectionObject->Intersection->Direction as $Directions) {
        foreach ($Directions->Phases->Phase as $Phase) {
            array_push($activePhases, (int) $Phase["name"]);
        }
    }

    sort($activePhases, SORT_NUMERIC);

    return $activePhases;
}

function extFromInt($phaseMap, $internal)
{
    if (!isset($phaseMap[$internal])) {
        return false;
    }

    return $phaseMap[$internal];
}

function intFromExt($phaseMap, $external)
{
    foreach ($phaseMap as $internalItem => $externalItem) {
        if ($external == $externalItem) {
            return $internalItem;
        }

    }

    return false;
}

function getIntExtPhaseMap()
{
    $phaseMap = [];
    // phaseMap will be filled with an associative array for all active phases
    // phaseMap [ internal# ] = external#

    require_once "databaseInterface_v2.php";

    $intersectionXML = getFile("Intersection.xml");
    $intersectionObj = simplexml_load_string($intersectionXML);

    foreach ($intersectionObj->Intersection->Direction as $Directions) {
        if ($Directions["name"] == "North") {
            foreach ($Directions->Phases->Phase as $Phase) {
                // thru
                if ((int) $Phase["name"] % 2 == 0) {
                    $phaseMap[6] = (int) $Phase["name"];
                }

                // left
                else {
                    $phaseMap[1] = (int) $Phase["name"];
                }

            }
        } else if ($Directions["name"] == "South") {
            foreach ($Directions->Phases->Phase as $Phase) {
                // thru
                if ((int) $Phase["name"] % 2 == 0) {
                    $phaseMap[2] = (int) $Phase["name"];
                }

                // left
                else {
                    $phaseMap[5] = (int) $Phase["name"];
                }

            }
        } else if ($Directions["name"] == "West") {
            foreach ($Directions->Phases->Phase as $Phase) {
                // thru
                if ((int) $Phase["name"] % 2 == 0) {
                    $phaseMap[8] = (int) $Phase["name"];
                }

                // left
                else {
                    $phaseMap[3] = (int) $Phase["name"];
                }

            }
        } else if ($Directions["name"] == "East") {
            foreach ($Directions->Phases->Phase as $Phase) {
                // thru
                if ((int) $Phase["name"] % 2 == 0) {
                    $phaseMap[4] = (int) $Phase["name"];
                }

                // left
                else {
                    $phaseMap[7] = (int) $Phase["name"];
                }

            }
        }
    }

    return $phaseMap;
}

/**
 * Sets default phase names in the DB
 */
function setDefaultPhaseNames()
{
    global $permissions;

    $phaseMap = getIntExtPhaseMap();
    $phaseArr = [];
    $phase = getDefaultPhases();

    for ($i = 1; $i < 9; $i++) {
        if (intFromExt($phaseMap, $i) != false) {
            $phaseArr[$i]["long"] = $phase[intFromExt($phaseMap, $i)]["long"];
            $phaseArr[$i]["short"] = $phase[intFromExt($phaseMap, $i)]["short"];
        } else {
            $phaseArr[$i]["long"] = "";
            $phaseArr[$i]["short"] = "";
        }
    }

    $db = openWebDB();

    if (!$db) {
        return false;
    }

    pg_query($db, "BEGIN TRANSACTION");
    $username = $permissions['username'];

    if (pg_query_params($db, 'delete from phase_renaming where "user" = $1', [$username])) {
        foreach ($phaseArr as $index => $names) {
            if (!pg_query_params($db, 'INSERT INTO phase_renaming ("user", phase_number, short, long) values ($1, $2, $3, $4)',
                [$username, $index, $names['short'], $names['long']])) {
                pg_query($db, "ROLLBACK TRANSACTION");
                pg_close($db);
                die("Unable to update phase names: " . pg_last_error($db));
            }
        }
        pg_query($db, "COMMIT TRANSACTION");
        pg_close($db);
        die("Success");
    } else {
        pg_query($db, "ROLLBACK TRANSACTION");
        pg_close($db);
        die("Unable to remove old phase names: " . pg_last_error($db));
    }
}

/**
 * Gets an associative array containing the long/short phase numbers of EXTERNAL phases
 * @return boolean|string
 */
function getPhaseNames()
{
    global $permissions;

    $db = openWebDB();

    if (!$db) {
        return false;
    }

    $username = $permissions['username'];

    $found_mappings = false;
    $returnArray = [];
    if ($result = pg_query_params($db, 'SELECT phase_number, short, long from phase_renaming where "user" = $1', [$username])) {
        while ($row = pg_fetch_assoc($result)) {
            $found_mappings = true;

            $returnArray[(int) $row["phase_number"]]["long"] = $row["long"];
            $returnArray[(int) $row["phase_number"]]["short"] = $row["short"];
        }

        if (!$found_mappings) {
            $phase = getDefaultPhases();

            $phaseMap = getIntExtPhaseMap();

            for ($i = 1; $i < 9; $i++) {
                if (intFromExt($phaseMap, $i) != false) {
                    $returnArray[$i]["long"] = $phase[intFromExt($phaseMap, $i)]["long"];
                    $returnArray[$i]["short"] = $phase[intFromExt($phaseMap, $i)]["short"];
                } else {
                    $returnArray[$i]["long"] = "";
                    $returnArray[$i]["short"] = "";
                }
            }
        }

        pg_close($db);

        return $returnArray;
    }

    pg_close($db);

    return false;
}

function getDefaultPhases()
{
    $phase = [];

    $phase[1] = ["long" => "Southbound Left", "short" => "SL"];

    $phase[2] = ["long" => "Northbound Through", "short" => "NT"];

    $phase[3] = ["long" => "Eastbound Left", "short" => "EL"];

    $phase[4] = ["long" => "Westbound Through", "short" => "WT"];

    $phase[5] = ["long" => "Northbound Left", "short" => "NL"];

    $phase[6] = ["long" => "Southbound Through", "short" => "ST"];

    $phase[7] = ["long" => "Westbound Left", "short" => "WL"];

    $phase[8] = ["long" => "Eastbound Through", "short" => "ET"];

    return $phase;
}

<?php

// if(!isset($loggedIn) || !$loggedIn)
// {
//     // this must be included on all pages to authenticate the user
//     require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
//     $permissions = authSystem::ValidateUser();
//     // end

//     if (empty($permissions["reports"]))
//         die("Error: You do not have permission to access this page.");
// }

ini_set('memory_limit', '256M');

require_once "pathDefinitions.php";
require_once "databaseInterface_v2.php";
require_once "phaseHelper_v2.php";

$action = "";
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

switch ($action) {
    /**
         * Downloads CSV for user, given paramaters
         */
    case "downloadcsv":
        {
            $start = "";
            if (isset($_REQUEST['startDateTime'])) {
                $start = $_REQUEST['startDateTime'];
            }

            $end = "";
            if (isset($_REQUEST['endDateTime'])) {
                $end = $_REQUEST['endDateTime'];
            }

            // limit time range to two days for memory/performance reasons
            $startStamp = strtotime($start);
            $endStamp = strtotime($end);

            if ($endStamp - $startStamp >= 172800) {
                die('Error: The requested time span was too large. Please choose a timespan of <48 hours.');
            }

            $waitFilter = "";
            if (isset($_REQUEST['waitFilter'])) {
                $waitFilter = $_REQUEST['waitFilter'];
            }

            $incArray = [];
            if (isset($_REQUEST['incArray'])) {
                if ($_REQUEST['incArray'] != "null") {
                    $incArray = explode(",", $_REQUEST['incArray']);
                }
            }

            $moveArray = [];
            if (isset($_REQUEST['moveArray'])) {
                if ($_REQUEST['moveArray'] != "null") {
                    $moveArray = explode(",", $_REQUEST['moveArray']);
                }
            }

            if ($start == "" || $end == "") {
                die('Error: Start or End dates are missing');
            }

            header("Content-type: text/csv; header=present");
            header("Content-disposition: attachment;filename=History.csv");

            $activePhases = getActivePhases();
            $phaseNames = getPhaseNames();

            $header = "Date,Time,Movement,Duration,";

            foreach ($phaseNames as $phase => $nameArr) {
                if (in_array($phase, $activePhases)) {
                    $header .= "Phase $phase (" . $nameArr["short"] . ") Queue,";
                    $header .= "Phase $phase (" . $nameArr["short"] . ") Wait,";
                }
            }

            $header .= "Period,Errors\r\n";
            echo $header;

            $historyData = loadHistoryData($start, $end, $activePhases);
            $periodData = loadPeriodData($start, $end);

            $combinedData = $historyData;

            if (is_array($periodData)) {
                foreach ($periodData as $timestamp => $data) {
                    $key = floatval($timestamp);

                    while (array_key_exists(strval($key), $historyData)) {
                        $key++;
                    }

                    $combinedData[strval($key)] = $data;
                }

                ksort($combinedData);
            }

            $indexedData = [];
            foreach ($combinedData as $timestamp => $data) {
                $indexedData[] = ["timestamp" => $timestamp, "data" => $data];
            }

            $internalPhaseMap = ["SouthBoundLeftTurn" => 1, "NorthBoundThrough" => 2, "EastBoundLeftTurn" => 3, "WestBoundThrough" => 4, "NorthBoundLeftTurn" => 5, "SouthBoundThrough" => 6, "WestBoundLeftTurn" => 7, "EastBoundThrough" => 8];

            $phaseMap = getIntExtPhaseMap();

            for ($i = 0; $i < count($indexedData); $i++) {
                $realTimestamp = $indexedData[$i]["timestamp"] / 1000;
                $data = $indexedData[$i]["data"];
                $show = false;

                $line = date("m/d/Y", $realTimestamp) . ",";
                $line .= date("H:i:s", $realTimestamp) . ",";

                $errStr = "";

                // movement
                if ($data["t"] == "I") {
                    $movements = $data["md"]["m"];

                    if ($movements == "AllRed") {
                        continue;
                    } else {
                        if (in_array("t", $incArray)) {
                            $show = true;
                        } else {
                            continue;
                        }

                    }

                    if (count($moveArray) > 0) {
                        $matchCount = 0;

                        for ($j = 0; $j < count($moveArray); $j++) {
                            if (str_contains($movements, $moveArray[$j])) {
                                $matchCount++;
                            }
                        }

                        if ($matchCount == 0) {
                            $show = false;
                            continue;
                        }
                    }

                    $movementArr = explode("-", $movements);
                    $movementString = "";

                    foreach ($movementArr as $movement) {
                        if (isset($internalPhaseMap[$movement])) {
                            $external = extFromInt($phaseMap, $internalPhaseMap[$movement]);
                            $movementString .= $phaseNames[$external]["short"] . "/";
                        }
                    }

                    $movementString = rtrim($movementString, "/");

                    $line .= $movementString . ",";

                    if ($i < count($indexedData) - 1) {
                        $duration = 0;

                        for ($j = $i + 1; $j < count($indexedData); $j++) {
                            if ($indexedData[$j]["data"]["t"] == "I") {
                                $duration = ($indexedData[$j]["timestamp"] - $indexedData[$i]["timestamp"]) / 1000;
                                break;
                            }
                        }

                        $line .= "$duration,";
                    } else {
                        $line .= "0,";
                    }

                    $wait_exceeded = false;

                    for ($j = 1; $j < 9; $j++) {
                        if (in_array($j, $activePhases)) {
                            if (isset($data["md"]["pd"][$j]["queue"]) && isset($data["md"]["pd"][$j]["wait"])) {
                                $line .= $data["md"]["pd"][$j]["queue"] . ",";
                                $line .= $data["md"]["pd"][$j]["wait"] . ",";

                                if ($waitFilter != "" && (intval($data["md"]["pd"][$j]["wait"]) > intval($waitFilter))) {
                                    $wait_exceeded = true;
                                }
                            } else {
                                $line .= "0,0,";
                            }

                            if (isset($data["md"]["pd"][$j]["error"])) {
                                $errStr .= "Phase $j (" . $phaseNames[$j]["short"] . "):" . $data["md"]["pd"][$j]["error"] . "/";
                            }

                        }
                    }

                    if ($waitFilter != "" && !$wait_exceeded) {
                        $show = false;
                    }

                    $errStr = trim($errStr, "/,\r\n");
                }

                // success
                else if ($data["t"] == "S") {
                    $line .= "Success,\"" . trim($data["d"]) . "\",";

                    for ($j = 0; $j < count($activePhases); $j++) {
                        $line .= "0,0,";
                    }

                    if (in_array("s", $incArray)) {
                        $show = true;
                    }

                }

                // period changing
                else if ($data["t"] == "P") {
                    $line .= "Period length changed to " . trim($data["p"]) . ",0,";

                    for ($j = 0; $j < count($activePhases); $j++) {
                        $line .= "0,0,";
                    }

                    if (in_array("per", $incArray)) {
                        $show = true;
                    }

                }

                // tunnel
                else if ($data["t"] == "T") {
                    $line .= "Tunnel,\"" . trim($data["d"]) . "\",0,";

                    for ($j = 0; $j < count($activePhases); $j++) {
                        $line .= "0,0,";
                    }

                    if (in_array("tun", $incArray)) {
                        $show = true;
                    }

                }

                // error or PED (dumb, but I didn't do it.)
                else if ($data["t"] == "E") {
                    $parts = explode(",", $data["d"]);

                    if ($parts[0] == "Ped") {
                        if (in_array("ped", $incArray)) {
                            $show = true;
                        }

                        $unscheduled = false;

                        if (str_contains($parts[1], "Unscheduled")) {
                            $unscheduled = true;
                        }

                        $phase = 0;

                        if (isset($parts[2])) {
                            $phase = substr(trim($parts[2]), -1);
                        }

                        if (str_contains($parts[1], "sent")) {
                            $line .= "Pedestrian Sent (Phase " . $phase . "),0,";
                        } else if ($unscheduled) {
                            $line .= "Unexpected Walk! (Phase " . $phase . "),0,";
                        } else {
                            $line .= "Pedestrian Called (Phase " . $phase . "),0,";
                        }

                        for ($j = 0; $j < count($activePhases); $j++) {
                            $line .= "0,0,";
                        }

                    } else {
                        $line .= "Error,\"" . $data["d"] . "\",";

                        for ($j = 0; $j < count($activePhases); $j++) {
                            $line .= "0,0,";
                        }

                        if (in_array("e", $incArray)) {
                            $show = true;
                        }

                    }
                }

                if (isset($data["p"]) && $data["p"] != "") {
                    $line .= $data["p"] . ",";
                } else {
                    $line .= "0,";
                }

                if ($show) {
                    echo "$line\"$errStr\"\r\n";
                }

            }
        }
        break;

    /**
         * Loads initial view on request
         */
    case "load":
        {
            $start = "";
            if (isset($_REQUEST['start'])) {
                $start = $_REQUEST['start'];
            }

            $end = "";
            if (isset($_REQUEST['end'])) {
                $end = $_REQUEST['end'];
            }

            if ($start == "" || $end == "") {
                die('{"error": "Start or End dates are missing"}');
            }

            // limit time range to two days for memory/performance reasons
            $startStamp = strtotime($start);
            $endStamp = strtotime($end);

            if ($endStamp - $startStamp >= 172800) {
                die('{"error":"The requested time span was too large. Please choose a timespan of <48 hours."}');
            }

            $activePhases = getActivePhases();
            $phaseNames = getPhaseNames();

            $activePhaseArr = [];
            foreach ($activePhases as $key => $value) {
                $activePhaseArr[$value]["short"] = $phaseNames[$value]["short"];
                $activePhaseArr[$value]["long"] = $phaseNames[$value]["long"];
            }

            $historyData = loadHistoryData($start, $end, $activePhases);
            $periodData = loadPeriodData($start, $end);

            $combinedData = $historyData;

            if (is_array($periodData)) {
                foreach ($periodData as $timestamp => $data) {
                    $key = floatval($timestamp);

                    while (array_key_exists(strval($key), $historyData)) {
                        $key++;
                    }

                    $combinedData[strval($key)] = $data;
                }

                ksort($combinedData);
            }

            $jsonData = [];
            $jsonData["activePhases"] = $activePhaseArr;
            $jsonData["data"] = $combinedData;

            $phaseMovementAssociation = getMovementPhaseAssociation();

            $jsonData["phaseMovementAssociation"] = $phaseMovementAssociation;
            $jsonData["timezone"] = date_default_timezone_get();

            echo json_encode($jsonData);
        }
        break;
}

function getMovementPhaseAssociation()
{
    $intersectionXML = getFile("Intersection.xml");
    $intersectionObject = simplexml_load_string($intersectionXML);

    $associationArray = [];

    foreach ($intersectionObject->Intersection->Direction as $Directions) {
        if ($Directions["name"] == "North") {
            foreach ($Directions->Phases->Phase as $Phase) {
                // thru
                if ((int) $Phase["name"] % 2 == 0) {
                    $associationArray["SouthBoundThrough"] = (int) $Phase["name"];
                }

                // left
                else {
                    $associationArray["SouthBoundLeftTurn"] = (int) $Phase["name"];
                }

            }
        } else if ($Directions["name"] == "South") {
            foreach ($Directions->Phases->Phase as $Phase) {
                // thru
                if ((int) $Phase["name"] % 2 == 0) {
                    $associationArray["NorthBoundThrough"] = (int) $Phase["name"];
                }

                // left
                else {
                    $associationArray["NorthBoundLeftTurn"] = (int) $Phase["name"];
                }

            }
        } else if ($Directions["name"] == "West") {
            foreach ($Directions->Phases->Phase as $Phase) {
                // thru
                if ((int) $Phase["name"] % 2 == 0) {
                    $associationArray["EastBoundThrough"] = (int) $Phase["name"];
                }

                // left
                else {
                    $associationArray["EastBoundLeftTurn"] = (int) $Phase["name"];
                }

            }
        } else if ($Directions["name"] == "East") {
            foreach ($Directions->Phases->Phase as $Phase) {
                // thru
                if ((int) $Phase["name"] % 2 == 0) {
                    $associationArray["WestBoundThrough"] = (int) $Phase["name"];
                }

                // left
                else {
                    $associationArray["WestBoundLeftTurn"] = (int) $Phase["name"];
                }

            }
        }
    }

    return $associationArray;
}

function loadHistoryData($startDateTime, $endDateTime, $activePhases)
{
    $startTimestamp = strtotime($startDateTime);
    $endTimestamp = strtotime($endDateTime);

    $validDates = createDateRange($startDateTime, $endDateTime, "Ymd");

    $historyFiles = @scandir(HISTORY_STATS_ROOT);

    if ($historyFiles == false) {
        die('{"error": "Unable to find any history files"}');
    }

    $historyList = [];

    foreach ($historyFiles as $file) {
        // history file
        if (str_starts_with($file, "HY_")) {
            // file DATE is within our range
            if (in_array(substr($file, 3, 8), $validDates)) {
                $historyList[] = $file;
            }

        }
    }

    $historyData = [];
    // add all valid entries to array
    foreach ($historyList as $file) {
        $handle = @fopen(HISTORY_STATS_ROOT . "/" . $file, "r");
        if ($handle) {
            while (($buffer = fgets($handle, 8096)) !== false) {
                if (str_starts_with($buffer, "Starting")) {
                    continue;
                }

                $lineParts = explode("\t", $buffer);
                $lineTime = strtotime($lineParts[0]);

                // only add if within our valid time range
                if ($lineTime >= $startTimestamp && $lineTime <= $endTimestamp) {
                    $index = $lineTime * 1000;

                    while (array_key_exists((string) $index, $historyData)) {
                        $index++;
                    }

                    $index = (string) $index;

                    if ($lineParts[1] == "S") {
                        $dataParts = explode(",", $lineParts[2]);

                        if ($dataParts[0] == "Tunnels") {
                            $historyData[$index] = ["t" => "T", "d" => $dataParts[1], "p" => ""];
                        } else {
                            $historyData[$index] = ["t" => $lineParts[1], "d" => $lineParts[2], "p" => ""];
                        }

                    } else if ($lineParts[1] == "I") {
                        $dataParts = explode(",", $lineParts[2]);

                        if ($dataParts[0] == "LightState") {
                            $element = [];
                            $element["t"] = $lineParts[1];
                            $element["md"] = [];

                            $element["md"]["v"] = $dataParts[0];
                            $element["md"]["m"] = trim($dataParts[1], " \r\n");
                            $element["md"]["pd"] = [];

                            for ($i = 2; $i < count($dataParts); $i += 3) {
                                $phaseNum = $dataParts[$i];

                                if (isset($dataParts[$i + 1]) && isset($dataParts[$i + 2])) {
                                    $queue = $dataParts[$i + 1];
                                    $wait = $dataParts[$i + 2];

                                    // if either of these contain a period identifier, we're in trouble
                                    // break out to avoid further errors
                                    if ($queue[0] == "P" || $wait[0] == "P") {
                                        break;
                                    }

                                    if (in_array($phaseNum, $activePhases)) {
                                        $error = "";

                                        $errorPos = strpos($queue, "(");

                                        if ($errorPos !== false) {
                                            // phase has an error
                                            $error = substr($queue, $errorPos);
                                            $queue = substr($queue, 0, $errorPos);
                                        }

                                        $element["md"]["pd"][$phaseNum]["queue"] = $queue;
                                        $element["md"]["pd"][$phaseNum]["wait"] = trim($wait, "\r\n");

                                        if ($error != "") {
                                            $element["md"]["pd"][$phaseNum]["error"] = $error;
                                        }

                                        ksort($element["md"]["pd"]);
                                    }
                                } else {
                                    break;
                                }

                            }

                            for ($i = count($dataParts) - 1; $i > 0; $i--) {
                                if ($dataParts[$i][0] == "P") {
                                    $element["p"] = substr($dataParts[$i], 1);
                                    $element["p"] = trim($element["p"], "\r\n");
                                    break;
                                }
                            }

                            $historyData[$index] = $element;
                        } else {
                            $historyData[$index] = ["t" => "E", "d" => trim($lineParts[2], "\r\n"), "p" => ""];
                        }

                    } else {
                        $historyData[$index] = ["t" => $lineParts[1], "d" => trim($lineParts[2], "\r\n"), "p" => ""];
                    }

                }
            }
            fclose($handle);
        }
    }

    return $historyData;
}

function loadPeriodData($startDateTime, $endDateTime)
{
    $dateRangeArray = createDateRange($startDateTime, $endDateTime, "Ymd");

    $startTimestamp = strtotime($startDateTime);
    $endTimestamp = strtotime($endDateTime);

    // remove dates with no log files
    $validDates = [];
    foreach ($dateRangeArray as $date) {
        if (file_exists(PERIOD_STATS_ROOT . "/" . "PD_" . $date . "_000000.txt")) {
            $validDates[] = $date;
        }
    }

    if (count($validDates) == 0) {
        return false;
    }

    $periodData = [];

    foreach ($validDates as $date) {
        $filename = PERIOD_STATS_ROOT . "/" . "PD_" . $date . "_000000.txt";

        $useDay = substr($date, 4, 2) . "/" . substr($date, 6, 2) . "/" . substr($date, 0, 4);

        $file = fopen($filename, "rb");

        // read lines until fgetcsv === FALSE (EOF)
        $lineNum = 0;
        while (($line = fgetcsv($file)) !== false) {
            $lineTime = strtotime("$useDay $line[0]");

            if ($lineTime < $startTimestamp || $lineTime > $endTimestamp) {
                continue;
            }

            if ($line[1] == "TUNNELLESS") {
                $periodData[(string) ($lineTime * 1000)] = ["t" => "P", "p" => -1];
            } else {
                $periodData[(string) ($lineTime * 1000)] = ["t" => "P", "p" => (int) $line[1]];
            }

        }

        fclose($file);
    }

    return $periodData;
}

function createDateRange($startDate, $endDate, $outputFormat)
{
    $startTimestamp = strtotime($startDate) - 86400;
    $endTimestamp = strtotime($endDate) + 86400;

    $dateArray = [];

    for ($date = $startTimestamp; $date <= $endTimestamp; $date += 86400) {
        $dateArray[] = date($outputFormat, $date);
    }

    return $dateArray;
}

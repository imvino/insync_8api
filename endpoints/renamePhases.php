<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . "/helpers/insyncInterface_v2.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/helpers/webdb.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/helpers/phaseHelper_v2.php";

// Authentication check (you may need to adjust this based on your actual auth system)
// if (empty($permissions["configure"])) {
//     http_response_code(403);
//     echo json_encode(["error" => "You do not have permission to access this API."]);
//     exit;
// }

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get':
        $phaseNames = getPhaseNames();
        $activePhases = getActivePhases();
        $response = [];
        foreach ($activePhases as $phase) {
            $response[$phase] = $phaseNames[$phase];
        }
        echo json_encode($response);
        break;

    case 'save':
        $phase = [];
        for ($i = 0; $i <= 8; $i++) {
            if (isset($_REQUEST["phase{$i}long"])) {
                $phase[$i]['long'] = cleanInput($_REQUEST["phase{$i}long"], 40);
            }
            if (isset($_REQUEST["phase{$i}short"])) {
                $phase[$i]['short'] = cleanInput($_REQUEST["phase{$i}short"], 6);
            }
        }

        $result = savePhaseNames($phase);
        echo json_encode(['status' => $result ? 'success' : 'error']);
        break;

    case 'reset':
        $result = setDefaultPhaseNames();
        echo json_encode(['status' => $result ? 'success' : 'error']);
        break;

    default:
        http_response_code(400);
        echo json_encode(["error" => "Invalid action specified."]);
        break;
}

function cleanInput($input, $maxLength)
{
    $input = htmlspecialchars($input);
    $input = str_replace([",", "\""], "", $input);
    return substr($input, 0, $maxLength);
}

function savePhaseNames($phase)
{
    global $permissions;
    $db = openWebDB();
    if (!$db) {
        return false;
    }

    pg_query($db, "BEGIN TRANSACTION");
    $username = $permissions['username'] ?? 'PEC';

    if (pg_query_params($db, 'DELETE FROM phase_renaming WHERE "user" = $1', [$username])) {
        foreach ($phase as $index => $names) {
            if (!empty($names['long']) || !empty($names['short'])) {
                if (!pg_query_params($db, 'INSERT INTO phase_renaming ("user", phase_number, short, long) VALUES ($1, $2, $3, $4)',
                    [$username, $index, $names['short'] ?? '', $names['long'] ?? ''])) {
                    pg_query($db, "ROLLBACK TRANSACTION");
                    pg_close($db);
                    return false;
                }
            }
        }
        pg_query($db, "COMMIT TRANSACTION");
        pg_close($db);
        return true;
    } else {
        pg_query($db, "ROLLBACK TRANSACTION");
        pg_close($db);
        return false;
    }
}

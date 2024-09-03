<?php

if (!isset($loggedIn) || !$loggedIn) {
    // this must be included on all pages to authenticate the user
    require_once $_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php";
    $permissions = authSystem::ValidateUser();
    // end

    if (empty($permissions["cameras"])) {
        die("Error: You do not have permission to access this page.");
    }

}

$action = "";
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

switch ($action) {
    case "reset":
        {
            $mode = "";
            if (isset($_REQUEST['mode'])) {
                $mode = $_REQUEST['mode'];
            }

            if ($mode == "") {
                die("Error: No mode specified.");
            }

            resetView($mode);
        }
        break;

    case "savezoom":
        {
            $mode = "";
            if (isset($_REQUEST['mode'])) {
                $mode = $_REQUEST['mode'];
            }

            $zoom = "";
            if (isset($_REQUEST['zoom'])) {
                $zoom = $_REQUEST['zoom'];
            }

            if ($mode == "") {
                die("Error: No mode specified.");
            }

            saveZoom($mode, $zoom);
        }
        break;

    case "saveprefs":
        {
            $mode = "";
            if (isset($_REQUEST['mode'])) {
                $mode = $_REQUEST['mode'];
            }

            $prefs = "";
            if (isset($_REQUEST['prefs'])) {
                $prefs = $_REQUEST['prefs'];
            }

            savePrefs($mode, $prefs);
        }
        break;
}

/**
 * Reset a users view preferences to defaults
 * @global type $permissions
 * @param type $mode view mode to reset
 */
function resetView($mode)
{
    global $permissions;
    $db = openPrefsDB();

    $username = $permissions['username'] ?? 'PEC';

    if ($result = pg_query_params($db, "DELETE FROM \"camera_view_settings\" WHERE \"user\"=$1 AND \"mode\"=$2",
        [$username, $mode])) {
        pg_close($db);
        die("Success");
    }

    pg_close($db);
    die("Error");
}

/**
 * Saves $zoom level for $mode
 * @global type $permissions
 * @param type $mode
 * @param type $zoom
 */
function saveZoom($mode, $zoom)
{
    global $permissions;
    $db = openPrefsDB();

    $username = $permissions['username'] ?? 'PEC';

    if ($result = pg_query_params($db, "SELECT \"zoom\" FROM \"camera_view_settings\" WHERE \"user\"=$1 AND \"mode\"=$2",
        [$username, $mode])) {
        $resultRow = pg_fetch_assoc($result);

        if ($resultRow == null) {
            pg_query_params($db, "INSERT INTO \"camera_view_settings\" (\"user\", \"mode\", \"prefs\", \"zoom\") VALUES($1,$2,$3,$4)",
                [$username, $mode, '', $zoom]);
        } else {
            pg_query_params($db, "UPDATE \"camera_view_settings\" SET \"zoom\"=$3 WHERE \"user\"=$1 AND \"mode\"=$2",
                [$username, $mode, $zoom]);
        }

        pg_close($db);
        die("Success");
    }

    pg_close($db);
    die("Error");
}

/**
 * Saves user $prefs for $mode
 * @global type $permissions
 * @param type $mode
 * @param type $prefs
 */
function savePrefs($mode, $prefs)
{
    global $permissions;

    $prefs = trim($prefs, ",");

    if ($mode == "") {
        die("Error: No mode specified.");
    }

    $db = openPrefsDB();

    $username = $permissions['username'] ?? 'PEC';

    if ($result = pg_query_params($db, "SELECT \"prefs\" FROM \"camera_view_settings\" WHERE \"user\"=$1 AND \"mode\"=$2",
        [$username, $mode])) {
        $resultRow = pg_fetch_assoc($result);

        if ($resultRow == null) {
            pg_query_params($db, "INSERT INTO \"camera_view_settings\" (\"user\", \"mode\", \"prefs\") VALUES($1,$2,$3)",
                [$username, $mode, $prefs]);
        } else {
            pg_query_params($db, "UPDATE \"camera_view_settings\" SET \"prefs\"=$3 WHERE \"user\"=$1 AND \"mode\"=$2",
                [$username, $mode, $prefs]);
        }
    }

    pg_close($db);
}

/**
 * Retrieves current zoom level for $mode and $username
 * @param type $username
 * @param type $mode
 * @return int
 */
function getZoomLevel($username, $mode)
{
    global $permissions;
    $db = openPrefsDB();

    $username = $permissions['username'] ?? 'PEC';

    if ($result = pg_query_params($db, "SELECT \"zoom\" FROM \"camera_view_settings\" WHERE \"user\"=$1 AND \"mode\"=$2",
        [$username, $mode])) {
        $resultRow = pg_fetch_assoc($result);

        if ($resultRow == null) {
            $zoom = 0;
        } else {
            $zoom = 0 + $resultRow['zoom'];
        }

        pg_close($db);
        return $zoom;
    }

    pg_close($db);
    return 0;
}

/**
 * Retrieves the $mode preferences for $username
 * @param type $username
 * @param type $mode
 * @return boolean
 */
function getPrefs($username, $mode)
{
    global $permissions;
    $db = openPrefsDB();

    $username = $permissions['username'] ?? 'PEC';

    if ($result = pg_query_params($db, "SELECT \"prefs\" FROM \"camera_view_settings\" WHERE \"user\"=$1 AND \"mode\"=$2",
        [$username, $mode])) {
        $resultRow = pg_fetch_assoc($result);

        if ($resultRow == null) {
            $prefs = false;
        } else {
            $prefs = $resultRow['prefs'];
        }

        pg_close($db);
        return $prefs;
    }

    pg_close($db);
    return false;
}

/**
 * Helper function to manage database connection
 * @return \mysqli
 */
function openPrefsDB()
{
    $db = pg_connect('host=127.0.0.1 dbname=insync user=web password=qey8xUf9 connect_timeout=30')
    or die("Error: Could not connect to database: " . $db->connect_error);

    return $db;
}

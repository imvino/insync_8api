<?php
require_once(__DIR__ . "/helpers/constants.php");

// set this variable to true, so insyncInterface doesnt try to authenticate
$loggedIn = true;
require_once(SITE_DOCUMENT_ROOT . "helpers/insyncInterface.php");

header('Content-Type: application/xml');
header("Cache-Control: no-store, no-cache, must-revalidate");

$insync = new InSyncInterface();
echo $insync->getStatus();

?>

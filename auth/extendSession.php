<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");

if(!authSystem::prepareDB())
{
    header('HTTP/1.1 503 Service Unavailable');
    exit("DB Down");
}

$permissions = authSystem::ValidateUser();
// end

?>

Session extended.

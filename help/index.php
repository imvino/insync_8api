<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "../auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = "Help";
$breadCrumb = "<h1>Help</h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<!-- END HEADER -->
HEAD_WRAP
;

include("../includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["web"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("../includes/footer.php");
    exit;
}

?>

<div class="row">
    <p>Help is not currently available.</p>
</div>

<?php

include("../includes/footer.php");

?>
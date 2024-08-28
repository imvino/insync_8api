<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end
?>

<html>
	<body>
		This feature has been disabled
	</body>
</html>

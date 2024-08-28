<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end
?>

<html>
	<body>
		<form action="/helpers/databaseInterface.php?action=putfile" method="post" enctype="multipart/form-data">
		<label for="file">Filename:</label>
		<input type="file" name="contents[]" id="contents[]"><br>
		<input type="submit" name="submit" value="Submit">
		</form>
	</body>
</html>

<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end
?>

<html>
	<body>
		<form action="/helpers/propagationHelper.php?action=putfile" method="post" enctype="multipart/form-data">
			Target file name & path (Ex C:\InSync\WebUI\www\test.php) <input type="text" name="target"><br />
			<label for="file">File:</label>
			<input type="file" name="file" id="file"><br>
			<input type="submit" name="submit" value="Submit">
		</form>
	</body>
</html>

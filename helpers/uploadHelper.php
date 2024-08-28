<?php
set_time_limit(0);
?>
<!DOCTYPE html>
<html lang="en-US">
	<head>
		<title>Upload Files - InSync</title>
		<link rel="shortcut icon" href="/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="/css/ui-lightness/jquery-ui-1.9.2.custom.min.css">
		<script language="javascript" type="text/javascript" src="/js/jquery/jquery-1.9.1.js"></script>
		<script language="javascript" type="text/javascript" src="/js/jquery/jquery-ui-1.9.2.custom.min.js"></script>
		<script language="javascript" type="text/javascript" src="/js/manualControl.js"></script>
	</head>
	<body>
<?php
if(isset($_REQUEST['submit']))
{
	$name = $_FILES["file"]["name"];
	$tmpName = addslashes($_FILES["file"]["tmp_name"]);
	
	echo "<script type='text/javascript'>window.opener.uploadComplete('$name', '$tmpName');</script>";
	exit;
}
else
{
?>
		<form action="uploadHelper.php" method="post" enctype="multipart/form-data">
			<label for="file">File:</label>
			<input type="file" name="file" id="file">
			<input type="submit" name="submit" value="Submit">
		</form>
<?php
}
?>
	</body>
</html>
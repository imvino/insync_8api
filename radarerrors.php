<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": RadarErrors Viewer";
$breadCrumb = "<h1>Radar <small>Radar Errors</small></h1>";
$menuCategory = "reports";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/history.css"/>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.stickytableheaders.js"></script> 
<script language="javascript" type="text/javascript" src="/js/radarerrors.js"></script> 
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.timepicker.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.keysequencedetector.js"></script>
<script language="javascript" type="text/javascript" src="/js/plugins/date.js"></script>
<!-- END HEADER -->
HEAD_WRAP;

if($permissions["username"] == "kiosk")
    include("includes/header_lite.php");
else
    include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["reports"]) || $permissions["username"] != "PEC")
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

?>

<div class="row">
	<div class="inline-block">
		<label for="startDateTime">Start Date/Time:</label>
		<input type="text" id="startDateTime" class="input-large input-purple" name="startDateTime" value="<?php if (!isset($_REQUEST['startDateTime'])) echo date("m/d/Y") . " 12:00 AM";
else echo $_REQUEST['startDateTime']; ?>" placeholder="Start Date/Time"/> 
	</div>
	<div class="inline-block">
		<label for="endDateTime">End Date/Time:</label>
		<input type="text" id="endDateTime" class="input-large input-purple" name="endDateTime" value="<?php if (!isset($_REQUEST['endDateTime'])) echo date("m/d/Y") . " 11:59 PM";
else echo $_REQUEST['endDateTime']; ?>" placeholder="End Date/Time"/> 
	</div>
</div>

<div class="row section" style="position:relative;z-index:7;">
	<label for="entertext">Enter Text To Filter On And Click Load Errors:</label>
	<input type="text" id="entertext" name="entertext" value="<?php if (!isset($_REQUEST['entertext']));
else echo $_REQUEST['entertext']; ?>" placeholder="Eg: connection, status, serial, unresponsive, east bound, north bound, etc." size="100"><br><br>
</div>	

<div class="row">
	<button id="submit" class="btn btn-default green">Load Errors</button>
<div id="notificationContents" class="row panel"></div>

<?php
if($permissions["username"] == "kiosk")
    include("includes/footer_lite.php");
else
    include("includes/footer.php")
?>
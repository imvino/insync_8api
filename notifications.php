<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Notifications Viewer";
$breadCrumb = "<h1>Notifications <small>Email Notifications</small></h1>";
$menuCategory = "reports";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/history.css"/>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.stickytableheaders.js"></script> 
<script language="javascript" type="text/javascript" src="/js/notifications.js"></script> 
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

//if(empty($permissions["reports"]))
//{
//	echo "<h3>Error: You do not have permission to access this page.</h3>";
//    include("includes/footer.php");
//    exit;
//}

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

<div class="row">
	<button id="submit" class="btn btn-default green">Load Notifications</button>
<?php
        if($permissions["username"] != "kiosk") {
?>
        <button id="download" class="btn btn-default">Download Notifications</button>
<?php
        }
?>
	<button id="submitactive" class="btn btn-default green">Load All Active Notifications</button>
	<button id="downloadactive" class="btn btn-default">Download All Active Notifications</button>
</div>

<div id="notificationContents" class="row panel"></div>

<!--
<div id="dialog-confirm-hang" title="Warning">
    <div class="warning">
        <p><strong>WARNING:</strong><br/>Hiding or showing Phase Volumes when a large number of items are displayed can cause your browser to hang for several minutes while processing.</p>
    </div>
	<p>Do you want to continue?</p>
</div>
-->

<?php
if($permissions["username"] == "kiosk")
    include("includes/footer_lite.php");
else
    include("includes/footer.php")
?>
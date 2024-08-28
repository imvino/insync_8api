<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Single Camera View";
$breadCrumb = "<h1>Views <small>Single Camera View</small></h1>";
$menuCategory = "views";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/cameraViews.css"/>
<script language="javascript" type="text/javascript" src="js/refreshView.js"></script>
<script language="javascript" type="text/javascript" src="js/cameraViewSingle.js"></script>

<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");


////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["cameras"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

?>

<div class="panel row" id="layout-content">
	<div id="camera-container" class="camera-container">
<?php
require_once("helpers/databaseInterface.php");
require_once("helpers/libraries/intersectionUtil.php");
	
$intersectionUtil = new IntersectionUtil();

if($intersectionUtil !== FALSE)
{
    $streamArray = $intersectionUtil->getCameraNames();
    
    if (count($streamArray) > 0 ) {
        echo '<img baseURL="helpers/insyncInterface.php?action=getImage&viewCamera=' . $streamArray[0] . '" class="cameraView" width="320" height="240" enablerefresh="true" />';
    } else {
        echo "Error: No cameras defined.";
    }
}
else
    echo "Error: Unable to load Intersection.xml";
?>
		</div>
		<div class="row section" style="position:relative;z-index:8;">
			<div class="inline-block">
				<label for="camera">Camera</label>
				<select id="camera" class="chosen-select single-select span3">
<?php

$count = 0;

foreach($streamArray as $camera)
{
	if($count == 0)
		echo "<option value='$camera' selected='selected'>$camera</option>";
	else
		echo "<option value='$camera'>$camera</option>";

	$count++;
}
?>
				</select>
		</div>
		<div class="inline-block">
			<label for="speed">Refresh</label>
			<select id="speed" class="chosen-select single-select span3">
                <option value="200">Fastest</option>
                <option value="1000">Every Second</option>
                <option value="2000" selected="selected">Every 2 Seconds</option>
                <option value="5000">Every 5 Seconds</option>
                <option value="30000">Every 30 Seconds</option>
				<option value="60000">Every 60 Seconds</option>
			</select>
		</div>
		<div class="inline-block">
			<label for="filter">Filter</label>
			<select id="filter" class="chosen-select single-select span3">
				<option value="normal" selected="selected">Normal</option>
				<option value="raw">Unprocessed</option>
				<option value="edge">Edge</option>
				<option value="foreground">Foreground</option>
				<option value="background">Background</option>
			</select>
		</div>
	</div>
	<div class="row section" style="position:relative;z-index:6;">
            
<?php
if(isset($permissions["cameracontrols"]))
{
    ?>
		<button id="reboot" class="btn btn-default">Reboot Camera</button> 
		<button id="background" class="btn btn-default">Grab Background</button>
		<button id="emergency" class="btn btn-default">Toggle Emergency</button> 
		<button id="fog" class="btn btn-default">Override Fog Mode</button>
    <?php
        if(isset($permissions["username"]) && $permissions["username"] == "PEC")
            echo '<button id="record" class="btn btn-default">Record Video</button>';
}
?>
	</div>
    
    <div id="cameraStatus">
        <h3><em class="icon-wrapper"><em class="icon-default"></em><em class="icon-hover"></em><em class="icon-active"></em><em class="icon-active-hover"></em></em>Camera Status</h3>
        <div><p>Waiting for status from camera...</p></div>
    </div>
</div>

<script type="text/javascript">		
	$(function() {initScript();});
</script>

<?php
include("includes/footer.php");
?>
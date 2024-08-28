<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Multi Camera View";
$breadCrumb = "<h1>Views <small>Multi Camera View</small></h1>";
$hideHeader= true;
$menuCategory = "views";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/cameraViews.css"/>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.fixedsortable.js"></script>
<script language="javascript" type="text/javascript" src="/js/cameraViewMulti.js"></script>
<script language="javascript" type="text/javascript" src="/js/refreshView.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.ui.touch-punch.min.js"></script>

<script language="javascript" type="text/javascript" src="js/manualControls.js"></script>

<!-- END HEADER -->
HEAD_WRAP
;

$fullscreen = false;
if(isset($_REQUEST["fullscreen"]))
{
    if($_REQUEST["fullscreen"] == "true")
        $fullscreen = true;
}

$cameraView = "";
if(isset($_REQUEST["cameraView"]))
	$cameraView = $_REQUEST["cameraView"];

if($cameraView == "")
{
	if(isset($_COOKIE['viewMode']))
	{
		if($_COOKIE['viewMode'] != "two-column" && $_COOKIE['viewMode'] != "three-column" && $_COOKIE['viewMode'] != "four-column" && $_COOKIE['viewMode'] != "wide-quad" && $_COOKIE['viewMode'] != "advanced-cartoon")
			$cameraView = 'two-column';
		else
			$cameraView = $_COOKIE['viewMode'];
	}
	else 
		$cameraView = "two-column";
}

$image_padding = 10;
$image_columns = 0;
$image_extra = 40;

$refreshRate = "2000";
if(isset($_REQUEST["refreshRate"]))
	$refreshRate = $_REQUEST["refreshRate"];


if(!$fullscreen)
    include("includes/header.php");
else
    include("includes/header_lite.php");


////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["cameras"]))
{
?>
    <h3>Error: You do not have permission to access this page.</h3>
<?php
    include("includes/footer.php");
    exit;
}

$loggedIn = true;
require_once("helpers/databaseInterface.php");
require_once("helpers/cameraViewHelper.php");
require_once("helpers/libraries/intersectionUtil.php");
	
$intersectionUtil = new IntersectionUtil();

$camera = $intersectionUtil->getCameras();

function CameraLink($name) {
    global $camera;
    if (empty($name)) {
        return '/img/camera-placeholder.png';
    } else if (isset($camera[$name])) {
        return $camera[$name]->url;
    } else {
        return '/img/no-camera.png';
    }
}

function CameraName($name) {
    global $camera;
    if (empty($name)) {
        return 'placeholder';
    } else if (isset($camera[$name])) {
        return $name;
    } else {
        return 'placeholder';
    }
}

function CanCameraRefresh($name) {
    global $camera;
    if (empty($name)) {
        return false;
    } else if (isset($camera[$name])) {
        return true;
    } else {
        return false;
    }
}

$streamArray = $intersectionUtil->getCameraNames();

// set image sizes based on user zoom level
$zoom = getZoomLevel($permissions['username'], $cameraView);

$imgWidth = ($zoom*40) + 320;
$imgHeight = ($zoom*30) + 240;

?>

<div <?=$fullscreen ? '' : 'id="containerDiv"'?>>
	<div id="layout-content">
            <div class="view-controls-right">
<?php

	if (!$fullscreen) {
		if (isset($permissions["manual"])) {
?>
				<div class="inline-block manual-ctrls">
					<label>&nbsp;</label>
					<button id="manual" class="btn btn-outline manual-ctrls-btn">Manual Controls</button>
				</div>
<?php
	    }
	 }
?>
	            <div class="inline-block ie7-pause">
					<label>&nbsp;</label>
					<button id="pauseButton" class="btn btn-default icon-only pause-btn"><span class="icon-default"></span></button> 
				</div>
<?php
	if (!$fullscreen) {
        if($cameraView !== "advanced-cartoon")
        {
?>
				<div class="inline-block">
					<label>&nbsp;</label>
					<button id="zoom-in" class="btn btn-default icon-only zoomin-btn zoom-controls"><span class="icon-default"></span></button>
				</div>
				<div class="inline-block">
					<label>&nbsp;</label>
					<button id="zoom-out" class="btn btn-default icon-only zoomout-btn zoom-controls" <?= ($zoom == 0) ? " disabled" : "" ?>><span class="icon-default"></span></button>
				</div>
<?php
        }
?>
				<div class="inline-block">
					<label>&nbsp;</label>
        			<button id="fullscreen" class="btn btn-default icon-only fullscreen-btn"><span class="icon-default"></span></button>
        		</div>
<?php
	}
?>
			</div>
		<div class="section view-controls">
			<div class="inline-block">
				<label for="layouts">View Styles</label>
				<select id="layouts" class="chosen-alt single-select">
					<option value="two-column" <?php if($cameraView == "two-column") echo "selected='selected'"; ?>>Standard</option>
                    <option value="wide-quad" <?= ($cameraView == "wide-quad") ? "selected='selected'" : "" ?>> <?= (strcasecmp($permissions["username"], "pec") == 0) ? 'Diamond 4 Cam View' : 'Diamond'?></option>
					<option value="three-column" <?php if($cameraView == "three-column") echo "selected='selected'"; ?>>3 Column</option>
					<option value="four-column" <?php if($cameraView == "four-column") echo "selected='selected'"; ?>>4 Column</option>
					<option value="advanced-cartoon" <?php if($cameraView == "advanced-cartoon") echo "selected='selected'"; ?>>Cartoon View</option>
				</select>
			</div>
			<div class="inline-block">
				<label for="refresh">Refresh</label>
				<select id="refresh" class="chosen-alt single-select">
					<option value="200" <?php if($refreshRate == "200") echo "selected='selected'"; ?>>Fastest</option>
					<option value="500" <?php if($refreshRate == "500") echo "selected='selected'"; ?>>Every 1/2 Second</option>
					<option value="1000" <?php if($refreshRate == "1000") echo "selected='selected'"; ?>>Every Second</option>
					<option value="2000" <?php if($refreshRate == "2000") echo "selected='selected'"; ?>>Every 2 Seconds</option>
					<option value="5000" <?php if($refreshRate == "5000") echo "selected='selected'"; ?>>Every 5 Seconds</option>
					<option value="30000" <?php if($refreshRate == "30000") echo "selected='selected'"; ?>>Every 30 Seconds</option>
					<option value="60000" <?php if($refreshRate == "60000") echo "selected='selected'"; ?>>Every 60 Seconds</option>
				</select>
			</div>
<?php
    if($cameraView !== "advanced-cartoon")
    {
?>
            <div class="inline-block">
                <label for="filter">Filter</label>
                <select id="filter" class="chosen-alt single-select">
                        <option value="normal">Normal</option>
                        <option value="raw">Unprocessed</option>
                        <option value="edge">Edge</option>
                        <option value="foreground">Foreground</option>
                        <option value="background">Background</option>
                </select>
            </div>
<?php
    }
?>
		</div>
        
<?php

if ($cameraView == "wide-quad") {
        $image_columns = 3;
        $image_extra = 100;
		$divWidth = (($imgWidth + $image_padding) * $image_columns) + $image_extra;
		
		$result = explode(",", getPrefs($permissions['username'], $cameraView), -1);

		if (count($result) == 0) 
		{
			$result = array_intersect(['North Bound', 'East Bound', 'South Bound', 'West Bound'], $streamArray);
		}

		$result = array_merge($result, array_diff($streamArray, $result));

		$ebName = "";
		$sbName = "";	
		$nbName = "";
		$wbName = "";	

		if (count($result) > 0)
		{
			for ($r = 0; $r < count($result); $r++)
			{
				$name = $result[$r];
				
				// use actual camera names instead of hard coded standard names. Select only one per approach.
				if (str_contains($name, "East Bound") && $ebName === "")
					$ebName = $name;
				else if (str_contains($name, "South Bound") && $sbName === "")
					$sbName = $name;
				else if (str_contains($name, "North Bound") && $nbName === "")
					$nbName = $name;			
				else if (str_contains($name, "West Bound")  && $wbName === "")
					$wbName = $name;
			}
		}
		
		// Doing this to show 'No Camera' on the quad camera view when no camera is configured for an approach
		if ($ebName === "")
			$ebName = "East Bound";
		if ($sbName === "")
			$sbName = "South Bound";
		if ($nbName === "")
			$nbName = "North Bound";
		if ($wbName === "")
			$wbName = "West Bound";
	
	// east bound
?>
	<div id="camera_scrollbox">
		<div id="view_container">
            <table>
                <tr>
                    <td>
                        <img <?= CanCameraRefresh($ebName) ? "baseURL" : "src" ?>="<?=CameraLink($ebName)?>" width="<?=$imgWidth?>" height="<?=$imgHeight?>" name="<?=CameraName($ebName)?>" enablerefresh="<?=CanCameraRefresh($ebName) ? 'true' : 'false'?>" />
                    </td>
                    <td>
<?php
    // north & south bound
?>
                        <img <?= CanCameraRefresh($sbName) ? "baseURL" : "src" ?>="<?=CameraLink($sbName)?>" width="<?=$imgWidth?>" height="<?=$imgHeight?>" name="<?=CameraName($sbName)?>" enablerefresh="<?=CanCameraRefresh($sbName) ? 'true' : 'false'?>" />
                        <br/>
                        <img <?= CanCameraRefresh($nbName) ? "baseURL" : "src" ?>="<?=CameraLink($nbName)?>" width="<?=$imgWidth?>" height="<?=$imgHeight?>" name="<?=CameraName($nbName)?>" enablerefresh="<?=CanCameraRefresh($nbName) ? 'true' : 'false'?>" />
                    </td>
                    <td>
<?php
    // west bound
?>
                        <img <?= CanCameraRefresh($wbName) ? "baseURL" : "src" ?>="<?=CameraLink($wbName)?>" width="<?=$imgWidth?>" height="<?=$imgHeight?>" name="<?=CameraName($wbName)?>" enablerefresh="<?=CanCameraRefresh($wbName) ? 'true' : 'false'?>" />
                    </td>
                </tr>
            </table>
		</div>
    </div>
<?php
} else if ($cameraView == "two-column" || $cameraView == "three-column" || $cameraView == "four-column") {
	$result = explode(",", getPrefs($permissions['username'], $cameraView), -1);

    // If no user preference for two columne show N/E/S/W and all other cameras by default
    // emulates quad view
    if (count($result) == 0 && $cameraView == "two-column") {
        $result = array_intersect(['North Bound', 'East Bound', 'South Bound', 'West Bound'], $streamArray);
    }

    // Add any newly added cameras
    $result = array_merge($result, array_diff($streamArray, $result));

	$image_columns = 2;
	
	if ($cameraView == "three-column")
		$image_columns = 3;
	
	if ($cameraView == "four-column")
		$image_columns = 4;
	
	$streamCount = count($streamArray);
	
	$rows = $streamCount / $image_columns;
	
	if ($rows < 1)
		$rows = 1;
	
	$divWidth = (($imgWidth + $image_padding) * $image_columns) + $image_extra;
	
	echo '<div id="camera_scrollbox"><div style="width: ' . $divWidth . 'px;" id="view_container"><ul id="original_items">';
	
        $rows = count($result) / $image_columns;
        
        for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $image_columns; $c++) {
                        $name = array_shift($result);
                        echo '<li><img ' . (CanCameraRefresh($name) ? "baseURL" : "src") . '="' .CameraLink($name). '" width="' .$imgWidth. '" height="' .$imgHeight. '" name="' .CameraName($name). '" enablerefresh="' . (CanCameraRefresh($name) ? 'true' : 'false') . '" /></li>';
                }
        }
?>
			</ul>
			<ul id="cloned_items" class="ui-helper-hidden"></ul>
			<div class="section">
				<div id="layout_control" class="layout-control">
					<div class="inline-block"><button class="btn btn-default" onclick="addRow(<?=$image_columns?>);">Add Row</button></div>
					<div class="inline-block"><button class="btn btn-default" onclick="deleteRow(<?=$image_columns?>);">Delete Row</button></div>
					<div class="inline-block"><button class="btn btn-default" onclick="resetLayout();">Reset Layout</button></div>
				</div>
			</div>
		</div>
	</div>
	
	
	
<?php
} else if($cameraView == "advanced-cartoon") {
	echo '<img id="view_container" baseURL="helpers/insyncInterface.php?action=getImage&viewCamera=cartoon&mode=advanced" width="640" height="640" enablerefresh="true" style="display:block;margin:0 auto 20px;"/>';
}
?>
		<div id="insyncStatus">
			<h3><em class="icon-wrapper"><em class="icon-default"></em><em class="icon-hover"></em><em class="icon-active"></em><em class="icon-active-hover"></em></em>InSync Status</h3>
			<div><p>Waiting for status from InSync...</p></div>
		</div>
		<div id="processorStatus">
			<h3><em class="icon-wrapper"><em class="icon-default"></em><em class="icon-hover"></em><em class="icon-active"></em><em class="icon-active-hover"></em></em>Processor Status</h3>
			<div><p>Waiting for status from processor...</p></div>
		</div>
	</div>
</div>
<?php
if (isset($permissions["manual"])) {
?>
    <div id="dialog-manual-controls" title="Manual Controls" class="ui-helper-hidden">
        <p>Loading controls, please wait...</p>
    </div>
<?php
}
?>

<div id="dialog-manual-controls-override" title="Manual Controls" class="ui-helper-hidden">
	<p>There is another user currently logged in to the Manual Controls page, and only one user is allowed control at a time.</p>
	<p>Do you want to take ownership of the manual controls?</p>
</div>

<script type="text/javascript">		
	$(function() {initScript(<?php echo $refreshRate . ",'" . $cameraView. "','" . $fullscreen . "'," . $zoom; ?>);});

	$(function() {
		$('.chosen-alt').chosen({
			disable_search: true,
			single_backstroke_delete: false,
			inherit_select_classes: true
		});

		$('.ui-tabs .ui-tabs-nav li:last-child').borderRadius('0 3px 0 0');
	});

        var image_padding = <?= $image_padding ?>;
        var image_columns = <?= $image_columns ?>;
        var image_extra = <?= $image_extra ?>;
</script>

<?php
if(!$fullscreen)
    include("includes/footer.php");
else
    include("includes/footer_lite.php");
?>

<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = "Multiple Camera View";
$breadCrumb = "<h1>Views <small>Multiple Cameras</small></h1>";
$menuCategory = "views";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/cameraViewsKiosk.css"/>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.fixedsortable.js"></script>
<script language="javascript" type="text/javascript" src="/js/cameraViewMulti.js"></script>
<script language="javascript" type="text/javascript" src="/js/refreshView.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.ui.touch-punch.min.js"></script>

<!-- END HEADER -->
HEAD_WRAP
;

$cameraView = "two-column";
if(isset($_REQUEST["cameraView"]))
	$cameraView = $_REQUEST["cameraView"];
	
if($cameraView == "")
{
	if(isset($_COOKIE['viewMode']))
		$cameraView = $_COOKIE['viewMode'];
	else 
		$cameraView = "two-column";
}

$image_padding = 10;
$image_columns = 0;
$image_extra = 40;

$refreshRate = "1000";
if(isset($_REQUEST["refreshRate"]))
	$refreshRate = $_REQUEST["refreshRate"];

$kiosk = false;

include("includes/header_lite.php");
$kiosk = true;


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

$imgWidth = 320;
$imgHeight = 240;

?>

<div id="containerDiv">

        <div class="tab-control-overlay right">

		<span class="tabDiv">
	        <ul class="sideTab">
	            <li class="<?= ($cameraView !== "advanced-cartoon") ? "selectedTab" : "backTab"?>"><a href="<?= ($cameraView !== "advanced-cartoon") ? "#tab_body" : "/cameraViewMultiKiosk.php"?>">Camera View</a></li>
	            <li class="<?= ($cameraView === "advanced-cartoon") ? "selectedTab" : "backTab"?>"><a href="<?= $cameraView === "advanced-cartoon" ? "#tab_body" : "/cameraViewMultiKiosk.php?cameraView=advanced-cartoon" ?>">Cartoon View</a></li>
	        </ul>
		</span>
		
        </div>
		<div id="tabs" class="row" style="display:none;">
	        <div id="tab_body">
	
				<span class="column" id="camera_scrollbox" style="padding-right: 0px; padding-left: 0px; padding-bottom: 0px">
<?php

if($cameraView == "two-column")
{	
	$result = explode(",", getPrefs($permissions['username'], $cameraView), -1);

        // If no user preference show N/E/S/W and all other cameras by default
        if (count($result) == 0) {
            $result = array_intersect(['North Bound', 'East Bound', 'South Bound', 'West Bound'], $streamArray);
        }

        // Add any newly added cameras or non-standard cameras
        $result = array_merge($result, array_diff($streamArray, $result));

	$image_columns = 2;
	$streamCount = count($streamArray);
	$rows = $streamCount / $image_columns;
	if($rows < 1)
		$rows = 1;

	if ($rows > 2)
	{
		// scale to fit Kiosk screen
		// It naturally holds two images at full size, so use that as the base
		$scale = (($imgHeight+$image_padding+10)*2) / ($imgHeight*$rows);
		$imgHeight *= $scale;
		$imgWidth *= $scale;
	}
	
	$divWidth = (($imgWidth + $image_padding) * $image_columns) + $image_extra;
	
	echo '<div style="width: ' . $divWidth . 'px;" id="view_container"><ul id="original_items">';

        $rows = count($result) / $image_columns;
        
        for($r = 0; $r < $rows; $r++)
        {
                for($c = 0; $c < $image_columns; $c++)
                {
                        $name = array_shift($result);
?>
                        <li><img class="cameraImage" <?= CanCameraRefresh($name) ? "baseURL" : "src" ?>="<?=CameraLink($name)?>" width="<?=$imgWidth?>" height="<?=$imgHeight?>" name="<?=CameraName($name)?>" enablerefresh="<?=CanCameraRefresh($name) ? 'true' : 'false'?>" /></li>
<?php
                }
        }
}
else if($cameraView == "advanced-cartoon")
{
	echo '<div id="view_container" style="width: 640px; margin: 0; padding: 0;"><ul style="margin: 1px 0 0 1px; padding: 0;">';

	echo '<li><img baseURL="helpers/insyncInterface.php?action=getImage&viewCamera=cartoon&mode=advanced" width="640" height="540" enablerefresh="true" style="display:block;margin:0;"/></li>';
}
?>
						</ul>
					</div>

				</span>

				<span class="column" style="position: fixed; left: 650px; width: 285px; font-size: 14pt"">
				
<?php if($cameraView == "advanced-cartoon")	{ ?>
						<p>Cartoon View shows all of the following:
						
						<ul><li>Light status</li>
							<li>Video detection zones<br/>(blue, filled when occupied)
							<li>Loop trigger status<br/>(orange when triggered, a padlock when locked or with a yellow outline when not triggered or locked)</li>
							<li>Phase numbers<br/>(blue when receiving a detector call, magenta for manual call, and white for no call)</li>
							<li>Wait time (red)</li>
							<li>Queue (white)</li>
							<li>Adjusted queue (yellow)</li>
							<li>Intersection name</li>
							<li>InSync configuration name</li>
							<li>Operational mode (detector or adaptive)</li>
							<li>Pedestrian phase information</li>
						</ul>
						

<?php } else {  ?>
						<span id="multi_camera_text">
							<p>Click a camera view to enlarge.</p>
							<p>&nbsp;</p>
							<p>Drag a view to reposition on the grid.</p>
						</span>
						
						<span id="single_camera_text" style="display:none; margin-bottom: 3em;">
							<p style="margin-bottom: 20px;">Use Grab Background to capture a background when no cars are in the view.</p>
							<p style="margin-bottom: 20px;">Use Clear Background when you cannot get a clear view or if you accidentally captured a car in the background.</p>
							<p>Click the view to return to a grid.</p>
						</span>
						
						<div style="display: inline; position: absolute; top: 24px;">
						<label style="margin-top: 15em;" for="filter">Filter</label>
						<select id="filter" class="chosen-alt single-select span3" style="width: 20em;">
								<option value="normal">Normal</option>
								<option value="raw">Unprocessed</option>
								<option value="edge">Edge</option>
								<option value="foreground">Foreground</option>
								<option value="background">Background</option>
						</select>
					
						<p>
						<button class="btn btn-default" style="margin-top: 13em; width: 20em;" onclick="resetLayout();">Reset Layout</button>
						</div>
						
<?php } ?>
				</span>

				<span id="single_camera_controls" style="position: fixed; left: 5px; top:326px; width: 650px; font-size: 14pt; display: none">
				<center>
						<button class="btn btn-default" style="margin-top: 13em;" onclick="grabActiveBackground();">Grab Background</button>
						<button class="btn btn-default" style="margin-top: 13em;" onclick="clearActiveBackground();">Clear Background</button>
						<button class="btn btn-default" style="margin-top: 13em;" onclick="setEmergencyMode();">Emergency Mode</button>
				</center>
				</span>
	
			</div>	
    </div>
    </div>

<script type="text/javascript">		
	$(function() {initScript(<?php echo $refreshRate . ",'" . $cameraView. "','" . false . "', 0"; ?>, customClickHandler);});

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
	var active_single_view_camera = "";

	function grabActiveBackground()
	{
		$.post("helpers/cameraioInterface.php", {action: "background", camera: active_single_view_camera});
	}

	function clearActiveBackground()
	{
		$.post("helpers/cameraioInterface.php", {action: "clear_background", camera: active_single_view_camera});
	}

	function setEmergencyMode()
	{
		$.post("helpers/cameraioInterface.php", {action: "emergency", camera: active_single_view_camera});
	}

	function customClickHandler(elem)
	{
		var clickedImage = $(elem);

		var w = clickedImage.attr("width");
		var bZooming = false;

		if (w <= 320)		// Unzoomed camera views are 320 or below (smaller if scaled to fit more than 4 views on the screen)
		{
			// Start zooming on the clicked item
			clickedImage.attr("originalWidth", clickedImage.attr("width"));
			clickedImage.attr("originalHeight", clickedImage.attr("height"));
		
			clickedImage.attr("width", 320*2);
			clickedImage.attr("height", 240*2);
			
			bZooming = true;
			active_single_view_camera = clickedImage.attr("name");
		}
		else
		{
			// Unzoom the clicked item
			clickedImage.attr("width", clickedImage.attr("originalWidth"));
			clickedImage.attr("height", clickedImage.attr("originalHeight"));
			
			bZooming = false;
			active_single_view_camera = "";
		}

		// Hide or Show all other images...
		var aElem = document.getElementsByClassName("cameraImage");
		for (var i = 0; i < aElem.length; ++i) {
			var item = aElem[i];  
		
			if (item.height != 240*2)
			{
				if (bZooming)
					item.style.display = "none";
				else
					item.style.display = "block";
			}
		}
		
		if (bZooming)
		{
			$("#single_camera_controls").show();
			$("#single_camera_text").show();
			$("#multi_camera_text").hide();
		}
		else 
		{
			$("#single_camera_controls").hide();
			$("#single_camera_text").hide();
			$("#multi_camera_text").show();
		}
	}

</script>

<?php
include("includes/footer_lite.php");
?>

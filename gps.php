<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": GPS Coordinates";
$breadCrumb = "<h1>Settings <small>GPS Coordinates</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/gpsCoordinates.css"/>\t\t
<script type="text/javascript" src="/js/jquery/jquery.mousewheel.js"></script> 
<script type="text/javascript" src="/js/jquery/jquery.mapbox.js"></script> 
<script type="text/javascript" src="/js/gps.js"></script>

<!-- END HEADER -->
HEAD_WRAP
;

if($permissions["username"] == "kiosk")
    include("includes/header_lite.php");
else
    include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["configure"])) 
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}
?>

<div class="row">
	<div class="inline-block">
		<label for="lat">Latitude</label>
		<input type="text" id="lat" class="input-green input-large" placeholder="Latitude"/>
	</div>
	<div class="inline-block">
		<label for="lon">Longitude</label>
		<input type="text" id="lon" class="input-green input-large" placeholder="Longitude"/>
	</div>
	<div class="inline-block">
		<label>&nbsp;</label>
		<button id="save" class="btn btn-default green">Save</button>
		<button id="cancel" class="btn btn-default">Cancel</button>
	</div>
</div>
<div id="error" class="row">
</div>
<div class="row section">
	<div id="viewport">
		<div style="background:url(img/map/worldmap1.png) no-repeat;width:1000px;height:1000px;"> 
			<!--top level map content goes here--> 
		</div> 
		<div style="height:2000px;width:2000px;"> 
			<img src="img/map/worldmap2.png" alt=""/> 
			<div class="mapcontent"> 
			</div> 
		</div> 
		<div style="height:3000px;width:3000px;"> 
			<img src="img/map/worldmap3.png" alt=""/> 
			<div class="mapcontent"> 
			</div> 
		</div> 
		<div style="height:4278px;width:4278px;"> 
			<img src="img/map/worldmap4.png" alt=""/> 
			<div class="mapcontent"> 
			</div> 
		</div> 
		<div class="marker"><img src="/img/map/map-marker.png"/></div>
	</div>
</div>

<script type="text/javascript">
	$(function() {initScripts();});
</script>

<?php
if($permissions["username"] == "kiosk")
    include("includes/footer_lite.php");
else
    include("includes/footer.php");
?>
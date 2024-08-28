<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Daily Summary";
$breadCrumb = "<h1>Reports <small>Daily Summary</small></h1>";
$menuCategory = "reports";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/reporting.css"/>
<script language="javascript" type="text/javascript" src="/js/dailySummary.js"></script>
<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="/js/flot/excanvas.min.js"></script><![endif]-->
<script type="text/javascript" src="js/flot/jquery.flot.js"></script>
<script type="text/javascript" src="js/flot/jquery.flot.time.min.js"></script>
<script type="text/javascript" src="js/flot/jquery.flot.resize.min.js"></script>
<script type="text/javascript" src="js/flot/jquery.flot.navigate.min.js"></script>
<script type="text/javascript" src="/js/plugins/date.js"></script>

<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");
require_once("helpers/FileIOHelper.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["reports"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

// Get the system type (0=Insync, 1=Hawkeye etc.)
$systemConfigurationType = getSystemType();

// if the intersection has a mix of radars and cameras then show all reports.
$cameraPhases = getPhasesWithCamera();
?>

<div class="row">
    <div class="inline-block">
        <label for="datePicker">Select a Date:</label>
    	<input type="text" id="datePicker" class="input-large input-purple" value="<?php echo date("m/d/Y"); ?>" placeholder="Select a Date"/> 
    </div>
    <div class="inline-block">
        <label>&nbsp;</label>
    	<button id="load" class="btn btn-default">Load Summary</button>
    </div>
</div>

<!-- If there are phases with cameras, then show all reports. This is to handle cases where an intersection has a combination of radars and cameras -->

<?php
if ($systemConfigurationType === 0 || $systemConfigurationType === "" || count($cameraPhases) > 0)
{
?>
	<div id="displays" class="row panel" style="display:none;">
		<div class="row">
			<h3>Intersection Totals</h3>
			<div id="intersection_data"></div>
		</div>
		
		<div class="row">
			<h3>Vehicle Counts</h3>
			<div id="vehicle_chart" class="chart-area"></div>
		</div>
		
		<div class="row">
			<h3>Period Lengths</h3>
			<div id="period_chart" class="chart-area"></div>
		</div>
	</div>
<?php
}
else
{
?>	
	<div id="displays" class="row panel" style="display:none;">
		<div class="row">
			<h3>Period Lengths</h3>
			<div id="period_chart" class="chart-area"></div>
		</div>
	</div>
<?php
}
?>

<?php
include("includes/footer.php")
?>
<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Statistics";
$breadCrumb = "<h1>Reports <small>Statistics</small></h1>";
$menuCategory = "reports";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/reporting.css"/>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.timepicker.js"></script>
<script language="javascript" type="text/javascript" src="/js/statistics.js"></script>
<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot/excanvas.min.js"></script><![endif]-->
<script type="text/javascript" src="/js/flot/jquery.flot.js"></script>
<script type="text/javascript" src="/js/flot/jquery.flot.time.min.js"></script>
<script type="text/javascript" src="/js/flot/jquery.flot.resize.min.js"></script>
<script type="text/javascript" src="/js/flot/jquery.flot.navigate.min.js"></script>
<script type="text/javascript" src="/js/flot/jquery.flot.errorbars.min.js"></script>
<script type="text/javascript" src="/js/flot/jquery.flot.pie.min.js"></script>
<script type="text/javascript" src="/js/flot/jquery.flot.axislabels.js"></script>
<script type="text/javascript" src="/js/plugins/date.js"></script>
<script type="text/javascript" src="/js/intersectionGraphDrawing.js"></script>

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

// get a list of phases that has cameras
// if the intersection has a mix of radars and cameras then show all reports.
$cameraPhases = getPhasesWithCamera();
?>

<div class="row">
	<div class="inline-block">
		<label for="startDate">Start Date/Time:</label>
		<input type="text" id="startDate" class="input-large input-purple" value="<?php echo date("m/d/Y") . " 12:00 AM"; ?>" placeholder="Start Date/Time"/> 
	</div>
	<div class="inline-block">
		<label for="endDate">End Date/Time:</label>
		<input type="text" id="endDate" class="input-large input-purple" value="<?php echo date("m/d/Y", time()+86400) . " 12:00 AM"; ?>" placeholder="End Date/Time"/> 
	</div>

<?php
if ($systemConfigurationType === 0 || $systemConfigurationType === "" || count($cameraPhases) > 0)
{
?>
	<div class="inline-block">
		<label>&nbsp;</label>
		<button id="load" class="btn btn-default green">Load Statistics</button> 
		<button id="download" class="btn btn-default" onclick='$("#downloadDialog").dialog("open")'>Download Statistics</button>
	</div>
<?php
}
else
{
?>
	<div class="inline-block">
		<label>&nbsp;</label>
		<button id="load" class="btn btn-default green">Load Statistics</button> 
	</div>
<?php	
}
?>
	
</div>

<?php
if ($systemConfigurationType === 0 || $systemConfigurationType === "" || count($cameraPhases) > 0)
{
?>
	<div id="tabs" class="row">
		<ul>
			<li><a href="#tabs-1"><em>Vehicle Graphs</em></a></li>
			<li><a href="#tabs-6"><em>Phase Volume Playback</em></a></li>
			<li><a href="#tabs-7"><em>Splits</em></a></li>
			<li><a href="#tabs-3"><em>Pedestrian Graphs</em></a></li>
			<li><a href="#tabs-4"><em>Period Graph</em></a></li>
			<li><a href="#tabs-5"><em>Count Totals</em></a></li>
			<li><a href="#tabs-2"><em>Processor Graphs</em></a></li>
		</ul>
		<div id="tabs-1">
			<div class="row">
				<h3>Vehicle Counts</h3>
				<div id="vehicle_chart" class="chart-area"></div>
				<h3>Vehicle Hourly Summary</h3>
				<div id="vehicle_chart_hourly" class="chart-area"></div>
			</div>
		</div>
		<div id="tabs-2">
			<div class="row">
				<h3>Processor Temperature</h3>
				<div id="temp_chart" class="chart-area"></div>
				<h3>Processor Load</h3>
				<div id="load_chart" class="chart-area"></div>
				
				<?php
				if($permissions["username"] == "PEC")
				{
				?>
				<h3><b>PEC ONLY</b> - Processor Speed</h3>
				<div id="speed_chart" class="chart-area"></div>
				<?php
				}
				?>
			</div>
		</div>
		<div id="tabs-3">
			<div class="row">
				<h3>Pedestrian Counts</h3>
				<div id="pedestrian_chart" class="chart-area"></div>
				<h3>Pedestrian Hourly Summary</h3>
				<div id="pedestrian_chart_hourly" class="chart-area"></div>
			</div>
		</div>
		<div id="tabs-4">
			<div class="row">
				<h3>Period Lengths</h3>
				<div id="period_chart" class="chart-area"></div>
			</div>
		</div>
		<div id="tabs-5">
			<div class="row">
				<h3>Intersection Totals</h3>
				<div id="intersection_data" class="row section"></div>
			</div>
		</div>
		<div id="tabs-6">
			<div class="row">
				<h3>Phase Volume Playback</h3>
				<div class="row section">
					<div class="inline-block two">
						<p id="playbackTime" class="text-right">12:00 AM</p>
					</div>
					<div class="inline-block eight">
						<div id="playbackSlider"></div>
					</div>
					<div class="inline-block one">
						<button id="playbackToggle" class="btn btn-default blue">Play</button>
					</div>
				</div>
				<div class="section">
								<div style="display:block;border:solid 1px black;width:400px;height:400px;margin-left: auto;margin-right: auto;overflow:hidden;zoom:1;position: relative;">
					<canvas id="playbackCanvas" width=400 height=400 style="position: absolute; top: 0; left: 0;margin: 1px;"></canvas>
								</div>
				</div>
			</div>
		</div>
		<div id="tabs-7">
			<div class="row">
				<div style="float:left;width:75%;clear:right;">
					<h3>Green Splits <small>by Phase, Min / Average / Max</small></h3>
					<div id="split_chart_1" class="chart-area"></div>
				</div>
				<div style="float:right;width:25%;clear:right;">
					<h3>Green Splits <small>by Phase, Percent</small></h3>
					<div id="split_chart_2" class="chart-area"></div>
				</div>
				<div style="clear:both;"></div>
			</div>
		</div>
	</div>
<?php
}
else
{
?>
	<div id="tabs" class="row">
		<ul>
			<li><a href="#tabs-4"><em>Period Graph</em></a></li>
			<li><a href="#tabs-7"><em>Splits</em></a></li>
			<li><a href="#tabs-3"><em>Pedestrian Graphs</em></a></li>			
			<li><a href="#tabs-2"><em>Processor Graphs</em></a></li>
		</ul>
		
		<div id="tabs-2">
			<div class="row">
				<h3>Processor Temperature</h3>
				<div id="temp_chart" class="chart-area"></div>
				<h3>Processor Load</h3>
				<div id="load_chart" class="chart-area"></div>
				
				<?php
				if($permissions["username"] == "PEC")
				{
				?>
				<h3><b>PEC ONLY</b> - Processor Speed</h3>
				<div id="speed_chart" class="chart-area"></div>
				<?php
				}
				?>
			</div>
		</div>

		<div id="tabs-3">
			<div class="row">
				<h3>Pedestrian Counts</h3>
				<div id="pedestrian_chart" class="chart-area"></div>
				<h3>Pedestrian Hourly Summary</h3>
				<div id="pedestrian_chart_hourly" class="chart-area"></div>
			</div>
		</div>

		<div id="tabs-4">
			<div class="row">
				<h3>Period Lengths</h3>
				<div id="period_chart" class="chart-area"></div>
			</div>
		</div>
		
		<div id="tabs-7">
			<div class="row">
				<div style="float:left;width:75%;clear:right;">
					<h3>Green Splits <small>by Phase, Min / Average / Max</small></h3>
					<div id="split_chart_1" class="chart-area"></div>
				</div>
				<div style="float:right;width:25%;clear:right;">
					<h3>Green Splits <small>by Phase, Percent</small></h3>
					<div id="split_chart_2" class="chart-area"></div>
				</div>
				<div style="clear:both;"></div>
			</div>
		</div>		

	</div>

<?php
}
?>

<?php
if ($systemConfigurationType === 0 || $systemConfigurationType === "" || count($cameraPhases) > 0)
{
?>
<div id="downloadDialog" title="Choose Options">
	<div class="dialog-interior">
		<p>Select the items you want to include in your download:</p>
		<form id="downloadForm">
			<ul class="unstyled">
				<li><input type="checkbox" id="vehicleCounts" class="pretty" name="vehicleCounts" data-label="Vehicle Counts" checked/></li>
				<li><input type="checkbox" id="pedestrianCounts" class="pretty" name="pedestrianCounts" data-label="Pedestrian Counts" checked/></li> 
				<li><input type="checkbox" id="hourlySummaries" class="pretty" name="hourlySummaries" data-label="Hourly Summaries" checked/></li>
			</ul>
		</form>
	</div>
</div>
<?php
}
?>


<?php
include("includes/footer.php")
?>

<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Recording Options";
$breadCrumb = "<h1>Views <small>Recording Options</small></h1>";
$menuCategory = "views";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/default.css">
<link rel="stylesheet" type="text/css" href="/css/cameraViews.css">
\t\t
<script language="javascript" type="text/javascript" src="/js/recording.js"></script>

<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");

//////////////////////////////////////////////////////
//// Page PHP
//////////////////////////////////////////////////////

if(empty($permissions["cameracontrols"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

$loggedIn = true;

require_once("helpers/pathDefinitions.php");
require_once("helpers/databaseInterface.php");
require_once("helpers/libraries/intersectionUtil.php");
	
$intersectionUtil = new IntersectionUtil();

$streamArray = $intersectionUtil->getCameraNames();

?>

<div class="row panel inline-block">
	<div id="users-contain">
		<table id="users" class="table table-striped">
			<thead>
				<tr>
					<th>Date</th>
					<th class="text-center">Time</th>
					<th class="text-center">Camera</th>
					<th class="text-center">Timestamp</th>
					<th class="text-center">Frame Rate</th>
					<th class="text-center">Size (approx.)</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
	<button id="add-recording" class="btn btn-default">Add Recording Event</button>
    <div id="errorNotice">Select a recording drive below to add new schedule items.</div>
</div>

<?php
require_once("helpers/recordingHelper.php");

$driveArr = getDriveList($permissions);

if(count($driveArr) < 1)
{
	echo "<p>There are no drives available to record to. Please insert a USB drive.</p>";
    echo '<script>$("#add-recording").hide();</script>';
}
else
{
?>

<br/>
<div class="row panel inline-block">
	<div id="drives-contain">
		<table id="drives" class="table table-striped">
			<thead>
				<tr>
					<th>Use</th>
					<th>Drive</th>
					<th>Name</th>
					<th>Free Space</th>
				</tr>
			</thead>
			<tbody>
	<?php
	foreach($driveArr as $drive) {
		echo "<tr>";
		echo "<td><input type='radio' id='drive_$drive[0]' class='pretty' name='drive'/></td><td>$drive[0]:\\</td><td>$drive[1]</td><td>$drive[2]</td>";
		echo "</tr>";
	}
	?>
			</tbody>
		</table>
	</div>
</div>

<?php
}
?>

<div id="dialog-add" title="Schedule Recording">
	<div class="form-horizontal">
		<div class="control-group">
			<label for="camera" class="control-label medium">Camera</label>
			<div class="controls medium">
				<div style="position:relative;z-index:1004;display:inline;">
					<select id="camera" class="chosen-dialog single-select span3">
		
<?php
foreach($streamArray as $stream)
	echo "<option value='$stream'>$stream</option>";
?>
					</select>
				</div>
			</div>
		</div>
		<div class="control-group">
			<label for="framerate" class="control-label medium">Frame Rate</label>
			<div class="controls medium">
				<select id="framerate" class="chosen-dialog single-select span3">
					<option value="10">Normal</option>
					<option value="1">Once a Second</option>
					<option value="0.2">Every 5 Seconds</option>
					<option value="0.1">Every 10 Seconds</option>
					<option value="0.033333">Every 30 Seconds</option>
					<option value="0.016667">Every Minute</option>
					<option value="0.003333">Every 5 Minutes</option>
				</select>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label medium">Timestamp</label>
			<div class="controls medium">
				<input type="checkbox" id="timestamp" class="pretty" name="timestamp"/>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label medium">Date</label>
			<div class="controls medium">
				<div class="inline-block" style="width:125px;">
					<input type="radio" id="record-spec" class="pretty" name="recordDate" value="specific" checked="checked" data-label="Specific Date"/>
				</div>
				<div class="inline-block">
					<input type="text" id="specificDateInput" class="input-purple span3" value="<?php echo date("m/d/Y") ?>"/>
				</div>
			</div>
		</div>
		<div class="control-group" style="margin-bottom:25px;">
			<div class="controls medium">
				<div class="inline-block" style="width:125px;">
					<input type="radio" id="record-recur" class="pretty" name="recordDate" value="recurring" data-label="Recurring"/>
				</div>
				<div class="inline-block" style="vertical-align:top;">
					<table class="table days-of-week-options">
						<tbody>
							<tr>
								<td><label>Sun</label></td>
								<td><input type="checkbox" id="sunday" class="pretty" name="sunday" onclick="$('input:radio[name=recordDate]').prop('checked','true')"/></td>
								<td><label>Mon</label></td>
								<td><input type="checkbox" id="monday" class="pretty" name="monday" onclick="$('input:radio[name=recordDate]').prop('checked','true')"/></td>
								<td><label>Tue</label></td>
								<td><input type="checkbox" id="tuesday" class="pretty" name="tuesday" onclick="$('input:radio[name=recordDate]').prop('checked','true')"/></td>
								<td><label>Wed</label></td>
								<td><input type="checkbox" id="wednesday" class="pretty" name="wednesday" onclick="$('input:radio[name=recordDate]').prop('checked','true')"/></td>
								<td><label>Thu</label></td>
								<td><input type="checkbox" id="thursday" class="pretty" name="thursday" onclick="$('input:radio[name=recordDate]').prop('checked','true')"/></td>
								<td><label>Fri</label></td>
								<td><input type="checkbox" id="friday" class="pretty" name="friday" onclick="$('input:radio[name=recordDate]').prop('checked','true')"/></td>
								<td><label>Sat</label></td>
								<td><input type="checkbox" id="saturday" class="pretty" name="saturday" onclick="$('input:radio[name=recordDate]').prop('checked','true')"/></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="control-group" style="margin-bottom:25px;">
			<label class="control-label medium">Time</label>
			<div class="controls medium" style="padding-top:9px;">
				<div class="inline-block">
					<span id="timeVal">00:00</span>
				</div>
				<div class="inline-block ten">
					<div id="time"></div>
				</div>
			</div>
		</div>
		<div class="control-group" style="margin-bottom:25px;">
			<label for="duration" class="control-label medium">Duration</label>
			<div class="controls medium">
				<input type="duration" id="duration" class="span1" value="10"/><label class="spinner-label">mins</label>
			</div>
		</div>
		<div id="estimated-storage" class="control-group">
			<p class="text-center">Estimated Storage Space Required: <span id="storageReq">114.44 megabytes</span> per event</p>
		</div>
	</div>
</div>


<script type="text/javascript">
	$(function() {initScripts(<?php
    
    $driveList = "[";
    
    foreach($driveArr as $drive) {
		$driveList .= "'$drive[0]',";
	}
    
    $driveList = rtrim($driveList, ",");
    
    echo $driveList . "]";
    
    ?>);});
</script>

<?php
include("includes/footer.php")
?>

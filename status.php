<?php

// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = "System Status";
$breadCrumb = "<h1>Reports <small>System Status</small></h1>";
$menuCategory = "reports";

// set this variable to true, so insyncInterface doesnt try to authenticate
//$loggedIn = true;

if($permissions["username"] == "kiosk")
    include("includes/header_lite.php");
else
    include("includes/header.php");
	

require_once("helpers/pathDefinitions.php");
require_once("helpers/constants.php");


require_once("helpers/insyncInterface.php");
$insync = new InSyncInterface();
$statusXML = @simplexml_load_string($insync->getStatus());

if($statusXML === FALSE)
	die("InSync not Running");

//print_r($statusXML->Devices->IOBoard);

$version = "";
if(isset($statusXML["version"]))
	$version = (string)$statusXML["version"];

$videodetection = "";
try
{
    $fso = new COM("Scripting.FileSystemObject");
    $FileVersion = $fso->GetFileVersion(CAMERAIO_EXE);
	$videodetection = $FileVersion;
}
catch(Exception $e)
{
    $videodetection = "Error reading version";
}

$IP = "";
if(isset($statusXML["IP"]))
	$IP = (string)$statusXML["IP"];
	
$Mode = "";
if(isset($statusXML["Mode"]))
	$Mode = (string)$statusXML["Mode"];
	
$DetectorSwitchEnabled = "";
if(isset($statusXML["DetectorSwitchEnabled"]))
	$DetectorSwitchEnabled = (string)$statusXML["DetectorSwitchEnabled"];

$SystemTime = "";
if(isset($statusXML->Time["Now"]))
	$SystemTime = (string)$statusXML->Time["Now"];	

$Uptime = "";
if(isset($statusXML->Time["Uptime"]))
	$Uptime = (string)$statusXML->Time["Uptime"];
	
$NTPServer = "";
if(isset($statusXML->NTP["Server"]))
	$NTPServer = (string)$statusXML->NTP["Server"];
	
$NTPStatus = "";
if(isset($statusXML->NTP["Status"]))
	$NTPStatus = (string)$statusXML->NTP["Status"];
	
$CurrentConfiguration = "";
if(isset($statusXML->Optimizer["CurrentConfiguration"]))
	$CurrentConfiguration = (string)$statusXML->Optimizer["CurrentConfiguration"];

	
?>

<table class="table table-striped">
	<thead>
		<tr>
			<th>InSync System Status</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th>Version</th>
			<td><?php echo $version;?></td>
		</tr>
		<tr>
			<th>Video Detection Version</th>
			<td><?php echo $videodetection;?></td>
		</tr>
		<tr>
			<th>IP</th>
			<td><?php echo $IP;?></td>
		</tr>
		<tr>
			<th>Mode</th>
			<td><?php echo $Mode;?></td>
		</tr>
		<tr>
			<th>Detector Switch Enabled</th>
			<td><?php echo $DetectorSwitchEnabled;?></td>
		</tr>
		<tr>
			<th>System Time</th>
			<td><?php echo $SystemTime;?></td>
		</tr>
		<tr>
			<th>System Up Time</th>
			<td><?php echo $Uptime;?></td>
		</tr>
		<tr>
			<th>NTP Server</th>
			<td><?php echo $NTPServer;?></td>
		</tr>
		<tr>
			<th>NTP Status</th>
			<td><?php echo $NTPStatus;?></td>
		</tr>
		<tr>
			<th>Current Configuration</th>
			<td><?php echo $CurrentConfiguration;?></td>
		</tr>

		<?php
			if(isset($statusXML->Devices))
			{
				if(isset($statusXML->Devices->IOBoard))
				{
					foreach($statusXML->Devices->IOBoard as $ioboard)
					{
						echo "<tr>";
						echo "	<th>IO Board: ".$ioboard["Type"]."</th>";
						echo "	<td>".$ioboard["Status"].", ".$ioboard["Firmware"]."</td>";
						echo "</tr>";
					}
				}
				if(isset($statusXML->Devices->VideoProcessor))
				{
					foreach($statusXML->Devices->VideoProcessor as $vidProc)
					{
						echo "<tr>";
						echo "	<th>Video Processor: " . $vidProc["IP"] . "</th>";
						echo "	<td>".$vidProc["Status"]."</td>";
						echo "</tr>";
					}
				}
				if(isset($statusXML->Devices->InSyncCamera))
				{
					foreach($statusXML->Devices->InSyncCamera as $camera)
					{
						echo "<tr>";
						echo "	<th>Camera: ".$camera["Name"]." (" . $camera["IP"] . ")</th>";
						echo "	<td>".$camera["Status"]."</td>";
						echo "</tr>";
					}
				}
				if(isset($statusXML->Devices->ExternalDetector))
				{
					foreach($statusXML->Devices->ExternalDetector as $extDet)
					{
						if (stripos($extDet["Name"], "faux") === false)
						{
							echo "<tr>";
							echo "	<th>Ext Det: ".$extDet["Name"]."</th>";
							echo "	<td>".$extDet["Status"]."</td>";
							echo "</tr>";
						}
					}
				}
				if(isset($statusXML->Devices->Panomorph))
				{
					foreach($statusXML->Devices->Panomorph as $panomorph)
					{
						echo "<tr>";
						echo "	<th>Panomorph: ".$panomorph["Name"]." (" . $panomorph["IP"] . ")</th>";
						echo "	<td>".$panomorph["Status"]."</td>";
						echo "</tr>";
					}
				}
			}
		?>

	</tbody>
</table>

<script type="text/javascript">
	$(function() {initScripts();});
</script>

<?php
if($permissions["username"] == "kiosk")
    include("includes/footer_lite.php");
else
    include("includes/footer.php");
?>
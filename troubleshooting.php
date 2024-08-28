<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Troubleshooting";
$breadCrumb = "<h1>Settings <small>Troubleshooting</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<script language="javascript" type="text/javascript" src="/js/troubleshooting.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.timepicker.js"></script>

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

if($permissions["username"] != "PEC" && $permissions["username"] != "ADMIN" && $permissions["username"] != "kiosk")
	die("This page is for Rhythm Engineering use only.");
?>

<!--************************************Troubleshooting package download********************************************* -->

<?php if($permissions["username"] == "kiosk" || $permissions["username"] == "ADMIN"): ?>
<?php else: ?>
<div class="row">
	<div class="inline-block">
		<label for="startDate">Start Date/Time:</label>
		<input type="text" id="startDate" class="input-large input-purple" value="<?php echo date("m/d/Y"); ?> 12:00AM" placeholder="Start Date/Time"/>
	</div>
	<div class="inline-block">
		<label for="endDate">End Date/Time:</label>
		<input type="text" id="endDate" class="input-large input-purple" value="<?php echo date("m/d/Y h:i A"); ?>" placeholder="End Date/Time"/>
	</div>
	<div class="inline-block">
	<label>&nbsp;</label>
		<button id="download" class="btn btn-default">Download</button>
	</div>
	<p><small>Note: It can take a while to generate the package file. Please wait after hitting "Download".</small></p>
</div>

    <hr class="five"/>

<?php endif; ?>

<!--***********************************Show boot logs********************************************** -->

<div class="row">
    <button id="bootLog" class="btn btn-default">Show Boot Logs</button>
    <div id="boot-log-dialog" title="Processor Reboot Log">
        <div style="overflow:auto;width:400px;height:400px">
<?php
require_once("helpers/pathDefinitions.php");
$contents = @file_get_contents(BOOT_LOG);

if($contents !== FALSE)
{
    $contents = str_replace("\n", "<br />", $contents);
    
    echo $contents;
}
?>
        </div>
    </div>

<!--******************************Network Tester*************************************************** -->

    <button id="networkTester" class="btn btn-default">Network Tester</button>
    <div id="network-dialog" title="Network Tester">
        Loading, please wait...
    </div>	

<!--**********************************InTraffic Syncs*********************************************** -->

	
    <button id="intrafficLog" class="btn btn-default">InTraffic Sync History</button>
    <div id="intraffic-sync-dialog" title="Intraffic Sync Logs">
        <div id="intraffic-content-wrap" style="overflow:auto;width:700px;height:350px">
 <?php
	 require_once("helpers/pathDefinitions.php");
	
	if (file_exists(INTRAFFIC_SYNC_LOG))
	{
		$path = @opendir(INTRAFFIC_SYNC_LOG);
		$list = [];
		$list = fileList($path);
		foreach ($list as $item)
	    {
	 	  echo $item;
	    }
	}	
?> 		
       </div>	
    </div>

<!--***********************************Task Manager********************************************** -->

<?php if($permissions["username"] == "kiosk" || $permissions["username"] == "ADMIN"): ?>
<?php else: ?>    
    <button id="taskManager" class="btn btn-default">Task Manager</button>
    
    <style>
        .wrap {
            width: 100%;
            height: 95%;
            overflow: auto;
        }
        table.task-list {
            background-color: white;
            color: black;
            width:100%;
            height:100%;
            margin-bottom: 0px;
        }
        table.task-list td {
            padding-left: 5px;
            padding-right: 5px;
            cursor: pointer;
        }
        table.task-list a {
            color: black;
        }
        
        #right-click-menu {
            position: absolute;
            display: none;
            width: 165px;
            height: 27px;
            background-color: #F0F0F0;
            border: solid 1px #979797;
            z-index: 99999;
        }
        
        table.status-bar {
            border-top: solid 1px gray;
            color: black;
            width: 100%;
            text-align: center;
            margin: 0;
        }
    </style>
    
    <div id="task-dialog" title="Task Manager">
        <div class="wrap">
            <table class="task-list">
                <thead>
                    <tr>
                        <th><a id="sortProcess" href="#">Process</a></th><th><a id="sortDesc" href="#">Description</a></th><th><a id="sortCPU" href="#">CPU</a></th><th><a id="sortRAM" href="#">RAM</a></th><th><a id="sortPID" href="#">PID</a></th>
                    </tr>
                </thead>
                <tbody id="task-list-body"></tbody>
            </table>
        </div>
        <table class="status-bar">
            <tr>
                <td id="processCount" style="width:20%">&nbsp;</td>
                <td id="cpuUsage" style="width:25%">&nbsp;</td>
                <td id="memoryUsage" style="width:25%">&nbsp;</td>
                <td id="cpuTemp" style="width:30%">&nbsp;</td>
            </tr>
        </table>
    </div>

<?php endif; ?>
    
<!--**********************************Video Manager********************************************** -->
	
<?php if($permissions["username"] == "kiosk" || $permissions["username"] == "ADMIN"): ?>
<?php else: ?>	
    <button id="videoManager" class="btn btn-default">Video Manager</button>
    
    <div id="video-dialog" title="Video Manager">
        <div id="video-content-wrap" style="overflow:auto;width:100%;height:100%">
            Please wait, loading...
        </div>
    </div>
    
    <button id="diskHealth" class="btn btn-default">Disk Health</button>
    
    <div id="disk-dialog" title="Disk Health">
        Please wait while the disk check is being run.<br /><br />This can take several minutes...
    </div>
<?php endif; ?>

<!--********************************RDP************************************************ -->

<?php if($permissions["username"] == "kiosk" || $permissions["username"] == "ADMIN"): ?>
<?php else: ?>
    <button id="rdp" class="btn btn-default">RDP</button>

<!--******************************Network Tester*************************************************** -->
    <!--
    <button id="networkTester" class="btn btn-default">Network Tester</button>
    <div id="network-dialog" title="Network Tester">
        Loading, please wait...
    </div>	-->

<!--*********************************Time Sync Status************************************************ -->

    <button id="timeSyncStatus" class="btn btn-default">Time Sync Status</button>
    <div id="time-dialog" title="Time Sync Status">
        <div id="time-content-wrap" style="overflow:auto;width:100%;height:100%">
       </div>	
    </div>	

<!--**********************************Clear History*********************************************** -->

    <button id="clearStorage" class="btn btn-default">Clear History</button>

<?php endif; ?>

<!--**********************************InTraffic Syncs*********************************************** -->
	
    <!--<button id="intrafficLog" class="btn btn-default">InTraffic Sync History</button>
    <div id="intraffic-sync-dialog" title="Intraffic Sync Logs">
        <div id="intraffic-content-wrap" style="overflow:auto;width:700px;height:350px"> -->	
 <?php
	/* require_once("helpers/pathDefinitions.php");
	
	if (file_exists(INTRAFFIC_SYNC_LOG))
	{
		$path = @opendir(INTRAFFIC_SYNC_LOG);
		$list = array();
		$list = fileList($path);
		foreach ($list as $item)
	    {
	 	  echo $item;
	    }
	}	*/
?> 		
       <!-- </div>	
    </div> -->	

</div>

<!--********************************************************************************* -->

<?php
if($permissions["username"] == "kiosk")
    include("includes/footer_lite.php");
else
    include("includes/footer.php");
?>

<!--********************************************************************************* -->

<?php
	function fileList($path)
	{
		$filelist = [];
		while($file = readdir($path))
		{
			if ($file != '.' and $file != '..')
			{
				// add the filename, to be sure not to
				// overwrite a array key
				$ctime = filectime($data_path . $file) . ',' . $file;
				$filelist[$ctime] = $file;
			}
       }
	   
	   closedir($path);
	   krsort($filelist);

		$datalist = [];
		foreach ($filelist as $file)
		{
			$fullPath = INTRAFFIC_SYNC_LOG."/".$file;
			if (file_exists($fullPath))
			{
				$myfile = fopen($fullPath, "r");
				while (!feof($myfile))
				{
					$contents = fgets($myfile);
					$contents = str_replace("\n", "<br />", $contents);
					$stringdateandtime = substr($contents,0,23);
					$datalist[$stringdateandtime] = $contents;
				}
			}
		}
	   
	   krsort($datalist);
       return $datalist;
	}
	
?>
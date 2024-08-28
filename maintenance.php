<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Maintenance";
$breadCrumb = "<h1>Settings <small>Maintenance</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->     

<link rel="stylesheet" type="text/css" href="/css/maintenance.css"/>
<script language="javascript" type="text/javascript" src="/js/maintenance.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.form.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.ui.progressbar.js"></script>

<!-- END HEADER -->
HEAD_WRAP
;

if(strcasecmp($permissions["username"],"kiosk") == 0)
    include("includes/header_lite.php");
else
    include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["maintenance"]))
{
    echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

require_once("helpers/maintenanceHelper.php");
require_once("helpers/pathDefinitions.php");

// Gather restore archive info
$autoRestoreFiles = [];

if (file_exists(INSYNC_CONF_ARCHIVE_ROOT)) 
{
    $dir = opendir(INSYNC_CONF_ARCHIVE_ROOT);

    while (($filename = readdir($dir)) !== false) 
    {
        if ($filename != '.' && $filename != '..') 
        {
            if (strpos($filename, ".insync"))
                $autoRestoreFiles[] = $filename;
        }
    }

    closedir($dir);
}

?>

<style>
    .ui-progressbar-value {
        background-color: #6E9140;
    }
</style>

<h3>Restore Configuration</h3>
<div class="row section">
    <button id="autoRestoreButton" class="btn btn-default">InSync Auto-Saves</button> 
    <button id="uploadRestoreButton" class="btn btn-default">Upload</button>
    <!-- Restore Config: InSync Auto-Saves -->
    <div id="dialog-restore-auto" title="Select Archive">
<?php
if(count($autoRestoreFiles) == 0)
    echo "<div class='section'><p>No restore archives found.</p></div>";
else
{
    echo "<div class='section'><p>Please select the archive you wish to restore from:</p></div>";
    echo '<div class="section"><div style="position:relative;z-index:1004;"><select id="autoRestore" class="chosen-dialog single-select span4">';

    foreach($autoRestoreFiles as $file)
    {
        $raw = $file;
        $name = "";
        $seperatorPos = strpos($file, ".");
        
        if($seperatorPos !== FALSE)
        {
            $name = substr($file, 0, $seperatorPos);
            $file = substr($file, $seperatorPos+1);
        }
        
        $datetime = substr($file, 4, 2) . "/" . substr($file, 6, 2) . "/" . substr($file, 0, 4) . " " . substr($file, 9, 2) . ":" . substr($file, 11, 2);
        echo "<option value='" . INSYNC_CONF_ARCHIVE_ROOT . "/$raw'>$name - $datetime</option>";
    }

    echo "</select></div></div>";
}
?>
    </div>
    <!-- // Restore Config: InSync Auto-Saves -->
        
    <!-- Restore Config: Upload -->     
    <div id="dialog-restore-upload" title="Upload Archive">
        <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
        <div id="dialogStart">
            <div class="section"><p>Choose the archive to upload, and then click "Upload".</p></div>
            <div class="section">
                <form id="restoreUpload" action="helpers/maintenanceHelper.php?action=putfile" method="post" enctype="multipart/form-data">
                    <input type="file" name="file" accept=".insync" id="file"/>
                </form>
            </div>
        </div>
        <div id="dialogProgress"></div>
    </div>
    <!-- // Restore Config: Upload -->  
</div>
<hr class="five"/>
<h3 class="inline">Save Archive</h3><div class="inline-block" style="white-space:nowrap;"><input type="checkbox" id="includeStats" class="pretty" data-label="Include Statistic Files"/></div>
<div class="row section">
    <button id="archiveDownload" class="btn btn-default">Download</button>
</div>
<hr class="five"/>
<h3>Application Deployment</h3>
<div class="row section">
    <button id="deployStart" class="btn btn-default">Update Software</button>
    
    <div id="dialog-deploy-pick" title="Update Software">
        Would you like to upload a new installer, or attempt to use an installer already uploaded?
    </div>
    
    <div id="dialog-deploy-existing" title="Existing Installers">
        Please wait while loading...
    </div>
    
    <!-- Application Deployment: Upload -->
    <div id="dialog-deploy-upload" title="Upload Deployment File">
        <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
        <div id="deployInfo">
            <div class="section"><p>Choose the file to upload, and then click "Upload".</p></div>
            <div class="section">
                <form id="appDeployment" action="helpers/deploymentHelper.php?action=putfile" method="post" enctype="multipart/form-data">
                    <input type="file" name="deployFile" accept=".exe" id="deployFile" />
                </form>
            </div>
            <div id="deploymentStatus"></div>
        </div>
        <div id="deployProgress"></div>
    </div>
    <!-- // Application Deployment: Upload -->
    
    <div id="dialog-deploy-deploy" title="Deploy Update">
        <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
        <p>Would you like to deploy this file to this intersection only, or to all intersections in the management group?</p>
    </div>

    <div id="dialog-deploy-propagate" title="Propagate File to Management Group"> 
        <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
        <p>Please Wait...</p>
    </div>
    
    <div id="dialog-deploy-execute" title="Executing File on Management Group"> 
        <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
        <p>Please Wait...</p>
    </div>
    
    <div id="dialog-deploy-selectiveexecute" title="Error During Propagation"> 
        <p>We could not propagate the deployment file to the following processors:</p>
        <div id="propagate-fail-status"></div>
        <p>Do you want to attempt to execute the file on all processors anyway?</p>
    </div>
</div>
<hr class="five"/>
<h3>Processor Management</h3>
<div class="row section">
    <button id="restartKiosk" class="btn btn-default">Restart Kiosk</button>
    <button id="restartProc" class="btn btn-default">Restart Processor</button>
    <button id="clearProc" class="btn btn-default">Clear History</button>
</div>

<hr class="five"/>
<h3>Network Utilities</h3>
<div class="row section">
    <button id="pingarp" class="btn btn-default">Ping / ARP Prompt</button>
</div>

<div id="dialog-network" title="Network Utilities">
    <div id="network-display" class="cmd-window">Commands are limited to "ping" and "arp".<br /><br /></div>
    <div class="cmd-wrap">
        <form method="post" action="helpers/maintenanceHelper.php?action=networkutil" id="networkForm">
            <label class="cmd-label">CMD></label>
            <input type="text" id="commandPrompt" name="cmd" autocomplete="off"/>
        </form>
    </div>
</div>

<hr class="five"/>
<h3>Remote Desktop Access</h3>
<div class="row section">
	<button id="enableRdp" class="btn btn-default">Enable</button>
	<button id="disableRdp" class="btn btn-default">Disable</button>	
</div>

<div id="dialog-restart-confirm" title="Warning!">
    <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
    <p>Are you sure you want to restart the system? This may result in inefficient intersection service until the processor finishes restarting!</p>
</div>

<div id="dialog-confirm" title="Warning!">
    <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
    <p>Are you sure you want to restore over the existing configuration?</p>
</div>

<div id="dialog-restore-finish" title="Restore Archive">
    <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
    <div class="content-msg">
        <p>Upload complete. You can now click "Restore" to restore the uploaded archive.</p>
        <input type="checkbox" id="upload-is-video" class="pretty" name="upload-is-video" data-label="Secondary Video Processor"/>
    </div>
    <div class="wait-msg">
        Please wait while we restore your configuration...<br /><br />
        <div id='restoreProgressBar'></div>
    </div>
</div>

<script type="text/javascript">
<?php
require_once("helpers/networkHelper.php" );
$Intersections = getCorridorIntersections();

$hasVideo = "false";

$Intersection = @simplexml_load_file(INTERSECTION_CONF_FILE);
    
if($Intersection === FALSE)
{
    writeDebug("deploymentLog.txt", __LINE__, "Could not open Intersection.xml.");
    die("Error: Could not open Intersection.xml.");
}

foreach($Intersection->xpath("//VideoDetectionDevice") as $vdd)                    
    if((string)$vdd->attributes()["machine"] != "." && (string)$vdd->attributes()["machine"] != getInSyncIP())
        $hasVideo = "true";

?>
    $(function() {initScripts(<?=count($Intersections)?>,<?=$hasVideo?> );});
</script>

<?php
if(strcasecmp($permissions["username"],"kiosk") == 0)
    include("includes/footer_lite.php");
else
    include("includes/footer.php");
?>

<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": NTP Server";
$breadCrumb = "<h1>Settings <small>NTP Server</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->
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
        <label>&nbsp;</label>
    </div>
</div>

<div class="row">
    <div class="inline-block">
        <label style="font-size:20px"><strong>NTP Server IP:</strong></label>
    </div>
    <div class="inline-block">
        <label id="configured_ntp_server_ip" style="font-size:20px"></label>
    </div>
</div>

<div class="row">
    <div class="inline-block">
        <label>&nbsp;</label>
    </div>
</div>

<div class="row">
    <div class="inline-block">
        <label for="enterIP">Enter IP</label>
        <input type="text" id="enterIP" class="input-large input-green" placeholder="Server IP Address"/>
    </div>
    <div class="inline-block">
        <label>&nbsp;</label>
        <button id="test" class="btn btn-default">Test</button>       
        <button id="ntp_sync" class="btn btn-default">Sync</button>
        <button id="save" class="btn btn-default">Save</button>
    </div>
</div>
<div class="row">
    <div class="panel" style="width:450px;">
        <div id="testResults" style="min-height:300px;"></div>
    </div>
</div>

<script type="text/javascript">
    $("#test").button().click(function() {
        var target = $("#enterIP").val();

        $("#testResults").html("Testing, please wait...");

        $.get("helpers/ntpHelper.php?action=test&target=" + target, function(data) {
            $("#testResults").html(data);
        });
    });
    $("#save").button().click(function() {
        var target = $("#enterIP").val();

        $.get("helpers/ntpHelper.php?action=save&target=" + target, function(data) {
            if(data.indexOf("Error") != -1)
                popupNotification(data, 2500);
            else
                popupNotification("Saved successfully.", 2500, "notice");
            
            $.get("helpers/ntpHelper.php?action=get", function(data) {
                if(data != "" && data.substring(0,5) != "Error") {
                    $("#configured_ntp_server_ip").html(data);
                }
            });
        });
    });
    
    $("#ntp_sync").button().click(function() {
        var target = $("#enterIP").val();

        $("#testResults").html("Synching with '" + target + "', please wait...");
        
        $.get("helpers/insyncInterface.php?action=ntpSync&server=" + target, function(data) {
            $("#testResults").html(data);
        });
    });
    
    $.get("helpers/ntpHelper.php?action=get", function(data) {
        if(data != "" && data.substring(0,5) != "Error") {
            $("#serverIPLabel").text("");

            $("#enterIP").focus();
            $("#configured_ntp_server_ip").html(data);
        }
    });
</script>

<?php
if($permissions["username"] == "kiosk")
    include("includes/footer_lite.php");
else
    include("includes/footer.php");
?>
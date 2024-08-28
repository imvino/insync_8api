<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Management Group View";
$breadCrumb = "<h1>Management Group <small>Management Group View</small></h1>";
$hideHeader = true;
$menuCategory = "corridor";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/corridorViewer.css"/>
<script language="javascript" type="text/javascript" src="/js/plugins/json2.js"></script>
<script language="javascript" type="text/javascript" src="/js/corridorViewer.js"></script>
<script language="javascript" type="text/javascript" src="/js/refreshView.js"></script>
<!-- END HEADER -->
HEAD_WRAP
;

$fullscreen = false;
if(isset($_REQUEST["fullscreen"]))
{
    if($_REQUEST["fullscreen"] == "true")
        $fullscreen = true;
}

if(!$fullscreen)
    include("includes/header.php");
else
    include("includes/header_lite.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if (empty($permissions["cameras"])) {
    echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

$refreshRate = "2000";
if(isset($_REQUEST["refreshRate"]))
    $refreshRate = $_REQUEST["refreshRate"];

if($fullscreen) {
?>
<div class="content">
<?php
}
?>

<div id="corridorControls" class="row">
    <div class="inline-block right">
        <div class="inline-block">
            <label for="refresh">Refresh</label>
            <select id="refresh" class="chosen-select single-select span3">
                <option value="1000" <?php if($refreshRate == "1000") echo "selected='selected'"; ?>>Every Second</option>
                <option value="2000" <?php if($refreshRate == "2000") echo "selected='selected'"; ?>>Every 2 Seconds</option>
                <option value="5000" <?php if($refreshRate == "5000") echo "selected='selected'"; ?>>Every 5 Seconds</option>
                <option value="30000" <?php if($refreshRate == "30000") echo "selected='selected'"; ?>>Every 30 Seconds</option>
                <option value="60000" <?php if($refreshRate == "60000") echo "selected='selected'"; ?>>Every 60 Seconds</option>
            </select>
        </div>
        <div class="inline-block">
            <label>&nbsp;</label>
            <button id="pauseButton" class="btn btn-default icon-only pause-btn"><span class="icon-default"></span></button> 
            <button id="rotateButton" class="btn btn-default icon-only rotate-btn"><span class="icon-default"></span></button> 
    <?php
            if (!$fullscreen) {
    ?>
            <button id="fullscreen" class="btn btn-default icon-only fullscreen-btn"><span class="icon-default"></span></button> 
    <?php
            } else {
    ?>
            <button id="hidecontrols" class="btn btn-default icon-only slideup-btn"><span class="icon-default"></span></button> 
    <?php
            }
    ?>
        </div>
    </div>
    <h2 id="corridorTitle" class="inline-block">Unnamed Management Group</h2>
</div>
<div id="collapsedControls" class="inline-block right" style="display: none">
        <button id="showcontrols" class="btn btn-default icon-only slidedown-btn"><span class="icon-default"></span></button> 
</div>

<div class="panel row" id="corridorContainer"></div>

<img class="imgOverlay" src="/img/no-camera.png" style="display:none;position:absolute;"/>

<?php

$loggedIn = true;
$portalData = "";

require_once("helpers/pathDefinitions.php");
require_once("helpers/corridorDesignerHelper.php");

if(file_exists(CORRIDORVIEWER_CONF_FILE))
{
    $corridorXML = simplexml_load_file(CORRIDORVIEWER_CONF_FILE);
    $corridorData = generateFromXML($corridorXML);
    $jsonData = json_encode($corridorData);

?>
<script>
    $(function() {

    importData = <?=$jsonData?>;

    $("#corridorContainer").empty();

    var title = "Unnamed Management Group";
    if(typeof importData.title != "undefined")
        title = importData.title;

    $("#corridorTitle").text(title);

    if(typeof importData.list != "undefined")
    {
        $.each(importData.list, function(index, value)
        {
            if(value.type == "intersection")
            {
                var camData = {};
                camData.list = new Array();

                $.each(value.cameras, function(index, value)
                {
                    camData.list.push(value);
                });

                addIntersection(value.ip, camData, value.name);
            }
            else if(value.type == "column")
            {
                var camData = {};
                camData.list = new Array();

                $.each(value.cameras, function(index, value)
                {
                    camData.list.push({ip:value.ip, name:value.name});
                });

                addColumn(camData, value.name);
            }
        });
    }

    initHovers();

    initImageRefresher( 2000 );
    });
</script>
<?php
}
if($fullscreen) {
?>
</div>
<?php
}

if(!$fullscreen)
    include("includes/footer.php");
else
    include("includes/footer_lite.php");
?>
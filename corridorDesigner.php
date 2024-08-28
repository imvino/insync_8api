<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Management Group View Designer";
$breadCrumb = "<h1>Settings <small>Management Group View Designer</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/corridorDesigner.css"/>
<script language="javascript" type="text/javascript" src="/js/plugins/json2.js"></script>
<script language="javascript" type="text/javascript" src="/js/corridorDesigner.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.form.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.hcsticky.js"></script>
<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if (empty($permissions["configure"])) {
    echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}
?>

<div class="row three">
    <label for="corridorTitle">Management Group Title</label>
    <input type="text" id="corridorTitle" class="input-green corridor-title" value="Unnamed Management Group" placeholder="Corridor Title">
</div>
<div class="designer-wrapper">
    <div class="toolbar">
        <ul id="menuButtons" class="toolbar-btns clearfix">
            <li class="clear"><button id="clear" class="btn btn-outline btn-clear">Clear Designer</button></li>
            <li class="first"><a id="btn-add" class="btn btn-default btn-add"><span class="icon icon-default"></span>Add<span class="arrow"></span></a>
                <div id="submenu-add" class="toolbar-submenu">
                    <ul>
                        <li><a href="#" id="addIntersection" target="corridorContainer">Intersection</a></li>
                        <li><a href="#" id="addCustomColumn" target="corridorContainer">Column</a></li>
                    </ul>
                </div>
            </li>
            <li><button id="rotate" class="btn btn-default btn-rotate"><span class="icon icon-default"></span>Rotate Layout</button></li>
            <li><button id="save" class="btn btn-default btn-save"><span class="icon icon-default"></span>Save to Processor</button></li>
<?php
require_once("helpers/networkHelper.php");

$intersections = getCorridorIntersections();

if(count($intersections) != 0)
    echo '<li><button id="propagate" class="btn btn-default btn-save"><span class="icon icon-default"></span>Save and Propagate</button></li>';
?>
            <li><button id="download" class="btn btn-default btn-download"><span class="icon icon-default"></span>Download</button></li>
            <li><button id="import" class="btn btn-default btn-file"><span class="icon icon-default"></span>Import from File</button></li>
        </ul>
    </div>
    <div id="corridorContainer" class="corridor-designer row">
        <p id="beginDesign" class="lead">Use the buttons above to add cameras to this management group view.</p>
    </div>
</div>

<div id="dialog-import" title="Import File">
    <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
    <p>Imported data will be appended to the current Management Group Design.</p>
    <p>Choose a file to import:</p>
    <form id="uploadForm" action="helpers/corridorDesignerHelper.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload"/>
        <div class="inline-block">
            <input type="file" name="file"/>
        </div>
        <div class="inline-block">
            <input type="submit" class="btn btn-default" value="Submit" name="uploadSubmitter"/>
        </div>
    </form>
</div>

<div id="dialog-add-column-camera" title="Add Custom Camera">
    <p>Enter the IP address to the processor that controls the intersection camera you want to add. The <strong>Management Group Designer</strong> will retrieve a list of the cameras assigned to that intersection.</p>
    <div class="form-horizontal">
        <div class="controls-row">
            <label for="intersection-column-ip" class="control-label medium">Intersection IP</label>
            <div class="controls medium">
                <input type="text" id="intersection-column-ip" class="input-green input-medium" placeholder="Intersection IP"/> 
            </div>
        </div>
        <div class="controls-row">
            <label for="addColumnCamName" class="control-label medium">Camera Name</label>
            <div class="controls medium">
                <div class="inline-block">
                    <select id="addColumnCamName" class="single-select chosen-dialog span3" disabled><option>Update List First</option></select>
                </div>
                <div class="inline-block">
                    <button id="getList" class="btn btn-default">Update List</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="dialog-add-intersection" title="Add Intersection">
    <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
    <p>Enter the IP address to the processor that controls the intersection you want to add. The <strong>Management Group Designer</strong> will retrieve a list of the cameras assigned to that intersection.</p>
    <div class="form-horizontal">
        <div class="controls-row">
            <label for="intersection-ip" class="control-label">Intersection IP</label>
            <div class="controls">
                <input type="text" id="intersection-ip" class="input-green input-medium" placeholder="Intersection IP"/>
            </div>
        </div>
    </div>
</div>

<div id="dialog-configure" title="Configure Camera">
    <div class="form-horizontal">
        <div class="controls-row">
            <label for="camList" class="control-label small">Camera</label>
            <div class="controls small">
                <select id="camList" class="single-select chosen-dialog span3"></select>
            </div>
        </div>
    </div>
</div>

<div id="dialog-propagate-status" title="Propagation Status">
    <div id="propagationStatusContainer"><p>Please wait while your changes are sent to the management group...</p></div>
</div>

<div id="dialog-propagate-confirm" title="Confirm Propagation">
    <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
    <p>Are you sure you want to save this Management Group View and send it to all other processors on this management group?</p>
</div>

<div id="dialog-clear" title="Confirm Overwrite">
    <p>Are you sure you want to delete all data from the Management Group Designer?</p>
</div>

<?php

$loggedIn = true;
$portalData = "";

require_once("helpers/pathDefinitions.php");
require_once("helpers/corridorDesignerHelper.php");
require_once("helpers/networkHelper.php");


$Intersections = getCorridorIntersectionsIncludingSelf();
$ips = [];
if($Intersections !== FALSE)
{
    foreach ($Intersections as $ip => $name)
    {
        $ips[] = $ip;
    }
}
echo "<script>setIPs(" . json_encode($ips) . ")</script>";

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
            
    $("#beginDesign").remove();

    $("#corridorTitle").val(importData.title);

    var title = "Unnamed Management Group";
    if(typeof importData.title != "undefined")
        title = importData.title;

    $("#corridorTitle").val(title);

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
            
    documentModified = false;
    });
</script>
<?php
}

include("includes/footer.php")
?>

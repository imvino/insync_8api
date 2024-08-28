<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Portal Designer";
$breadCrumb = "<h1>Settings <small>Portal Designer</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/portalDesigner.css"/>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.base64.min.js"></script>
<script language="javascript" type="text/javascript" src="/js/plugins/json2.js"></script>
<script language="javascript" type="text/javascript" src="/js/portalDesigner.js"></script>
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
    <label for="portalTitle">Portal Title</label>
    <input type="text" id="portalTitle" class="portal-title input-green" value="Portal Title" placeholder="Portal Title"/>
</div>

<div class="designer-wrapper">
    <div class="toolbar">
        <ul id="menuButtons" class="toolbar-btns clearfix">
            <li class="clear"><button id="clear" class="btn btn-outline btn-clear">Clear Designer</button></li>
            <li class="first"><a id="btn-add" class="btn btn-default btn-add"><span class="icon icon-default"></span>Add<span class="arrow"></span></a>
                <div id="submenu-add" class="toolbar-submenu">
                    <ul>
                        <li><a href="#" id="addCorridor" target="portalContainer">Management Group</a></li>
                        <li><a href="#" id="addMap" target="portalContainer">Map</a></li>
                    </ul>
                </div>
            </li>
            <li><button id="savePortal" class="btn btn-default btn-save"><span class="icon icon-default"></span>Save to Processor</button></li>
<?php
require_once("helpers/networkHelper.php");

$intersections = getCorridorIntersections();

if(count($intersections) != 0)
    echo '<li><button id="propagatePortal" class="btn btn-default btn-save"><span class="icon icon-default"></span>Save and Propagate</button></li>';
?>
            <li><button id="downloadPortal" class="btn btn-default btn-download"><span class="icon icon-default"></span>Download</button></li>
            <li><button id="autoGen" class="btn btn-default btn-generate"><span class="icon icon-default"></span>Auto Generate</button></li>
            <li><button id="import" class="btn btn-default btn-file"><span class="icon icon-default"></span>Import from File</button></li>
        </ul>
    </div>
    <div id="portalContainer" class="portal-designer row">
        <p id="beginDesign" class="lead">Use the buttons above to start designing this portal page.</p>
    </div>
</div>

<div id="dialog-import" title="Import File">
    <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
    <p>Choose a file to import:</p>
    <form id="uploadForm" action="helpers/portalDesignerHelper.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload"/>
        <div class="inline-block">
            <input type="file" name="file"/>
        </div>
        <div class="inline-block">
            <input type="submit" class="btn btn-default" value="Submit" name="uploadSubmitter"/>
        </div>
    </form>
</div>

<div id="dialog-propagate-status" title="Propagation Status">
  <div id="propagationStatusContainer"><p>Please wait while your changes are sent to the management group...</p></div>
</div>

<div id="dialog-propagate-confirm" title="Confirm Propagation">
    <div class="error-msg" style="margin: 0; color: #e88737; font-weight: 600;"></div>
    <p>Are you sure you want to save this Portal and send it to all other processors on this management group?</p>
</div>

<div id="dialog-clear" title="Confirm Overwrite">
  <p>Are you sure you want to delete all data from the Portal Designer?</p>
</div>

<div id="dialog-import-choose" title="Choose Method">
  <p>Please select if you would like to append this XML to the existing Design, or overwrite it.</p>
  <p><strong>Overwriting will DELETE all existing data on the Portal Designer.</strong></p>
</div>

<div id="dialog-import-confirm-csv" title="Confirm Overwrite">
  <p>Importing a CSV will <strong>clear</strong> the existing Design and replace it with the imported Portal CSV.</p>
  <p>Live Views and Map links will also not be imported, and will need to be created manually.</p>
  <p>Do you want to continue with this action?</p>
</div>

<div id="dialog-import-confirm-portal" title="Append or Replace">
  <p>Would you like to replace the existing portal, or append to it?</p>
</div>

<?php

$loggedIn = true;
$portalData = "";

require_once("helpers/pathDefinitions.php");
require_once("helpers/portalDesignerHelper.php");
if(file_exists(PORTAL_CONF_FILE))
{
    $portalXML = simplexml_load_file(PORTAL_CONF_FILE);
    $portalData = generateFromPortalXML($portalXML);
    $jsonData = json_encode($portalData);
    
?>
<script>
    importedData = <?=$jsonData?>;
            
    $(document).ready(function() {
        currentTarget = "#portalContainer";
        $(currentTarget).empty();

        $("#portalTitle").val(importedData.title.replace(/&quot;/g, "\""));

        for(i=0; i < importedData.data.length; i++) {
            var node = importedData.data[i];

            if (node.type == "map")
                addMap(currentTarget, node.name.replace(/&quot;/g, "\""), node.url, false);
            else if (node.type == "corridor")
                generateCorridor(node.name.replace(/&quot;/g, "\""), node.data, false);

            currentTarget = "#portalContainer";
        }

        documentModified = false;
    });
</script>
<?php
}

include("includes/footer.php")
?>

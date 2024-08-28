<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Re-IP";
$breadCrumb = "<h1>Settings <small>Re-IP</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/reip.css"/>
<script language="javascript" type="text/javascript" src="/js/plugins/json2.js"></script>
<script language="javascript" type="text/javascript" src="/js/reip.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.form.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.hcsticky.js"></script>
<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if (empty($permissions["configure"])) 
{
    echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}
?>

<div class="row ten">
    <div id="documentContainer" class="panel">
        <div id="beginDesign"><p class="lead">Use the buttons at the right to configure IP addresses.</p></div>
    </div>
</div>
<div id="menuButtons">
    <button id="addIntersection" class="btn btn-default" target="corridorContainer">+ Intersection</button><br />
    <br />
    <button id="save" class="btn btn-default">Apply to Processor</button><br />
    <button id="propagate" class="btn btn-default">Apply and Propagate</button><br /><br />
    <button id="generate" class="btn btn-default">Generate from Management Group</button><br />
    <button id="import" class="btn btn-default">Import from File</button><br />
    <button id="download" class="btn btn-default">Download</button><br />
    <br /><br />
    <button id="clear" class="btn btn-default">Clear Designer</button>
</div>

<div id="dialog-import" title="Import File">
    <p>Imported data will overwrite the existing document.</p>
    <p>Choose a file to import:</p>
    <form id="uploadForm" action="helpers/reipHelper.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload" />
        File: <input type="file" name="file" />
        <input type="submit" value="Submit" name="uploadSubmitter" />
    </form>
</div>

<div id="dialog-propagate-status" title="Propagation Status">
    <div id="propagationStatusContainer" style="height: 200px;overflow: auto"><p>Please wait while your changes are sent to the management group...</p></div>
</div>

<div id="dialog-propagate-confirm" title="Confirm Propagation">
    <p>Are you sure you want to save this Re-IP document and apply it to all other processors on this management group?</p>
</div>

<div id="dialog-clear" title="Confirm Clear">
    <p>Are you sure you want to delete all data from the Re-IP tool?</p>
</div>

<?php
include("includes/footer.php")
?>
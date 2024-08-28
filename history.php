<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": History Viewer";
$breadCrumb = "<h1>Reports <small>History Viewer</small></h1>";
$menuCategory = "reports";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/history.css"/>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.stickytableheaders.js"></script> 
<script language="javascript" type="text/javascript" src="/js/history.js"></script> 
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.timepicker.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.keysequencedetector.js"></script>
<script language="javascript" type="text/javascript" src="/js/plugins/date.js"></script>
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

if(empty($permissions["reports"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

?>

<div class="row">
	<div class="inline-block">
		<label for="startDateTime">Start Date/Time:</label>
		<input type="text" id="startDateTime" class="input-large input-purple" name="startDateTime" value="<?php if (!isset($_REQUEST['startDateTime'])) echo date("m/d/Y") . " 12:00 AM";
else echo $_REQUEST['startDateTime']; ?>" placeholder="Start Date/Time"/> 
	</div>
	<div class="inline-block">
		<label for="endDateTime">End Date/Time:</label>
		<input type="text" id="endDateTime" class="input-large input-purple" name="endDateTime" value="<?php if (!isset($_REQUEST['endDateTime'])) echo date("m/d/Y") . " 11:59 PM";
else echo $_REQUEST['endDateTime']; ?>" placeholder="End Date/Time"/> 
	</div>
</div>
<div class="row section" style="position:relative;z-index:7;">
	<label for="optionInclude">Include:</label>
	<select id="optionInclude" class="chosen-multiselect multi-select" data-placeholder="" multiple>
		<optgroup label="General Movements">
			<option value="t" selected>Phase Volumes</option>
			<option value="ped" selected>Pedestrians</option>
		</optgroup>
		<optgroup label="Results">
			<option value="e" selected>Errors</option>
			<option value="s" selected>Successes</option>
            <option value="tun">Tunnels</option>
		</optgroup>
		<optgroup label="Periods">
			<option value="per" selected>Period Changes</option>
		</optgroup>
	</select>
</div>
<div class="row section" style="position:relative;z-index:5;">
	<label for="optionMovements">Limit to Movements Including:</label>
	<select id="optionMovements" class="chosen-multiselect multi-select" data-placeholder="" multiple>
		<optgroup label="Movements">
<?php
$associationArray = [];

require_once("helpers/databaseInterface.php");
$intersection = getFile("Intersection.xml");
$intersectionXML = @simplexml_load_string($intersection);

if($intersectionXML !== FAlSE)
{
    foreach($intersectionXML->Intersection->Direction as $Directions)
    {
        if($Directions["name"] == "North")
        {
            foreach($Directions->Phases->Phase as $Phase)
            {
                // thru
                if((int)$Phase["name"] % 2 == 0)
                    $associationArray["SouthBoundThrough"] = (int)$Phase["name"];
                // left
                else
                    $associationArray["SouthBoundLeftTurn"] = (int)$Phase["name"];
            }
        }
        else if($Directions["name"] == "South")
        {
            foreach($Directions->Phases->Phase as $Phase)
            {
                // thru
                if((int)$Phase["name"] % 2 == 0)
                    $associationArray["NorthBoundThrough"] = (int)$Phase["name"];
                // left
                else
                    $associationArray["NorthBoundLeftTurn"] = (int)$Phase["name"];
            }
        }
        else if($Directions["name"] == "West")
        {
            foreach($Directions->Phases->Phase as $Phase)
            {
                // thru
                if((int)$Phase["name"] % 2 == 0)
                    $associationArray["EastBoundThrough"] = (int)$Phase["name"];
                // left
                else
                    $associationArray["EastBoundLeftTurn"] = (int)$Phase["name"];
            }
        }
        else if($Directions["name"] == "East")
        {
            foreach($Directions->Phases->Phase as $Phase)
            {
                // thru
                if((int)$Phase["name"] % 2 == 0)
                    $associationArray["WestBoundThrough"] = (int)$Phase["name"];
                // left
                else
                    $associationArray["WestBoundLeftTurn"] = (int)$Phase["name"];
            }
        }
    }
}

require_once("helpers/phaseHelper.php");
$phaseNames = getPhaseNames();

foreach($associationArray as $dir=>$num)
    echo "<option value='$dir'>" . $phaseNames[$num]["long"] . "</option>\r\n";
?>
		</optgroup>
	</select>
</div>
<div class="row section">
	<div class="inline-input">
	    <p>Highlight wait times over <input type="text" id="hilite" class="span1 input-green" name="hilite"/> seconds</p>
	</div>
    <div class="inline-input">
    	<p>Only show wait times over <input type="text" id="waitfilter" class="span1 input-green" name="waitfilter"/> seconds</p>
    </div>
</div>
<div class="row">
	<button id="submit" class="btn btn-default green">Load History</button>
<?php
        if($permissions["username"] != "kiosk") {
?>
        <button id="download" class="btn btn-default">Download History</button>
<?php
        }
?>
</div>

<div id="historyContents" class="row panel"></div>

<div id="dialog-confirm-hang" title="Warning">
    <div class="warning">
        <p><strong>WARNING:</strong><br/>Hiding or showing Phase Volumes when a large number of items are displayed can cause your browser to hang for several minutes while processing.</p>
    </div>
	<p>Do you want to continue?</p>
</div>

<?php
if($permissions["username"] == "kiosk")
    include("includes/footer_lite.php");
else
    include("includes/footer.php")
?>
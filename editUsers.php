<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Edit Users";
$breadCrumb = "<h1>Account <small>Edit Users</small></h1>";
$menuCategory = "account";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/account.css"/>
<script language="javascript" type="text/javascript" src="/js/editUser.js"></script>
\t\t
<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["adminUsers"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

$loggedIn = true;
require_once("helpers/pathDefinitions.php");
require_once("helpers/networkHelper.php" );

$Intersections = getCorridorIntersections();
?>
<div class="row">
	<div class="panel five">
		<table class="users-table table table-fullwidth table-striped">
			<thead>
				<tr>
					<th>Users</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
			</tead>
			<tbody id="userTableBody">
				<tr><td><p>Loading data...</p></td></tr>
			</tbody>
		</table>
		<button id="addUser" class="btn btn-default">New User</button>
<?php
if(count($Intersections) != 0)
	echo '<button id="sync" class="btn btn-default">Propagate Users</button>';
?>
	</div>
</div>

<div id="addUserDialog" title="Add New User">
	<div class="form-horizontal">
		<div class="control-group">
			<label for="addUsername" class="control-label">Username</label>
			<div class="controls">
				<input type="text" id="addUsername" class="input-large input-green" placeholder="Username"/>
			</div>
		</div>
		<div class="control-group">
			<label for="addPassword" class="control-label">Password</label>
			<div class="controls">
				<input type="password" id="addPassword" class="input-large input-green" placeholder="Password"/>
			</div>
		</div>
        <div class="control-group">
			<label for="addPassword2" class="control-label">Confirm Password</label>
			<div class="controls">
				<input type="password" id="addPassword2" class="input-large input-green" placeholder="Confirm Password"/>
			</div>
		</div>
		<div class="control-group">
            <label for="addWebAPI" class="control-label">Web / API Access</label>
            <div class="controls">
                <div style="position:relative;z-index:1004;display:inline;">
                    <select id="addWebAPI" class="chosen-dialog single-select span3">
                        <option value="api">API Only</option>
                        <option value="webapi" selected="selected">WebUI &amp; API</option>
                    </select>
                </div>
                <div class="inline-help" inlinehelp="<ul><li><strong>API Only:</strong> User has access to the InSync WebUI API only.</li><li><strong>WebUI &amp; API:</strong> User has access to both options.</li></ul>">
					<span class="icon-default"></span>
					<span class="icon-hover"></span>
				</div>
            </div>
        </div>
        <div class="control-group">
            <label for="addCameras" class="control-label">Cameras</label>
            <div class="controls">
                <div style="position:relative;z-index:1003;display:inline;">
                    <select id="addCameras" class="chosen-dialog single-select span3">
                        <option value="d">Disabled</option>
                        <option value="v" selected="selected">View Only</option>
                        <option value="vc">View / Control</option>
                    </select> 
                </div>
                <div class="inline-help" inlinehelp="<ul><li><strong>Disabled:</strong> No access to cameras.</li><li><strong>View Only:</strong> User can view cameras but not record or control them.</li><li><strong>View &frasl; Record:</strong> User has full access to view, control, and record cameras.</li></ul>">
					<span class="icon-default"></span>
					<span class="icon-hover"></span>
				</div>
            </div>
        </div>
        <div class="control-group">
            <label for="addSession" class="control-label">Session Timeout</label>
            <div class="controls">
                <select id="addSession" class="chosen-dialog single-select span2">
                    <option value="0">Disabled</option>
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="30" selected="selected">30</option>
                    <option value="45">45</option>
                    <option value="60">60</option>
                    <option value="180">120</option>
                    <option value="540">540</option>
                </select> Minutes
                <div class="inline-help" inlinehelp="<ul><li>The number of minutes your browser can sit</li><li>idle before you are logged out of the WebUI</li></ul>">
					<span class="icon-default"></span>
					<span class="icon-hover"></span>
				</div>
            </div>
        </div>
        <table class="table add-user-options">
        	<tbody>
        		<tr>
        			<td><label for="addEnabled">User Enabled</label></td>
        			<td>
        				<input type="checkbox" id="addEnabled" class="pretty" checked/>
	        			<div class="inline-help" inlinehelp="<ul><li>Chooses if the user is active &frasl; inactive.</li><li>If they are not enabled, they cannot login.</li></ul>">
							<span class="icon-default"></span>
							<span class="icon-hover"></span>
						</div>
					</td>
        			<td><label for="addReports">View Reports</label></td>
        			<td>
        				<input type="checkbox" id="addReports" class="pretty"/>
        				<div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables viewing of Reports pages.</li></ul>">
							<span class="icon-default"></span>
							<span class="icon-hover"></span>
						</div>
        			</td>
        		</tr>
        		<tr>
        			<td><label for="addMaintenance">InSync Maintenance</label></td>
        			<td>
        				<input type="checkbox" id="addMaintenance" class="pretty"/>
						<div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables access to the Maintenance page.</li></ul>">
							<span class="icon-default"></span>
							<span class="icon-hover"></span>
						</div>
					</td>
        			<td><label for="addCorridor">Management Group Maintenance</label></td>
        			<td>
        				<input type="checkbox" id="addCorridor" class="pretty"/>
        				<div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables access to Management Group Maintenance.</li></ul>">
							<span class="icon-default"></span>
							<span class="icon-hover"></span>
						</div>
        			</td>
        		</tr>
        		<tr>
        			<td><label for="addConfiguration">Configure Intersection</label></td>
        			<td>
        				<input type="checkbox" id="addConfiguration" class="pretty"/>
	        			<div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables configuration via</li><li>the &quot;Configure Detectors&quot; web utility.</li></ul>">
							<span class="icon-default"></span>
							<span class="icon-hover"></span>
						</div>
					</td>
        			<td><label for="addManual">Manual Traffic Calls</label></td>
        			<td>
        				<input type="checkbox" id="addManual" class="pretty"/>
        				<div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables placing of manual</li><li>calls, queues, and pedestrian button pushes.</li></ul>">
							<span class="icon-default"></span>
							<span class="icon-hover"></span>
						</div>
        			</td>
        		</tr>
        		<tr>
        			<td><label for="addAdmin">Administer Users</label></td>
        			<td>
        				<input type="checkbox" id="addAdmin" class="pretty"/>
	        			<div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables administration of users.</li><li>With this flag, users can add, edit, and delete any user!</li></ul>">
							<span class="icon-default"></span>
							<span class="icon-hover"></span>
						</div>
					</td>
        			<td>&nbsp;</td>
        			<td>&nbsp;</td>
        		</tr>
        	</tbody>
        </table>
	</div>
</div>

<div id="editUserDialog" title="Edit User">
	<p>Please wait...</p>
</div>

<div id="deleteUserDialog" title="Delete User?">
	<p>Are you sure you want to delete user <strong><span id="deleteUsername"></span></strong>?</p>
</div>

<div id="syncConfirm" title="Propagate Users">
	<p>This will attempt to synchronize these user accounts on all other processors in the management group.<p>
    <div class="warning">
    	<p><strong>WARNING:</strong><br/>All user accounts on the management group processors listed will be removed and updated with this new list.</p>
        <div style="overflow:auto;max-height:200px;">
            <ul>
<?php
foreach ($Intersections as $ip => $name)
{
	echo "<li>$name - $ip</li>";
}
?>
        	</ul>
    	</div>
    </div>
	<p>Are you sure you want to continue?</p>
</div>

<div id="synchronizingDialog" title="Propagating...">
	<p class="text-center">Propagating to other intersections in the management group...</p>
	<center><img src="/img/wait-spinner.gif" /></center>
</div>

<script>
$(function() {initScripts(<?php echo count($Intersections) . ",'" . $permissions["username"] ?>');});
</script>

<?php
include("includes/footer.php");
?>

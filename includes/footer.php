<?php
$scriptFile = $_SERVER['SCRIPT_FILENAME'];
$pathParts = pathinfo($scriptFile);
$activeClass = $pathParts["filename"];

require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/FileIOHelper.php");

// get system type (0=InSync, 1=Hawkeye etc.)
$systemConfigurationType = getSystemType();

if (!isset($menuCategory)) {
    $menuCategory = '';
}
?>

					</div>
                </div>
            </section>
            <section id="icon-panel" class="icon-panel">
                <ul>
<?php
if(isset($permissions["cameras"]))
{
    echo '<li><a href="#corridor-menu" class="corridor ';
    if($menuCategory == "corridor")
        echo "active"; 
    echo '"><span class="icon-default"></span><span class="icon-hover"></span><span class="icon-active"></span>Management Group</a></li>';
}

if(isset($permissions["cameras"]))
{
    echo '<li><a href="#views-menu" class="views ';
    if($menuCategory == "views")
        echo "active"; 
    echo '"><span class="icon-default"></span><span class="icon-hover"></span><span class="icon-active"></span>Views</a></li>';
}

if(isset($permissions["reports"]))
{
    echo '<li><a href="#reporting-menu" class="reports ';
    if($menuCategory == "reports")
        echo "active"; 
    echo '"><span class="icon-default"></span><span class="icon-hover"></span><span class="icon-active"></span>Reports</a></li>';
}

if(isset($permissions["configure"]) || isset($permissions["maintenance"]))
{
    echo '<li><a href="#settings-menu" class="settings ';
    if($menuCategory == "settings") 
        echo "active";
    echo '"><span class="icon-default"></span><span class="icon-hover"></span><span class="icon-active"></span>Settings</a></li>';
}

if(isset($permissions["adminUsers"]))
{
    echo '<li><a href="#account-menu" class="account ';
    if($menuCategory == "account") 
        echo "active";
    echo '"><span class="icon-default"></span><span class="icon-hover"></span><span class="icon-active"></span>Account</a></li>';
}
?>
                </ul>
                <div class="footer">
                    <a href="/help" class="btn btn-outline help" target="_blank">Help</a>
                </div>
            </section>
            <section id="sidebar" class="sidebar">
<?php

if(isset($permissions["cameras"]))
{
    echo '<ul id="corridor-menu" class="menu-panel">
        <li><a href="/portal.php">Portal</a></li>
        <li><a href="/corridorViewer.php">Management Group View</a></li>
        <li><a href="/map/index.php">Map</a></li>';
    
//	Commenting these lines to remove CentralSync option from webUI. 
//   If another option needs to be added please do so here.     
//    if(isset($permissions["corridor"]))
//        echo '<li><a href="#" id="menu-centralsync">CentralSync</a></li>';
    
    if(isset($permissions["reports"]))
        echo '<li><a href="/corridorStatusNew.php">Status</a></li>';
    
    echo '</ul>';
}

if(isset($permissions["cameras"]))
{
    echo '<ul id="views-menu" class="menu-panel">
        <li><a href="/cameraViewMulti.php">Multi Camera View</a></li>
        <li><a href="/cameraViewSingle.php">Single Camera View</a></li>
        <li><a href="/recording.php">Recording Options</a></li>
        </ul>';
}

if(isset($permissions["reports"]))
{
    if(isset($permissions["username"]) && $permissions["username"] == "PEC" && $systemConfigurationType === 1)
	{
		echo '<ul id="reporting-menu" class="menu-panel">
			<li><a href="/dailySummary.php">Daily Summary</a></li>
			<li><a href="/statistics.php">Statistics</a></li>
			<li><a href="/history.php">History Viewer</a></li>
			<li><a href="/radarerrors.php">Radar Errors</a></li>
			<li><a href="/notifications.php">Notifications</a></li>
			</ul>';
	}
	else
	{
		echo '<ul id="reporting-menu" class="menu-panel">
			<li><a href="/dailySummary.php">Daily Summary</a></li>
			<li><a href="/statistics.php">Statistics</a></li>
			<li><a href="/history.php">History Viewer</a></li>
			<li><a href="/notifications.php">Notifications</a></li>
			</ul>';		
	}
}

if(isset($permissions["configure"]) || isset($permissions["maintenance"]))
{
    echo '<ul id="settings-menu" class="menu-panel">';

    if(isset($permissions["configure"])) echo "<li><a href='/config/configure.php'>Configure Detectors</a></li>";
    if(isset($permissions["configure"])) echo "<li><a href='/renamePhases.php'>Rename Phases</a></li>";
	if(isset($permissions["configure"])) echo "<li><a href='/emailTester.php'>Email Test</a></li>";
    //if(isset($permissions["configure"])) echo "<li><a href='/reip.php'>Re-IP</a></li>";
    if(isset($permissions["configure"])) echo "<li><a href='/gps.php'>GPS Coordinates</a></li>";
    if(isset($permissions["configure"])) echo "<li><a href='/ntp.php'>NTP Server</a></li>";
    if(isset($permissions["configure"])) echo "<li><a href='/corridorDesigner.php'>Management Group View Designer</a></li>";
    if(isset($permissions["configure"])) echo "<li><a href='/portalDesigner.php'>Portal Designer</a></li>";
    if(isset($permissions["maintenance"])) echo "<li><a href='/maintenance.php'>Maintenance</a></li>";
                  
    if(isset($permissions["username"]) && ($permissions["username"] == "PEC") || ($permissions["username"] == "ADMIN"))	
    {
        if($activeClass == "troubleshooting")
            echo '<li><a href="/troubleshooting.php" class="active">Troubleshooting</a></li>';
        else
            echo '<li><a href="/troubleshooting.php">Troubleshooting</a></li>';
    }
    
    echo '</ul>';
}

if(isset($permissions["adminUsers"]))
{
    echo '<ul id="account-menu" class="menu-panel">
        <li><a href="/invalidLogins.php">Invalid Logins</a></li>
        <li><a href="/editUsers.php">Edit Users</a></li>
    </ul>'; 
}
?>
            </section>
        </div>
    </div>
	
<div id="dialog-timeout-notice" title="Session Timeout" style="display:none;">
	<p>You will be logged off due to inactivity in 60 seconds.</p>
	<p>Press Cancel to extend your session.</p>
</div>
<!-- Commenting the CentralSync Launcher that is displayed when CentralSync button is used.	
<div id="dialog-centralsync" title="CentralSync Launcher" style="display:none;">
	<p>You must have a current version of CentralSync installed to use this feature.</p>
	<p>If you do not have CentralSync, please <a href="http://www.rhythmtraffic.com/tools/CentralSync/v1.4/" class="text-link" target="_blank">visit our website</a> to download.</p>
	<p><input type="checkbox" id="hide-centralsync" class="pretty" data-label="Don't show this dialog again"/></p>
</div> -->

<div id="dialog-logout-confirm" title="Confirm Logout" style="display:none;">
	<p>Are you sure you want to log off the InSync WebUI?</p>
</div>
	
<div id="please-wait" class="busy_overlay" style="display: none;">
    <span class="busy_icon" />
</div>
<script src="/js/jquery/jquery.offcanvas.js"></script>
<script src="/js/main.js"></script>
<script src="/js/sessionTimer.js.php"></script>
</body>
</html>

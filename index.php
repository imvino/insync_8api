<?php
// this must be included on all pages to authenticate the user
// require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
// $permissions = authSystem::ValidateUser();
// end

$title = ": Home";
$breadCrumb = "<h1>Home</h1>";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/dashboard.css"/>

<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["web"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}
?>

<div class="row">
        <ul class="dash-row">
<?php
if(isset($permissions["cameras"]))
{
    echo '<li>
            <div class="dash-item">
                <a href="/cameraViewMulti.php" class="dash-img dash-multiview">
                    <div class="dash-info">
                        <h2>Multi Camera View</h2>
                    </div>
                </a>  
            </div>
        </li>';
}

if(isset($permissions["reports"]))
{
    echo '<li>
            <div class="dash-item">
                <a href="/dailySummary.php" class="dash-img dash-dailysum">
                    <div class="dash-info">
                        <h2>Daily Summary</h2>
                    </div>
                </a>  
            </div>
        </li>';
    
    echo '<li>
            <div class="dash-item">
                <a href="/history.php" class="dash-img dash-histview">
                    <div class="dash-info">
                        <h2>History Viewer</h2>
                    </div>
                </a>  
            </div>
        </li>';
		
    // These options are commented out so that they won't shown up on the Home page of webUI.
	// Un-comment if you need to add them back
	//echo '<li>
    //        <div class="dash-item">
    //            <a href="/notifications.php" class="dash-img dash-histview">
    //                <div class="dash-info">
    //                    <h2>Notifications</h2>
    //                </div>
    //            </a>  
    //        </div>
    //    </li>';		
		
    //echo '<li>
    //        <div class="dash-item">
    //            <a href="/radarerrors.php" class="dash-img dash-histview">
    //                <div class="dash-info">
    //                    <h2>Radar Errors</h2>
    //               </div>
    //            </a>  
    //        </div>
    //    </li>';
		
}

if(isset($permissions["adminUsers"]))
{
    echo '<li>
            <div class="dash-item">
                <a href="/invalidLogins.php" class="dash-img dash-invalidlog">
                    <div class="dash-info">
                        <h2>Invalid Logins</h2>
                    </div>
                </a>  
            </div>
        </li>';
}
?>
    </ul>
</div>

<?php
include("includes/footer.php");
?>
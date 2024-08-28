<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Configure Detectors";
$breadCrumb = "<h1>Settings <small>Configure Detectors</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<!-- END HEADER -->
HEAD_WRAP
;

include("../includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["configure"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("../includes/footer.php");
    exit;
}

?>
<div class="row">
    <button id="launch" class="btn btn-default">Launch Configuration Utility</button>
</div>

<script>
	$(document).ready(function() {
		$('#launch').click(function() {
			window.location = "ConfigLaunch.php";
			return false;
		});
	});
</script>

<?php
include("../includes/footer.php");
?>
<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/webdb.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Invalid Logins";
$breadCrumb = "<h1>Account <small>Invalid Logins</small></h1>";
$menuCategory = "account";

$head = <<<HEAD_WRAP
<!-- HEADER -->

<link rel="stylesheet" type="text/css" href="/css/account.css"/>

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

?>

<div class="row panel invalid-logins inline-block">
	<table class="table table-striped">
		<thead>
			<tr><th>Date</th><th>Failed Username</th><th>Source IP</th></tr>
		</thead>
		<tbody id="userTableBody">
<?php
$db = openWebDB();
	
if(!$db)
    echo "<tr><td>Error: Could not open database.</td></tr>";

$count = 0;

if ($result = pg_query($db, 'SELECT "timestamp","user",ip FROM invalid_logins ORDER BY "timestamp" DESC')) 
{
while ($row = pg_fetch_assoc($result)) 
{
	echo '<tr>';
	echo '<td>' . (new DateTime($row['timestamp'], new DateTimeZone("UTC")))
                ->setTimezone(new DateTimeZone(date_default_timezone_get()))
                ->format("m/d/Y g:i:s A") . '</td>';
	echo '<td>' . htmlspecialchars($row['user']) . '</td>';
	echo '<td>' . htmlspecialchars($row['ip']) . '</td>';
	echo '</tr>';

	$count++;
}

if($count == 0)
	echo "<tr><td colspan='3'><p>No invalid login attempts logged.</p></td></tr>";

}

pg_close($db);
?>
		</tbody>
	</table>
</div>

<?php
include("includes/footer.php")
?>

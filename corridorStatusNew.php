<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Status";
$breadCrumb = "<h1>Management Group <small>Status</small></h1>";
$menuCategory = "corridor";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/corridorStatus.css"/>
<!-- END HEADER -->
HEAD_WRAP;

include("includes/header.php");
require_once("helpers/networkHelper.php");

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
    <div class="inline-block panel">
        <table class="table table-striped">
            <thead>
			<!--	<meta http-equiv="refresh" content="5" >	Auto refresh after 5 seconds -->		
                <tr>
                    <th style="width: 250px">Intersection</th>
                    <th style="width: 150px">IP</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="status_body">

<?php

	// create a stream context for 5 sec. timeout
	// of reception of data...we can't wait forever
	$ctx = stream_context_create(['http'=>
		['timeout' => 5]]);	
		
		$Intersections = getCorridorIntersections();
		//$myfile = fopen("C:\Newfile.txt", "w") or die("Unable to open file!");
	
		if($Intersections !== FALSE)
		{	
			foreach ($Intersections as $ip => $name)
			{
				$xmlString = file_get_contents("http://" . $ip . "/specialcalls.php", false, $ctx);

				// Did not get a response from the intersection
				if ($xmlString !== false)
				{
					$IPAddr = "";
					$xml = simplexml_load_string($xmlString);
					foreach ($xml->Network[0] -> attributes() as $a => $b)
					{
						if ($a == "IP")		
						{
							$IPAddr = $b;

							//$txt = $IPAddr . $ip . "\n";
							//fwrite($myfile, $txt);												

							if ($IPAddr == $ip)
							{
								$IntersectionName = $name;
								$IPAddress = $IPAddr;
								$Status = "Online";
							}
							else
							{
								$IntersectionName = $name;
								$IPAddress = $ip;
								$Status = "Offline";
							}

							echo '<tr>';
								echo '<td>' . htmlspecialchars($IntersectionName) . '</td>';
								echo '<td>' . htmlspecialchars($IPAddress) . '</td>';

								if ($Status == "Online")
									echo '<td>' . htmlspecialchars($Status) . '</td>';
								else
									echo '<td style="color:red">'. htmlspecialchars($Status) .'</td>';

							echo '</tr>';	

						}

					}						

				}
				else
				{
					echo '<tr>';
						echo '<td>' . htmlspecialchars($name) . '</td>';
						echo '<td>' . htmlspecialchars($ip) . '</td>';
						echo '<td style="color:red">'. htmlspecialchars("Offline") .'</td>';														
					echo '</tr>';						
				}

			}
			//fclose($myfile);						
		
		}
		else
		{
			echo '<tr>';
				echo '<td>' . htmlspecialchars("Waiting...") . '</td>';
				echo '<td>' . htmlspecialchars("Waiting...") . '</td>';
				echo '<td style="color:red">'. htmlspecialchars("No Intersections to load.") .'</td>';														
			echo '</tr>';						
		}
            
?>			
			</tbody>
        </table>
    </div>
</div>


<?php
include("includes/footer.php")
?>

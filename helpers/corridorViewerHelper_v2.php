<?php
// if(!isset($loggedIn))
// {
//     // this must be included on all pages to authenticate the user
//     require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
//     $permissions = authSystem::ValidateUser();
//     // end

//     if (empty($permissions["cameras"])) 
//     {
//         echo "<h3>Error: You do not have permission to access this page.</h3>";
//         exit;
//     }
// }

//corridorDesignerHelper.php


require_once("pathDefinitions.php");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

switch($action)
{
    case "getremoteimage":
    {
        $cam = "";
        if(isset($_REQUEST['cam']))
            $cam = $_REQUEST['cam'];
        
        $ip = "";
        if(isset($_REQUEST['ip']))
            $ip = $_REQUEST['ip'];
        
        $width = 160;
        if(isset($_REQUEST['width']))
            $width = $_REQUEST['width'];
        
        $height = 120;
        if(isset($_REQUEST['height']))
            $height = $_REQUEST['height'];
        
        $quality = 80;
        if(isset($_REQUEST['quality']))
            $quality = $_REQUEST['quality'];
        
        if($quality < 1)
            $quality = 1;
        if($quality > 100)
            $quality = 100;
        

		$remoteURL = "$ip/helpers/insyncInterface_v2.php?action=getImage&viewCamera=" . urlencode($cam) . "&width=$width&height=$height&quality=$quality&session=false&u=" . urlencode(base64_encode("PEC")) . "&p=" . urlencode(base64_encode("lenpec4321"));


		$ctx = stream_context_create(array('http' => array('timeout' => 5))); 
		$results = @file_get_contents("https://$remoteURL", 0, $ctx); 

		if($results === FALSE)
		{
			$ctxR = stream_context_create(array('http' => array('timeout' => 5))); 
			$results = @file_get_contents("http://$remoteURL", 0, $ctxR); 
			if($results === FALSE)
			{
				header("Content-type: image/png");
				readfile("../img/no-camera-160.png");
				exit;
			}
		}

		header("Content-type: image/jpeg");
		echo $results;
		exit;
    }
    break;
}
?>

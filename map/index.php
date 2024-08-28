<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = "Map";
$breadCrumb = "<h1>Management Group <small>Map</small></h1>";
$menuCategory = "corridor";

require_once("../helpers/pathDefinitions.php");

if(file_exists(CORRIDOR_CONF_FILE))
{
    $head = <<<HEAD_WRAP
    <!-- HEADER -->

    <link href="stylesheets/site.css" media="screen, projection" rel="stylesheet" type="text/css" />
    <script src="https://maps.googleapis.com/maps/api/js?libraries=geometry&key=AIzaSyCWNHNmt6Ml53ng0l_NYEfMapHU9JDT0QE&v=3.27" type="text/javascript"></script>
    <!--<script src="javascripts/jquery.transform2d.js" type="text/javascript"></script>  -->
    <!--[if lte IE 8]><script language="javascript" type="text/javascript" src="javascripts/excanvas.js"></script><![endif]-->
    <script src="javascripts/jquery.hoverbox.js" type="text/javascript"></script>  
    <script src="javascripts/custommarker.js" type="text/javascript"></script>  
    <script src="javascripts/alpha_markers.js" type="text/javascript"></script>   
    <script src="javascripts/application.js" type="text/javascript"></script>   

    <!-- END HEADER -->
HEAD_WRAP;
}

include("../includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////


if(file_exists(CORRIDOR_CONF_FILE))
{
    $corridorXML = @simplexml_load_file(CORRIDOR_CONF_FILE);
    
    if($corridorXML !== FALSE)
    {    
        $error = "";
        
        foreach($corridorXML->Intersection as $Intersection)
        {
            $IP = "An intersection";
            if(isset($Intersection["IP"]) && (string)$Intersection["IP"] != "" && (string)$Intersection["IP"] != "127.0.0.1" && ip2long((string)$Intersection["IP"]) !== false)
                $IP = (string)$Intersection["IP"];
            
            foreach($Intersection->TraVisConfiguration->Intersection as $subIntersection)
            {
                $name = "An intersection";
                if(isset($subIntersection["name"]) && (string)$subIntersection["name"] != "")
                {
                    $name = (string)$subIntersection["name"];
                            
                    if($IP != "")
                        $name .= " / " . $IP;
                }
                else
                    $name = $IP;
                
                foreach($subIntersection->Direction->Phases->Phase as $phase)
                {
                    if(!isset($phase["Location"]))
                        $error .= "$name does not have a latitude/longitude set, and so this map cannot be displayed.<br />";
                    else
                        if($phase["Location"] == "")
                            $error .= "$name does not have a latitude/longitude set, and so this map cannot be displayed.<br />";

                    if(!isset($phase["Angle"]))
                        $error .= "$name does not have an intersection angle set, and so this map cannot be displayed.<br />";
                    else
                        if($phase["Angle"] == "")
                            $error .= "$name does not have an intersection angle set, and so this map cannot be displayed.<br />";                    
                }
            }
        }

        if($error == "")
        {
?>
			<script src="corridor.js.php" type="text/javascript"></script>
			<div id="wait_for_login" style="display:none;" title="Please Wait..."><p>Attempting to login to InSync...</p></div>
			<div id="mapCanvas" style="width:100%;"></div>
<?php
        }
        else
            echo $error;
    }
    else
        echo "Cannot load Corridor.xml!";
}
else
    echo "There is no Management Group file to generate a map from.";

include("../includes/footer.php");
?>

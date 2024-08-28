<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Portal";
$breadCrumb = "<h1>Management Group <small>Portal</small></h1>";
$menuCategory = "corridor";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/portal.css"/>
<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["cameras"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}
?>

<?php
require_once("helpers/pathDefinitions.php");

$protocol = "http";
if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "")
    $protocol = "https";

if(file_exists(PORTAL_CONF_FILE))
{
    $portal = @simplexml_load_file(PORTAL_CONF_FILE);
    
    if($portal != FALSE)
    {
        if($portal['title'] != "")
        {
            $title = str_replace("&quot;", "\"", $portal['title']);
            echo "<div class='row'><h2>" . htmlspecialchars($title) . "</h2></div><div id='layout-content' class='portal-page panel row'>";
        }
        else
            echo "<div class='row'><h2>Untitled Portal</h2></div><div id='layout-content' class='panel row'>";
        
        foreach($portal->children() as $child)
        {    
            if(strtolower($child->getName()) == "map")
            {
                $name = str_replace("&quot;", "\"", $child["name"]);
                echo "<div><a href='" . $child["url"] . "'>" . htmlspecialchars($name) . "</a></div>";
            }
            else if(strtolower($child->getName()) == "corridor")
            {
                $corridorID = uniqid("corridor");
                
                echo "<div id='$corridorID'>";
                
                if($child["name"] != "")
                {
                     $name = str_replace("&quot;", "\"", $child["name"]);
                    echo "<h3><em class='icon-wrapper'><em class='icon-default'></em><em class='icon-hover'></em><em class='icon-active'></em><em class='icon-active-hover'></em></em>" . htmlspecialchars($name) . "</h3>";
                }
                else
                    echo "<h3><em class='icon-wrapper'><em class='icon-default'></em><em class='icon-hover'></em><em class='icon-active'></em><em class='icon-active-hover'></em></em>Unnamed Management Group</h3>";
                
                echo "<div><table class='table table-fullwidth portal-table'><tbody>";
                
                foreach($child->children() as $subChildren)
                {
                    if(strtolower($subChildren->getName()) == "map")
                    {
                        $url = "#";
                        $name = "Unnamed Map";
                        
                        if($subChildren["url"] != "")
                            $url = $subChildren["url"];
                        
                        if($subChildren["name"] != "")
                            $name = $subChildren["name"];
                        
                        $name = str_replace("&quot;", "\"", $name);                        
                        $name = htmlspecialchars($name);
                        
                        echo "<tr><td><span class='icon icon-default icon-map'></span><a href='$url'>$name</a></td></tr>";
                    }
                    else if(strtolower($subChildren->getName()) == "intersection")
                    {
                        echo "<tr>";
                        
                        $name = "Unnamed Camera";
                        $ip = "#";
                        
                        if($subChildren["name"] != "")
                            $name = $subChildren["name"];
                        if($subChildren["ip"] != "")
                            $ip = $subChildren["ip"];
                        
                        $name = str_replace("&quot;", "\"", $name);           
                        $name = htmlspecialchars($name);
                        
                        echo "<td><a href='$protocol://$ip/cameraViewMulti.php'>$name</a></td>";
                                
                        foreach($subChildren->children() as $subSubChildren)
                        {
                            $nodeType = strtolower($subSubChildren->getName());
                            
                            if($nodeType == "camera")
                                $name = "Camera";
                            else if($nodeType == "relay")
                                $name = "DIN Relay";
                            
                            $ip = "#";
                                
                            if($subSubChildren["name"] != "")
                                $name = $subSubChildren["name"];
                            if($subSubChildren["ip"] != "")
                                $ip = $subSubChildren["ip"];
                            
                            $name = str_replace("&quot;", "\"", $name);
                            $name = htmlspecialchars($name);
                                
                            if($nodeType == "camera")
                                echo " <td><span class='icon icon-default icon-camera'></span><a href='http://$ip'>$name</a></td>";
                            else if($nodeType == "relay")
                                echo " <td><a href='http://$ip'>$name</a></td>";
                        }
                        echo "</tr>";
                    }
                }
                
                echo "</tbody></table></div>";
                echo "</div>";
                
                echo "<script>";
                echo "$('#$corridorID').accordion({collapsible:true,heightStyle: 'content'});";
                echo "</script>";
            }
        }
    }
    else
        echo "Error while attempting to read the portal configuration file.";
}
else
    echo "<p class='lead'>No portal configuration is present on this system.</p>";
?>
</div>

<?php
include("includes/footer.php")
?>
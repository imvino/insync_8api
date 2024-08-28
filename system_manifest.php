<?php

// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

// set this variable to true, so insyncInterface doesnt try to authenticate
//$loggedIn = true;

require_once("helpers/pathDefinitions.php");
require_once("helpers/constants.php");

// determine what action to take
$action = "";
if (isset($_REQUEST["action"]))
    $action = $_REQUEST["action"];

switch ($action) 
{
    /**
     * Displays System Manifest information
     */
    case "showManifest": 
        {
            showPage($permissions);
        }
        break;
    /**
     * Echos System Manifest xml data
     */
    case "getXML": 
        {
            header('Content-Type: application/xml');
            header("Cache-Control: no-store, no-cache, must-revalidate");

            $systemManifestObj = new SystemManifest();
            $systemManifestObj->Load();
            echo $systemManifestObj->GetXMLContents();
        }
        break;
}

function showPage($permissions)
{
    $title = " System Manifest";
    $breadCrumb = "<h1>Reports <small>System Manifest</small></h1>";
    $menuCategory = "reports";
    
    if($permissions["username"] == "kiosk")
        include("includes/header_lite.php");
    else
        include("includes/header.php");
    
    echo '<script type="text/javascript">$(function() {initScripts();});</script>';
        
    // get the system manifest data
    // create a new system manifest object and
    // load the xml from the file
    $systemManifestObj = new SystemManifest();
    $systemManifestObj->Load();
    
    // display the system manifest table
    getHTMLTable($systemManifestObj->GetSystemManifestXMLObj());
        
    if($permissions["username"] == "kiosk")
        include("includes/footer_lite.php");
    else
        include("includes/footer.php");
}

function getHTMLTable($xmlObj)
{
    if ($xmlObj === FALSE)
        die("XML Object is null.");
    
    echo '<table class="table table-striped"';
    echo '<tbody>';
    
    // iterate thru all xml nodes
    foreach($xmlObj as $xmlNode)
    {
        if(isset($xmlNode["display_name"]))
        {
            $name = (string)$xmlNode["display_name"];
            
            if (isset($xmlNode["value"]))
            {
                $value = (string)$xmlNode["value"];
                
                $error = "false";
                if (isset($xmlNode["error"]))
                {
                    $error = (string)$xmlNode["error"];
                }
                
                echo '<tr>';
                echo "<th>$name</th>";
                if(strcasecmp($error, "true") === 0)
                {
                    echo "<td><font color='red'>$value</font></td>";
                }
                else
                {
                    echo "<td>$value</td>";
                }
                echo '</tr>';
            }
        }
    }

    echo '</tbody>';
    echo '</table>';
}

class SystemManifest
{
    // System Name
    private $m_systemName = "";
    public function SystemName()
    {
        return $this->m_systemName;
    }

    // Intersection Name
    private $m_intersectionName = "";
    public function IntersectionName()
    {
        return $this->m_intersectionName;
    }

    // IP Address
    private $m_IPAddress = "";
    public function IPAddress()
    {
        return $this->m_IPAddress;
    }

    // Drive Name
    private $m_driveFriendlyName = "";
    public function DriveFriendlyName()
    {
        return $this->m_driveFriendlyName;
    }

    // Drive Firmware Version
    private $m_driveFirmwareVersion = "";
    public function DriveFirmwareVersion()
    {
        return $this->m_driveFirmwareVersion;
    }

    // The System Manifest XML file on disk
    // that was temporarily written
    private $m_systemManifestXMLFile = "";

    private $m_systemManifestXMLObj;
    public function GetSystemManifestXMLObj()
    {
        return $this->m_systemManifestXMLObj;
    }

    // load the contents of the system manifest
    // xml file and assign to variables
    public function Load()
    {
        // get the system manifest xml
        $randNum = random_int(1,10000);
        $tempFile = TEMP_ROOT . "/system_manifest-$randNum.xml";

        // invoke the systemmanifest exec with the filename
        $WshShell = new COM("WScript.Shell"); 
        $systemManifestReturnCode = $WshShell->Run(SYSTEM_MANIFEST_EXE . " $tempFile", 0, true); 

        if ($systemManifestReturnCode !== 0)
        {
            die ("Error executing System Manifest.  Return code: $systemManifestReturnCode");
        }
        
        $this->m_systemManifestXMLFile = $tempFile;
        
        if(file_exists($this->m_systemManifestXMLFile))
        {
            $this->m_systemManifestXMLObj = @simplexml_load_file($this->m_systemManifestXMLFile);
        }
        else
        {
            die("Error loading xml file.  System Manifest XML file '$this->m_systemManifestXMLFile' does not exist.");
        }
    }

    // echo the contents of the system manifest
    // xml file
    public function GetXMLContents()
    {
        if(file_exists($this->m_systemManifestXMLFile))
        {
            return file_get_contents($this->m_systemManifestXMLFile);
        }
        else
        {
            die("Error getting XML contents.  System Manifest XML file '$this->m_systemManifestXMLFile' does not exist.");
        }
    }
}
?>
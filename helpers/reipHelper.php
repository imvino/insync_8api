<?php
if(!isset($loggedIn))
    {
    // this must be included on all pages to authenticate the user
    require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
    $permissions = authSystem::ValidateUser();
    // end

    if (empty($permissions["configure"])) 
    {
        echo "<h3>Error: You do not have permission to access this page.</h3>";
        exit;
    }
}

require_once("rolling-curl/RollingCurl.php");
require_once("pathDefinitions.php");
require_once("networkHelper.php");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];


class PropagationCollector 
{
    private $rc;
    private $statusHash;

    function __construct()
    {
        $this->rc = new RollingCurl([$this, 'processResponse']);
        $this->rc->window_size = 10;
    }

    function processResponse($response, $info, $request)
    {
        if ($info['retried']) {
            return;
        }
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
            return;
        
        $intersection = &$statusXML->xpath("//intersection[@ip='" . $info["primary_ip"] . "']");
        
        if($intersection == FALSE)
            return;
        
        if($response == "Success")
            $intersection[0]["status"] = "completed";
        else
            $intersection[0]["status"] = "error";
        
        file_put_contents($statusFile, $statusXML->asXML());
    }

    function run($urls, $postParams, $hash)
    {
        $this->statusHash = $hash;
        
        foreach ($urls as $url)
        {
            $request = new RollingCurlRequest($url);
            $request->options = [CURLOPT_POST => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_CONNECTTIMEOUT => 25, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 45, CURLOPT_POSTFIELDS => $postParams];
            $fallback_request = new RollingCurlRequest(preg_replace('/^https:/i', 'http:', $url));
            $fallback_request->options = $request->options;
            $request->fallback_request = $fallback_request;
            $this->rc->add($request);
        }
        
        $this->rc->execute();
        
        // after updating the intersections, check to see if ALL intersections 
        // are complete or any returned an error
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
            return;
        
        $intersections = &$statusXML->xpath("//intersection");
        $errors = 0;
        
        foreach($intersections as $intersection)
            if($intersection["status"] == "error")
                $errors++;
            
        if($errors > 0)
            $statusXML["status"] = "error";
        else
            $statusXML["status"] = "completed";
        
        file_put_contents($statusFile, $statusXML->asXML());
    }
}


switch($action)
{
    case "status":
    {
        $hash = "";
        if(isset($_REQUEST['hash']))
            $hash = $_REQUEST['hash'];
        
        if($hash == "")
            die("Error: No hash specified.");
        
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";
        
        $result = @readfile($statusFile);
        
        if($result == FALSE)
            die("Error: Cannot read status file.");
    }
    break;

    case "propagate":
    {
        ignore_user_abort(true);
        

		$Intersections = getCorridorIntersections();
		if ($Intersections)
		{
		    
		    $reipXML = file_get_contents(REIP_CONF_FILE);
		    
		    if($reipXML == "")
		        die("Error: Re-IP file is empty, aborting send.");
		    
                    $protocol = "https://";
		    
		    $hash = md5($reipXML);
	
		    $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";

		    // create status XML document
		    $statusXML = new SimpleXMLElement("<corridor></corridor>");	
		    $statusXML->addAttribute("status", "working");
		    
		    // get all intersection IPs
		    $intersectionArr = [];
			foreach ($Intersections as $IntIP => $name)
			{
	            $intersectionArr[] = $protocol . $IntIP . "/helpers/reipHelper.php";

	            $intersectionXML = $statusXML->addChild("intersection");
	            $intersectionXML->addAttribute("ip", $IntIP);
	            $intersectionXML->addAttribute("status", "working");
	        }
		    
		    // set the PHP time limit to account for # of servers * 45. we should
		    // NEVER hit this limit due to the request pooling for Rolling-Curl
		    set_time_limit(count($intersectionArr) * 45);
		    
		    file_put_contents($statusFile, $statusXML->asXML());
		    
		    $postParams = ["action"=>"upload", "save"=>"true", "file"=>"@" . REIP_CONF_FILE, "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321")];
		    
		    $collector = new PropagationCollector();
		    $collector->run($intersectionArr, $postParams, $hash);
		}
		else
		{
			die("Error:  No other Intersections in Management Group!");
		}
    }
    break;

    case "download":
    {
        $data = "";
        if(isset($_REQUEST['data']))
            $data = $_REQUEST['data'];
        
        if($data == "")
            die("Error: No data to save.");
        
        $title = "";
        if(isset($_REQUEST['title']))
            $title = $_REQUEST['title'];
        
        $xmlDoc = new DOMDocument();
        $xmlDoc->formatOutput = true;
        
        $ipNode = $xmlDoc->createElement("IPs");
        $ipNode = $xmlDoc->appendChild($ipNode);
        
        if($data != "")
            $data = json_decode($data, true);
        
        addNodes($xmlDoc, $ipNode, $data);
        
        $xmlOutput = $xmlDoc->saveXML();
        
        header("Content-type: text/xml");
        header("Content-disposition: attachment; filename=ReIP.xml");        
        
        echo $xmlOutput;
    }
    break;

    case "upload":
    {
        $save = "";
        if(isset($_REQUEST['save']))
            $save = $_REQUEST['save'];
        
        if ($_FILES["file"]["error"] > 0)
            die("Error: " . $_FILES["file"]["error"]);
        
        if($_FILES["file"]["size"] == 0)
            die("Error: File contains no data.");
        
        if(strlen($_FILES["file"]["name"]) < 5)
            die("Error: Cannot determine file type. Please upload a valid XML or CSV file.");
        
        $type = pathinfo($_FILES["file"]["name"]);
        
        // importing XML file
        if($type['extension'] == strtolower("xml"))
        {
            $xml = simplexml_load_file($_FILES["file"]["tmp_name"]);
            
            if(!$xml)
                die("Error: Couldn't load uploaded XML.");
            
            if($xml->getName() == "IPs")
            {
                $jsonData = generateFromReIPXML($xml);

                if($save == "")
                    echo json_encode($jsonData);
                else
                {
                    echo "Success";
                    file_put_contents(REIP_CONF_FILE, $xml->asXML());
                }
            }
            else
                die("Error: Uploaded XML is not a valid Re-IP file.");
        }
        else
            die("Error: Import file type must be a valid Re-IP XML.");
    }
    break;
    
    case "save":
    {
        ignore_user_abort(true);
        
        $data = "";
        if(isset($_REQUEST['data']))
            $data = $_REQUEST['data'];
        
        if($data == "")
            die("Error: No data to save.");
        
        $xmlDoc = new DOMDocument();
        $xmlDoc->formatOutput = true;
        
        $docNode = $xmlDoc->createElement("IPs");
        $docNode = $xmlDoc->appendChild($docNode);
        
        addNodes($xmlDoc, $docNode, $data);
        
        $xmlOutput = $xmlDoc->saveXML();
        
        $hash = md5($xmlOutput);
        
        if(file_put_contents(REIP_CONF_FILE, $xmlOutput) === FALSE)
            die("Error: Could not write Re-IP configuration file.");
        else
            die("Success: $hash");
    }
    break;

    case "generate":
    {
        if(!file_exists(CORRIDOR_CONF_FILE))
            die("Error: No management group file to load from!");
        
        $corridorXML = simplexml_load_file(CORRIDOR_CONF_FILE);
        
        $jsonData = generateFromCorridorXML($corridorXML);
        echo json_encode($jsonData);
    }
    break;
}

function generateFromReIPXML($reipXML)
{
    $jsonData = [];    

    foreach($reipXML as $child)
    {
        $type = strtolower($child->getName());

        if($type == "intersection")
        {
            $objDataChild = [];
            $objDataChild["type"] = "intersection";
            $objDataChild["ip"] = (string)$child["ip"];
            $objDataChild["new"] = (string)$child["new"];
            $objDataChild["subnet"] = (string)$child["subnet"];
            $objDataChild["gateway"] = (string)$child["gateway"];
            $objDataChild["data"] = [];

            foreach($child->children() as $intersectionChild)
            {
                $type = strtolower($intersectionChild->getName());
                $objDataChild["data"][] = ["type"=>$type, "new"=>(string)$intersectionChild["new"], "ip"=>(string)$intersectionChild["ip"]];
            }
            
            $jsonData[] = $objDataChild;
        }
    }
    
    return $jsonData;
}

function generateFromCorridorXML($corridorXML)
{
    $jsonData = [];
    $count = 0;

    foreach($corridorXML->Intersection as $Intersection)
    {
        $jsonData[$count] = [];

        $ip = (string)$Intersection["IP"];
        $gateway = (string)$Intersection["Gateway"];
        $subnet = (string)$Intersection["Subnet"];

        $jsonData[$count]["ip"] = $ip;
        $jsonData[$count]["type"] = "intersection";
        $jsonData[$count]["gateway"] = $gateway;
        $jsonData[$count]["subnet"] = $subnet;
        $jsonData[$count]["data"] = [];

        foreach($Intersection->TraVisConfiguration->VideoStreamSettings->VideoStream as $VideoStream)
        {
            $url = (string)$VideoStream["Name"];
            $ip = parse_url($url, PHP_URL_HOST);

            $jsonData[$count]["data"][] = ["type"=>"camera", "ip"=>$ip];
        }

        $count++;
    }
    
    return $jsonData;
}

function addNodes($root, $parent, $data)
{
    foreach($data as $child)
    {
        switch($child["type"])
        {
            case "intersection":
            {
                $node = $root->createElement("Intersection");
                $node->setAttribute("ip", $child["ip"]);
                $node->setAttribute("new", $child["new"]);
                $node->setAttribute("subnet", $child["subnet"]);
                $node->setAttribute("gateway", $child["gateway"]);
                
                $node = $parent->appendChild($node);
                
                if(isset($child["children"]) && count($child["children"]) > 0)
                    addNodes($root, $node, $child["children"]);
            }
            break;
            
            case "camera":
            {
                $node = $root->createElement("Camera");
                $node->setAttribute("new", $child["new"]);
                $node->setAttribute("ip", $child["old"]);
                $node = $parent->appendChild($node);
            }
            break;
        
            case "relay":
            {
                $node = $root->createElement("Relay");
                $node->setAttribute("new", $child["new"]);
                $node->setAttribute("ip", $child["old"]);
                $node = $parent->appendChild($node);
            }
            break;
        }
    }
    
}

?>

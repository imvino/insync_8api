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
        header("Content-Encoding: none");
        
        ob_end_clean();
        header("Connection: close");
        ignore_user_abort();
        ob_start();
        echo "Starting propagation...";
        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush();
        flush();
        
		$Intersections = getCorridorIntersections();
		if($Intersections === FALSE)
		{
            die("Error: No management group file to load from!");
		}
		if ($Intersections)
		{        
		    $portalXML = file_get_contents(PORTAL_CONF_FILE);
		    
		    if($portalXML == "")
		        die("Error: Portal is empty, aborting send.");
		    
                    $protocol = "https://";
		    
		    $hash = md5($portalXML);
	
		    $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";

		    // create status XML document
		    $statusXML = new SimpleXMLElement("<corridor></corridor>");	
		    $statusXML->addAttribute("status", "working");
		    
		    // get all intersection IPs
		    $intersectionArr = [];
			foreach ($Intersections as $IntIP => $name)
			{
				$intersectionArr[] = $protocol . $IntIP . "/helpers/portalDesignerHelper.php";

				$intersectionXML = $statusXML->addChild("intersection");
				$intersectionXML->addAttribute("ip", $IntIP);
				$intersectionXML->addAttribute("status", "working");
		    }
		    
		    // set the PHP time limit to account for # of servers * 45. we should
		    // NEVER hit this limit due to the request pooling for Rolling-Curl
		    set_time_limit(count($intersectionArr) * 45);
		    
		    file_put_contents($statusFile, $statusXML->asXML());
		    
		    $postParams = ["action"=>"upload", "save"=>"true", "file"=>"@" . PORTAL_CONF_FILE, "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321")];
		    
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
        
        $data = base64_decode($data);
        $title = base64_decode($title);
        
        $xmlDoc = new DOMDocument();
        $xmlDoc->formatOutput = true;
        
        $portalNode = $xmlDoc->createElement("Portal");
        $portalNode->setAttribute("title", $title);
        $portalNode = $xmlDoc->appendChild($portalNode);
        
        if($data != "")
            $data = json_decode($data, true);
        
        addNodes($xmlDoc, $portalNode, $data);
        
        $xmlOutput = $xmlDoc->saveXML();
        
        header("Content-type: text/xml");
        header("Content-disposition: attachment; filename=portal.xml");        
        
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
            
            if($xml->getName() == "Corridor")
            {            
                $jsonData = generateFromCorridorXML($xml);

                $returnArray = [];
                $returnArray["type"] = "corridor";
                $returnArray["data"] = $jsonData;

                echo json_encode($returnArray);
            }
            else if($xml->getName() == "Portal")
            {
                $jsonData = generateFromPortalXML($xml);

                $returnArray = [];
                $returnArray["type"] = "portal";
                $returnArray["data"] = $jsonData;

                if($save == "")
                    echo json_encode($returnArray);
                else
                {
                    echo "Success";
                    file_put_contents(PORTAL_CONF_FILE, $xml->asXML());
                }
            }
            else
                die("Error: Uploaded XML is not a valid Management Group file.");
        }
        else if($type['extension'] == strtolower("csv"))
        {
            // sucky code below
            $jsonData = generateFromCSV($_FILES["file"]["tmp_name"]);
            
            if($jsonData === FALSE)
                die("Error: Could not parse uploaded CSV file.");
            
            $returnArray = [];
            $returnArray["type"] = "csv";
            $returnArray["data"] = $jsonData;
            
            echo json_encode($returnArray);
        }
        else
            die("Error: Import file type must be XML or CSV.");
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
        
        $title = "";
        if(isset($_REQUEST['title']))
            $title = $_REQUEST['title'];
        
        $xmlDoc = new DOMDocument();
        $xmlDoc->formatOutput = true;
        
        $portalNode = $xmlDoc->createElement("Portal");
        $portalNode->setAttribute("title", $title);
        $portalNode = $xmlDoc->appendChild($portalNode);
        
        addNodes($xmlDoc, $portalNode, $data);
        
        $xmlOutput = $xmlDoc->saveXML();
        
        $hash = md5($xmlOutput);
        
        if(file_put_contents(PORTAL_CONF_FILE, $xmlOutput) === FALSE)
            die("Error: Could not write new Portal file.");
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

function generateFromCSV($filename)
{
    $contents = file_get_contents($filename);
    
    if($contents === FALSE)
        return false;
    
    $lines = explode("\n", $contents);
    
    $lineCount = count($lines);
    
    $jsonData = [];
    $jsonData["title"] = "Imported Portal";
    $jsonData["data"] = [];
    
    $corridorCount = 0;
    
    for($i = 0; $i < $lineCount; $i++)
    {
        $line = strtolower(trim($lines[$i]));
        $line = trim($line, ',');
        
        if($line == "title:")
        {
            $title = trim($lines[++$i]);
            $title = trim($title, '",');
            $jsonData["title"] = $title;
        }
        
        if($line == "corridor:")
        {            
            $corridorName = trim($lines[++$i]);
            $jsonData["data"][$corridorCount] = [];
            $corridor = &$jsonData["data"][$corridorCount];
            $corridor["name"] = trim($corridorName, ',');
            $corridor["type"] = "corridor";
            
            $intersectionCount = 0;
            
            // add all intersection data
            for($j = $i+1; $j < $lineCount; $j++)
            {
                $line = strtolower(trim($lines[$j]));
                $line = trim($line, ',');
                
                if($line == "intersection:")
                {
                    $corridor["data"][$intersectionCount] = [];
                    $intersection = &$corridor["data"][$intersectionCount];
                    
                    $intersectionNameIP = explode(",", trim($lines[++$j]));
                    
                    $intersection["name"] = $intersectionNameIP[0];
                    $intersection["ip"] = $intersectionNameIP[1];
                    $intersection["type"] = "intersection";
                    $intersection["data"] = [];
                    
                    // add all camera/relay data
                    for($k = $j+1; $k < $lineCount; $k++)
                    {
                        $line = trim($lines[$k]);
                        $line = trim($line, ',');
                        
                        if($line == "")
                            break;
                        
                        $line = explode(",", $line);
                        
                        if(count($line) != 2)
                            continue;
                        
                        if(strtolower($line[0]) == "din relay")
                            $intersection["data"][] = ["type"=>"relay", "name"=>"DIN Relay", "ip"=>$line[1]];
                        else
                            $intersection["data"][] = ["type"=>"camera", "name"=>$line[0], "ip"=>$line[1]];
                    }
                    
                    $intersectionCount++;
                }
                else if($line == "corridor views:")
                    break;
            }
            
            // decrement $j by 1 and set $i equal to this value
            // this starts us on the appropriate data line in the "CSV"
            $i = $j - 1; 
            $corridorCount++;
            continue;
        }
    }
    
    return $jsonData;
}

function generateFromPortalXML($corridorXML)
{
    $jsonData = [];
    $count = 0;
    
    $jsonData["title"] = (string)$corridorXML["title"];
    $jsonData["data"] = [];
    
    foreach($corridorXML as $node)
    {
        $type = strtolower($node->getName());
       
        if($type == "map")
        {
            $objData = [];
            $objData["type"] = "map";
            $objData["name"] = (string)$node["name"];
            $objData["url"] = (string)$node["url"];
            
            $jsonData["data"][] = $objData;
        }
        else if($type == "corridor")
        {
            $objData = [];
            $objData["type"] = "corridor";
            $objData["name"] = (string)$node["name"];
            $objData["data"] = [];
            
            foreach($node->children() as $child)
            {
                $type = strtolower($child->getName());
                
                if($type == "map")
                {
                    $objDataChild = [];
                    $objDataChild["type"] = "map";
                    $objDataChild["name"] = (string)$child["name"];
                    $objDataChild["url"] = (string)$child["url"];

                    $objData["data"][] = $objDataChild;
                }
                else if($type == "intersection")
                {
                    $objDataChild = [];
                    $objDataChild["type"] = "intersection";
                    $objDataChild["name"] = (string)$child["name"];
                    $objDataChild["ip"] = (string)$child["ip"];
                    $objDataChild["data"] = [];
                    
                    foreach($child->children() as $intersectionChild)
                    {
                        $type = strtolower($intersectionChild->getName());
                        $objDataChild["data"][] = ["type"=>$type, "name"=>(string)$intersectionChild["name"], "ip"=>(string)$intersectionChild["ip"]];
                    }
                    
                    $objData["data"][] = $objDataChild;
                }
            }
            
            $jsonData["data"][] = $objData;
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
        require_once("libraries/intersectionUtil.php");
        $intersectionUtil = new IntersectionUtil($Intersection->TraVisConfiguration);

        $jsonData[$count] = [];

        $name = (string)$Intersection->TraVisConfiguration->Intersection["name"];
        $ip = (string)$Intersection["IP"];

        $jsonData[$count]["ip"] = $ip;
        $jsonData[$count]["name"] = $name;
        $jsonData[$count]["type"] = "intersection";
        $jsonData[$count]["data"] = [];

        $cameras = $intersectionUtil->getCameras();

        $emit_panomorph = false;
        $panomorph_ip = null;

        foreach ($intersectionUtil->getCameraNames() as $cameraName) {
                if (!$cameras[$cameraName]->videoServer) {
                    $url = $cameras[$cameraName]->cameraUrl;
                    $name = $cameras[$cameraName]->name;
                    $ip = parse_url($url, PHP_URL_HOST);

                    $jsonData[$count]["data"][] = ["type"=>"camera", "name"=>$name, "ip"=>$ip];
                } else {
                    $emit_panomorph = true;
                    $panomorph_ip = parse_url($cameras[$cameraName]->cameraUrl, PHP_URL_HOST);
                }
        }

        if ($emit_panomorph) {
            $jsonData[$count]["data"][] = ["type"=>"camera", "name"=>"Panomorph", "ip"=>$panomorph_ip];
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
            case "corridor":
            {
                $node = $root->createElement("Corridor");
                $node->setAttribute("name", $child["name"]);
                $node = $parent->appendChild($node);
                
                if(isset($child["children"]) && count($child["children"]) > 0)
                    addNodes($root, $node, $child["children"]);
            }
            break;
            
            case "intersection":
            {
                $node = $root->createElement("Intersection");
                $node->setAttribute("name", $child["name"]);
                $node->setAttribute("ip", $child["ip"]);
                $node = $parent->appendChild($node);
                
                if(isset($child["children"]) && count($child["children"]) > 0)
                    addNodes($root, $node, $child["children"]);
            }
            break;
            
            case "camera":
            {
                $node = $root->createElement("Camera");
                $node->setAttribute("name", $child["name"]);
                $node->setAttribute("ip", $child["ip"]);
                $node = $parent->appendChild($node);
            }
            break;
        
            case "relay":
            {
                $node = $root->createElement("Relay");
                $node->setAttribute("name", $child["name"]);
                $node->setAttribute("ip", $child["ip"]);
                $node = $parent->appendChild($node);
            }
            break;
        
            case "map":
            {
                $node = $root->createElement("Map");
                $node->setAttribute("name", $child["name"]);
                $node->setAttribute("url", $child["url"]);
                $node = $parent->appendChild($node);
            }
            break;
        }
    }
    
}

?>

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
require_once("networkHelper.php" );

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

switch($action)
{
    case "save":
    {
        $data = "";
        if(isset($_REQUEST['data']))
            $data = $_REQUEST['data'];
        
        if($data == "")
            die("Error: No data to save.");
        
        $title = "";
        if(isset($_REQUEST['title']))
            $title = $_REQUEST['title'];
        
        $xmlOutput = generateXML($title, $data);
        
        $hash = md5($xmlOutput);
        
        if(file_put_contents(CORRIDORVIEWER_CONF_FILE, $xmlOutput) === FALSE)
            die("Error: Could not write new Management Group file.");
        else
            die("Success: $hash");
    }
    break;
    
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
            
            if($xml->getName() == "CorridorViewer")
            {            
                $jsonData = generateFromXML($xml);
                
                if($save == "")
                    echo json_encode($jsonData);
                else
                {
                    echo "Success";
                    file_put_contents(CORRIDORVIEWER_CONF_FILE, $xml->asXML());
                }
            }
            else
                die("Error: Uploaded XML is not a valid Management Group Viewer file.");
        }
        else
            die("Error: Import file type must be XML.");
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
		   
		    $corridorXML = file_get_contents(CORRIDORVIEWER_CONF_FILE);
		    
		    if($corridorXML == "")
		        die("Error: Management Group is empty, aborting send.");
		    
                    $protocol = "https://";
		    
		    $hash = md5($corridorXML);
	
		    $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";

		    // create status XML document
		    $statusXML = new SimpleXMLElement("<corridor></corridor>");	
		    $statusXML->addAttribute("status", "working");
		    
		    // get all intersection IPs
		    $intersectionArr = [];
			foreach ($Intersections as $IntIP => $name)
			{
	            $intersectionArr[] = $protocol . $IntIP . "/helpers/corridorDesignerHelper.php";

	            $intersectionXML = $statusXML->addChild("intersection");
	            $intersectionXML->addAttribute("ip", $IntIP);
	            $intersectionXML->addAttribute("status", "working");
		    }
		    
		    // set the PHP time limit to account for # of servers * 45. we should
		    // NEVER hit this limit due to the request pooling for Rolling-Curl
		    set_time_limit(count($intersectionArr) * 45);
		    
		    file_put_contents($statusFile, $statusXML->asXML());
		    
		    $postParams = ["action"=>"upload", "save"=>"true", "file"=>"@" . CORRIDORVIEWER_CONF_FILE, "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321")];
		    
		    $collector = new PropagationCollector();
		    $collector->run($intersectionArr, $postParams, $hash);
		}
		else
		{
			die("Error: No Intersections in Management Group file!");
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
        
        $xmlOutput = generateXML($title, json_decode($data, true));
        
        header("Content-type: text/xml");
        header("Content-disposition: attachment; filename=CorridorViewer.xml");        
        
        echo $xmlOutput;
    }
    break;

    case "getcameralist":
    {
        $ip = "";
        if(isset($_REQUEST['ip']))
            $ip = $_REQUEST['ip'];
        $remoteURL = "$ip/helpers/insyncInterface.php?action=getCameraList&u=" . base64_encode("PEC") . "&p=" . base64_encode("lenpec4321");
        $results = @file_get_contents("https://$remoteURL");
        
        if($results === FALSE)
		{
			//Try again in http instead of https
	        $results = @file_get_contents("http://$remoteURL");
			if($results === FALSE)
			{
	            die('{"error":"true"}');
			}
		}
        
        echo $results;
    }
    break;
    
    case "getremoteimage":
    {
        $cam = "";
        if(isset($_REQUEST['cam']))
            $cam = $_REQUEST['cam'];
        
        $ip = "";
        if(isset($_REQUEST['ip']))
            $ip = $_REQUEST['ip'];
        
		$remoteURL = "$ip/helpers/insyncInterface.php?action=getImage&viewCamera=" . urlencode($cam) . "&width=160&height=120&session=false&u=" . urlencode(base64_encode("PEC")) . "&p=" . urlencode(base64_encode("lenpec4321"));
       
        $results = @file_get_contents("https://$remoteURL");
        
        if($results === FALSE)
        {
			//Try again in http instead of https
	        $results = @file_get_contents("http://$remoteURL");
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

function generateFromXML($xml)
{
    $json = [];
    
    $json["title"] = (string)$xml["title"];
    $json["list"] = [];
    
    $objCount = 0;
    
    foreach($xml->children() as $node)
    {
        if($node->getName() == "Intersection")
        {
            $json["list"][$objCount] = [];
            $json["list"][$objCount]["type"] = "intersection";
            $json["list"][$objCount]["name"] = (string)$node["name"];
            $json["list"][$objCount]["ip"] = (string)$node["ip"];
            $json["list"][$objCount]["cameras"] = [];

            foreach($node->Camera as $camera)
                $json["list"][$objCount]["cameras"][] = (string)$camera["name"];
        }
        else if($node->getName() == "Column")
        {
            $json["list"][$objCount] = [];
            $json["list"][$objCount]["type"] = "column";
            $json["list"][$objCount]["name"] = (string)$node["name"];
            $json["list"][$objCount]["cameras"] = [];

            foreach($node->Camera as $camera)
            {
                if((string)$camera["name"] == "gap")
                    $json["list"][$objCount]["cameras"][] = ["name"=>(string)$camera["name"], "ip"=>""];
                else
                    $json["list"][$objCount]["cameras"][] = ["name"=>(string)$camera["name"], "ip"=>(string)$camera["ip"]];
            }
        }
        
        $objCount++;
    }
    
    return $json;
}

function generateXML($title, $data)
{
    $xmlDoc = new DOMDocument();
    $xmlDoc->formatOutput = true;

    $corridorNode = $xmlDoc->createElement("CorridorViewer");
    $corridorNode->setAttribute("title", $title);
    $corridorNode = $xmlDoc->appendChild($corridorNode);

    foreach($data as $node)
    {
        if($node["type"] == "intersection")
        {
            $intersectionNode = $xmlDoc->createElement("Intersection");
            $intersectionNode->setAttribute("name", $node["name"]);
            $intersectionNode->setAttribute("ip", $node["ip"]);
            $intersectionNode = $corridorNode->appendChild($intersectionNode);

            foreach($node["cameras"] as $camera)
            {
                $camNode = $xmlDoc->createElement("Camera");
                $camNode->setAttribute("name", $camera);
                $camNode = $intersectionNode->appendChild($camNode);
            }
        }
        else if($node["type"] == "column")
        {
            $columnNode = $xmlDoc->createElement("Column");
            $columnNode->setAttribute("name", $node["name"]);
            $columnNode = $corridorNode->appendChild($columnNode);

            if(isset($node["list"]))
            {
                foreach($node["list"] as $camera)
                {
                    $camNode = $xmlDoc->createElement("Camera");
                    $camNode->setAttribute("name", $camera["name"]);

                    if($camera["name"] != "gap")
                        $camNode->setAttribute("ip", $camera["ip"]);

                    $camNode = $columnNode->appendChild($camNode);
                }
            }
        }
    }

    return $xmlDoc->saveXML();
}

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
        
        @file_put_contents($statusFile, $statusXML->asXML());
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
?>

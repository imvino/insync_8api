<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/networkHelper.php");

$specialdataArray = [];
$jsondata = "";

if (isset($_REQUEST["action"]))
	$action = $_REQUEST["action"];

switch($action) 
{
	case "getalldata":
	{
		$ipList = getCorridorIntersectionsIncludingSelf();
		$specialdataArray = getCorridorXmlData($ipList);
		$jsondata = json_encode($specialdataArray);
		echo $jsondata;
                
		break;
	}
	case "performTimeCompare":
	{
		$ipList = getCorridorIntersectionsIncludingSelf();
		$offsetJson = getOffsets($ipList);
		echo $offsetJson;
                
		break;
	}
	case "getmyIP":
	{
		$myIP = getInSyncIP();
		echo $myIP;
                
		break;	
	}
        case "getIntersectionIPsIncludingSelf":
	{
		$ipList = json_encode(getCorridorIntersectionsIncludingSelf());
		echo $ipList;
                
		break;
	}
	case "getIntersectionIPs":
	{
		$ipList = json_encode(getCorridorIntersections());
		echo $ipList;
                
		break;
	}	
	case "getTimeOffsets":
	{
		$compareIPaddress = "";
		if(isset($_REQUEST['iptoCompare']))
			$compareIPaddress = $_REQUEST['iptoCompare'];

		$offset = getOffset($compareIPaddress);
		echo $offset;
                
		break;
	}	
	case "getOffsetFromOtherSystemExecution":
	{
		$compareIPaddress = "";
		if(isset($_REQUEST['iptoCompare']))
                    $compareIPaddress = $_REQUEST['iptoCompare'];

		$remoteIP = "";
		if(isset($_REQUEST['remoteIP']))
                    $remoteIP = $_REQUEST['remoteIP'];

		$url = "http://$remoteIP/helpers/timetoolsHelper.php?action=getTimeOffsets&iptoCompare=$compareIPaddress&u=aW5zeW5j&p=a2VDMmVzd2U%3d";
                
                // create a stream context for 20 sec. timeout
                // of reception of data...we can't wait forever
                $ctx = stream_context_create(['http'=>
                    ['timeout' => 20]]);
                
		$response = file_get_contents($url, false, $ctx);
		
                if($response === false)
                    echo "<font color='red'>Timeout</font>";
                else
                    echo $response;
                
                break;
	}
	case "performTimeSync":
	{
		$processorIP = "";
		if(isset($_REQUEST['processortoSyncfromCheckbox']))
                    $processorIP = $_REQUEST['processortoSyncfromCheckbox'];
 
		$altServerIP = "";
		if(isset($_REQUEST['altNtpServer']))
                    $altServerIP = $_REQUEST['altNtpServer'];

		// create a stream context for 20 sec. timeout
                // of reception of data...we can't wait forever
                $ctx = stream_context_create(['http'=>
                    ['timeout' => 20]]);
                
		if (isValidIP($altServerIP)===true)
		{
                    $response = file_get_contents("http://$processorIP/helpers/insyncInterface.php?action=ntpSyncWithOptionalAlt&alt=$altServerIP&u=aW5zeW5j&p=a2VDMmVzd2U%3d", false, $ctx);
		}
		else
		{
                    $response = file_get_contents("http://$processorIP/helpers/insyncInterface.php?action=ntpSyncWithOptionalAlt&u=aW5zeW5j&p=a2VDMmVzd2U%3d", false, $ctx);
		}

		if($response === false)
                    echo "<font color='red'>Timeout</font>";
                else
                    echo $response;
                
		break;
	}

}

function  getCorridorXmlData($ipList)
{
    $my_IP = getInSyncIP();	

    //Doing this to show my ip on top of the list 
    foreach ($ipList as $key_IP => $value)
    {	
        if ($key_IP == $my_IP) {
            $specialdataArray = getDatafromXmlandLoad($key_IP, $specialdataArray);
            break;
        }
    }

    foreach ($ipList as $key_IP => $value)
    {	
        if ($key_IP != $my_IP) {
            $specialdataArray = getDatafromXmlandLoad($key_IP, $specialdataArray);
        }
    }
    return $specialdataArray;
}

function getDatafromXmlandLoad($key_IP, $specialdataArray)
{
    // create a stream context for 4 sec. timeout
    // of reception of data...we can't wait forever
    $ctx = stream_context_create(['http'=>
        ['timeout' => 4]]);

    $xmlString = file_get_contents("http://" . $key_IP . "/specialcalls.php", false, $ctx);
    
    $ProcessorTime = "<font color='red'>Timeout</font>";
    $NtpServer = "<font color='red'>Timeout</font>";
    $NtpStatus = "<font color='red'>Timeout</font>";

    // see if we got the entire stream or not
    if ($xmlString !== false)
    {
        $xml = simplexml_load_string($xmlString);

        //Get current time
        foreach ($xml->Time[0] -> attributes() as $a => $b)
        {
            //$ProcessorTime = "";
            if ($a == "Now")		
            {
                $ProcessorTime = (string)$b;
                break;
            }
        }
        //Get NTP ip address and status
        foreach ($xml->NTP[0] -> attributes() as $a1 => $b1)
        {
            if ($a1 == 'Status') 
            {
                $NtpStatus = (string)$b1;
                
                if ($NtpStatus !== "GOOD")
                {
                    $NtpStatus = "<font color='red'>$NtpStatus</font>";
                }
            }
            else if ($a1 = 'Server') 
            {
                $NtpServer =(string) $b1;
                if ($NtpServer === "NTPNOTCONFIGURED"
                        || $NtpServer === "")
                {
                    $NtpServer = "<font color='red'>NOT CONFIGURED</font>";
                }
            }
        }
    }

    $specialCallsDataArray = ["time" => $ProcessorTime, "ntp_server" => $NtpServer, "ntp_status" => $NtpStatus];
    $specialdataArray[$key_IP] = $specialCallsDataArray;

    return $specialdataArray;
}

//function getOffset($ipList)
//{
//	$OffsetCount++;
//	foreach ($ipList as $IP => $value)
//	{
//		$OffsetCount++;
//		$valueReturned = getOffset($IP);	
//		// http://$IP/helpers/timetoolsHelper.php?action=getOffset&ip=$IP&u=<uname>&p=<pass>
//		$valueOffsetArray = array("time_offset" => $valueReturned);	
//		$offsetArray[$IP] = $valueOffsetArray;
//	}
//	$offsetJson = json_encode($offsetArray);
//	return $offsetJson;
//}


function getOffset($compareIPaddress)
{
	$outputfile = "C:\\InSync\\Temp\\ntp_offset_log.txt";
	$command = "C:\\InSync\\Scripts\\ntp_offset.bat $compareIPaddress > $outputfile";

	$line = @exec($command);

	$contents = file_get_contents($outputfile);
	$lines = explode("\r\n", $contents);
	
        // get the offset
	$offsetValue = getoffsetfromArray($lines);
        
        // round it to 3 decimal places
        $offsetValue = round((float)$offsetValue, 3);
        
        // if the offset if > 1 sec. difference,
        // mark it red
        if ($offsetValue > 1 || $offsetValue < -1)
        {
            $offsetValue = "<font color='red'>$offsetValue</font>";
        }
	return $offsetValue;
}

function getoffsetfromArray($lines)
{
	foreach ($lines as $value)
	{
		if (str_contains($value, 'ntp offset')) {
		}
		elseif (str_contains($value, 'offset '))  {
			$val = substr($value, 7);
			return $val;
		}
	}
}


?>
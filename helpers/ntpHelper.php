<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

if (empty($permissions["configure"]))
    die("Error: You do not have permission to access this page.");

$action = "";
if (isset($_REQUEST["action"]))
    $action = $_REQUEST["action"];

$target = "";
if (isset($_REQUEST["target"]))
    $target = $_REQUEST["target"];

/**
 * Saves NTP information to DB
 */
if($action == "save")
{
    if(strlen($target) == 0)
        die("Error: Invalid NTP server address.");
    
    if(!filter_var($target, FILTER_VALIDATE_IP))
        die("Error: Invalid IP Address");
	
    require_once("databaseInterface.php");

    $intersectionXML = getFile("Intersection.xml");	
    $intersectionObject = simplexml_load_string($intersectionXML);
    $intersectionObject->NTP["IP"] = $target;
    $intersectionXML = $intersectionObject->asXML();

    putFileFromString("Intersection.xml", $intersectionXML);
}

/**
 * Retrieves the NTP info from the DB
 */
if($action == "get")
{
	require_once("databaseInterface.php");
	
	$intersectionXML = getFile("Intersection.xml");	
	$intersectionObject = simplexml_load_string($intersectionXML);
	exit($intersectionObject->NTP["IP"]);
}

/**
 * Tests a NTP server and returns the results
 */
if ($action == "test") 
{	
    if(strlen($target) == 0)
        die("<strong>Error: </strong>Invalid NTP server address.");
    
    if(!filter_var($target, FILTER_VALIDATE_IP))
        die("<strong>Error: </strong>Invalid IP Address");
    
    $retArray = testNTP($target);
    
    if($retArray == FALSE)
        die("<strong>Error: <strong>Could not reach NTP server.");
	
    echo "<strong>Success: </strong>Server tested GOOD<br />";
    echo "<strong>Server: </strong>$target<br />";
    echo "<strong>Version Number: </strong>" . $retArray["vn_response"] . "<br />";
    echo "<strong>Stratum: </strong>" . $retArray["stratum_response"] . "<br />";
    echo "<strong>Delay: </strong>" . $retArray["delay_ms"] . "<br />";
    echo "<strong>NTP Server Time: </strong>" . $retArray["ntp_time_formatted"] . "<br />";
    echo "<strong>This System Time: </strong>" . $retArray["server_time_formatted"] . "<br />";
    echo "<strong>Offset: </strong>" . $retArray["offset_ms"] . " ms<br />";
}

function testNTP($target)
{
    $bit_max = 4294967296;
    $vn = 3;

    //see rfc5905, page 20
    //first byte
    //LI (leap indicator), a 2-bit integer. 00 for 'no warning'
    $header = '00';
    //VN (version number), a 3-bit integer.  011 for version 3
    $header .= sprintf('%03d', decbin($vn));
    //Mode (association mode), a 3-bit integer. 011 for 'client'
    $header .= '011';

    //construct the packet header, byte 1
    $request_packet = chr(bindec($header));

    //we'll use a for loop to try additional servers should one fail to respond
    $socket = @fsockopen('udp://' . $target, 123, $err_no, $err_str, 1);

    if ($socket) 
    {
        //add nulls to position 11 (the transmit timestamp, later to be returned as originate)
        //10 lots of 32 bits
        for ($j = 1; $j < 40; $j++)
            $request_packet .= chr(0x0);

        //the time our packet is sent from our server (returns a string in the form 'msec sec')
        $local_sent_explode = explode(' ', microtime());
        $local_sent = $local_sent_explode[1] + $local_sent_explode[0];

        //add 70 years to convert unix to ntp epoch
        $originate_seconds = $local_sent_explode[1] + 2208988800;

        //convert the float given by microtime to a fraction of 32 bits
        $originate_fractional = round($local_sent_explode[0] * $bit_max);

        //pad fractional seconds to 32-bit length
        $originate_fractional = sprintf('%010d', $originate_fractional);

        //pack to big endian binary string
        $packed_seconds = pack('N', $originate_seconds);
        $packed_fractional = pack("N", $originate_fractional);

        //add the packed transmit timestamp
        $request_packet .= $packed_seconds;
        $request_packet .= $packed_fractional;

        if (fwrite($socket, $request_packet)) 
        {
            $data = NULL;
            stream_set_timeout($socket, 1);
            $response = fread($socket, 48);

            //the time the response was received
            $local_received = microtime(true);
        }
        fclose($socket);

        //the response was of the right length, assume it's valid and break out of the loop
        if (strlen($response) != 48)
            return false;
    } 
    else
        return false;

    //unpack the response to unsiged lonng for calculations
    $unpack0 = unpack("N12", $response);
    //print_r($unpack0);
    //present as a decimal number
    $remote_originate_seconds = sprintf('%u', $unpack0[7]) - 2208988800;
    $remote_received_seconds = sprintf('%u', $unpack0[9]) - 2208988800;
    $remote_transmitted_seconds = sprintf('%u', $unpack0[11]) - 2208988800;

    $remote_originate_fraction = sprintf('%u', $unpack0[8]) / $bit_max;
    $remote_received_fraction = sprintf('%u', $unpack0[10]) / $bit_max;
    $remote_transmitted_fraction = sprintf('%u', $unpack0[12]) / $bit_max;

    $remote_originate = $remote_originate_seconds + $remote_originate_fraction;
    $remote_received = $remote_received_seconds + $remote_received_fraction;
    $remote_transmitted = $remote_transmitted_seconds + $remote_transmitted_fraction;

    //unpack to ascii characters for the header response
    $unpack1 = unpack("C12", $response);
    //the header response in binary (base 2)
    $header_response = base_convert($unpack1[1], 10, 2);

    //pad with zeros to 1 byte (8 bits)
    $header_response = sprintf('%08d', $header_response);

    //Mode (the last 3 bits of the first byte), converting to decimal for humans;
    $mode_response = bindec(substr($header_response, -3));

    //VN
    $vn_response = bindec(substr($header_response, -6, 3));

    //the header stratum response in binary (base 2)
    $stratum_response = base_convert($unpack1[2], 10, 2);
    $stratum_response = bindec($stratum_response);

    //calculations assume a symmetrical delay, fixed point would give more accuracy
    $delay = (($local_received - $local_sent) / 2) - ($remote_transmitted - $remote_received);
    $delay_ms = round($delay * 1000) . ' ms';

    $ntp_time = $remote_transmitted - $delay;
    $ntp_time_explode = explode('.', $ntp_time);

    $ntp_time_formatted = date('Y-m-d H:i:s', $ntp_time_explode[0]) . '.' . $ntp_time_explode[1];

    //compare with the current server time
    $server_time = microtime();
    $server_time_explode = explode(' ', $server_time);
    $server_time_micro = round($server_time_explode[0], 4);

    $server_time_formatted = date('Y-m-d H:i:s', time()) . '.' . substr($server_time_micro, 2);
    
    $offsetInterval = round($server_time_explode[1] - $ntp_time_explode[0], 3)*1000;
    
    $retArray = [];
    
    $retArray["vn_response"] = $vn_response;
    $retArray["stratum_response"] = $stratum_response;
    $retArray["delay_ms"] = $delay_ms;
    $retArray["ntp_time_formatted"] = $ntp_time_formatted;
    $retArray["server_time_formatted"] = $server_time_formatted;
    $retArray["offset_ms"] = $offsetInterval;
    
    return $retArray;
}
?>

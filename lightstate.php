<?php
require_once(__DIR__ . "/helpers/constants.php");
ob_start();
require_once(SITE_DOCUMENT_ROOT . "helpers/tcpUtils.php");

function ArrayToJsonSingle($values, $ip)
{
    $answer = '{';
    if (!empty($ip))
    {
        $answer .= '"ip":"' . $ip . '",';
    }
    $answer .= '"signals": [';
    for($phase=1; $phase<=8; $phase++)
    {
        if ($phase>1) { $answer .= ','; }
        $answer .= '{"phase": ' . $phase . ', "state": "' . $values[$phase] . '"}';
    }
    $answer .= ']}';
    return $answer;
}

if (isset($_REQUEST['callback']))
{
    $callback = $_REQUEST['callback'];
}
else
{
    $callback = "";
}

if (strcmp($_SERVER['REQUEST_METHOD'], 'POST')==0)
{ // produce JSONP output from array of IP addresses
    // array of IPs
    $ipList = [];
    foreach($_POST as $key=>$value)
    {
        if (!str_starts_with($key, 'ip')) { continue; }
        if (empty($value)) { continue; }
        $ipList[] = $value;
    }
    // build JSONP response
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header('Content-type: application/json');
    $answer = $callback . '([';
    $nPort = 20000;
    $isFirst = true;
    foreach($ipList as $ip)
    {
        if ( !$objSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) )
        {
            // ?? error message ??
            continue;
        }
        if ( !socket_connect($objSocket, $szAddress, $nPort) )
        {
            // ?? error message ??
            socket_close($objSocket);
            continue;
        }
        $szBuffer = "CF,END";
        socket_write($objSocket, $szBuffer, strlen($szBuffer));
        $szReturn = '';
        while ($out = socket_read($objSocket, 2048))
        {
           $szReturn .= $out;
        }
        socket_close($objSocket);
        $values = [];
        for($phase=1; $phase<=8; $phase++)
        {
            $ch = $szReturn[$phase+2];
            switch($ch)
            {
                case '0':
                {
                    $ch = 'G';
                    break;
                }
                case '1':
                {
                    $ch = 'R';
                    break;
                }
                case '2':
                {
                    $ch = 'A';
                    break;
                }
            }
            $values[$phase] = $ch . "";
        }
        if (!$isFirst)
        {
            $isFirst = false;
        }
        else
        {
            $answer .=',';
        }
        $answer .= ArrayToJsonSingle($values, $ip);
    } // foreach($ipList as $ip)
    $answer .= '])';
    echo $answer;
} // if (strcmp($_SERVER['REQUEST_METHOD'], 'POST')==0)
else if (isset($_REQUEST['all']))
{ // request all phases' colors
    if ( $objSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) )
    {
        $nPort = 20000;
    	if ( socket_connect($objSocket, $szAddress, $nPort) )
    	{
            $szBuffer = "CF,END";
    		socket_write($objSocket, $szBuffer, strlen($szBuffer));
    		$szReturn = '';
    		while ($out = socket_read($objSocket, 2048))
    		{
    		   $szReturn .= $out;
    		}
            $values = [];
            for($phase=1; $phase<=8; $phase++)
            {
                $ch = $szReturn[$phase+2];
                switch($ch)
                {
                    case '0':
                    {
                        $ch = 'G';
                        break;
                    }
                    case '1':
                    {
                        $ch = 'R';
                        break;
                    }
                    case '2':
                    {
                        $ch = 'A';
                        break;
                    }
                }
                $values[$phase] = $ch . "";
            }
            if (empty($callback))
            {
                header("Cache-Control: no-store, no-cache, must-revalidate");
                header("Content-type: application/xml");
                // return is CF,signals,queues,calls,ped calls,END
                // for the moment, signals is phases 1-8: 0=Green, 1=Red, 2=Amber
                $answer = "<signals>";
                for($phase=1; $phase<=8; $phase++)
                {
                    $answer .= '<signal phase="' . $phase . '" state="' . $values[$phase] . '" />';
                }
                $answer .= '</signals>';
                echo $answer;
            }
            else
            {
                header("Cache-Control: no-store, no-cache, must-revalidate");
                header('Content-type: application/json');
        		$answer = $callback . '(';
                $answer .= ArrayToJsonSingle($values, '');
                $answer .= ')';
                echo $answer;
            }
    	}
    	else
    	{
            if (empty($callback))
            {
                echo '<signals />'; // return empty states
            }
            else
            {
        		echo $callback . '({"signal":0})'; // return invalid state
            }
    	}
    	socket_close($objSocket);
    }
    else
    {
        if (empty($callback))
        {
        	echo '<signals />'; // return empty states
        }
        else
        {
    		echo $callback . '({"signal":0})'; // return invalid state
        }
    }
} // else if (isset($_REQUEST['all']))
else
{ // request one thru and one turn phase
    $callback = $_REQUEST['callback'];
    $sig_thru = $_REQUEST['sig_thru'];
    $sig_turn = $_REQUEST['sig_turn'];
    $queryString = $_SERVER['QUERY_STRING'];
    if ( $objSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) )
    {
    	if ( socket_connect($objSocket, $szAddress, $nPort) )
    	{
    	 	$szBuffer = pack('CCa*', TRAVIS_TCP_HEADER, 0x02, $queryString);
    		$nCRC = calcChecksum($szBuffer, strlen($szBuffer));
    		$szBuffer .= pack('C', $nCRC);
    		socket_write($objSocket, $szBuffer, strlen($szBuffer));
    		header("Cache-Control: no-store, no-cache, must-revalidate");
    		header("Content-type: text/html");
    		$szReturn = '';
    		while ($out = socket_read($objSocket, 2048))
    		{
    		   $szReturn .= $out;
    		}
    		// $szReturn is state number; figure out which of sig_thru and sig_turn it is in
    		$thruGreen = str_contains($sig_thru, $szReturn);
    		$turnGreen = str_contains($sig_turn, $szReturn);
    		$sig_thruReturn = $thruGreen ? $szReturn : '0';
    		$sig_turnReturn = $turnGreen ? $szReturn : '0';
    		$replyMessage = $callback . '({"signal":' . $szReturn . ',"sig_thru":"' . $sig_thruReturn . '","sig_turn":"' . $sig_turnReturn . '"})';
    		echo $replyMessage;
    	}
    	else
    	{
    		echo $callback . '({"signal":0})'; // return invalid state
    	}
    	socket_close($objSocket);
    }
    else
    {
    	echo $callback . '({"signal":0})'; // return invalid state
    }
}
?>
<?php
function getInSyncIP()
{
    require_once("pathDefinitions.php");
    
	$ip = "";
	if ( file_exists( NETWORK_SETTINGS_CONF_FILE ) )
	{
		$contents = @file_get_contents(NETWORK_SETTINGS_CONF_FILE);
	
		if($contents !== FALSE)
		{
			$xml = @simplexml_load_string($contents, null, LIBXML_NOENT);
		
			if($xml !== FALSE)
			{
				$ip = $xml->IPAddress;
				if($xml->InlinkIP !== FALSE)
				{
					$ip = $xml->InlinkIP;
				}
			}
		}
	}

	if($ip == "")
	{
		// get the ip from ManageIPConfig.exe -get -publicip
		// return natip if used else return the ip address
		$ip = exec( MANAGE_IP_CONF_EXE . " -get -publicip" ); 
	}
	return $ip;
}

function GetAllCorridorIPs($IncludeSelf)
{
    require_once("pathDefinitions.php");
    
	$iplist = [];
	if(file_exists(CORRIDOR_CONF_FILE))
	{
		$corridorXML = @simplexml_load_file(CORRIDOR_CONF_FILE);
		if($corridorXML !== FALSE)
		{        
			$localIP = getInSyncIP();
		    foreach($corridorXML->Intersection as $intersection)
		    {
		        $ip = "";
		        if(isset($intersection["IP"]))
		            $ip = (string)$intersection["IP"];

		        if(($IncludeSelf || (!$IncludeSelf && ($ip != $localIP))) && isValidIP($ip) === TRUE)
		        {
					$name = "Unnamed Intersection";
					if(isset($intersection->TraVisConfiguration))
					{
						if(isset($intersection->TraVisConfiguration->Intersection))
						{
						    if(isset($intersection->TraVisConfiguration->Intersection["name"]))
							{
						        $name = (string)$intersection->TraVisConfiguration->Intersection["name"];
							}
							$videoDetectionDevices = $intersection->xpath("//VideoDetectionDevice");

							// Find all remote processors
							foreach ($videoDetectionDevices as $vdd)
							{
								$machine = (string)($vdd->attributes()['machine']);		// Either an IP address or "."
								
								//Sanity check just in case we're on the video only processor and we do this?
								if(($machine != "." && ($IncludeSelf || (!$IncludeSelf && ($machine != $localIP)))) && isValidIP($machine) === TRUE)
								if (isValidIP($ip))
								{
									$iplist[$machine] = $name . " (Video Processor)";
								}
							}
						}
					}
				
					$iplist[$ip] = $name;
				}
		    }
		}
		else
		{
			return FALSE;
		}
	}
	else
	{
		return FALSE;
	}
	return $iplist;
}

function private_GetCorridorIntersections($IncludeSelf)
{
    require_once("pathDefinitions.php");
    
	$iplist = [];
	if(file_exists(CORRIDOR_CONF_FILE))
	{
		$corridorXML = @simplexml_load_file(CORRIDOR_CONF_FILE);
		if($corridorXML !== FALSE)
		{        
			$localIP = getInSyncIP();
		    foreach($corridorXML->Intersection as $intersection)
		    {
		        $ip = "";
		        if(isset($intersection["IP"]))
		            $ip = (string)$intersection["IP"];

		        if(($IncludeSelf || (!$IncludeSelf && ($ip != $localIP))) && isValidIP($ip) === TRUE)
		        {
					$name = "Unnamed Intersection";
					if(isset($intersection->TraVisConfiguration))
					{
						if(isset($intersection->TraVisConfiguration->Intersection))
						{
						    if(isset($intersection->TraVisConfiguration->Intersection["name"]))
							{
						        $name = (string)$intersection->TraVisConfiguration->Intersection["name"];
							}
						}
					}
				
					$iplist[$ip] = $name;
				}
		    }
		}
		else
		{
			return FALSE;
		}
	}
	else
	{
		return FALSE;
	}
	return $iplist;
}


function getCorridorIntersections()
{
	return private_GetCorridorIntersections(false);

}

function getCorridorIntersectionsIncludingSelf()
{
	return private_GetCorridorIntersections(true);
}





function isValidIP($ip)
{
	if($ip != "" && $ip != "127.0.0.1" && ip2long($ip) !== FALSE)
	{
		return TRUE;
	}

	return FALSE;
}


?>

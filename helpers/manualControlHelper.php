<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/webdb.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/insyncInterface.php");
require_once("pathDefinitions.php");
$permissions = authSystem::ValidateUser();
// end

if (empty($permissions["manual"]))
    die("Error: You do not have permission to access this page.");

require_once("FileIOHelper.php");	

// get system type (0=InSync, 1=Hawkeye etc.)
$systemConfigurationType = getSystemType();

$UTC = new DateTimeZone("UTC");
$action = "";

if(isset($_REQUEST['action']))
   $action = $_REQUEST['action'];

switch($action)
{
   /**
    * USED ONLY BY InSync
    * Returns the status of the manual call DB and clears
    * all non-persistent calls
    */
   case "getstateinsync":
   {
      getStateInSync();
   }
   break;

   /**
    * Get HTML to display manual controls
    */
   case "gethtml":
   {
      $readOnly = false;
      if(isset($_REQUEST['readonly']))
      {
         if($_REQUEST['readonly'] == "true")
            $readOnly = true;
         else
            $readOnly = false;
      }
      
      echo getHTML($readOnly);
   }
   break;

   /**
    * Tries to acquire a write lock on the manualcall table
    */
   case "acquirelock":
   {
      $override = false;
      if(isset($_REQUEST['override']))
      {
         if($_REQUEST['override'] == "true")
            $override = true;
         else
            $override = false;
      }
      
      acquireLock($override);
   }
   break;

   /**
    * Returns the state of the Manual Call DB
    */
   case "getstate":
   {
      getManualCallState();
   }
   break;

   /**
    * Places manual calls into DB
    */
   case "manualcalls":
   {

      setManualCalls();

   }
   break;
   
 //  case "notifyinsync":
 //  {
   //      notifyinsync();
 //  }
  // break;
   
   /**
    * Closes manual call session
    */
   case "close":
   {
      endSession();
   }
   break;
   

}

/**
 * Get HTML to display manual controls
 * @param boolean $readOnly
 */
function getHTML($readOnly)
{
   require_once("pathDefinitions.php");
   require_once("databaseInterface.php");
   require_once("phaseHelper.php");
   
   $intersectionXML = getFile("Intersection.xml");   
   $intersectionObject = simplexml_load_string($intersectionXML);
   
   global $systemConfigurationType;
   
    // For InSync:Hawkeye - Get emergency mode data from file
	if (file_exists(HAWKEYE_EMERGENCY_MODE_PHASES) && $systemConfigurationType === 1)
	{
		$stringJson = file_get_contents(HAWKEYE_EMERGENCY_MODE_PHASES);
		$emergencyPhases = json_decode($stringJson, true);		
	}
   
   // get active phases
   $activePhases = [];
   foreach($intersectionObject->Intersection->Direction as $Directions)
      foreach($Directions->Phases->Phase as $Phase)
         array_push($activePhases, $Phase["name"]);
   sort($activePhases, SORT_NUMERIC);

   $insync = new InsyncInterface();
   $pedInfoData = $insync->getPedestrianInfo();
   $xml = simplexml_load_string($pedInfoData);

   // get active peds
   $pedNumbers = [];
   foreach($xml->PedPhase as $pedPhase)
   {
      $number = (int)$pedPhase["Number"];
      $pedNumbers[] = $number;
   }
    
   sort($pedNumbers, SORT_NUMERIC);
    
   $phaseNames = getPhaseNames();

   // start our output
   $output = "<center>";

   $output .= '<table class="table manual-controls" id="manualControlContainer"><thead><tr><th>&nbsp;</th>';
   if ($systemConfigurationType !== 1)
   {
		$output .= '<th>Force Presence</th><th>Manual Call</th>';
   }
   else
   {
		$output .= '<th>Force Presence</th><th>Manual Call</th><th>Hawkeye Emergency Mode</th>';
   }
    
   $foundPeds = false;
   if(count($pedNumbers) > 0)
   {
       $output .= "<th>&nbsp;</th><th>Pedestrian</th>";
       $foundPeds = true;
   }
    
   $output .= "</tr></thead>";
    
   for($i=1; $i <= 8; $i++)
   {
      if(in_array($i, $activePhases) || in_array($i, $pedNumbers))
      {
         $output .= "<tr>";
         
         if(in_array($i, $activePhases))
         {
            $name = $phaseNames[$i]['short'];
            $output .= "<td>Veh$i / $name</td>";
            $output .= "<td><input type='checkbox' id='queue$i'/><label for='queue$i' class='btn btn-default'>Off</label></td>";
            $output .= "<td><input type='checkbox' id='call$i'/><label for='call$i' class='btn btn-default'>Off</label></td>";

			if ($systemConfigurationType === 1)
			{
				if (!empty($emergencyPhases))
				{
					$keyString = "Emergency".$i;
					if (array_key_exists($keyString, $emergencyPhases))
					{
						if ($emergencyPhases[$keyString])
						{
							$output .= "<td><input type='checkbox' id='emergency$i'/><label for='emergency$i' class='btn btn-default'>On</label></td>";
						}
						else
						{
							$output .= "<td><input type='checkbox' id='emergency$i'/><label for='emergency$i' class='btn btn-default'>Off</label></td>";
						}
					}
					else
					{
						$output .= "<td><input type='checkbox' id='emergency$i'/><label for='emergency$i' class='btn btn-default'>Off</label></td>";
					}		
				}
				else
				{
					$output .= "<td><input type='checkbox' id='emergency$i'/><label for='emergency$i' class='btn btn-default'>Off</label></td>";
				}
			}
         }
         else
         {
            $output .= "<td>&nbsp;</td>";
            $output .= "<td>&nbsp;</td>";
            $output .= "<td>&nbsp;</td>";
			$output .= "<td>&nbsp;</td>";
         }
         
         if(in_array($i, $pedNumbers))
         {
            $output .= "<td>Ped$i</td>";
            $output .= "<td><input type='checkbox' id='ped$i'/><label for='ped$i' class='btn btn-default'>Off</label></td>";
         }
         else
         {
            $output .= "<td>&nbsp;</td>";
            $output .= "<td>&nbsp;</td>";
         }
                 
         $output .= "</tr>";
      }
   }
    
   $output .= '</table>';
   $loggedIn = true;
   require_once("insyncInterface.php");
   $insync = new InSyncInterface();
   $detvalue = $insync->getWebUIDetectorMode();

   if(!$readOnly)
   {
      if($detvalue === TRUE)
      {
         $output .= '<input type="checkbox" id="disableAdaptive"/><label for="disableAdaptive" class="btn btn-default orange">Enable Adaptive</label>';
      }
      else
      {
         $output .= '<input type="checkbox" id="disableAdaptive"/><label for="disableAdaptive" class="btn btn-default orange">Disable Adaptive</label>';
      }
   }
   
   $output .= '</center>';
   
   $output .= '<div id="dialog-confirm" title="Disable InSync Adaptive Mode"><p>Are you sure you wish to disable adaptive mode?</p><p>This setting will persist after Manual Controls are closed!</p></div>';
   
   //$output .= '<script type="text/javascript">var activePhases = new Array();var activePeds = new Array();';
   $output .= '<script type="text/javascript">var activePhases = new Array();var activePeds = new Array();var emergencyPhases = new Array();';
   
   foreach($activePhases as $phase)
      $output .= "activePhases.push($phase);\n";
   
   foreach($pedNumbers as $phase)
      $output .= "activePeds.push($phase);\n";

	foreach ($emergencyPhases as $key=>$value)
	{	if ($emergencyPhases[$key])
		{
			$phase = substr($key,9,1);
			$output .= "emergencyPhases.push($phase);\n";
		}
	}
   
   if($readOnly)
      $readOnly = "true";
   else
      $readOnly = "false";
   
   $sessionID = $permissions['sessionID'];
  
   $output .= "initManualScript(activePhases, activePeds, '$sessionID',$readOnly, '$detvalue', emergencyPhases);</script>";
   

   
   return $output;
}

/**
 * Tries to acquire a write lock on the manualcall table
 * @param boolean $override If set to true, this will override any existing
 * locks on the table.
 */
function acquireLock($override)
{
        global $UTC;
   global $permissions;
   
   $db = openWebDB();

   if (!$db)
   {
      die('{"error": "Could not connect to database."}');
   }
        pg_query($db, "BEGIN TRANSACTION");

   if($result = pg_query($db, "SELECT value,\"timestamp\" FROM manualcalls WHERE key='lock_holder'"))
   {      
      $resultRow = pg_fetch_assoc($result);
      
      $time = new DateTime("now", $UTC);
      $sessionID = $permissions["sessionID"];
        
      // no lock key found
      if($resultRow == NULL)
      {
                    pg_query_params($db,
                            "INSERT INTO manualcalls (key, value, \"timestamp\") values ('lock_holder', $1, transaction_timestamp() at time zone 'UTC')",
                            [$sessionID]);
                    pg_query($db, "UPDATE manualcalls set value='0', \"timestamp\"=transaction_timestamp() at time zone 'UTC' where key='increment'");
                    pg_query($db, "INSERT INTO manualcalls (key, value, \"timestamp\") select 'increment', '0', transaction_timestamp() at time zone 'UTC' where not exists (select 1 from manualcalls where key='increment')");
      }
      // found lock key
      else
      {
                    if($resultRow["value"] != $sessionID)
                    {            
                        if(($time->getTimestamp() - (new DateTime($resultRow['timestamp'], $UTC))->getTimestamp()) < 900 && !$override)
                        {
                            // ask if user wants to override previous write lock
                            die('{"error": "locked"}');
                        }
                        else
                        {
                            // you're taking over from nothing?                    
                            pg_query_params($db, "UPDATE manualcalls set value=$1, \"timestamp\"=transaction_timestamp() at time zone 'UTC' where key='lock_holder'", [$sessionID]);
                            pg_query($db, "UPDATE manualcalls set value='0', \"timestamp\"=transaction_timestamp() at time zone 'UTC' where key='increment'");
                            pg_query($db, "INSERT INTO manualcalls (key, value, \"timestamp\") select 'increment', '0', transaction_timestamp() at time zone 'UTC' where not exists (select 1 from manualcalls where key='increment')");
                        }
                    }
      }
   }
   else
   {
                pg_query($db, "ROLLBACK TRANSACTION");
                pg_close($db);
      die('{"error": "Could not read from thedatabase."}');
   }

        pg_query($db, "COMMIT TRANSACTION");
        pg_close($db);
   die('{"success": "success"}');
}

/**
* USED ONLY BY InSync
* Returns the status of the manual call DB and clears
* all non-persistent calls
*/
 //Not used anymore
function getStateInSync()
{
        global $UTC;

   $db = openWebDB();

   if (!$db)
   {
                pg_close($db);
      echo "Error: Could not connect to database.";
      exit;
   }
   
   set_time_limit(300);
   
   $startTime = new DateTime("now", $UTC);
   
   while(true)
   {   
      $xmlDoc = "<manualCalls>";

      $time = new DateTime("now", $UTC);
      
      if(($time->getTimestamp() - $startTime->getTimestamp()) >= 290)
         break;

      if($result = pg_query($db, "SELECT key,value,\"timestamp\" FROM manualcalls"))
      {      
         while($row = pg_fetch_assoc($result))
         {
            $entryTime = new DateTime($row["timestamp"], $UTC);

            if($row["key"] == 'lock_holder')
            {
                                    if(($time->getTimestamp() - $entryTime->getTimestamp()) >= 900)
                                        pg_query($db, "DELETE FROM manualcalls WHERE key='lock_holder'");
            }
            else if($row["key"] == 'increment')
            {
                                    continue;
            }
            else if(str_starts_with($row["key"], 'PedButton'))
            {
                                    pg_query_params($db, "DELETE FROM manualcalls WHERE key=$1", [$row["key"]]);
            }
            else
            {
                                    if(($time->getTimestamp() - $entryTime->getTimestamp()) >= 60)
                                        pg_query_params($db, "DELETE FROM manualcalls WHERE key=$1", [$row["key"]]);
                                    // handles negative times for DST / NTP updates
                                    else if(($time->getTimestamp() - $entryTime->getTimestamp()) <= -5)
                                        pg_query_params($db, "DELETE FROM manualcalls WHERE key=$1", [$row["key"]]);
            }

            $xmlDoc .= "<row key=\"" . $row['key']. "\" value=\"" . $row['value']. "\" />";
         }
      }
      
     $xmlDoc .= "</manualCalls>\r\n";


      $xml = simplexml_load_string($pedInfoData);
      
      echo $xmlDoc;
      ob_flush();
      flush();
      sleep(1);
   }

        pg_close($db);

}



/**
 * Returns the state of the Manual Call DB
 */
function getManualCallState()
{
   $loggedIn = true;
   require_once("insyncInterface.php");
   $insync = new InSyncInterface();

   $db = openWebDB();

   if (!$db)
   {
      echo "Error: Could not connect to database.";
      exit;
   }
        pg_query($db, "BEGIN TRANSACTION");
   
   $jsonObj = [];
   $xmlDoc = "<manualCalls>";
   
   if(isset($_REQUEST['initial']))
        {
            pg_query($db, "UPDATE manualcalls set value='0', \"timestamp\"=transaction_timestamp() at time zone 'UTC' where key = 'increment'");
            pg_query($db, "INSERT INTO manualcalls (key, value, \"timestamp\") select 'increment', '0', transaction_timestamp() at time zone 'UTC' where not exists (select 1 from manualcalls where key = 'increment')");
        }
   //could be why the web ui coloring gets lagged
   //After enough updates are put into the db there's more to retrive and oberserve
   $count = 0;
   $flagBadXML = false;
   if($result = pg_query($db, "SELECT key,value FROM manualcalls"))
   {      
      while($row = pg_fetch_assoc($result))
      {
         $jsonObj[$row["key"]] = $row["value"];
         $xmlDoc .= "<row key=\"" . $row['key']. "\" value=\"" . $row['value']. "\" />";

         $count++;
      }

      $detvalue = $insync->getWebUIDetectorMode();
      if($detvalue == TRUE)
      {
         $jsonObj["Disable_Adaptive"] = "1";
      }
      else
      {
         $jsonObj["Disable_Adaptive"] = "0";
      }
   }
   // \r is called a carriage return
         $xmlDoc .= "</manualCalls>";
         
         echo json_encode($jsonObj);

        pg_query($db, "COMMIT TRANSACTION");
        pg_close($db);
        

            return $xmlDoc;

            
}

/**
 * Places manual calls into DB
 */
 //Doesn't get used anymore. 
function setManualCalls()
{
   $loggedIn = true;
   require_once("insyncInterface.php");
   $insync = new InSyncInterface();

   global $systemConfigurationType;
   
      $message = "";
   if(isset($_REQUEST['message']))
   $message = $_REQUEST['message'];
   
   $emArray = [];   
   		foreach($message as $key=>$value)
		{
			if (str_starts_with($key, "Emergency"))
			{
				$jsonObject[$key] = $value;
				$contents = json_encode($jsonObject);
			}
		}

   // only for Hawkeye
   if ($systemConfigurationType === 1)
   {
	   if ((!file_exists(HAWKEYE_EMERGENCY_MODE_PHASES)) && (file_exists(HAWKEYE_CONF)))
	   {
			//$jsonObject = array();
			$emfile = fopen(HAWKEYE_EMERGENCY_MODE_PHASES, "w") or die("Unable to open file!");

			fwrite($emfile, $contents);							
			fclose($emfile);
	   }
	   if (file_exists(HAWKEYE_EMERGENCY_MODE_PHASES))
	   {
			SaveJson($contents);
	   }
   }
        
   require_once("insyncInterface.php");
   $insync = new InsyncInterface();
   $detvalue = $insync->setManualCalls($xmlDoc);

}




/**
 * For InSync:Hawkeye - Save Emergency Json
 */
//Not used in this file anymore. 
function SaveJson($emArray)
{
	$myfile = fopen(HAWKEYE_EMERGENCY_MODE_PHASES, "w") or die("Unable to open file!");		
	//$emContents = json_encode($emArray);
	fwrite($myfile, $emArray);	
	fclose($myfile);
}

/**
 * Closes a manual controls session
 */
function endSession()
{
   $db = openWebDB();

   if (!$db) {
      echo "Error: Could not connect to database.";
      exit;
   }
      
   pg_query($db, "BEGIN TRANSACTION");
   pg_query($db, "DELETE FROM manualcalls");
   pg_query($db, "INSERT INTO manualcalls (key, value, \"timestamp\") VALUES('increment','999999999999',transaction_timestamp() at time zone 'UTC')");
   pg_query($db, "COMMIT TRANSACTION");
   pg_close($db);
}
?>

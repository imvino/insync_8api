<?php
/*
if(!isset($loggedIn) || !$loggedIn)
{
	// this must be included on all pages to authenticate the user
	require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
	$permissions = authSystem::ValidateUser();
	// end
    
    if (empty($permissions["reports"]))
        die("Error: You do not have permission to access this page.");
}
*/
$loggedIn = true;
ini_set('memory_limit','256M');

require_once("pathDefinitions.php");
//require_once("databaseInterface.php");
//require_once("phaseHelper.php");

$IOFailMessageGood = 'I/O device communication restored';
$IOFailMessageBad = 'A failure in this intersections I/O device(s)';
$CameraImageMessageGood = 'All camera image problems have been resolved';
$CameraImageMessageBad = 'Camera detector(s) have reported image problems';		
$CameraFailureMessageGood = 'All camera failures have been resolved';
$CameraFailureMessageBad = 'Camera detector(s) have reported a failure';				
$NetworkFailureMessageGood = 'Network connectivity has been restored to all';
$NetworkFailureMessageBad = 'Network connectivity has been lost to some';						
$TimeFailMessageGood = 'Time syncing has returned to normal operation';
$TimeFailMessageBad = 'InSync is unable to communicate with time servers';								
$PedestrianFailureMessageGood = 'pedestrian buttons have returned to working normally';
$PedestrianFailureMessageBad = 'pedestrian button is stuck in the triggered';		
$DetectorFailureMessageGood = 'detector failures have been resolved';
$DetectorFailureMessageBad = 'Detectors on lane(s) have reported failures';
$InFlashMessageGood = 'Intersection is no longer in flash';
$InFlashMessageBad = 'Intersection is in flash';		
$LightUnresponsiveMessageGood = 'lights have returned to responding normally';
$LightUnresponsiveMessageBad = 'have been reported as unresponsive';
$LongWaitMessageGood = 'wait times have returned to normal';
$LongWaitMessageBad = 'long wait time has been detected';						


$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

switch($action)
{
    /**
	 * Downloads JSON for user.
	 */
	case "downloadjson":
	{
		$start = "";
		if(isset($_REQUEST['startDateTime']))
			$start = $_REQUEST['startDateTime'];
		
		$end = "";
		if(isset($_REQUEST['endDateTime']))
			$end = $_REQUEST['endDateTime'];
        
        // limit time range to two days for memory/performance reasons
        //$startStamp = strtotime($start);
        //$endStamp = strtotime($end);

		$currentDateandTime = date("Y-m-d") . " " . date("H.i.s");
		$fileName = "Notifications_".$currentDateandTime;

        header("Content-type: application/json; header=present");
        //header("Content-disposition: attachment;filename=Notifications.json");
		header('Content-Disposition: attachment; filename="'.$fileName.'.json"');

        //if($endStamp-$startStamp >= 172800)
        //    die('Error: The requested time span was too large. Please choose a timespan of <48 hours.');
		
		$notificationData = loadNotificationData($start, $end);        
		
	//$myfile = fopen("C:\Newfile.txt", "w") or die("Unable to open file!");
	//foreach ($notificationData as $key => $value)
	//foreach ($notificationData as $testData)
	//{
	//	$txt = $value;
	////	$txt = "Manoj Test";
	//	fwrite($myfile, $txt);
	//}
	//fclose($myfile);				
		
		
		$IOFail = [];
		$CameraImage = [];		
		$CameraFailure = [];
		$NetworkFailure = [];
		$TimeFail = [];
		$PedestrianFailure = [];
		$DetectorFailure = [];
		$InFlash = [];
		$LightUnresponsive = [];
		$LongWait = [];			
		
	foreach ($notificationData as $key => $value)
	{
		// I/O Fail
		if (str_contains($value, $IOFailMessageBad))
		{
			//$IOFail[] = array('DateTime:'=>$key, 'Event:'=>$value);
			$IOFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
		}
		else if (str_contains($value, $IOFailMessageGood))
		{
			//$IOFail[] = array('DateTime:'=>$key, 'Event:'=>$value);
			$IOFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];								
		}
		// Camera Image
		else if (str_contains($value, $CameraImageMessageBad))
		{
			//$CameraImage[] = array('DateTime:'=>$key, 'Event:'=>$value);
			$CameraImage[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];												
		}
		else if (str_contains($value, $CameraImageMessageGood))
		{
			//$CameraImage[] = array('DateTime:'=>$key, 'Event:'=>$value);
			$CameraImage[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																
		}
		// Camera Failure
		else if (str_contains($value, $CameraFailureMessageBad))
		{
			//$CameraFailure[] = array('DateTime:'=>substr($key,0,19), 'Event:'=>$value);
			$CameraFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																				
		}
		else if (str_contains($value, $CameraFailureMessageGood))
		{
			//$CameraFailure[] = array('DateTime:'=>substr($key,0,19), 'Event:'=>$value);
			$CameraFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																								
		}						
		// Network Failure
		else if (str_contains($value, $NetworkFailureMessageBad))
		{
			//$NetworkFailure[] = array('DateTime:'=>$key, 'Event:'=>$value);
			$NetworkFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																
		}
		else if (str_contains($value, $NetworkFailureMessageGood))
		{
			//$NetworkFailure[] = array('DateTime:'=>$key, 'Event:'=>$value);
			$NetworkFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																				
		}
		// Time Fail
		else if (str_contains($value, $TimeFailMessageBad))
		{
			//$TimeFail[] = array('DateTime:'=>$key, 'Event:'=>$value);
			$TimeFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																				
		}
		else if (str_contains($value, $TimeFailMessageGood))
		{
			//$TimeFail[] = array('DateTime:'=>$key, 'Event:'=>$value);
			$TimeFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																								
		}
		// Pedestrian Failure (stuck)
		else if (str_contains($value, $PedestrianFailureMessageBad))			
		{
			$PedestrianFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																								
		}
		else if (str_contains($value, $PedestrianFailureMessageGood))			
		{
			$PedestrianFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																												
		}	
		// Detector Failure
		else if (str_contains($value, $DetectorFailureMessageBad))			
		{
			$DetectorFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																								
		}
		else if (str_contains($value, $DetectorFailureMessageGood))			
		{
			$DetectorFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																												
		}
		// 	In flash
		else if (str_contains($value, $InFlashMessageBad))			
		{
			$InFlash[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																								
		}
		else if (str_contains($value, $InFlashMessageGood))			
		{
			$InFlash[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																												
		}
		// Light Unresponsive
		else if (str_contains($value, $LightUnresponsiveMessageBad))			
		{
			$LightUnresponsive[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																								
		}
		else if (str_contains($value, $LightUnresponsiveMessageGood))			
		{
			$LightUnresponsive[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																												
		}
		// Long Wait		
		else if (str_contains($value, $LongWaitMessageBad))			
		{
			$LongWait[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																								
		}
		else if (str_contains($value, $LongWaitMessageGood))			
		{
			$LongWait[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																												
		}			
	}
		
		$jsonData = [];
		$jsonData["IOFail"] = $IOFail;
		$jsonData["CameraImage"] = $CameraImage;
		$jsonData["CameraFailure"] = $CameraFailure;
		$jsonData["NetworkFailure"] = $NetworkFailure;
		$jsonData["TimeFail"] = $TimeFail;
		$jsonData["PedestrianFailure"] = $PedestrianFailure;	
		$jsonData["DetectorFailure"] = $DetectorFailure;
		$jsonData["InFlash"] = $InFlash;		
		$jsonData["LightUnresponsive"] = $LightUnresponsive;	
		$jsonData["LongWait"] = $LongWait;						

		//$fp = fopen("C:\NewJson.json", "w") or die("Unable to open file!");
		//fwrite($fp, json_encode($jsonData));
		//fclose($fp);

		echo json_encode($jsonData);
		
	}
	break;
    /**
	 * Get JSON when hit with URL - Based on entries in date/time picker
	 * http://localhost/helpers/notificationsHelper.php?action=getjson&startDateTime=08/30/2019 12:00 AM&endDateTime=08/30/2019 11:59 PM
	 */
	case "getjson":
	{
		$start = "";
		if(isset($_REQUEST['startDateTime']))
			$start = $_REQUEST['startDateTime'];
		
		$end = "";
		if(isset($_REQUEST['endDateTime']))
			$end = $_REQUEST['endDateTime'];
        
        //$startStamp = strtotime($start);
        //$endStamp = strtotime($end);

		$notificationData = loadNotificationData($start, $end);        
		
		$IOFail = [];
		$CameraImage = [];		
		$CameraFailure = [];
		$NetworkFailure = [];
		$TimeFail = [];
		$PedestrianFailure = [];
		$DetectorFailure = [];
		$InFlash = [];
		$LightUnresponsive = [];
		$LongWait = [];			
		
		foreach ($notificationData as $key => $value)
		{
			// I/O Fail
			if (str_contains($value, $IOFailMessageBad))
			{
				$IOFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];				
			}
			else if (str_contains($value, $IOFailMessageGood))
			{
				$IOFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];								
			}
			// Camera Image
			else if (str_contains($value, $CameraImageMessageBad))
			{
				$CameraImage[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];												
			}
			else if (str_contains($value, $CameraImageMessageGood))
			{
				$CameraImage[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];																
			}
			// Camera Failure
			else if (str_contains($value, $CameraFailureMessageBad))
			{
				$CameraFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];																				
			}
			else if (str_contains($value, $CameraFailureMessageGood))
			{
				$CameraFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];																								
			}						
			// Network Failure
			else if (str_contains($value, $NetworkFailureMessageBad))
			{
				$NetworkFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];																
			}
			else if (str_contains($value, $NetworkFailureMessageGood))
			{
				$NetworkFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];																				
			}
			// Time Fail
			else if (str_contains($value, $TimeFailMessageBad))
			{
				$TimeFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];																				
			}
			else if (str_contains($value, $TimeFailMessageGood))
			{
				$TimeFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];																								
			}
			// Pedestrian Failure (stuck)
			else if (str_contains($value, $PedestrianFailureMessageBad))			
			{
				$PedestrianFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];																								
			}
			else if (str_contains($value, $PedestrianFailureMessageGood))			
			{
				$PedestrianFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];																												
			}	
			// Detector Failure
			else if (str_contains($value, $DetectorFailureMessageBad))			
			{
				$DetectorFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];																								
			}
			else if (str_contains($value, $DetectorFailureMessageGood))			
			{
				$DetectorFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];																												
			}
			// 	In flash
			else if (str_contains($value, $InFlashMessageBad))			
			{
				$InFlash[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];																								
			}
			else if (str_contains($value, $InFlashMessageGood))			
			{
				$InFlash[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];																												
			}
			// Light Unresponsive
			else if (str_contains($value, $LightUnresponsiveMessageBad))			
			{
				$LightUnresponsive[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];																								
			}
			else if (str_contains($value, $LightUnresponsiveMessageGood))			
			{
				$LightUnresponsive[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>$value]];																												
			}
			// Long Wait		
			else if (str_contains($value, $LongWaitMessageBad))			
			{
				$LongWait[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>"$value"]];																								
			}
			else if (str_contains($value, $LongWaitMessageGood))			
			{
				$LongWait[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Resolved', 'Message'=>"$value"]];																												
			}			
		}
		
		$jsonData = [];
		$jsonData["IOFail"] = $IOFail;
		$jsonData["CameraImage"] = $CameraImage;
		$jsonData["CameraFailure"] = $CameraFailure;
		$jsonData["NetworkFailure"] = $NetworkFailure;
		$jsonData["TimeFail"] = $TimeFail;
		$jsonData["PedestrianFailure"] = $PedestrianFailure;	
		$jsonData["DetectorFailure"] = $DetectorFailure;
		$jsonData["InFlash"] = $InFlash;		
		$jsonData["LightUnresponsive"] = $LightUnresponsive;	
		$jsonData["LongWait"] = $LongWait;						

		echo json_encode($jsonData);
	}	
	break;

    /**
	 * Downloads JSON for user - All active notifications.
	 */
	case "downloadactivejson":
	{
		$currentDateandTime = date("Y-m-d") . " " . date("H.i.s");
		$fileName = "ActiveNotifications_".$currentDateandTime;
		
        header("Content-type: application/json; header=present");
        //header("Content-disposition: attachment;filename=ActiveNotifications.json");
		header('Content-Disposition: attachment; filename="'.$fileName.'.json"');		
		
		$notificationData = loadAllNotificationData();        
		
	//$myfile = fopen("C:\Newfile.txt", "w") or die("Unable to open file!");
	//foreach ($notificationData as $testData)
	//{
	//	$txt = $testData;
	////	$txt = "Manoj Test";
	//	fwrite($myfile, $txt);
	//}
	//fclose($myfile);				
		
		$IOFail_tmp = [];
		$CameraImage_tmp = [];		
		$CameraFailure_tmp = [];
		$NetworkFailure_tmp = [];
		$TimeFail_tmp = [];
		$PedestrianFailure_tmp = [];
		$DetectorFailure_tmp = [];
		$InFlash_tmp = [];
		$LightUnresponsive_tmp = [];
		$LongWait_tmp = [];			
		
		// Reads both good or bad messages from the email. These records are sorted in ascending date time order. 
		// For example, if you were to select only the bad message the resolved message shown below would not have been selected because we take only the latest message into account.
		// 2019-09-04 20:29:32.9531 All camera image problems have been resolved. 
		// 2019-09-04 15:38:22.8593/MESSAGE_SENT: Camera detector(s) have reported image problems. West Bound
		// Later on we filter out and show only the bad messages
		
		foreach ($notificationData as $key => $value)
		{
			// I/O Fail
			if (((str_contains($value, $IOFailMessageBad)) || 
				(str_contains($value, $IOFailMessageGood))) &&
				(count($IOFail_tmp) == 0))
			{
				$IOFail_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Camera Image
			else if (((str_contains($value, $CameraImageMessageBad)) || 
					(str_contains($value, $CameraImageMessageGood))) &&
					(count($CameraImage_tmp) == 0))
			{
				$CameraImage_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Camera Failure
			else if (((str_contains($value, $CameraFailureMessageBad)) || 
					(str_contains($value, $CameraFailureMessageGood))) && 
					 (count($CameraFailure_tmp) == 0))
			{
				$CameraFailure_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
				
			// Network Failure
			else if (((str_contains($value, $NetworkFailureMessageBad)) ||
					(str_contains($value, $NetworkFailureMessageGood))) && 
					(count($NetworkFailure_tmp) == 0))
			{
				$NetworkFailure_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Time Fail
			else if (((str_contains($value, $TimeFailMessageBad)) || 
					(str_contains($value, $TimeFailMessageGood))) && 
					(count($TimeFail_tmp) == 0))
			{
				$TimeFail_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Pedestrian failure (stuck)	
			else if (((str_contains($value, $PedestrianFailureMessageBad)) || 
					(str_contains($value, $PedestrianFailureMessageGood))) &&
					(count($PedestrianFailure_tmp) == 0))		
			{
				$PedestrianFailure_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// Detector Failure	
			else if (((str_contains($value, $DetectorFailureMessageBad)) || 
					(str_contains($value, $DetectorFailureMessageGood))) && 
					(count($DetectorFailure_tmp) == 0))
			{
				$DetectorFailure_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// In flash
			else if (((str_contains($value, $InFlashMessageBad)) || 
					(str_contains($value, $InFlashMessageGood))) && 
					(count($InFlash_tmp) == 0))
			{
				$InFlash_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// 	Light Unresponsive
			else if (((str_contains($value, $LightUnresponsiveMessageBad)) || 
					(str_contains($value, $LightUnresponsiveMessageGood))) && 
					(count($LightUnresponsive_tmp) == 0))
			{
				$LightUnresponsive_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// Long Wait
			else if (((str_contains($value, $LongWaitMessageBad)) || 
					(str_contains($value, $LongWaitMessageGood))) && 
					(count($LongWait_tmp) == 0))
			{
				$LongWait_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}			
				
		}

		// Take the value from the array and see if it contains an error. This would be the active error.

		$IOFail = [];
		$CameraImage = [];		
		$CameraFailure = [];
		$NetworkFailure = [];
		$TimeFail = [];
		$PedestrianFailure = [];
		$DetectorFailure = [];
		$InFlash = [];
		$LightUnresponsive = [];
		$LongWait = [];			

		$jsonData = [];
	
		if (count($IOFail_tmp) > 0)
		{
			$value = $IOFail_tmp[0]['Event:'];
			if (str_contains($value, $IOFailMessageBad))
			{
				$IOFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
			$jsonData["IOFail"] = $IOFail;
			}
		}
		
		if (count($CameraImage_tmp) > 0)
		{
			$value = $CameraImage_tmp[0]['Event:'];
			if (str_contains($value, $CameraImageMessageBad))	
			{	
				$CameraImage[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];		
				$jsonData["CameraImage"] = $CameraImage;
			}
		}
		
		if (count($CameraFailure_tmp) > 0)
		{
			$value = $CameraFailure_tmp[0]['Event:'];
			if (str_contains($value, $CameraFailureMessageBad))
			{
				$CameraFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["CameraFailure"] = $CameraFailure;
			}
		}		
		
		if (count($NetworkFailure_tmp) > 0)
		{
			$value = $NetworkFailure_tmp[0]['Event:'];
			if (str_contains($value, $NetworkFailureMessageBad))
			{
				$NetworkFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["NetworkFailure"] = $NetworkFailure;
			}
		}
		
		if (count($TimeFail_tmp) > 0)
		{
			$value = $TimeFail_tmp[0]['Event:'];
			if (str_contains($value, $TimeFailMessageBad))
			{
				$TimeFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["TimeFail"] = $TimeFail;
			}
		}
		
		if (count($PedestrianFailure_tmp) > 0)
		{
			$value = $PedestrianFailure_tmp[0]['Event:'];
			if (str_contains($value, $PedestrianFailureMessageBad))
			{
				$PedestrianFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["PedestrianFailure"] = $PedestrianFailure;
			}
		}
		
		if (count($DetectorFailure_tmp) > 0)
		{
			$value = $DetectorFailure_tmp[0]['Event:'];
			if (str_contains($value, $DetectorFailureMessageBad))
			{
				$DetectorFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["DetectorFailure"] = $DetectorFailure;
			}
		}
		
		if (count($InFlash_tmp) > 0)
		{
			$value = $InFlash_tmp[0]['Event:'];
			if (str_contains($value, $InFlashMessageBad))
			{
				$InFlash[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["InFlash"] = $InFlash;
			}
		}			
		
		if (count($LightUnresponsive_tmp) > 0)
		{
			$value = $LightUnresponsive_tmp[0]['Event:'];
			if (str_contains($value, $LightUnresponsiveMessageBad))	
			{
				$LightUnresponsive[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["LightUnresponsive"] = $LightUnresponsive;
			}
		}
		
		if (count($LongWait_tmp) > 0)
		{
			$value = $LongWait_tmp[0]['Event:'];
			if (str_contains($value, $LongWaitMessageBad))
			{
				$LongWait[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["LongWait"] = $LongWait;
			}
		}									
	
		//$fp = fopen("C:\AllJson.json", "w") or die("Unable to open file!");
		//fwrite($fp, json_encode($jsonData));
		//fclose($fp);

		echo json_encode($jsonData);
		
	}
	
	break;
	
    /**
	 * Downloads JSON when hit with URL - All current active notifications
	 * http://IP/helpers/notificationsHelper.php?action=getactivejson
	 */
	case "getactivejson":
	{
		$notificationData = loadAllNotificationData();        
		
		$IOFail_tmp = [];
		$CameraImage_tmp = [];		
		$CameraFailure_tmp = [];
		$NetworkFailure_tmp = [];
		$TimeFail_tmp = [];
		$PedestrianFailure_tmp = [];
		$DetectorFailure_tmp = [];
		$InFlash_tmp = [];
		$LightUnresponsive_tmp = [];
		$LongWait_tmp = [];			
		
		// Reads both good or bad messages from the email. These records are sorted in ascending date time order. 
		// For example, if you were to select only the bad message the resolved message shown below would not have been selected because we take only the latest message into account.
		// 2019-09-04 20:29:32.9531 All camera image problems have been resolved. 
		// 2019-09-04 15:38:22.8593/MESSAGE_SENT: Camera detector(s) have reported image problems. West Bound
		// Later on we filter out and show only the bad messages
		
		foreach ($notificationData as $key => $value)
		{
			// I/O Fail
			if (((str_contains($value, $IOFailMessageBad)) || 
				(str_contains($value, $IOFailMessageGood))) &&
				(count($IOFail_tmp) == 0))
			{
				$IOFail_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Camera Image
			else if (((str_contains($value, $CameraImageMessageBad)) || 
					(str_contains($value, $CameraImageMessageGood))) &&
					(count($CameraImage_tmp) == 0))
			{
				$CameraImage_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Camera Failure
			else if (((str_contains($value, $CameraFailureMessageBad)) || 
					(str_contains($value, $CameraFailureMessageGood))) && 
					 (count($CameraFailure_tmp) == 0))
			{
				$CameraFailure_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
				
			// Network Failure
			else if (((str_contains($value, $NetworkFailureMessageBad)) ||
					(str_contains($value, $NetworkFailureMessageGood))) && 
					(count($NetworkFailure_tmp) == 0))
			{
				$NetworkFailure_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Time Fail
			else if (((str_contains($value, $TimeFailMessageBad)) || 
					(str_contains($value, $TimeFailMessageGood))) && 
					(count($TimeFail_tmp) == 0))
			{
				$TimeFail_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Pedestrian failure (stuck)	
			else if (((str_contains($value, $PedestrianFailureMessageBad)) || 
					(str_contains($value, $PedestrianFailureMessageGood))) &&
					(count($PedestrianFailure_tmp) == 0))		
			{
				$PedestrianFailure_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// Detector Failure	
			else if (((str_contains($value, $DetectorFailureMessageBad)) || 
					(str_contains($value, $DetectorFailureMessageGood))) && 
					(count($DetectorFailure_tmp) == 0))
			{
				$DetectorFailure_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// In flash
			else if (((str_contains($value, $InFlashMessageBad)) || 
					(str_contains($value, $InFlashMessageGood))) && 
					(count($InFlash_tmp) == 0))
			{
				$InFlash_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// 	Light Unresponsive
			else if (((str_contains($value, $LightUnresponsiveMessageBad)) || 
					(str_contains($value, $LightUnresponsiveMessageGood))) && 
					(count($LightUnresponsive_tmp) == 0))
			{
				$LightUnresponsive_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// Long Wait
			else if (((str_contains($value, $LongWaitMessageBad)) || 
					(str_contains($value, $LongWaitMessageGood))) && 
					(count($LongWait_tmp) == 0))
			{
				$LongWait_tmp[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}			
				
		}

		// Take the value from the array and see if it contains an error. This would be the active error.

		$IOFail = [];
		$CameraImage = [];		
		$CameraFailure = [];
		$NetworkFailure = [];
		$TimeFail = [];
		$PedestrianFailure = [];
		$DetectorFailure = [];
		$InFlash = [];
		$LightUnresponsive = [];
		$LongWait = [];			

		$jsonData = [];
	
		if (count($IOFail_tmp) > 0)
		{
			$value = $IOFail_tmp[0]['Event:'];
			if (str_contains($value, $IOFailMessageBad))
			{
				$IOFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
			$jsonData["IOFail"] = $IOFail;
			}
		}
		
		if (count($CameraImage_tmp) > 0)
		{
			$value = $CameraImage_tmp[0]['Event:'];
			if (str_contains($value, $CameraImageMessageBad))	
			{	
				$CameraImage[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];		
				$jsonData["CameraImage"] = $CameraImage;
			}
		}
		
		if (count($CameraFailure_tmp) > 0)
		{
			$value = $CameraFailure_tmp[0]['Event:'];
			if (str_contains($value, $CameraFailureMessageBad))
			{
				$CameraFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["CameraFailure"] = $CameraFailure;
			}
		}		
		
		if (count($NetworkFailure_tmp) > 0)
		{
			$value = $NetworkFailure_tmp[0]['Event:'];
			if (str_contains($value, $NetworkFailureMessageBad))
			{
				$NetworkFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["NetworkFailure"] = $NetworkFailure;
			}
		}
		
		if (count($TimeFail_tmp) > 0)
		{
			$value = $TimeFail_tmp[0]['Event:'];
			if (str_contains($value, $TimeFailMessageBad))
			{
				$TimeFail[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["TimeFail"] = $TimeFail;
			}
		}
		
		if (count($PedestrianFailure_tmp) > 0)
		{
			$value = $PedestrianFailure_tmp[0]['Event:'];
			if (str_contains($value, $PedestrianFailureMessageBad))
			{
				$PedestrianFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["PedestrianFailure"] = $PedestrianFailure;
			}
		}
		
		if (count($DetectorFailure_tmp) > 0)
		{
			$value = $DetectorFailure_tmp[0]['Event:'];
			if (str_contains($value, $DetectorFailureMessageBad))
			{
				$DetectorFailure[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["DetectorFailure"] = $DetectorFailure;
			}
		}
		
		if (count($InFlash_tmp) > 0)
		{
			$value = $InFlash_tmp[0]['Event:'];
			if (str_contains($value, $InFlashMessageBad))
			{
				$InFlash[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["InFlash"] = $InFlash;
			}
		}			
		
		if (count($LightUnresponsive_tmp) > 0)
		{
			$value = $LightUnresponsive_tmp[0]['Event:'];
			if (str_contains($value, $LightUnresponsiveMessageBad))	
			{
				$LightUnresponsive[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["LightUnresponsive"] = $LightUnresponsive;
			}
		}
		
		if (count($LongWait_tmp) > 0)
		{
			$value = $LongWait_tmp[0]['Event:'];
			if (str_contains($value, $LongWaitMessageBad))
			{
				$LongWait[] = ['DateTime'=>substr($key,0,19), 'Event'=>['State'=>'Failure', 'Message'=>$value]];				
				$jsonData["LongWait"] = $LongWait;
			}
		}									
	
		//$fp = fopen("C:\AllJson.json", "w") or die("Unable to open file!");
		//fwrite($fp, json_encode($jsonData));
		//fclose($fp);

		echo json_encode($jsonData);
		
	}	
	
	break;
    
	/**
	 * Loads initial view on request
	 */
	case "load":
	{
		$start = "";
		if(isset($_REQUEST['start']))
			$start = $_REQUEST['start'];
		
		$end = "";
		if(isset($_REQUEST['end']))
			$end = $_REQUEST['end'];
		
		if($start == "" || $end == "")
			die('{"error": "Start or End dates are missing"}');
        
        // limit time range to two days for memory/performance reasons
        $startStamp = strtotime($start);
        $endStamp = strtotime($end);

        //if($endStamp-$startStamp >= 172800)
        //    die('{"error":"The requested time span was too large. Please choose a timespan of <48 hours."}');

		$notificationData = loadNotificationData($start, $end);        
		
		$IOFail = [];
		$CameraImage = [];
		$CameraFailure = [];
		$NetworkFailure = [];
		$TimeFail = [];
		$PedestrianFailure = [];
		$DetectorFailure = [];
		$InFlash = [];
		$LightUnresponsive = [];
		$LongWait = [];			

		
	foreach ($notificationData as $key => $value)
	{

		// I/O Fail
		if ((str_contains($value, $IOFailMessageBad)) || 
			(str_contains($value, $IOFailMessageGood)))
		{
			$IOFail[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}

		// Camera Image
		else if ((str_contains($value, $CameraImageMessageBad)) || 
				(str_contains($value, $CameraImageMessageGood)))
		{
			$CameraImage[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}
		
		// Camera Failure
		else if ((str_contains($value, $CameraFailureMessageBad)) || 
				(str_contains($value, $CameraFailureMessageGood)))
		{
			$CameraFailure[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}
	
		// Network Failure
		else if ((str_contains($value, $NetworkFailureMessageBad)) ||
				(str_contains($value, $NetworkFailureMessageGood)))
		{
			$NetworkFailure[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}

		// Time Fail
		else if ((str_contains($value, $TimeFailMessageBad)) || 
				(str_contains($value, $TimeFailMessageGood)))
		{
			$TimeFail[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}

		// Pedestrian failure (stuck)	
		else if ((str_contains($value, $PedestrianFailureMessageBad)) || 
				(str_contains($value, $PedestrianFailureMessageGood)))
		{
			$PedestrianFailure[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}
		// Detector Failure	
		else if ((str_contains($value, $DetectorFailureMessageBad)) || 
				(str_contains($value, $DetectorFailureMessageGood)))
		{
			$DetectorFailure[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}
		// In flash
		else if ((str_contains($value, $InFlashMessageBad)) || 
				(str_contains($value, $InFlashMessageGood)))
		{
			$InFlash[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}
		// 	Light Unresponsive
		else if ((str_contains($value, $LightUnresponsiveMessageBad)) || 
				(str_contains($value, $LightUnresponsiveMessageGood)))
		{
			$LightUnresponsive[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}
		// Long Wait
		else if ((str_contains($value, $LongWaitMessageBad)) || 
				(str_contains($value, $LongWaitMessageGood)))
		{
			$LongWait[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
		}			
	}
	
	//$myfile = fopen("C:\Newfile.txt", "w") or die("Unable to open file!");
	//foreach ($notificationData as $testData)
	//{
	//	$txt = $testData . "\n";
	////	$txt = "Manoj Test";
	//	fwrite($myfile, $txt);
	//}
	//fclose($myfile);				
		
		$jsonData = [];
		if (count($IOFail) > 0)
		{
			$jsonData["IOFail"] = $IOFail;
		}
		
		if (count($CameraImage) > 0)
		{
			$jsonData["CameraImage"] = $CameraImage;
		}
		
		if (count($CameraFailure) > 0)
		{
			$jsonData["CameraFailure"] = $CameraFailure;
		}		
		
		if (count($NetworkFailure) > 0)
		{
			$jsonData["NetworkFailure"] = $NetworkFailure;
		}
		
		if (count($TimeFail) > 0)
		{
			$jsonData["TimeFail"] = $TimeFail;
		}
		
		if (count($PedestrianFailure) > 0)
		{
			$jsonData["PedestrianFailure"] = $PedestrianFailure;
		}
		
		if (count($DetectorFailure) > 0)
		{
			$jsonData["DetectorFailure"] = $DetectorFailure;
		}
		
		if (count($InFlash ) > 0)
		{
			$jsonData["InFlash"] = $InFlash ;
		}			
		
		if (count($LightUnresponsive ) > 0)
		{
			$jsonData["LightUnresponsive"] = $LightUnresponsive ;
		}
		
		if (count($LongWait ) > 0)
		{
			$jsonData["LongWait"] = $LongWait ;
		}									

		//$fp = fopen("C:\NewJson.json", "w") or die("Unable to open file!");
		//fwrite($fp, json_encode($jsonData));
		//fclose($fp);

		echo json_encode($jsonData);
	}
	break;
		/**
	 * Loads currently active notifications
	 */
	case "loadactive":
	{
		$notificationData = loadAllNotificationData();        
		
		$IOFail = [];
		$CameraImage = [];
		$CameraFailure = [];
		$NetworkFailure = [];
		$TimeFail = [];
		$PedestrianFailure = [];
		$DetectorFailure = [];
		$InFlash = [];
		$LightUnresponsive = [];
		$LongWait = [];			

		foreach ($notificationData as $key => $value)
		{
			// I/O Fail
			if (((str_contains($value, $IOFailMessageBad)) || 
				(str_contains($value, $IOFailMessageGood))) &&
				(count($IOFail) == 0))
			{
				$IOFail[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Camera Image
			else if (((str_contains($value, $CameraImageMessageBad)) || 
					(str_contains($value, $CameraImageMessageGood))) &&
					(count($CameraImage) == 0))
			{
				$CameraImage[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Camera Failure
			else if (((str_contains($value, $CameraFailureMessageBad)) || 
					(str_contains($value, $CameraFailureMessageGood))) && 
					 (count($CameraFailure) == 0))
			{
				$CameraFailure[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
				
			// Network Failure
			else if (((str_contains($value, $NetworkFailureMessageBad)) ||
					(str_contains($value, $NetworkFailureMessageGood))) && 
					(count($NetworkFailure) == 0))
			{
				$NetworkFailure[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Time Fail
			else if (((str_contains($value, $TimeFailMessageBad)) || 
					(str_contains($value, $TimeFailMessageGood))) && 
					(count($TimeFail) == 0))
			{
				$TimeFail[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}

			// Pedestrian failure (stuck)	
			else if (((str_contains($value, $PedestrianFailureMessageBad)) || 
					(str_contains($value, $PedestrianFailureMessageGood))) &&
					(count($PedestrianFailure) == 0))		
			{
				$PedestrianFailure[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// Detector Failure	
			else if (((str_contains($value, $DetectorFailureMessageBad)) || 
					(str_contains($value, $DetectorFailureMessageGood))) && 
					(count($DetectorFailure) == 0))
			{
				$DetectorFailure[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// In flash
			else if (((str_contains($value, $InFlashMessageBad)) || 
					(str_contains($value, $InFlashMessageGood))) && 
					(count($InFlash) == 0))
			{
				$InFlash[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// 	Light Unresponsive
			else if (((str_contains($value, $LightUnresponsiveMessageBad)) || 
					(str_contains($value, $LightUnresponsiveMessageGood))) && 
					(count($LightUnresponsive) == 0))
			{
				$LightUnresponsive[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}
			// Long Wait
			else if (((str_contains($value, $LongWaitMessageBad)) || 
					(str_contains($value, $LongWaitMessageGood))) && 
					(count($LongWait) == 0))
			{
				$LongWait[] = ['DateTime:'=>substr($key,0,19), 'Event:'=>$value];
			}			
				
		}

		// Take the value from the array and see if it contains an error. This would be active error.

		$jsonData = [];
	
		if (count($IOFail) > 0)
		{
			$value = $IOFail[0]['Event:'];
			if (str_contains($value, $IOFailMessageBad))
			{			
				$jsonData["IOFail"] = $IOFail;
			}
		}
		
		if (count($CameraImage) > 0)
		{
			$value = $CameraImage[0]['Event:'];
			if (str_contains($value, $CameraImageMessageBad))	
			{		
				$jsonData["CameraImage"] = $CameraImage;
			}
		}
		
		if (count($CameraFailure) > 0)
		{
			$value = $CameraFailure[0]['Event:'];
			if (str_contains($value, $CameraFailureMessageBad))
			{
				$jsonData["CameraFailure"] = $CameraFailure;
			}
		}		
		
		if (count($NetworkFailure) > 0)
		{
			$value = $NetworkFailure[0]['Event:'];
			if (str_contains($value, $NetworkFailureMessageBad))
			{
				$jsonData["NetworkFailure"] = $NetworkFailure;
			}
		}
		
		if (count($TimeFail) > 0)
		{
			$value = $TimeFail[0]['Event:'];
			if (str_contains($value, $TimeFailMessageBad))
			{
				$jsonData["TimeFail"] = $TimeFail;
			}
		}
		
		if (count($PedestrianFailure) > 0)
		{
			$value = $PedestrianFailure[0]['Event:'];
			if (str_contains($value, $PedestrianFailureMessageBad))
			{		
				$jsonData["PedestrianFailure"] = $PedestrianFailure;
			}
		}
		
		if (count($DetectorFailure) > 0)
		{
			$value = $DetectorFailure[0]['Event:'];
			if (str_contains($value, $DetectorFailureMessageBad))
			{			
				$jsonData["DetectorFailure"] = $DetectorFailure;
			}
		}
		
		if (count($InFlash ) > 0)
		{
			$value = $InFlash[0]['Event:'];
			if (str_contains($value, $InFlashMessageBad))
			{		
				$jsonData["InFlash"] = $InFlash ;
			}
		}			
		
		if (count($LightUnresponsive ) > 0)
		{
			$value = $LightUnresponsive[0]['Event:'];
			if (str_contains($value, $LightUnresponsiveMessageBad))	
			{		
				$jsonData["LightUnresponsive"] = $LightUnresponsive ;
			}
		}
		
		if (count($LongWait ) > 0)
		{
			$value = $LongWait[0]['Event:'];
			if (str_contains($value, $LongWaitMessageBad))
			{		
				$jsonData["LongWait"] = $LongWait ;
			}
		}									

		//$fp = fopen("C:\NewJson.json", "w") or die("Unable to open file!");
		//fwrite($fp, json_encode($jsonData));
		//fclose($fp);
		
		//$myfile = fopen("C:\Newfile.txt", "w") or die("Unable to open file!");
		//$txt = $value;
		//fwrite($myfile, $txt);
		//fclose($myfile);						

		echo json_encode($jsonData);
	}
	break;
}

function loadNotificationData($startDateTime, $endDateTime)
{
	$validDates = createDateRange($startDateTime, $endDateTime, "Ymd");
	
	$startTimestamp = strtotime($startDateTime);
	$endTimestamp = strtotime($endDateTime);
	
	// Get all files from C:\InSync\Logs\Notifications
	$notificationFiles = [];
	if (file_exists(NOTIFICATION_LOG))
	{
		$path = @opendir(NOTIFICATION_LOG);
		while($file = readdir($path))
		{
			if ($file != '.' and $file != '..')
			{
				// add the filename, to be sure not to
				// overwrite a array key
				$ctime = filectime($data_path . $file) . ',' . $file;
				$notificationFiles[$ctime] = $file;
			}
       }
	   closedir($path);
	   krsort($filelist);
	}
	
	if($notificationFiles == FALSE)
		die('{"error": "Unable to find any notification files"}');	
	
	// Select files from C:\InSync\Logs\Notifications for the date range entered. Default would be the current date
	
	$notificationList = [];
	foreach($notificationFiles as $file)
	{
		// notification file
		if(str_starts_with($file, "IS_"))
		{
			// file DATE is within our range
			if(in_array(substr($file, 3, 8), $validDates))
			{
				$notificationList[] = $file;
			}	
		}
	}

	//$myfile = fopen("C:\Newfile3.txt", "w") or die("Unable to open file!");
	//foreach ($notificationList as $File)
	//{
	//	$txt = $File . "\n";
	//	fwrite($myfile, $txt);
	//}
	//fclose($myfile);							
	
	if(count($notificationList) == 0)
		return false;
		
	$notificationData = [];
	foreach($notificationList as $file)
	{
		$fullPath = NOTIFICATION_LOG."/".$file;
		if (file_exists($fullPath))
		{
			$myfile = fopen($fullPath, "r");
			while (!feof($myfile))
			{
				$contents = fgets($myfile);
				$contents = str_replace("\r\n", "", $contents);
				$lineParts = explode("\t", $contents);
				$lineTime = strtotime($lineParts[0]);
				
				if($lineTime >= $startTimestamp && $lineTime <= $endTimestamp)
				{
					$stringdateandtime = $lineParts[0];
					$stringMessage = $lineParts[2];
					$formattedString = substr($stringMessage, 14);
					
					if (str_contains($contents, 'MESSAGE_SENT:'))
					{
						$notificationData[$stringdateandtime] = $formattedString;
					}
				}
			}
		}
	}
	krsort($datalist);
	
	//$myfile = fopen("C:\Newfile.txt", "w") or die("Unable to open file!");
	//foreach ($notificationData as $testData)
	//foreach ($notificationData as $key => $value)
	//{
	//	$txt = $key . "/" . $value;
	//	$txt = $testData;
	//	fwrite($myfile, $txt);
	//}
	//fclose($myfile);		
	
	return $notificationData;
}

function loadAllNotificationData()
{

	if (file_exists(NOTIFICATION_LOG))
	{
		$path = @opendir(NOTIFICATION_LOG);
		$notificationFiles = [];
		$notificationFiles = fileList($path);
	}
	
	if($notificationFiles == FALSE)
		die('{"error": "Unable to find any notification files"}');	
	
	//$myfile = fopen("C:\Newfile2.txt", "w") or die("Unable to open file!");
	//foreach ($notificationFiles as $testData)
	//foreach ($notificationData as $key => $value)
	//{
	//	$txt = $key . "/" . $value;
	//	$txt = $testData;
	//	fwrite($myfile, $txt);
	//}
	//fclose($myfile);			
	

	if(count($notificationFiles) == 0)
		return false;
		
	return $notificationFiles;
}
	
// Get a list of all files from C:\InSync\Logs\Notifications
// Read records with the text "MESSAGE_SENT:" from the files.
// Whenever a notification is genrtated from InSync, it also writes an entry with "MESSAGE_SENT:" as part of the notification
// to C:\InSync\Logs\Notifications folder.

function fileList($path)
{
	$filelist = [];
	while($file = readdir($path))
	{
		if ($file != '.' and $file != '..')
		{
			// add the filename, to be sure not to
			// overwrite a array key
			$ctime = filectime($data_path . $file) . ',' . $file;
			$filelist[$ctime] = $file;
		}
   }
   
   closedir($path);
   krsort($filelist);

	$datalist = [];
	foreach ($filelist as $file)
	{
		$fullPath = NOTIFICATION_LOG."/".$file;
		if (file_exists($fullPath))
		{
			$myfile = fopen($fullPath, "r");
			while (!feof($myfile))
			{
				$contents = fgets($myfile);
				$contents = str_replace("\r\n", "", $contents);
				$lineParts = explode("\t", $contents);
				$stringdateandtime = $lineParts[0];
				$stringMessage = $lineParts[2];					
				$formattedString = substr($stringMessage, 14);
				
				if (str_contains($contents, 'MESSAGE_SENT:'))
				{
					$datalist[$stringdateandtime] = $formattedString;
					//$datalist[$stringdateandtime] = $stringdateandtime . "/" . $stringMessage;
				}
			}
		}
	}
   
   krsort($datalist);
   return $datalist;
}

function createDateRange($startDate, $endDate, $outputFormat)
{
	$startTimestamp = strtotime($startDate) - 86400;
	$endTimestamp = strtotime($endDate) + 86400;
	
	$dateArray = [];
	
	for($date = $startTimestamp; $date <= $endTimestamp; $date += 86400)
		$dateArray[] = date($outputFormat, $date);
	
	return $dateArray;
}
?>
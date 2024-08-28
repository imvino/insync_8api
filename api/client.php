<?php
ini_set("soap.wsdl_cache_enabled", "0");
 
$wsdl = "http://172.16.162.128/api/InSyncAPI.wsdl";
#$wsdl = "http://localhost/insync/api/InSyncAPI.wsdl";
 
$client = new SoapClient($wsdl, ['compress' => 0, 'login' => 'PEC', 'password' => 'lenpec4321', 'connection_timeout' => 30]);

header("Content-Type: image/jpeg");
$return = $client->__soapCall('getCameraImage', ['camera_name' => 'North Bound', 'filter' => "normal", 'quality' => 75, 'width' => 320, 'height' => 240, 'mode' => 'simple']);
 
/*
$return = $client->__soapCall("getWebUIVersion", array());
 */

echo $return;
 
?>
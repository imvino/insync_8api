<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/insyncInterface.php");
require_once("pathDefinitions.php");

if(!isset($loggedIn) || !$loggedIn)
{
	// this must be included on all pages to authenticate the user
	require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
	$permissions = authSystem::ValidateUser();
	// end

    if (empty($permissions["web"]) && empty($permissions["api"]))
        if($permissions["username"] != "kiosk")
            die("Error: You do not have permission to access this page.");
}

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

if($action == "reset")
{
	
}

if($action == "send")
{
	$answer = "";
	
	$to = "";
	if(isset($_GET['toadd']))
		$to = $_GET['toadd'];
	
	$from = "";
	if(isset($_GET['fromadd']))
		$from = $_GET['fromadd'];
	
	$server = "";
	if(isset($_GET['server']))
		$server = $_GET['server'];		

	$port = "";
	if(isset($_GET['port']))
		$port = $_GET['port'];

	$tryall = false;
	if(isset($_GET['tryall']))
		$tryall = $_GET['tryall'];

	$useauth = false;
	if(isset($_GET['useauth']))
		$useauth = $_GET['useauth'];
	
	$usessl = false;
	if(isset($_GET['usessl']))
		$usessl = $_GET['usessl'];		
	
	$user = "";
	if(isset($_GET['user']))
		$user = $_GET['user'];	
	
	$pass = "";
	if(isset($_GET['pass']))
		$pass = $_GET['pass'];	

	$_to = "";
	$_from = "";
	$_server = "";
	$_port = "";	
	$_tryall = "";
	$_useauth = "";
	$_usessl = "";
	$_user = "";
	$_pass = "";
	
	$_to = "-to ". $to;
	$_from = " -from ". $from;
	$_server = " -server ". $server;
	$_port = " -port ". $port;
	
	if ($tryall == "true")
		$_tryall = " -t";

	if ($usessl == "true")
		$_usessl = " -s";
	
	if ($useauth == "true")
	{
		$_useauth = " -a";	
		$_user = " -username ". $user;
		$_pass = " -password ". $pass;
	}

	//$myfile = fopen("C:\Newfile.txt", "w") or die("Unable to open file!");
	//$txt = $_to . $_from . $_server . $_port . $_tryall . $_useauth . $_usessl . $_user . $_pass . "\n";
	//fwrite($myfile, $txt);
	//fclose($myfile);	

	$answer = shell_exec("C://InSync/Apps/EmailTester.exe $_to $_from $_server $_port $_tryall $_useauth $_usessl $_user $_pass");
	
	//$answer = shell_exec("C://InSync/Apps/EmailTesterNew.exe $to, $from, $server, $port, $tryall, $useauth, $usessl, $user, $pass");
	//$answer = shell_exec("C://InSync/Apps/EmailTesterNew.exe");
	//$answer = shell_exec(EMAIL_TESTER_NEW_EXE);
	
	die($answer);
}

?>
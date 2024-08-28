<?php

require_once("authSystem.php");

header("NeedsLogin: true");

$target = "/index.php";
if(isset($_REQUEST['target']))
	$target = $_REQUEST['target'];

if(isset($_REQUEST['reason']))
	authSystem::InvalidateUser();

if(isset($_REQUEST["submit"]))
{    
    if(!authSystem::prepareDB())
        header('HTTP/1.1 503 Service Unavailable');
    else
    {    
        authSystem::InvalidateUser();

        $username = "";
        if(isset($_REQUEST['username']))
            $username = base64_decode($_REQUEST['username']);

        $password = "";
        if(isset($_REQUEST['password']))
            $password = base64_decode($_REQUEST['password']);

        // remove leading and trailing whitespace
        $username = trim($username);

        if(authSystem::ValidateLogin($username, $password, true))
        {
            header("Location: $target");
            die("Success");
        }
        else
            header('HTTP/1.1 401 Unauthorized');
    }
}

$version = @file_get_contents("../includes/version.txt");

$bgNum = random_int(1,5);
?>

<!DOCTYPE html>
<!--[if lt IE 7]> <html class="lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html class="lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html class="lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!-->  
<html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8"></meta>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"></meta>
    <title>InSync</title>
    <meta name="description" content=""></meta>
    <meta name="viewport" content="width=1024"></meta>

    <link rel="stylesheet" type="text/css" href="/css/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="/css/type.css"/>
    <link rel="stylesheet" type="text/css" href="/css/styles.css"/>
    
	<script src="/js/jquery/jquery-1.9.1.min.js"></script>
	<script src="/js/jquery/jquery-migrate-1.1.1.min.js"></script>
	<script src="/js/jquery/jquery-ui.min.js"></script>
	<script src="/js/jquery/ultbuttons1.1.min.js"></script>
    <script src="/js/plugins/modernizr.min.js"></script>
	<script src="/js/plugins/plugins.js"></script>
    <script src="/js/plugins/chosen_v1.0.0/chosen.jquery.js"></script>
</head>
<body id="page" class="off-canvas slide-nav embedded">
<link rel="stylesheet" type="text/css" href="/css/login.css"/>
<script language="javascript" type="text/javascript" src="/js/jquery/jquery.base64.min.js"></script>
<script language="javascript" type="text/javascript" src="/js/login.js"></script>

<div class="color-bar"></div>
<div class="login">
    <div class="login-header">
        <div class="brand">
            <div class="emblem"></div>
            <span>InSync</span><h1>Web UI</h1>
            <div class="version"><h6><?php echo $version; ?></h6></div>
        </div>
    </div>
    <div class="login-validation"></div>
    <form class="login-form">
        <input type="hidden" value="<?php echo rawurlencode($target); ?>" id="redirect_target">
        <input type="text" id="username" name="username" class="input-login" placeholder="Username" />
        <input type="password" id="password" name="password" class="input-login" placeholder="Password" />
        <input type="submit" class="btn" value="Log In" />
    </form>
</div>
<img src="/img/backgrounds/bg-login0<?=$bgNum?>.jpg" id="login-bg" alt=""/>
<footer><p>&copy; 2013 Rhythm Engineering</p></footer>

<?php
if($bgNum == 5)
{
    echo "<div id='bug_container_1' style='top:15%;left:28%' class='bug_container'>";
    for($i=0;$i<6;$i++)
        echo "<div id='glowbug_" . $i . "' class='glowbug' style='top:" . random_int(0,150) . "px;left:" . random_int(0,100) . "px'></div>";
    echo "</div>";
}
?>

<script>
    $(document).ready(function()
    {
       initLogin(<?=$bgNum?>); 
    });
</script>

<script src="/js/jquery/jquery.offcanvas.js"></script>
<script src="/js/main.js"></script>
</body>
</html>

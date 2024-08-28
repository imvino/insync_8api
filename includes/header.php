<?php
$loggedIn = true;
require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/databaseInterface.php");

$intersectionXML = getFile("Intersection.xml");
$intersectionName = "InSync";

if(substr($intersectionXML,5) != "Error")
{
	$intersectionObject = simplexml_load_string($intersectionXML);
	
	if(isset($intersectionObject->Intersection["name"]))
		$intersectionName = $intersectionObject->Intersection["name"];
}

$intersectionName = htmlspecialchars($intersectionName);
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
    <title><?php echo $intersectionName; if(isset($title)) echo $title; ?></title>
    <meta name="description" content=""></meta>
    <meta name="viewport" content="width=1024"></meta>

    <link rel="stylesheet" type="text/css" href="/css/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="/css/type.css"/>
    <link rel="stylesheet" type="text/css" href="/css/styles.css"/>
    <!--[if lt IE 9]> <script type="text/javascript" src="/js/html5shiv.js"></script> <![endif]-->
    
	<script type="text/javascript" src="/js/jquery/jquery-1.9.1.min.js"></script>
	<script type="text/javascript" src="/js/jquery/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/js/jquery/ultbuttons1.1.min.js"></script>
    <script type="text/javascript" src="/js/jquery/jquery.ui.rhythmslider.js"></script>
    <script type="text/javascript" src="/js/plugins/modernizr.min.js"></script>
	<script type="text/javascript" src="/js/plugins/plugins.js"></script>
	<script type="text/javascript" src="/js/plugins/chosen_v1.0.0/chosen.jquery.js"></script>
    <script type="text/javascript" src="/js/helpBox.js"></script>
    <script type="text/javascript" src="/js/jquery/jquery.keysequencedetector.js"></script>
    <!--[if lt IE 9]>
    <script type="text/javascript" src="/js/css3-mediaqueries.js"></script>
    <![endif]-->
   <!--<script type="text/javascript" src="/firebug-lite/content/firebug-lite-dev.js"></script> -->
	
	<?php if(isset($head)) echo $head; ?>
</head>

<body id="page" class="off-canvas slide-nav">
    <div class="container">
        <header>
            <div class="left">
                <a class="brand" href="/index.php">
                    <div class="emblem"></div>
                    <span>InSync</span><h1>Web UI</h1>
                </a>
            </div>
            <div class="right">
                <div class="intersection">
                    <div class="info">
<?php

echo '<h4>' . $intersectionName . '</h4>';

require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/networkHelper.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/helpers/FileIOHelper.php");

// get system type (0=InSync, 1=Hawkeye etc.)
$systemConfigurationType = getSystemType();

echo '<h5>' . getInSyncIP() . '</h5>';
?>
                    </div>
                    <a href="#" id="utility-menu-btn" class="utility-menu-btn"><span class="icon-default"></span><span class="icon-hover"></span><span class="icon-active"></span></a>
                    <div id="utility-menu" class="utility-menu" style="display:none;">
                        <ul>
                            <li><a href="/index.php" class="home">Home<span class="icon-default"></span><span class="icon-hover"></span></a></li>
							<?php
							if (file_exists("C:\\hawkeye-ui") && $systemConfigurationType === 1)
							{
								$host = $_SERVER['SERVER_ADDR'];
								$hawkeyeLink = '<li><a href="//'.$host.':5001" target="_blank" class="hawkeye">Hawkeye<span class="icon-default"></span><span class="icon-hover"></span></a></li>';
								echo $hawkeyeLink;
							}
							?>
                            <li><a href="/help" class="help">Help<span class="icon-default"></span><span class="icon-hover"></span></a></li>
                        </ul>
                        <hr class="divider"/>
                        <a href="#" id="logout-btn" class="btn btn-outline logout" onclick="$('#dialog-logout-confirm').dialog('open')">Log Out</a>
                    </div>   
                </div>
            </div>
        </header>
        <div class="row wrapper">
            <section id="main" class="main">
                <div class="content clearfix">
                    <div class="column">
<?php
$showHeader = true;

if (isset($hideHeader)) {
    $showHeader = !$hideHeader;
}
if ($showHeader) {
?>
                        <div class="page-header">
                            <?= $breadCrumb ?>
                        </div>
<?php
}
?>
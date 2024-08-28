<?php

/**
 * Launches Java Web Start for the Intersection
 * Configuration Java Utility.
 * Retrieves system IP via the networkHelper.
 * Constructs the jnlp file to access the .jar
 * file and provide appropriate arguments to
 * the application.
 */

require_once( "../helpers/pathDefinitions.php" );
require_once( "../helpers/networkHelper.php" );

// get the script name
$script_name = $_SERVER['SCRIPT_NAME'];

$ip = getInSyncIP();

// get the host as http or https
$host = "http://" . $ip;
if ( isset($_SERVER["HTTPS"]) && $_SERVER['HTTPS'] != "" )
	$host = "https://" . $ip;

header("Content-Type: application/x-java-jnlp-file");
header("Content-Disposition: filename=\"ConfigLaunch.jnlp\"");

?>
<jnlp
  spec="6.0+"
  codebase="<?php echo $host; ?>/"
  href="<?php echo $script_name; ?>">
  <information>
    <title>InSync Detector Configuration Utility</title>
    <vendor>Rhythm Engineering</vendor>
    <homepage href="/help/index.html"/>
    <description>InSync Detector Configuration Utility</description>
    <description kind="short">A tool to configure camera detection zones
        and loop to lane relationships.</description>
  </information>
  <resources>
    <j2se version="1.6+" java-vm-args="-esa -Xnoclassgc"/>
    <jar href="<?php echo INTERSECTION_CONFIG_UI_JAR_WEB_REL; ?>"/>
  </resources>
  <application-desc main-class="com.rhythmtraffic.configapplet.dialogs.Configure">
    <argument><?php echo $host; ?>/</argument>
    <argument><?php echo $ip; ?></argument>
  </application-desc>
</jnlp> 
